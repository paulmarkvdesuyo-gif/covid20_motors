<?php
// ============================================
// EMAIL CONFIGURATION
// ============================================

// From email address
define('SMTP_USERNAME', 'noreply@covid20smotors.com');

// Admin email (kung saan papunta ang admin notifications)
define('ADMIN_EMAIL', 'admin@covid20smotors.com');  // PALITAN ITO

// Site name
define('SITE_NAME', 'Co_vid 20\'s Motor Parts');

// Build links from the current host and project path instead of localhost.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$projectPath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if (substr($projectPath, -5) === '/user') {
	$projectPath = substr($projectPath, 0, -5);
}
define('BASE_URL', $scheme . '://' . $host . ($projectPath === '' ? '/' : $projectPath . '/'));

// Email settings
define('EMAIL_ENABLED', true);
?>