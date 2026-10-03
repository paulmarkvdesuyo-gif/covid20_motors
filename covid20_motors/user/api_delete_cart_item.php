<?php
require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $user_id = $data['user_id'] ?? 0;
    $cart_item_id = $data['cart_item_id'] ?? 0;
    
    if (!$user_id || !$cart_item_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }
    
    $stmt = $pdo->prepare("DELETE FROM cart_items WHERE id = ? AND user_id = ?");
    $stmt->execute([$cart_item_id, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Removed from cart']);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>