<?php
/**
 * connDB.php
 * รองรับทั้ง Localhost (MAMP) และ Cloud Environment Variables (Railway)
 */

$servername = getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: "localhost";
$username   = getenv('MYSQLUSER') ?: getenv('DB_USER') ?: "root";
$password   = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: "root";
$dbname     = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: "db_northwind";
$port       = getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306;

try {
    $dsn = "mysql:host={$servername};port={$port};dbname={$dbname};charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'เกิดข้อผิดพลาดในการเชื่อมต่อฐานข้อมูล: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
