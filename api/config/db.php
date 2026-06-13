<?php
/* ==========================================================================
   DATABASE CONNECTION (Singleton Pattern)
   ========================================================================== */
define("DB_DSN",  "mysql:host=localhost;dbname=videodb;charset=utf8mb4");
define("DB_USER", "videouser");
define("DB_PASS", "Video@Pass123");

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
