<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['user_id']) || !isset($data['product_id']) || !isset($data['quantity'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$user_id = intval($data['user_id']);
$product_id = intval($data['product_id']);
$quantity = intval($data['quantity']);

if ($user_id <= 0 || $product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Update cart item
$sql = "UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $quantity, $user_id, $product_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Cart updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
}

$stmt->close();
$conn->close();
?>