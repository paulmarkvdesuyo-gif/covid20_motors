<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT s.*, p.product_name,
                                COALESCE(o.payment_method, 'admin') AS payment_method
                                 FROM sales s
                                 JOIN products p ON s.product_id = p.id
                                 LEFT JOIN orders o ON o.id = s.order_id
                                 WHERE s.order_id IS NULL
                                     OR o.status NOT IN ('cancelled', 'denied', 'refunded')
                                 ORDER BY s.sale_date DESC");
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sales]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>