<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once 'db_connection.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get low stock products
        $lowStock = Database::getResults(
            "SELECT * FROM low_stock_view WHERE alert_level = 'CRITICAL' ORDER BY stock_quantity ASC"
        );
        
        $count = Database::getRow(
            "SELECT COUNT(*) as count FROM products WHERE stock_quantity <= low_stock_threshold"
        );
        
        echo json_encode([
            'low_stock_items' => $lowStock,
            'total_low_stock' => $count['count'],
            'threshold' => 5
        ]);
        break;
        
    case 'POST':
        // Mark notification as resolved
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['notification_id'])) {
            Database::execute(
                "UPDATE low_stock_notifications SET status = 'resolved' WHERE notification_id = ?",
                [$data['notification_id']]
            );
            echo json_encode(['success' => true, 'message' => 'Notification resolved']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
        }
        break;
}
?>