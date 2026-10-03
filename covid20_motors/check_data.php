<?php
header('Content-Type: application/json');

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $productsStmt = $pdo->query("SELECT * FROM products ORDER BY product_id");
    $products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $salesStmt = $pdo->query("
        SELECT s.sale_id, s.invoice_number, s.sale_date, s.customer_name, s.grand_total,
               si.product_id, si.quantity, si.unit_price, si.subtotal, p.product_name
        FROM sales s
        LEFT JOIN sale_items si ON s.sale_id = si.sale_id
        LEFT JOIN products p ON si.product_id = p.product_id
        ORDER BY s.sale_date DESC
    ");
    $sales = $salesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalSales = 0;
    foreach ($sales as $sale) {
        $totalSales += floatval($sale['subtotal'] ?? 0);
    }
    
    echo json_encode([
        'success' => true,
        'database' => $dbname,
        'total_products' => count($products),
        'products' => $products,
        'total_sales' => $totalSales,
        'sales' => $sales
    ], JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
