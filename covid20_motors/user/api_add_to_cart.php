<?php
require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $data['user_id'] ?? 0;
    $product_id = $data['product_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;
    
    if (!$user_id || !$product_id || $quantity <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    // Check stock
    $stmt = $pdo->prepare("SELECT stock, product_name FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    if ($product['stock'] < $quantity) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Insufficient stock. Only ' . $product['stock'] . ' available']);
        exit;
    }
    
    // Check if item already in cart
    $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        $newQty = $existing['quantity'] + $quantity;
        if ($newQty > $product['stock']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Cannot add more than stock. Max: ' . $product['stock']]);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->execute([$newQty, $existing['id']]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $product_id, $quantity]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Added to cart']);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>