<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = filter_var($_POST['stock'] ?? null, FILTER_VALIDATE_INT);
    $category = $_POST['category'] ?? 'General';
    $image = null;

    if (!is_numeric($price) || (float)$price <= 0 || (float)$price > 99999) {
        echo json_encode(['success' => false, 'message' => 'Price must be between 0.01 and 99,999']);
        exit;
    }
    if ($stock === false || $stock < 0 || $stock > 99999) {
        echo json_encode(['success' => false, 'message' => 'Stock quantity must be a whole number from 0 to 99,999']);
        exit;
    }
    
    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        
        $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $targetPath)) {
            $image = $targetPath;
        }
    }
    
    $stmt = $pdo->prepare("INSERT INTO products (product_name, price, stock, category, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $stock, $category, $image]);
    
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>