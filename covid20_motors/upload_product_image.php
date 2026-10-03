<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/require_admin_auth.php';

include 'db_connection.php';

$database = new Database();
$conn = $database->getConnection();
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Create uploads directory in the current app root
$upload_dir = 'uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Create product_images directory
$product_images_dir = 'uploads/product_images/';
if (!file_exists($product_images_dir)) {
    mkdir($product_images_dir, 0777, true);
}

// Check if product_id is provided
if (!isset($_POST['product_id']) || empty($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$product_id = intval($_POST['product_id']);

// Check if file is uploaded
if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
    exit;
}

$file = $_FILES['product_image'];
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$max_size = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, GIF, WEBP']);
    exit;
}

// Validate file size
if ($file['size'] > $max_size) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'product_' . $product_id . '_' . time() . '.' . $extension;
$filepath = $product_images_dir . $filename;
$relative_path = 'uploads/product_images/' . $filename;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // Update database with image path
    $sql = "UPDATE products SET image = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $updated = $stmt->execute([$relative_path, $product_id]);

    if ($updated) {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        $script_dir = dirname($_SERVER['SCRIPT_NAME']);
        $script_dir = rtrim($script_dir, '/\\');
        $image_url = $protocol . $host . $script_dir . '/' . ltrim($relative_path, '/');

        echo json_encode([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'image_url' => $image_url,
            'relative_path' => $relative_path
        ]);
    } else {
        unlink($filepath);
        $errorInfo = $stmt->errorInfo();
        $message = isset($errorInfo[2]) ? $errorInfo[2] : 'Database update failed';
        echo json_encode(['success' => false, 'message' => $message]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
}

$conn = null;
?>