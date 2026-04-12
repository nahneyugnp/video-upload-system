<?php
// File: workers/process_video.php
// Chay bang lenh: php process_video.php <video_id> <input_path>
// KHONG goi truc tiep qua trinh duyet
 
require_once __DIR__ . '/../api/config/database.php';
require_once __DIR__ . '/../api/config/constants.php';
 
// Nhan tham so tu command line
$videoId   = (int)($argv[1] ?? 0);
$inputPath = $argv[2] ?? '';
 
if (!$videoId || !file_exists($inputPath)) {
    echo 'Loi: Thieu video_id hoac file khong ton tai: ' . $inputPath . PHP_EOL;
    exit(1);
}
 
echo "[{$videoId}] Bat dau xu ly: {$inputPath}" . PHP_EOL;
 
$pdo      = getDB();
$baseName = pathinfo($inputPath, PATHINFO_FILENAME);
 
// Ten file dau ra
$processedFile = $baseName . '_720p.mp4';
$thumbFile     = $baseName . '_thumb.jpg';
$processedPath = UPLOAD_DIR_PROCESSED . $processedFile;
$thumbPath     = UPLOAD_DIR_THUMBS    . $thumbFile;
 
// ---- BUOC 1: TAO THUMBNAIL ----
echo "[{$videoId}] Tao thumbnail..." . PHP_EOL;
$thumbCmd = FFMPEG . ' -y -i ' . escapeshellarg($inputPath)
          . ' -ss 00:00:03 -vframes 1 -q:v 2'
          . ' ' . escapeshellarg($thumbPath) . ' 2>&1';
exec($thumbCmd, $outThumb, $retThumb);
 
if ($retThumb !== 0) {
    // Thu fallback: lay frame dau tien
    $thumbCmd2 = FFMPEG . ' -y -i ' . escapeshellarg($inputPath)
               . ' -vframes 1 -q:v 2'
               . ' ' . escapeshellarg($thumbPath) . ' 2>&1';
    exec($thumbCmd2, $_, $retThumb);
}
echo ($retThumb === 0 ? 'OK' : 'THAT BAI') . PHP_EOL;
 
// ---- BUOC 2: TRANSCODE SANG MP4 H.264 ----
echo "[{$videoId}] Transcoding..." . PHP_EOL;
// -movflags +faststart: cho phep xem khi chua tai xong (streaming)
// -crf 23: chat luong (0=tot nhat, 51=kem nhat, 23=can bang)
// -preset fast: toc do xu ly (ultrafast/fast/medium/slow)
$transCmd = FFMPEG . ' -y -i ' . escapeshellarg($inputPath)
          . ' -vcodec libx264 -acodec aac'
          . ' -movflags +faststart'
          . ' -preset fast -crf 23'
          . ' ' . escapeshellarg($processedPath) . ' 2>&1';
exec($transCmd, $outTrans, $retTrans);
echo ($retTrans === 0 ? 'OK' : 'THAT BAI') . PHP_EOL;
 
// ---- BUOC 3: LAY THOI LUONG VIDEO ----
$duration = 0;
if ($retTrans === 0) {
    $durCmd  = FFPROBE . ' -v quiet -show_entries format=duration'
             . ' -of default=noprint_wrappers=1:nokey=1'
             . ' ' . escapeshellarg($processedPath);
    $duration = (int)round((float)trim(shell_exec($durCmd)));
}
 
// ---- BUOC 4: CAP NHAT DATABASE ----
$status = ($retTrans === 0) ? 'ready' : 'error';
$errorMsg = ($retTrans !== 0) ? implode('\n', array_slice($outTrans, -10)) : null;
 
$pdo->prepare(
    'UPDATE videos SET status=?, processed_path=?, thumbnail_path=?, duration=?,
     updated_at=NOW() WHERE id=?'
)->execute([
    $status,
    ($retTrans === 0) ? 'uploads/processed/' . $processedFile : null,
    ($retThumb === 0) ? 'uploads/thumbnails/' . $thumbFile    : null,
    $duration,
    $videoId,
]);
 
// Cap nhat log
$pdo->prepare(
    'UPDATE upload_logs SET end_time=NOW(), status=?, error_msg=?
     WHERE video_id=? ORDER BY id DESC LIMIT 1'
)->execute([$status, $errorMsg, $videoId]);
 
echo "[{$videoId}] Hoan thanh: {$status} (thoi luong: {$duration}s)" . PHP_EOL;
?>
