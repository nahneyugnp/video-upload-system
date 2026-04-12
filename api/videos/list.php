<?php
// File: api/videos/list.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helper.php';
 
requireMethod('GET');
 
$page  = max(1, (int)($_GET['page']  ?? 1));
$limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;
 
$pdo = getDB();
 
// Dem tong so video
$total = (int)$pdo->query('SELECT COUNT(*) FROM videos WHERE status = "ready"')->fetchColumn();
 
// Lay danh sach video voi phan trang
$stmt = $pdo->prepare(
    'SELECT id, title, description, thumbnail_path, duration, views, created_at
     FROM videos WHERE status = "ready"
     ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
);
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$videos = $stmt->fetchAll();
 
jsonResponse(true, 'OK', [
    'videos'      => $videos,
    'total'       => $total,
    'page'        => $page,
    'total_pages' => (int)ceil($total / $limit),
]);
?>
