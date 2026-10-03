<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $data = json_decode(file_get_contents('php://input'), true);

    $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'out_for_delivery', 'delivered', 'completed', 'cancelled', 'denied', 'refunded'];
    $orderId = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $status = $data['status'] ?? '';
    if ($orderId <= 0 || !in_array($status, $allowedStatuses, true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid order ID and status are required']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE orders SET status = ?, payment_status = CASE WHEN ? = 'refunded' THEN 'refunded' ELSE payment_status END WHERE id = ?");
    $stmt->execute([$status, $status, $orderId]);

    if ($stmt->rowCount() === 0) {
        $checkStmt = $pdo->prepare("SELECT id FROM orders WHERE id = ?");
        $checkStmt->execute([$orderId]);
        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }
    }
    
    echo json_encode(['success' => true]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>