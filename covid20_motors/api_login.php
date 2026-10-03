<?php
// ============================================
// API LOGIN - COMPLETE FIX
// ============================================

error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/database_config.php';
require_once __DIR__ . '/auth_security.php';

$input = json_decode(file_get_contents('php://input'), true);
$username_input = isset($input['username']) && is_string($input['username']) ? trim($input['username']) : '';
$password_input = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $attemptKey = getLoginAttemptKey($username_input);
    $lockoutRemaining = getLoginLockoutRemaining($pdo, $attemptKey);
    if ($lockoutRemaining > 0) {
        respondLoginLocked($lockoutRemaining);
    }
    
    $stmt = $pdo->prepare("SELECT id, username, fullname, email, phone, address, role, password FROM users WHERE (username = ? OR email = ?) AND role = 'customer'");
    $stmt->execute([$username_input, $username_input]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && verifyAndUpgradePassword($pdo, (int)$user['id'], (string)$user['password'], $password_input)) {
        clearLoginFailures($pdo, $attemptKey);
        unset($user['password']);
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        $lockoutRemaining = recordLoginFailure(
            $pdo,
            $attemptKey,
            CUSTOMER_LOGIN_FAILURE_LIMIT,
            LOGIN_FAILURE_WINDOW_SECONDS,
            CUSTOMER_LOGIN_LOCKOUT_SECONDS
        );
        if ($lockoutRemaining > 0) {
            respondLoginLocked($lockoutRemaining);
        }
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} catch(PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>