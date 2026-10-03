<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create orders table if not exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'orders'");
    if ($tableCheck->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE orders (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_number VARCHAR(50) UNIQUE NOT NULL,
                user_id INT NOT NULL,
                customer_name VARCHAR(255) NOT NULL,
                customer_email VARCHAR(255),
                total_amount DECIMAL(10,2) NOT NULL,
                payment_method VARCHAR(50) DEFAULT 'cash',
                shipping_address TEXT NOT NULL,
                status VARCHAR(50) DEFAULT 'pending',
                order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }
    
    // Create order_items table if not exists
    $tableCheck2 = $pdo->query("SHOW TABLES LIKE 'order_items'");
    if ($tableCheck2->rowCount() == 0) {
        $pdo->exec("
            CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                product_name VARCHAR(255) NOT NULL,
                quantity INT NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL
            )
        ");
    }
    
    // Get data from request
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Log for debugging
    error_log("Checkout data: " . print_r($data, true));
    
    // Validate required fields
    if (!isset($data['user_id']) || empty($data['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User ID required']);
        exit;
    }
    
    if (!isset($data['cart']) || empty($data['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit;
    }
    
    if (!isset($data['address']) || empty(trim($data['address']))) {
        echo json_encode(['success' => false, 'message' => 'Delivery address required']);
        exit;
    }
    
    $userId = intval($data['user_id']);
    $cart = $data['cart'];
    $address = trim($data['address']);
    $payment = isset($data['payment_method']) ? $data['payment_method'] : 'cash';
    $deliveryInfo = isset($data['delivery_info']) ? $data['delivery_info'] : [];
    
    $fullname = isset($deliveryInfo['fullname']) ? trim($deliveryInfo['fullname']) : '';
    $email = isset($deliveryInfo['email']) ? trim($deliveryInfo['email']) : '';
    
    if (empty($fullname)) {
        echo json_encode(['success' => false, 'message' => 'Full name required']);
        exit;
    }
    
    // Calculate total
    $total = 0;
    foreach ($cart as $item) {
        $price = isset($item['price']) ? floatval($item['price']) : 0;
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
        $total += $price * $quantity;
    }
    
    // Generate order number
    $orderNumber = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);
    
    // Insert order
    $sql = "INSERT INTO orders (order_number, user_id, customer_name, customer_email, total_amount, payment_method, shipping_address) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $orderNumber,
        $userId,
        $fullname,
        $email,
        $total,
        $payment,
        $address
    ]);
    $orderId = $pdo->lastInsertId();
    
    // Insert order items
    foreach ($cart as $item) {
        $productId = isset($item['product_id']) ? intval($item['product_id']) : 0;
        $productName = isset($item['name']) ? trim($item['name']) : '';
        $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
        $price = isset($item['price']) ? floatval($item['price']) : 0;
        $subtotal = $price * $quantity;
        
        // Skip if product name is empty
        if (empty($productName)) {
            error_log("Warning: Empty product name for item: " . print_r($item, true));
            continue;
        }
        
        $sql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price, subtotal) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $orderId,
            $productId,
            $productName,
            $quantity,
            $price,
            $subtotal
        ]);

        // Record customer purchases in the admin sales history.
        $stmt = $pdo->prepare("INSERT INTO sales (product_id, quantity, selling_price, total, user_id, order_id)
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$productId, $quantity, $price, $subtotal, $userId, $orderId]);
        
        // Update stock
        $stmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
        $stmt->execute([$quantity, $productId]);
    }
    
    // Clear cart
    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'order_number' => $orderNumber,
        'message' => 'Order placed successfully!'
    ]);
    
} catch(PDOException $e) {
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>