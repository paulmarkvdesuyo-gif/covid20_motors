<?php
require_once __DIR__ . '/database_config.php';

echo "<h2>Database Connection Test</h2>";

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color:green'>✅ Database connected successfully!</p>";
    
    // Check products
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
    $result = $stmt->fetch();
    echo "<p>📦 Total products: " . $result['count'] . "</p>";
    
    // Show products
    $stmt = $pdo->query("SELECT * FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    echo "<h3>Sample Products:</h3>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th></tr>";
    foreach($products as $p) {
        echo "<tr>";
        echo "<td>" . $p['product_id'] . "</td>";
        echo "<td>" . htmlspecialchars($p['product_name']) . "</td>";
        echo "<td>₱" . $p['selling_price'] . "</td>";
        echo "<td>" . $p['stock_quantity'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check sales
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sales");
    $result = $stmt->fetch();
    echo "<p>💰 Total sales: " . $result['count'] . "</p>";
    
} catch(PDOException $e) {
    echo "<p style='color:red'>❌ Database Error: " . $e->getMessage() . "</p>";
}
?>