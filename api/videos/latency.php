<?php
/* ==========================================================================
   API: ĐO ĐỘ TRỄ UPLOAD & XỬ LÝ (LATENCY)
   ========================================================================== */
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helper.php";
method("GET");

$rows = db()->query(
    "SELECT l.video_id, v.title,"
    . " ROUND(v.file_size/1048576, 2) AS size_mb,"
    . " TIMESTAMPDIFF(MICROSECOND, l.start_time, l.end_time)/1000 AS latency_ms,"
    . " ROUND(v.file_size/1048576 / (TIMESTAMPDIFF(MICROSECOND, l.start_time, l.end_time)/1000000), 2) AS throughput_mbs"
    . " FROM upload_logs l JOIN videos v ON l.video_id = v.id"
    . " WHERE l.end_time IS NOT NULL ORDER BY l.id DESC LIMIT 20"
)->fetchAll();

$ms  = array_column($rows, "latency_ms");
$avg = count($ms) ? round(array_sum($ms)/count($ms)) : 0;

json(true, "OK", [
    "records" => $rows,
    "stats"   => ["avg_ms"=>$avg, "max_ms"=>$ms?max($ms):0, "min_ms"=>$ms?min($ms):0],
]);
