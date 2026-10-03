<?php
require_once __DIR__ . '/../require_admin_auth.php';
require_once 'config.php';

try {
    $name = $_POST['name'] ?? '';
    $price = $_POST['price'] ?? 0;
    $stock = $_POST['stock'] ?? 0;
    $category = $_POST['category'] ?? 'General';
    $image_url = null;

    if (empty($name) || $price <= 0 || $stock < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid product data']);
        exit;
    }

    // Handle image upload
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($ext, $allowed)) {
            $filename = 'product_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($_FILES['product_image']['tmp_name'], $filepath)) {
                $image_url = 'api/uploads/' . $filename;
            }
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (product_name, price, stock, category, image) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$name, $price, $stock, $category, $image_url]);
    
    echo json_encode(['success' => true, 'message' => 'Product added']);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>