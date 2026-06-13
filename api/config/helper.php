<?php
/* ==========================================================================
   HELPER FUNCTIONS
   ========================================================================== */

// Trả về JSON Response kèm CORS Headers
function json(bool $ok, string $msg, array $data = [], int $code = 200): void {
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    
    echo json_encode(["success" => $ok, "message" => $msg] + $data);
    exit;
}

// Kiểm tra và chặn HTTP Method không hợp lệ
function method(string $m): void {
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        exit(0);
    }
    if ($_SERVER["REQUEST_METHOD"] !== strtoupper($m)) {
        json(false, "Method not allowed", [], 405);
    }
}
