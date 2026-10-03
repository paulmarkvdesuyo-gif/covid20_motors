<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
require_once __DIR__ . '/require_admin_auth.php';

require_once 'db_connection.php';
// Get dashboard statistics with safe defaults
try {
    $totalProductsRow = Database::getRow("SELECT COUNT(*) as total FROM products WHERE status = 'active'");
    $lowStockRow = Database::getRow("SELECT COUNT(*) as total FROM products WHERE stock_quantity <= low_stock_threshold");
    $totalSalesRow = Database::getRow("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE DATE(sale_date) = CURDATE()");
    $monthlySalesRow = Database::getRow("SELECT COALESCE(SUM(grand_total), 0) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE())");

    $totalProducts = isset($totalProductsRow['total']) ? intval($totalProductsRow['total']) : 0;
    $lowStock = isset($lowStockRow['total']) ? intval($lowStockRow['total']) : 0;
    $todaySales = isset($totalSalesRow['total']) ? floatval($totalSalesRow['total']) : 0.0;
    $monthlySales = isset($monthlySalesRow['total']) ? floatval($monthlySalesRow['total']) : 0.0;

    // Get sales chart data (last 7 days)
    $chartData = Database::getResults(
        "SELECT 
            DATE(sale_date) as date,
            COUNT(*) as transactions,
            COALESCE(SUM(grand_total), 0) as revenue
         FROM sales 
         WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
         GROUP BY DATE(sale_date)
         ORDER BY date ASC"
    );

    // Get recent sales
    $recentSales = Database::getResults(
        "SELECT s.sale_id, s.invoice_number, s.sale_date, s.customer_name, s.grand_total,
                COUNT(si.sale_item_id) as item_count
         FROM sales s
         LEFT JOIN sale_items si ON s.sale_id = si.sale_id
         GROUP BY s.sale_id
         ORDER BY s.sale_date DESC
         LIMIT 10"
    );

    echo json_encode([
        'total_products' => $totalProducts,
        'low_stock_count' => $lowStock,
        'today_sales' => $todaySales,
        'monthly_sales' => $monthlySales,
        'chart_data' => $chartData ?: [],
        'recent_sales' => $recentSales ?: []
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>