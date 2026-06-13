<?php
/* ==========================================================================
   API: LẤY TRẠNG THÁI VIDEO (Phục vụ Polling)
   ========================================================================== */
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helper.php";
method("GET");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);
if (!$id) json(false, "Missing ID", [], 400);

$st = db()->prepare("SELECT status, thumb_path, duration FROM videos WHERE id = ?");
$st->execute([$id]);
$v = $st->fetch();

if (!$v) json(false, "Not found", [], 404);
json(true, "OK", $v);
