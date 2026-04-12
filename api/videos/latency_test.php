<?php
// File: api/videos/latency_test.php
// Goi: GET /api/videos/latency_test.php
// Tra ve ket qua do luong upload tu upload_logs
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
 
requireMethod('GET');
 
$pdo  = getDB();
$rows = $pdo->query(
    'SELECT l.video_id, v.title, v.file_size,
            l.start_time, l.end_time,
            TIMESTAMPDIFF(MICROSECOND, l.start_time, l.end_time)/1000 AS latency_ms
     FROM upload_logs l JOIN videos v ON l.video_id = v.id
     WHERE l.end_time IS NOT NULL
     ORDER BY l.id DESC LIMIT 20'
)->fetchAll();
 
// Tinh thong ke
$latencies = array_column($rows, 'latency_ms');
$avg = count($latencies) ? round(array_sum($latencies) / count($latencies)) : 0;
$max = count($latencies) ? max($latencies) : 0;
$min = count($latencies) ? min($latencies) : 0;
 
jsonResponse(true, 'OK', [
    'records' => $rows,
    'stats'   => ['avg_ms' => $avg, 'max_ms' => $max, 'min_ms' => $min],
]);
?>
