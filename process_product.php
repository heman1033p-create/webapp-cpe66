<?php
header('Content-Type: application/json; charset=utf-8');

if (file_exists(__DIR__ . '/inc/connDB.php')) {
    require_once __DIR__ . '/inc/connDB.php';
} elseif (file_exists(__DIR__ . '/inc/ConnDB.php')) {
    require_once __DIR__ . '/inc/ConnDB.php';
} elseif (file_exists(__DIR__ . '/connDB.php')) {
    require_once __DIR__ . '/connDB.php';
} else {
    require_once __DIR__ . '/../connDB.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['success' => false, 'message' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
   exit;
}

$productName = trim($_POST['ProductName'] ?? '');
$supplierId  = trim($_POST['SupplierID'] ?? '');
$catId       = trim($_POST['CatID'] ?? '');
$unit        = trim($_POST['Unit'] ?? '');
$price       = trim($_POST['Price'] ?? '');
$productID   = $_POST['ProductID'] ?? '';
$action      = $_POST['action'] ?? 'insert';

// ตรวจสอบความถูกต้องของข้อมูล (Validation)
$errors = [];
if (empty($productName)) {
    $errors['ProductName'] = 'กรุณาระบุชื่อสินค้า';
}
if (empty($supplierId)) {
    $errors['SupplierID'] = 'กรุณาเลือกผู้จัดจำหน่าย';
}
if (empty($catId)) {
    $errors['CatID'] = 'กรุณาเลือกหมวดหมู่สินค้า';
}
if (empty($unit)) {
    $errors['Unit'] = 'กรุณาระบุหน่วยนับ';
}
if ($price === '' || !is_numeric($price) || floatval($price) < 0) {
    $errors['Price'] = 'กรุณาระบุราคาที่ถูกต้อง';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'ข้อมูลไม่ผ่านการตรวจสอบ',
        'errors'  => $errors
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
   if ($action === 'update' && !empty($productID)) {
        $sql = "UPDATE tb_products 
                SET c_ProductName = :productName, 
                    i_SupplierID = :supplierId, 
                    i_CategoryID = :catId, 
                    c_Unit = :unit, 
                    i_Price = :price 
                WHERE i_ProductID = :productID";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':productName', $productName, PDO::PARAM_STR);
        $stmt->bindParam(':supplierId', $supplierId, PDO::PARAM_INT);
        $stmt->bindParam(':catId', $catId, PDO::PARAM_INT);
        $stmt->bindParam(':unit', $unit, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
        $stmt->bindParam(':productID', $productID, PDO::PARAM_INT);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'อัปเดตข้อมูลสำเร็จ',
                'id'      => $productID
            ], JSON_UNESCAPED_UNICODE);
        } 

   } else {
        $sql = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price) 
                VALUES (:productName, :supplierId, :catId, :unit, :price)";
        $stmt = $conn->prepare($sql); 
        $stmt->bindParam(':productName', $productName, PDO::PARAM_STR);
        $stmt->bindParam(':supplierId', $supplierId, PDO::PARAM_INT);
        $stmt->bindParam(':catId', $catId, PDO::PARAM_INT);
        $stmt->bindParam(':unit', $unit, PDO::PARAM_STR);
        $stmt->bindParam(':price', $price, PDO::PARAM_STR);
    
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'บันทึกข้อมูลสำเร็จ',
                'id'      => $conn->lastInsertId()
            ], JSON_UNESCAPED_UNICODE);
        } 
   }

} catch(PDOException $e) {
   http_response_code(500);
   echo json_encode([
       'success' => false,
       'message' => 'ไม่สามารถบันทึกข้อมูลได้: ' . $e->getMessage()
   ], JSON_UNESCAPED_UNICODE);
}
?>
