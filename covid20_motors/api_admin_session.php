<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/require_admin_auth.php';
echo json_encode(['success' => true]);