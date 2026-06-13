<?php
/* ==========================================================================
   API: HTTP RANGE STREAMING (Cho phép tua Video)
   ========================================================================== */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . "/../config/constants.php";

$id      = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$quality = trim($_GET['quality'] ?? '');

if (!$id) { http_response_code(400); exit; }

$st = db()->prepare("SELECT * FROM videos WHERE id = ? AND status = 'ready'");
$st->execute([$id]);
$video = $st->fetch();

if (!$video) { http_response_code(404); exit; }

$filePath = null;

// Xác định bản chất lượng
if ($quality !== '') {
    $rs = db()->prepare('SELECT file_path FROM video_renditions WHERE video_id=? AND label=?');
    $rs->execute([$id, $quality]);
    if ($r = $rs->fetch()) {
        $filePath = ROOT . '/' . $r['file_path'];
    }
}

if (!$filePath) $filePath = ROOT . '/' . $video['processed_path'];
if (!is_readable($filePath)) { http_response_code(404); exit; }

$size  = filesize($filePath);
$start = 0; $end = $size - 1;

// Xử lý Range Request
if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        http_response_code(416); header('Content-Range: bytes */' . $size); exit;
    }
    $start = (int)$m[1];
    $end   = ($m[2] !== '') ? (int)$m[2] : $size - 1;
    $end   = min($end, $size - 1);
    
    if ($start > $end) { http_response_code(416); exit; }
    
    http_response_code(206);
    header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
} else { 
    http_response_code(200); 
}

if (ob_get_length()) { ob_end_clean(); }

header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('Content-Length: ' . ($end - $start + 1));
header('Cache-Control: public, max-age=3600');

$fp = fopen($filePath, 'rb');
fseek($fp, $start);
$rem = $end - $start + 1;

// Stream từng chunk 64KB
while (!feof($fp) && $rem > 0 && !connection_aborted()) {
    $buf = fread($fp, min(65536, $rem));
    if (!$buf) break;
    echo $buf; 
    $rem -= strlen($buf); 
    flush();
}
fclose($fp);
