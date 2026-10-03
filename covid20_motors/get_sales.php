<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';
require_once 'db_connection.php';

try {
    // Check what columns exist in sales table
    $stmt = $pdo->query("SHOW COLUMNS FROM sales");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine the correct ID column name
    $idColumn = 'id';
    if (!in_array('id', $columns)) {
        if (in_array('sale_id', $columns)) {
            $idColumn = 'sale_id';
        } elseif (in_array('sales_id', $columns)) {
            $idColumn = 'sales_id';
        }
    }
    
    // Build query with correct column names
    $sql = "SELECT 
                s.$idColumn as id, 
                s.product_id, 
                p.product_name, 
                s.quantity, 
                s.selling_price, 
                s.total, 
                s.sale_date 
            FROM sales s 
            JOIN products p ON s.product_id = p.product_id 
            ORDER BY s.sale_date DESC";
    
    $stmt = $pdo->query($sql);
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $sales]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
?>