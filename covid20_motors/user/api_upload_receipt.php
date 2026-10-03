<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
require_once __DIR__ . '/../database_config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Create table if not exists
$createTable = "CREATE TABLE IF NOT EXISTS receipt_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    receipt_path VARCHAR(255) NOT NULL,
    receipt_name VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    INDEX idx_user_id (user_id),
    INDEX idx_created (created_at)
)";
try {
    $pdo->exec($createTable);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to create table: ' . $e->getMessage()]);
    exit();
}

// Create uploads directory if not exists
$uploadDir = __DIR__ . '/uploads/receipts/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Check if file was uploaded via form
if (isset($_FILES['receipt_image']) && $_FILES['receipt_image']['error'] === UPLOAD_ERR_OK) {
    // Traditional file upload
    $file = $_FILES['receipt_image'];
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    
    // Validate file
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, and WEBP are allowed']);
        exit();
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'File too large. Max 5MB allowed']);
        exit();
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $dbPath = 'uploads/receipts/' . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit();
    }
    
    // Save to database
    try {
        $stmt = $pdo->prepare("INSERT INTO receipt_uploads (user_id, receipt_path, receipt_name, created_at, used) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->execute([$user_id, $dbPath, $file['name']]);
        $receipt_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Receipt uploaded successfully',
            'receipt_id' => $receipt_id,
            'image_url' => $dbPath,
            'filename' => $file['name']
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

// Check if base64 image was sent in JSON
$input = json_decode(file_get_contents('php://input'), true);

if ($input && isset($input['receipt_image'])) {
    $user_id = isset($input['user_id']) ? intval($input['user_id']) : 0;
    $receipt_name = isset($input['receipt_name']) ? $input['receipt_name'] : 'receipt.png';
    
    // Decode base64 image
    $image_data = $input['receipt_image'];
    
    // Remove data URL prefix if present
    if (strpos($image_data, 'data:image') === 0) {
        $image_data = preg_replace('#^data:image/\w+;base64,#i', '', $image_data);
    }
    
    $image_data = base64_decode($image_data);
    
    if ($image_data === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid image data']);
        exit();
    }
    
    // Generate unique filename
    $extension = pathinfo($receipt_name, PATHINFO_EXTENSION);
    if (empty($extension)) {
        $extension = 'png';
    }
    $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    $dbPath = 'uploads/receipts/' . $filename;
    
    // Save file
    if (file_put_contents($filepath, $image_data) === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit();
    }
    
    // Save to database
    try {
        $stmt = $pdo->prepare("INSERT INTO receipt_uploads (user_id, receipt_path, receipt_name, created_at, used) VALUES (?, ?, ?, NOW(), 0)");
        $stmt->execute([$user_id, $dbPath, $receipt_name]);
        $receipt_id = $pdo->lastInsertId();
        
        echo json_encode([
            'success' => true,
            'message' => 'Receipt uploaded successfully',
            'receipt_id' => $receipt_id,
            'image_url' => $dbPath,
            'filename' => $receipt_name
        ]);
    } catch(PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

echo json_encode(['success' => false, 'message' => 'No image data received']);
?>