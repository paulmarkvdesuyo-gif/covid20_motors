<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/database_config.php';
require_once __DIR__ . '/auth_security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$identifier = isset($input['username']) && is_string($input['username']) ? trim($input['username']) : '';
$password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';
$attemptKey = getLoginAttemptKey($identifier);

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    provisionInitialAdmin($pdo);

    $lockoutRemaining = getLoginLockoutRemaining($pdo, $attemptKey);
    if ($lockoutRemaining > 0) {
        respondLoginLocked($lockoutRemaining);
    }

    $columns = $pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
    $hasUsername = in_array('username', $columns, true);
    if ($hasUsername) {
        $statement = $pdo->prepare("SELECT id, fullname, email, password, role FROM users WHERE role = 'admin' AND (username = ? OR email = ?) LIMIT 1");
        $statement->execute([$identifier, $identifier]);
    } else {
        $lookupEmail = $identifier;
        if (strtolower($identifier) === 'admin') {
            $initialAdminEmail = getenv('INITIAL_ADMIN_EMAIL');
            $lookupEmail = $initialAdminEmail !== false && filter_var($initialAdminEmail, FILTER_VALIDATE_EMAIL)
                ? $initialAdminEmail
                : 'admin@covid20smotors.com';
        }
        $statement = $pdo->prepare("SELECT id, fullname, email, password, role FROM users WHERE role = 'admin' AND email = ? LIMIT 1");
        $statement->execute([$lookupEmail]);
    }

    $admin = $statement->fetch(PDO::FETCH_ASSOC);
    if (!$admin || !verifyAndUpgradePassword($pdo, (int)$admin['id'], (string)$admin['password'], $password)) {
        $lockoutRemaining = recordLoginFailure($pdo, $attemptKey);
        if ($lockoutRemaining > 0) {
            respondLoginLocked($lockoutRemaining);
        }
        echo json_encode(['success' => false, 'message' => 'Invalid admin credentials']);
        exit;
    }

    clearLoginFailures($pdo, $attemptKey);
    startAdminSession();
    session_regenerate_id(true);
    $_SESSION['admin_user_id'] = (int)$admin['id'];
    $_SESSION['admin_email'] = $admin['email'];

    echo json_encode(['success' => true]);
} catch (Throwable $error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to process admin login']);
}