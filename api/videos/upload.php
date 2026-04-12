<?php
// File: api/videos/upload.php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/helper.php';
 
requireMethod('POST');
 
// Kiem tra file co duoc gui len khong
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $errMap = [1=>'File qua lon (PHP)',2=>'File qua lon (Form)',
               3=>'Chi upload duoc 1 phan',4=>'Khong co file'];
    $errCode = $_FILES['video']['error'] ?? 4;
    jsonResponse(false, $errMap[$errCode] ?? 'Loi upload khong xac dinh', [], 400);
}
 
$file  = $_FILES['video'];
$title = trim($_POST['title'] ?? '');
$desc  = trim($_POST['description'] ?? '');
 
if (empty($title)) jsonResponse(false, 'Thieu tieu de video', [], 400);
 
// Kiem tra kich thuoc
if ($file['size'] > MAX_FILE_SIZE) {
    jsonResponse(false, 'File qua lon. Toi da 500MB.', [], 413);
}
 
// Kiem tra MIME type thuc su (khong tin vao ten file)
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, ALLOWED_MIME)) {
    jsonResponse(false, 'Dinh dang khong ho tro: ' . $mimeType, [], 415);
}
 
// Tao ten file duy nhat de tranh trung lap
$ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$baseName = uniqid('vid_', true);
$origFile = $baseName . '.' . $ext;
$origPath = UPLOAD_DIR_ORIGINAL . $origFile;
 
// Chuyen file tu thu muc tam sang thu muc cua minh
if (!move_uploaded_file($file['tmp_name'], $origPath)) {
    jsonResponse(false, 'Khong the luu file. Kiem tra quyen thu muc uploads/', [], 500);
}
 
// Ghi vao database
$pdo  = getDB();
$stmt = $pdo->prepare(
    'INSERT INTO videos (user_id, title, description, original_path, file_size, status)
     VALUES (:uid, :title, :desc, :path, :size, "processing")'
);
$stmt->execute([
    ':uid'   => $_SESSION['user_id'] ?? 1,
    ':title' => htmlspecialchars($title),
    ':desc'  => htmlspecialchars($desc),
    ':path'  => 'uploads/original/' . $origFile,
    ':size'  => $file['size'],
]);
$videoId = (int)$pdo->lastInsertId();
 
// Ghi log thoi diem bat dau upload
$pdo->prepare(
    'INSERT INTO upload_logs (video_id, start_time, file_size, status, ip_address)
     VALUES (?, NOW(), ?, "uploaded", ?)'
)->execute([$videoId, $file['size'], $_SERVER['REMOTE_ADDR'] ?? '']);
 
// Goi worker FFmpeg chay nen (khong cho ket qua, tra ve ngay)
$workerScript = '/var/www/videohub/workers/process_video.php';
$logFile = '/tmp/ffmpeg_' . $videoId . '.log';
$cmd = PHP_BINARY . ' ' . escapeshellarg($workerScript)
     . ' ' . escapeshellarg((string)$videoId)
     . ' ' . escapeshellarg($origPath)
     . ' > ' . escapeshellarg($logFile) . ' 2>&1 &';
exec($cmd);
 
jsonResponse(true, 'Upload thanh cong! Dang xu ly video...', ['video_id' => $videoId]);
?>
