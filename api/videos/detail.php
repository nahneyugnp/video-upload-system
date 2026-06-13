<?php
/* ==========================================================================
   API: CHI TIẾT VIDEO & ĐẾM VIEWS
   ========================================================================== */
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helper.php";
method("GET");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) json(false, "Missing ID", [], 400);

$st = db()->prepare("SELECT * FROM videos WHERE id = ?");
$st->execute([$id]);
$v = $st->fetch();

if (!$v) json(false, "Not found", [], 404);

// Tăng views
db()->prepare("UPDATE videos SET views = views + 1 WHERE id = ?")->execute([$id]);
$v["views"] = $v["views"] + 1;

unset($v["original_path"]); // Bảo mật file gốc
json(true, "OK", ["video" => $v]);
