<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include 'db_connection.php';

$data = json_decode(file_get_contents('php://input'), true);

// Log the request for debugging
error_log("=== CART REMOVE REQUEST ===");
error_log("Request Data: " . print_r($data, true));

if (!isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit;
}

$user_id = intval($data['user_id']);

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid User ID']);
    exit;
}

$success = false;
$message = '';
$deleted = 0;

// Check if we have cart_item_id
$cart_item_id = isset($data['cart_item_id']) ? intval($data['cart_item_id']) : 0;
$product_id = isset($data['product_id']) ? intval($data['product_id']) : 0;

error_log("User ID: $user_id, Cart Item ID: $cart_item_id, Product ID: $product_id");

// METHOD 1: Delete by cart_item_id
if ($cart_item_id > 0) {
    $sql = "DELETE FROM cart_items WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $cart_item_id, $user_id);
        if ($stmt->execute()) {
            $deleted = $stmt->affected_rows;
            error_log("Deleted rows (Method 1): $deleted");
            if ($deleted > 0) {
                $success = true;
                $message = "Item removed from cart by ID";
            } else {
                $message = "Item not found or already removed (ID: $cart_item_id)";
            }
        } else {
            $message = "Failed to remove item: " . $stmt->error;
            error_log("SQL Error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        $message = "Database error: " . $conn->error;
        error_log("Prepare Error: " . $conn->error);
    }
}

// METHOD 2: If cart_item_id didn't work, try by product_id
if (!$success && $product_id > 0) {
    $sql = "DELETE FROM cart_items WHERE product_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $product_id, $user_id);
        if ($stmt->execute()) {
            $deleted = $stmt->affected_rows;
            error_log("Deleted rows (Method 2): $deleted");
            if ($deleted > 0) {
                $success = true;
                $message = "Item removed from cart by Product ID";
            } else {
                $message = "Product not found in cart (Product ID: $product_id)";
            }
        } else {
            $message = "Failed to remove item by product: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Database error: " . $conn->error;
    }
}

// METHOD 3: Fallback - delete all items for this user
if (!$success) {
    $sql = "DELETE FROM cart_items WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $deleted = $stmt->affected_rows;
            error_log("Deleted rows (Method 3 - Fallback): $deleted");
            if ($deleted > 0) {
                $success = true;
                $message = "Cleared all items from cart (fallback)";
            } else {
                $message = "No items found in cart for user";
            }
        } else {
            $message = "Failed to clear cart: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Database error: " . $conn->error;
    }
}

error_log("Final Result - Success: " . ($success ? 'true' : 'false') . ", Message: $message");

echo json_encode([
    'success' => $success,
    'message' => $message,
    'deleted_count' => $deleted,
    'user_id' => $user_id,
    'cart_item_id' => $cart_item_id,
    'product_id' => $product_id
]);

$conn->close();
?>