<?php
/* ==========================================================================
   API: LẤY DANH SÁCH VIDEO (CÓ PHÂN TRANG)
   ========================================================================== */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/helper.php';
method('GET');

$page  = max(1, (int)($_GET['page']  ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
$off   = ($page - 1) * $limit;
$pdo   = db();

$total = (int)$pdo->query("SELECT COUNT(*) FROM videos WHERE status = 'ready'")->fetchColumn();

$st = $pdo->prepare(
    "SELECT id, title, thumb_path, duration, views, created_at "
    . "FROM videos WHERE status = 'ready' ORDER BY created_at DESC LIMIT :lim OFFSET :off"
);
$st->bindValue(':lim', $limit, PDO::PARAM_INT);
$st->bindValue(':off', $off,   PDO::PARAM_INT);
$st->execute();

json(true, 'OK', [
    'videos' => $st->fetchAll(),
    'total'  => $total,
    'page'   => $page,
    'pages'  => (int)ceil($total / $limit),
]);
