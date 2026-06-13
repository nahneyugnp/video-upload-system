<?php
/* ==========================================================================
   FFMPEG WORKER: XỬ LÝ NỀN (Thumbnail + Transcoding)
   ========================================================================== */
require_once __DIR__ . '/../api/config/db.php';
require_once __DIR__ . '/../api/config/constants.php';

$videoId = (int)($argv[1] ?? 0);
$src     = $argv[2] ?? '';

if (!$videoId || !file_exists($src)) die("Lỗi: Không tìm thấy file gốc.\n");

$baseName = pathinfo($src, PATHINFO_FILENAME);

// 1. ĐỌC THÔNG SỐ VIDEO GỐC
$probeJson = shell_exec(FFPROBE . ' -v quiet -print_format json -show_streams ' . escapeshellarg($src));
$probe = json_decode($probeJson, true);

$nativeH = 0; $nativeW = 0; $duration = 0;
foreach (($probe['streams'] ?? []) as $stream) {
    if ($stream['codec_type'] === 'video') {
        $nativeH  = (int)($stream['height'] ?? 0);
        $nativeW  = (int)($stream['width'] ?? 0);
        $duration = (float)($stream['duration'] ?? 0);
        break;
    }
}

db()->prepare('UPDATE videos SET native_height=?, native_width=?, duration=? WHERE id=?')
    ->execute([$nativeH, $nativeW, $duration, $videoId]);

// 2. TẠO THUMBNAIL
$thumbPath = DIR_THUMB . $baseName . '_thumb.jpg';
$dbThumbPath = 'uploads/thumbs/' . $baseName . '_thumb.jpg';

exec(FFMPEG . ' -y -i ' . escapeshellarg($src) . ' -ss 7 -vframes 1 -q:v 2 ' . escapeshellarg($thumbPath) . ' 2>&1', $_, $r1);
if ($r1 !== 0) {
    exec(FFMPEG . ' -y -i ' . escapeshellarg($src) . ' -vframes 1 -q:v 2 ' . escapeshellarg($thumbPath) . ' 2>&1');
}

// 3. CHUẨN BỊ BẢN NÉN (Chống Upscale)
$allProfiles = [
    ['label'=>'360p',  'height'=>360,  'vb'=>600,  'ab'=>96],
    ['label'=>'480p',  'height'=>480,  'vb'=>1000, 'ab'=>128],
    ['label'=>'720p',  'height'=>720,  'vb'=>2500, 'ab'=>128],
    ['label'=>'1080p', 'height'=>1080, 'vb'=>4000, 'ab'=>192],
];

$profilesToRun = [];
foreach ($allProfiles as $p) {
    if ($nativeH === 0 || $p['height'] <= $nativeH) $profilesToRun[] = $p;
}
if (empty($profilesToRun)) {
    $profilesToRun[] = ['label'=>'original', 'height'=>$nativeH, 'vb'=>600, 'ab'=>96];
}

// 4. TRANSCODING (Progressive)
$isFirstDone = false;
$defaultPath = null;

foreach ($profilesToRun as $p) {
    $suffix  = $p['label'];
    $outFile = DIR_PROC . $baseName . '_' . $suffix . '.mp4';
    $dbPath  = 'uploads/processed/' . $baseName . '_' . $suffix . '.mp4';

    // Tính toán chiều rộng (Width) mới dựa trên chiều cao (Height) để giữ đúng tỉ lệ gốc
    $calcW = 0;
    if ($nativeH > 0 && $nativeW > 0 && $p['height'] > 0) {
        $calcW = round(($nativeW * $p['height']) / $nativeH);
        // Bắt buộc: H.264 yêu cầu width/height phải là số chẵn
        $calcW = ($calcW % 2 !== 0) ? $calcW + 1 : $calcW;
    } else {
        $calcW = $nativeW;
    }

    $cmd = FFMPEG . ' -y -i ' . escapeshellarg($src);
    if ($p['height'] > 0) $cmd .= ' -vf scale=-2:' . $p['height'];
    
    $cmd .= ' -vcodec libx264 -preset ultrafast -maxrate ' . $p['vb'] . 'k -bufsize ' . ($p['vb']*2) . 'k';
    $cmd .= ' -acodec aac -b:a ' . $p['ab'] . 'k -movflags +faststart ' . escapeshellarg($outFile) . ' 2>&1';

    $lines = [];
    exec($cmd, $lines, $rc);

    if ($rc === 0 && file_exists($outFile)) {
        db()->prepare(
            'INSERT INTO video_renditions (video_id, label, height, width, file_path, file_size, bitrate, trans_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        )->execute([
            $videoId, $suffix, $p['height'], $calcW, $dbPath, filesize($outFile), $p['vb'], 'done'
        ]);

        $defaultPath = $dbPath;

        // Mở khóa xem video ngay khi bản 360p hoàn thành
        if ($isFirstDone === false) {
            db()->prepare('UPDATE videos SET status=?, processed_path=?, thumb_path=? WHERE id=?')
                ->execute(['ready', $dbPath, $dbThumbPath, $videoId]);
            db()->prepare('UPDATE upload_logs SET end_time=NOW(), status=? WHERE video_id=?')
                ->execute(['ready', $videoId]);
            $isFirstDone = true;
        }
        echo "[$videoId] Nen xong ban $suffix \n";
    } else {
        echo "[$videoId] Loi nen ban $suffix \n";
    }
}

if ($defaultPath && $isFirstDone) {
    db()->prepare('UPDATE videos SET processed_path=? WHERE id=?')->execute([$defaultPath, $videoId]);
}

if ($isFirstDone === false) {
    db()->prepare("UPDATE videos SET status='error' WHERE id=?")->execute([$videoId]);
    db()->prepare("UPDATE upload_logs SET end_time=NOW(), status='error' WHERE video_id=?")->execute([$videoId]);
    echo "[$videoId] Loi nen toan bo!\n";
} else {
    echo "[$videoId] Hoan tat!\n";
}
