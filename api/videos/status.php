<?php
// File: api/videos/status.php
// Frontend goi API nay moi 3 giay de biet video da xu ly xong chua
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
 
requireMethod('GET');
 
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) jsonResponse(false, 'Thieu ID', [], 400);
 
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT id, status, thumbnail_path, duration FROM videos WHERE id = ?');
$stmt->execute([$id]);
$video = $stmt->fetch();
 
if (!$video) jsonResponse(false, 'Khong tim thay video', [], 404);
 
jsonResponse(true, 'OK', [
    'status'    => $video['status'],
    'thumbnail' => $video['thumbnail_path'],
    'duration'  => $video['duration'],
]);
?>
