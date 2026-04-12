<?php
// File: api/config/database.php
// Thay doi thong tin ket noi neu can
 
define('DB_HOST', 'localhost');
define('DB_NAME', 'videodb');
define('DB_USER', 'videouser');
define('DB_PASS', 'Video@12345');  // Mat khau da tao o Phan 1.5
 
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
?>
