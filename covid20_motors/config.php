<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// =============================================
// DATABASE CONFIGURATION
// =============================================
require_once __DIR__ . '/database_config.php';
require_once __DIR__ . '/auth_security.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// =============================================
// CREATE TABLES
// =============================================
try {
    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        fullname VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NULL,
        address TEXT NULL,
        role VARCHAR(50) DEFAULT 'customer',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_name VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        category VARCHAR(100) DEFAULT 'General',
        image VARCHAR(500) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Cart items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS cart_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    // Sales table
    $pdo->exec("CREATE TABLE IF NOT EXISTS sales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        selling_price DECIMAL(10,2) NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        user_id INT NULL,
        order_id INT NULL,
        sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(50) UNIQUE NOT NULL,
        user_id INT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20) NULL,
        delivery_address TEXT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(50) DEFAULT 'cash',
        status VARCHAR(50) DEFAULT 'pending',
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // Order details table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");

    provisionInitialAdmin($pdo);

    // Insert sample products if empty
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO products (product_name, price, stock, category) VALUES
            ('Piston Ring Set - Standard', 450.00, 20, 'Engine Parts'),
            ('Cylinder Head Gasket', 380.00, 15, 'Engine Parts'),
            ('Valve Seal Set', 280.00, 25, 'Engine Parts'),
            ('Front Brake Pads - Organic', 450.00, 25, 'Brakes'),
            ('Rear Brake Pads - Sintered', 520.00, 20, 'Brakes'),
            ('Brake Disc Rotor - Front', 1850.00, 10, 'Brakes'),
            ('Spark Plug - Iridium', 850.00, 20, 'Electrical'),
            ('Ignition Coil', 680.00, 12, 'Electrical'),
            ('Rectifier Regulator', 950.00, 8, 'Electrical'),
            ('Chain and Sprocket Kit - 520', 2200.00, 10, 'Drive Train'),
            ('Front Tire - 110/70-17', 2500.00, 8, 'Tires'),
            ('Rear Tire - 140/70-17', 3200.00, 6, 'Tires'),
            ('Handlebar Grips - Rubber', 180.00, 30, 'Accessories'),
            ('Motorcycle Cover - Small', 650.00, 7, 'Accessories'),
            ('Engine Oil 20W-50 - 1L', 320.00, 40, 'Lubricants'),
            ('Chain Lube Spray - 400ml', 280.00, 25, 'Lubricants'),
            ('Spark Plug Wrench Set', 280.00, 18, 'Tools'),
            ('Chain Breaker Tool', 450.00, 12, 'Tools'),
            ('Fender Set - Front', 850.00, 8, 'Body Parts'),
            ('Fork Seal Kit', 320.00, 15, 'Suspension')
        ");
    }
} catch(PDOException $e) {
    // Tables might already exist, ignore errors
}
?>