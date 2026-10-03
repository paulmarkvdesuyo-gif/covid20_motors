<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $itemStmt = $pdo->prepare("SELECT oi.order_id, oi.product_id, oi.quantity, oi.price, oi.subtotal,
                                      COALESCE(p.product_name, CONCAT('Product #', oi.product_id)) AS product_name
                               FROM order_items oi
                               LEFT JOIN products p ON p.id = oi.product_id
                               WHERE oi.order_id = ?");

    foreach ($orders as &$order) {
        $itemStmt->execute([$order['id']]);
        $order['items'] = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    unset($order);
    
    echo json_encode(['success' => true, 'orders' => $orders]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>