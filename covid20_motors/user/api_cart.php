<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../database_config.php';

$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, (int)DB_PORT);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get cart items
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit();
    }
    
    $sql = "SELECT c.id, c.product_id, c.quantity, p.product_name as name, p.price 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $cart = [];
    while ($row = $result->fetch_assoc()) {
        $cart[] = $row;
    }
    
    echo json_encode(['success' => true, 'cart' => $cart]);
    exit();
    
} elseif ($method === 'POST') {
    // Add to cart
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        exit();
    }
    
    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;
    $product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;
    $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
    
    if ($user_id <= 0 || $product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID or product ID']);
        exit();
    }
    
    // Check if product exists
    $checkProduct = "SELECT id, stock FROM products WHERE id = ?";
    $checkStmt = $conn->prepare($checkProduct);
    $checkStmt->bind_param("i", $product_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    $product = $checkResult->fetch_assoc();
    
    // Check if product exists in cart
    $checkSql = "SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ii", $user_id, $product_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        // Update existing
        $row = $checkResult->fetch_assoc();
        $newQuantity = $row['quantity'] + $quantity;
        
        // Check if new quantity exceeds stock
        if ($newQuantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock. Available: ' . $product['stock']]);
            exit();
        }
        
        $updateSql = "UPDATE cart SET quantity = ? WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $newQuantity, $row['id']);
        
        if ($updateStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Cart updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
        }
    } else {
        // Insert new
        // Check if quantity exceeds stock
        if ($quantity > $product['stock']) {
            echo json_encode(['success' => false, 'message' => 'Not enough stock. Available: ' . $product['stock']]);
            exit();
        }
        
        $insertSql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iii", $user_id, $product_id, $quantity);
        
        if ($insertStmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Item added to cart']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add to cart: ' . $conn->error]);
        }
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid request method']);
$conn->close();
?>