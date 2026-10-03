<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth_security.php';

startAdminSession();
$_SESSION = [];

$cookie = session_get_cookie_params();
setcookie(session_name(), '', [
    'expires' => time() - 42000,
    'path' => $cookie['path'] ?: '/',
    'secure' => $cookie['secure'],
    'httponly' => $cookie['httponly'],
    'samesite' => $cookie['samesite'] ?? 'Lax',
]);
session_destroy();

echo json_encode(['success' => true]);