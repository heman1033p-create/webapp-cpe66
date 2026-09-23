<?php
class ProductController
{
    private PDO $conn;

    public function __construct(PDO $conn)
    {
        $this->conn = $conn;
    }

    /** Helper: อ่านข้อมูล input ทั้งจาก JSON body และ $_POST */
    private function getRequestData(): array
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            return $input;
        }

        // กรณีเป็น Form-urlencoded หรือ Multipart
        if (!empty($_POST)) {
            return $_POST;
        }

        // กรณี PUT/DELETE ที่ส่งมาแบบ urlencoded
        parse_str(file_get_contents('php://input'), $parsed);
        return is_array($parsed) ? $parsed : [];
    }

    /** GET /api/products — ดึงรายการสินค้าทั้งหมด พร้อมรองรับการค้นหา (?search=...) */
    public function index(): void
    {
        try {
            $search = trim($_GET['search'] ?? '');
            
            $sql = "SELECT 
                        p.i_ProductID AS id,
                        p.c_ProductName AS name,
                        p.i_SupplierID AS supplier_id,
                        COALESCE(s.c_SupplierName, '-') AS supplier_name,
                        p.i_CategoryID AS category_id,
                        COALESCE(c.c_CategoryName, '-') AS category_name,
                        p.c_Unit AS unit,
                        p.i_Price AS price
                    FROM tb_products p
                    LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID
                    LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID";

            if ($search !== '') {
                $sql .= " WHERE p.c_ProductName LIKE :searchLike 
                             OR s.c_SupplierName LIKE :searchLike 
                             OR c.c_CategoryName LIKE :searchLike
                             OR p.i_ProductID = :searchExact";
            }

            $sql .= " ORDER BY p.i_ProductID DESC";

            $stmt = $this->conn->prepare($sql);
            if ($search !== '') {
                $searchLike = "%{$search}%";
                $stmt->bindValue(':searchLike', $searchLike, PDO::PARAM_STR);
                $stmt->bindValue(':searchExact', is_numeric($search) ? (int)$search : 0, PDO::PARAM_INT);
            }
            $stmt->execute();

            Response::success($stmt->fetchAll(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            Response::error('ไม่สามารถดึงข้อมูลสินค้าได้: ' . $e->getMessage(), 500);
        }
    }

    /** GET /api/products/{id} — ดึงข้อมูลสินค้ารายชิ้น */
    public function show(string $id): void
    {
        try {
            $sql = "SELECT 
                        p.i_ProductID AS id,
                        p.c_ProductName AS name,
                        p.i_SupplierID AS supplier_id,
                        COALESCE(s.c_SupplierName, '-') AS supplier_name,
                        p.i_CategoryID AS category_id,
                        COALESCE(c.c_CategoryName, '-') AS category_name,
                        p.c_Unit AS unit,
                        p.i_Price AS price
                    FROM tb_products p
                    LEFT JOIN tb_suppliers s ON p.i_SupplierID = s.i_SupplierID
                    LEFT JOIN tb_categories c ON p.i_CategoryID = c.i_CategoryID
                    WHERE p.i_ProductID = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();

            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                Response::notFound('ไม่พบสินค้ารหัสนี้');
                return;
            }

            Response::success($product);
        } catch (PDOException $e) {
            Response::error('ไม่สามารถดึงข้อมูลสินค้าได้: ' . $e->getMessage(), 500);
        }
    }

    /** POST /api/products — เพิ่มสินค้าใหม่ */
    public function store(): void
    {
        $data = $this->getRequestData();

        $name       = trim($data['ProductName'] ?? $data['name'] ?? '');
        $supplierId = trim($data['SupplierID'] ?? $data['supplier_id'] ?? '');
        $catId      = trim($data['CatID'] ?? $data['category_id'] ?? '');
        $unit       = trim($data['Unit'] ?? $data['unit'] ?? '');
        $price      = trim($data['Price'] ?? $data['price'] ?? '');

        // Validation
        $errors = [];
        if ($name === '' || mb_strlen($name) < 2) {
            $errors['name'] = 'ชื่อสินค้าต้องมีอย่างน้อย 2 ตัวอักษร';
        }
        if ($supplierId === '' || !is_numeric($supplierId)) {
            $errors['supplier_id'] = 'กรุณาเลือกผู้จัดจำหน่าย';
        }
        if ($catId === '' || !is_numeric($catId)) {
            $errors['category_id'] = 'กรุณาเลือกหมวดหมู่สินค้า';
        }
        if ($unit === '') {
            $errors['unit'] = 'กรุณาระบุหน่วยนับ';
        }
        if ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $errors['price'] = 'ราคาสินค้าต้องเป็นตัวเลขมากกว่าหรือเท่ากับ 0';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        try {
            $sql = "INSERT INTO tb_products (c_ProductName, i_SupplierID, i_CategoryID, c_Unit, i_Price)
                    VALUES (:name, :supplierId, :catId, :unit, :price)";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':supplierId', (int)$supplierId, PDO::PARAM_INT);
            $stmt->bindValue(':catId', (int)$catId, PDO::PARAM_INT);
            $stmt->bindValue(':unit', $unit, PDO::PARAM_STR);
            $stmt->bindValue(':price', (float)$price);
            $stmt->execute();

            $newId = $this->conn->lastInsertId();
            Response::success([
                'id'   => $newId,
                'name' => $name
            ], 'บันทึกข้อมูลสินค้าเรียบร้อยแล้ว', 201);
        } catch (PDOException $e) {
            Response::error('เกิดข้อผิดพลาดในการบันทึกสินค้า: ' . $e->getMessage(), 500);
        }
    }

    /** PUT /api/products/{id} — แก้ไขข้อมูลสินค้า */
    public function update(string $id): void
    {
        $data = $this->getRequestData();

        $name       = trim($data['ProductName'] ?? $data['name'] ?? '');
        $supplierId = trim($data['SupplierID'] ?? $data['supplier_id'] ?? '');
        $catId      = trim($data['CatID'] ?? $data['category_id'] ?? '');
        $unit       = trim($data['Unit'] ?? $data['unit'] ?? '');
        $price      = trim($data['Price'] ?? $data['price'] ?? '');

        $errors = [];
        if ($name === '' || mb_strlen($name) < 2) {
            $errors['name'] = 'ชื่อสินค้าต้องมีอย่างน้อย 2 ตัวอักษร';
        }
        if ($supplierId === '' || !is_numeric($supplierId)) {
            $errors['supplier_id'] = 'กรุณาเลือกผู้จัดจำหน่าย';
        }
        if ($catId === '' || !is_numeric($catId)) {
            $errors['category_id'] = 'กรุณาเลือกหมวดหมู่สินค้า';
        }
        if ($unit === '') {
            $errors['unit'] = 'กรุณาระบุหน่วยนับ';
        }
        if ($price === '' || !is_numeric($price) || (float)$price < 0) {
            $errors['price'] = 'ราคาสินค้าต้องเป็นตัวเลขมากกว่าหรือเท่ากับ 0';
        }

        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        try {
            // ตรวจสอบว่ามีสินค้านี้อยู่จริงหรือไม่
            $checkStmt = $this->conn->prepare("SELECT i_ProductID FROM tb_products WHERE i_ProductID = :id");
            $checkStmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $checkStmt->execute();
            if (!$checkStmt->fetch()) {
                Response::notFound('ไม่พบสินค้าที่ต้องการแก้ไข');
                return;
            }

            $sql = "UPDATE tb_products 
                    SET c_ProductName = :name, 
                        i_SupplierID = :supplierId, 
                        i_CategoryID = :catId, 
                        c_Unit = :unit, 
                        i_Price = :price 
                    WHERE i_ProductID = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':name', $name, PDO::PARAM_STR);
            $stmt->bindValue(':supplierId', (int)$supplierId, PDO::PARAM_INT);
            $stmt->bindValue(':catId', (int)$catId, PDO::PARAM_INT);
            $stmt->bindValue(':unit', $unit, PDO::PARAM_STR);
            $stmt->bindValue(':price', (float)$price);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();

            Response::success([
                'id'   => $id,
                'name' => $name
            ], 'อัปเดตข้อมูลสินค้าเรียบร้อยแล้ว');
        } catch (PDOException $e) {
            Response::error('เกิดข้อผิดพลาดในการอัปเดตสินค้า: ' . $e->getMessage(), 500);
        }
    }

    /** DELETE /api/products/{id} — ลบสินค้า */
    public function destroy(string $id): void
    {
        try {
            $checkStmt = $this->conn->prepare("SELECT i_ProductID FROM tb_products WHERE i_ProductID = :id");
            $checkStmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $checkStmt->execute();
            if (!$checkStmt->fetch()) {
                Response::notFound('ไม่พบสินค้าที่ต้องการลบ');
                return;
            }

            $stmt = $this->conn->prepare("DELETE FROM tb_products WHERE i_ProductID = :id");
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();

            Response::success(['id' => $id], 'ลบข้อมูลสินค้าเรียบร้อยแล้ว');
        } catch (PDOException $e) {
            Response::error('เกิดข้อผิดพลาดในการลบสินค้า: ' . $e->getMessage(), 500);
        }
    }
}
?>
