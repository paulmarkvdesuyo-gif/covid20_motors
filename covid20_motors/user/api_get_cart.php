<?php
require_once 'config.php';

try {
    $user_id = $_GET['user_id'] ?? 0;
    
    if (!$user_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT c.id, c.product_id, c.quantity, p.product_name as name, p.price, p.image 
        FROM cart_items c 
        LEFT JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = ?
        ORDER BY c.id DESC
    ");
    $stmt->execute([$user_id]);
    $cart = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'cart' => $cart]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>