<?php
// File: api/videos/stream.php
// Phat video ho tro HTTP Range Request (cho phep tua/highlight tren player)
require_once __DIR__ . '/../config/database.php';
 
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit; }
 
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ? AND status = "ready"');
$stmt->execute([$id]);
$video = $stmt->fetch();
if (!$video) { http_response_code(404); exit; }
 
$filePath = '/var/www/videohub/' . $video['processed_path'];
if (!is_readable($filePath)) { http_response_code(404); exit; }
 
// Tang luot xem
$pdo->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$id]);
 
$size  = filesize($filePath);
$start = 0;
$end   = $size - 1;
 
// Xu ly Range Request (trinh duyet gui header Range khi tua video)
if (isset($_SERVER['HTTP_RANGE'])) {
    if (!preg_match('/bytes=(\d+)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
        header('HTTP/1.1 416 Range Not Satisfiable');
        header('Content-Range: bytes */' . $size);
        exit;
    }
    $start = (int)$m[1];
    $end   = $m[2] !== '' ? (int)$m[2] : $size - 1;
    $end   = min($end, $size - 1);
    if ($start > $end) { http_response_code(416); exit; }
    http_response_code(206);
    header("Content-Range: bytes {$start}-{$end}/{$size}");
} else {
    http_response_code(200);
}
 
// Headers phat video
header('Content-Type: video/mp4');
header('Accept-Ranges: bytes');
header('Content-Length: ' . ($end - $start + 1));
header('Cache-Control: public, max-age=3600');
 
// Doc va gui du lieu video theo tung chunk 64KB
$fp = fopen($filePath, 'rb');
fseek($fp, $start);
$remaining = $end - $start + 1;
while (!feof($fp) && $remaining > 0 && !connection_aborted()) {
    $chunk = fread($fp, min(65536, $remaining));
    if ($chunk === false) break;
    echo $chunk;
    $remaining -= strlen($chunk);
    flush();
}
fclose($fp);
?>
