<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);
    $productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;
    $quantity = filter_var($data['quantity'] ?? null, FILTER_VALIDATE_INT);

    if ($productId <= 0 || $quantity === false || $quantity <= 0 || $quantity > 99999) {
        echo json_encode(['success' => false, 'message' => 'Quantity must be a whole number from 1 to 99,999']);
        exit;
    }
    
    // Get product details
    $stmt = $pdo->prepare("SELECT product_name, price, stock FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    if ($product['stock'] < $quantity) {
        echo json_encode(['success' => false, 'message' => 'Insufficient stock']);
        exit;
    }
    
    $total = $product['price'] * $quantity;
    
    // Record sale
    $stmt = $pdo->prepare("INSERT INTO sales (product_id, selling_price, quantity, total) VALUES (?, ?, ?, ?)");
    $stmt->execute([$productId, $product['price'], $quantity, $total]);
    
    // Update stock
    $newStock = $product['stock'] - $quantity;
    $stmt = $pdo->prepare("UPDATE products SET stock = ? WHERE id = ?");
    $stmt->execute([$newStock, $productId]);
    
    echo json_encode(['success' => true, 'total' => $total]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>