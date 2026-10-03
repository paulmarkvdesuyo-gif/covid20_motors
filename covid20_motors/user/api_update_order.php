<?php
require_once __DIR__ . '/../require_admin_auth.php';
require_once 'config.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $order_id = $data['order_id'] ?? 0;
    $status = $data['status'] ?? '';

    $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'denied', 'refunded'];
    if (!$order_id || !in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid order ID and status are required']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = CASE WHEN ? = 'refunded' THEN 'refunded' ELSE payment_status END WHERE id = ?");
    $stmt->execute([$status, $status, (int)$order_id]);

    if ($stmt->rowCount() === 0) {
        $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
        $checkStmt->execute([(int)$order_id]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Order status updated']);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>