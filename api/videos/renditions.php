<?php
/* ==========================================================================
   API: LẤY CÁC BẢN CHẤT LƯỢNG ĐÃ NÉN (360p, 720p...)
   ========================================================================== */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper.php';
method('GET');

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) json(false, 'Missing ID', [], 400);

$st = db()->prepare(
    'SELECT label, height, width, file_path, file_size, bitrate '
    . 'FROM video_renditions WHERE video_id = ? ORDER BY height DESC'
);
$st->execute([$id]);
$renditions = $st->fetchAll();

$v = db()->prepare('SELECT native_height, native_width FROM videos WHERE id = ?');
$v->execute([$id]);
$info = $v->fetch();

json(true, 'OK', [
    'renditions'    => $renditions,
    'native_height' => $info['native_height'] ?? 0,
    'native_width'  => $info['native_width']  ?? 0,
]);
