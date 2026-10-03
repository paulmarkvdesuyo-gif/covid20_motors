<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once 'db_connection.php';

try {
    // Check what columns exist in products table
    $stmt = $pdo->query("SHOW COLUMNS FROM products");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Determine the correct ID column name
    $idColumn = 'product_id';
    if (!in_array('product_id', $columns)) {
        if (in_array('id', $columns)) {
            $idColumn = 'id';
        }
    }
    
    // Determine correct column names
    $nameColumn = in_array('product_name', $columns) ? 'product_name' : (in_array('name', $columns) ? 'name' : 'product_name');
    $priceColumn = in_array('selling_price', $columns) ? 'selling_price' : (in_array('price', $columns) ? 'price' : 'selling_price');
    $stockColumn = in_array('stock_quantity', $columns) ? 'stock_quantity' : (in_array('stock', $columns) ? 'stock' : 'stock_quantity');
    
    // Build query
    $sql = "SELECT 
                $idColumn as id, 
                $nameColumn as name, 
                $priceColumn as price, 
                $stockColumn as stock 
            FROM products 
            ORDER BY $idColumn DESC";
    
    $stmt = $pdo->query($sql);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $products]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'data' => []]);
}
?>