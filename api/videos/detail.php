<?php
// File: api/videos/detail.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
 
requireMethod('GET');
 
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) jsonResponse(false, 'Thieu ID', [], 400);
 
$pdo  = getDB();
$stmt = $pdo->prepare('SELECT * FROM videos WHERE id = ?');
$stmt->execute([$id]);
$video = $stmt->fetch();
 
if (!$video) jsonResponse(false, 'Khong tim thay video', [], 404);
 
// Xoa thong tin nhay cam truoc khi tra ve
unset($video['original_path']);
 
jsonResponse(true, 'OK', ['video' => $video]);
?>
