<?php
require_once __DIR__ . '/../require_admin_auth.php';
require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $order_id = $data['order_id'] ?? 0;
    
    if (!$order_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Order ID required']);
        exit;
    }
    
    $pdo->beginTransaction();
    
    // Delete order details
    $stmt = $pdo->prepare("DELETE FROM order_details WHERE order_id = ?");
    $stmt->execute([$order_id]);
    
    // Delete order
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order deleted']);
    
} catch(PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>