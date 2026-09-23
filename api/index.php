<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ---------- เชื่อมต่อฐานข้อมูล ----------
if (file_exists(__DIR__ . '/../inc/connDB.php')) {
    require_once __DIR__ . '/../inc/connDB.php';
} else {
    require_once __DIR__ . '/../inc/ConnDB.php';
}

// ---------- โหลดคลาสหลัก ----------
require_once __DIR__ . '/core/Response.php';
require_once __DIR__ . '/core/Router.php';

// --------- โหลด controller ----------
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/SupplierController.php';
require_once __DIR__ . '/controllers/ProductController.php';

try {
    // ---------- สร้าง instance ของ controller ----------
    $categoryController = new CategoryController($conn);
    $supplierController = new SupplierController($conn);
    $productController  = new ProductController($conn);

    // ---------- ลงทะเบียน Route ทั้งหมดของระบบไว้ที่เดียว ----------
    $router = new Router();

    // Suppliers API
    $router->get('/suppliers', [$supplierController, 'index']);
    $router->get('/suppliers/{id}', [$supplierController, 'show']);

    // Categories API
    $router->get('/categories', [$categoryController, 'index']);
    $router->get('/categories/{id}', [$categoryController, 'show']);

    // Products API (Full CRUD)
    $router->get('/products', [$productController, 'index']);
    $router->get('/products/{id}', [$productController, 'show']);
    $router->post('/products', [$productController, 'store']);
    $router->put('/products/{id}', [$productController, 'update']);
    $router->delete('/products/{id}', [$productController, 'destroy']);

    // ---------- ดึง path จริงจาก query string ที่ .htaccess ส่งมาให้ (__route) ----------
    $requestPath    = $_GET['__route'] ?? '';
    $requestMethod  = $_SERVER['REQUEST_METHOD'];

    $router->dispatch($requestMethod, $requestPath);

} catch (Throwable $e) {
    Response::error('เกิดข้อผิดพลาดที่ไม่คาดคิดภายในระบบ: ' . $e->getMessage(), 500);
}
?>
