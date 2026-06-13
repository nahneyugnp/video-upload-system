<?php
/* ==========================================================================
   API: XÓA VIDEO VÀ DỌN DẸP Ổ CỨNG
   ========================================================================== */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/helper.php';

method('POST');

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if (!$id) json(false, 'Missing video ID', [], 400);

$pdo = db();
$st = $pdo->prepare('SELECT original_path, thumb_path FROM videos WHERE id = ?');
$st->execute([$id]);
$vid = $st->fetch();

if (!$vid) json(false, 'Video not found or already deleted', [], 404);

$stRend = $pdo->prepare('SELECT file_path FROM video_renditions WHERE video_id = ?');
$stRend->execute([$id]);
$renditions = $stRend->fetchAll();

// Xóa file vật lý
if (!empty($vid['original_path'])) @unlink(ROOT . '/' . $vid['original_path']);
if (!empty($vid['thumb_path']))    @unlink(ROOT . '/' . $vid['thumb_path']);
foreach ($renditions as $r) {
    if (!empty($r['file_path'])) @unlink(ROOT . '/' . $r['file_path']);
}

// Xóa Database
$pdo->prepare('DELETE FROM video_renditions WHERE video_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM upload_logs WHERE video_id = ?')->execute([$id]);
$pdo->prepare('DELETE FROM videos WHERE id = ?')->execute([$id]);

json(true, 'Video deleted successfully!');
