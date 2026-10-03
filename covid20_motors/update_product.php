<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require_once __DIR__ . '/require_admin_auth.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load database configuration from the root folder
require_once __DIR__ . '/db_config.php';

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

// Debug: Log the received data
error_log("Update product received: " . print_r($data, true));

// Check if data was received
if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received. Please check your request.']);
    exit;
}

// Validate required fields
if (!isset($data['id']) || empty($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID is required']);
    exit;
}

if (!isset($data['name']) || empty(trim($data['name']))) {
    echo json_encode(['success' => false, 'message' => 'Product name is required']);
    exit;
}

if (!isset($data['price']) || $data['price'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valid price is required']);
    exit;
}

if (!is_numeric($data['price']) || (float)$data['price'] > 99999) {
    echo json_encode(['success' => false, 'message' => 'Price cannot exceed 99,999 (maximum 5 digits)']);
    exit;
}

$stockValue = filter_var($data['stock'] ?? null, FILTER_VALIDATE_INT);
if ($stockValue === false || $stockValue < 0 || $stockValue > 99999) {
    echo json_encode(['success' => false, 'message' => 'Stock quantity must be a whole number from 0 to 99,999']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if connection is successful
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Check if product exists
    $checkQuery = "SELECT id FROM products WHERE id = ?";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute([$data['id']]);
    $exists = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$exists) {
        echo json_encode(['success' => false, 'message' => 'Product not found with ID: ' . $data['id']]);
        exit;
    }
    
    // Update the product
    $query = "UPDATE products SET 
              product_name = ?, 
              price = ?, 
              stock = ?, 
              category = ? 
              WHERE id = ?";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([
        trim($data['name']),
        floatval($data['price']),
        $stockValue,
        $data['category'] ?? 'General',
        intval($data['id'])
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        // Get the updated product
        $getQuery = "SELECT * FROM products WHERE id = ?";
        $getStmt = $db->prepare($getQuery);
        $getStmt->execute([$data['id']]);
        $updatedProduct = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Product updated successfully',
            'product' => $updatedProduct
        ]);
    } else if ($result && $stmt->rowCount() == 0) {
        // No rows updated (maybe same values)
        echo json_encode([
            'success' => true, 
            'message' => 'Product updated successfully (no changes needed)'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
} catch(PDOException $e) {
    error_log("Update product error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>