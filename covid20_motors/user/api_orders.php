<?php
require_once 'config.php';

try {
    $user_id = $_GET['user_id'] ?? 0;

    if ($user_id) {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    }
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $itemStmt = $pdo->prepare("SELECT oi.order_id, oi.product_id, oi.quantity, oi.price, oi.subtotal,
                                      COALESCE(p.product_name, CONCAT('Product #', oi.product_id)) AS name
                               FROM order_items oi
                               LEFT JOIN products p ON p.id = oi.product_id
                               WHERE oi.order_id = ?");

    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);

    echo json_encode(['success' => true, 'data' => $orders]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>