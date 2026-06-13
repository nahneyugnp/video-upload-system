<?php
/* ==========================================================================
   API: UPLOAD VIDEO & KÍCH HOẠT WORKER
   ========================================================================== */
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/constants.php";
require_once __DIR__ . "/../config/helper.php";

method("POST");

// 1. Kiểm tra File
if (!isset($_FILES["video"]) || $_FILES["video"]["error"] !== UPLOAD_ERR_OK) {
    $errMap = [
        1 => "File too large (exceeds php.ini limit)",
        2 => "File too large (exceeds HTML form limit)",
        3 => "Partial upload error",
        4 => "No file selected",
    ];
    $code = $_FILES["video"]["error"] ?? 4;
    json(false, $errMap[$code] ?? "Upload error $code", [], 400);
}

$f = $_FILES["video"];
$title = trim($_POST["title"] ?? "");
$desc  = trim($_POST["description"] ?? "");

if (!$title) json(false, "Title is required", [], 400);
if ($f["size"] > MAX_BYTES) json(false, "File exceeds 500MB limit", [], 413);

// 2. Kiểm tra định dạng (MIME)
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($f["tmp_name"]);
if (!in_array($mime, ALLOWED_MIME)) {
    json(false, "Unsupported format: $mime", [], 415);
}

// 3. Lưu file
$base   = uniqid("v_", true);
$ext    = strtolower(pathinfo($f["name"], PATHINFO_EXTENSION));
$stored = $base . "." . $ext;
$path   = DIR_ORIG . $stored;

if (!move_uploaded_file($f["tmp_name"], $path)) {
    json(false, "Failed to save file. Check permissions.", [], 500);
}

// 4. Ghi Database & Log
$pdo = db();
$pdo->prepare(
    "INSERT INTO videos (title, description, original_path, file_size, status) VALUES (?, ?, ?, ?, 'processing')"
)->execute([htmlspecialchars($title), htmlspecialchars($desc), "uploads/original/" . $stored, $f["size"]]);
$videoId = (int)$pdo->lastInsertId();

$pdo->prepare(
    "INSERT INTO upload_logs (video_id, start_time, file_size, status, ip) VALUES (?, NOW(), ?, 'uploaded', ?)"
)->execute([$videoId, $f["size"], $_SERVER["REMOTE_ADDR"] ?? ""]);

// 5. Khởi chạy Worker nền (Non-blocking)
$worker  = ROOT . "/workers/process.php";
$logFile = sys_get_temp_dir() . "/ffmpeg_{$videoId}.log";

$cmd = "/usr/bin/php " . escapeshellarg($worker) . " " . (string)$videoId
     . " " . escapeshellarg($path) . " > " . escapeshellarg($logFile) . " 2>&1 &";
exec($cmd);

json(true, "Upload successful, processing...", ["video_id" => $videoId]);
