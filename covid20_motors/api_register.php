<?php
// ============================================
// API REGISTER - COMPLETE FIX
// ============================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/database_config.php';

$input = json_decode(file_get_contents('php://input'), true);
$fullname = isset($input['fullname']) ? $input['fullname'] : '';
$username_input = isset($input['username']) ? $input['username'] : '';
$email = isset($input['email']) ? $input['email'] : '';
$phone = isset($input['phone']) ? $input['phone'] : '';
$address = isset($input['address']) ? $input['address'] : '';
$pass = isset($input['password']) ? $input['password'] : '';

if (strlen($pass) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->execute([$username_input, $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, fullname, email, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, 'customer')");
    $stmt->execute([$username_input, password_hash($pass, PASSWORD_DEFAULT), $fullname, $email, $phone, $address]);
    
    $user_id = $pdo->lastInsertId();
    
    $userData = [
        'id' => (int)$user_id,
        'username' => $username_input,
        'fullname' => $fullname,
        'email' => $email,
        'phone' => $phone,
        'address' => $address,
        'role' => 'customer'
    ];
    
    echo json_encode(['success' => true, 'message' => 'Registration successful', 'user' => $userData]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>