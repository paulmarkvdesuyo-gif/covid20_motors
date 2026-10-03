<?php
// Set DB_HOST, DB_NAME, DB_USER, DB_PASSWORD, and DB_PORT on the host,
// or add database_config.local.php with a PHP array of connection values.
$localConfigPath = __DIR__ . '/database_config.local.php';
$localConfig = is_file($localConfigPath) ? require $localConfigPath : [];
if (!is_array($localConfig)) {
	$localConfig = [];
}

$dbHost = getenv('DB_HOST');
$dbName = getenv('DB_NAME');
$dbUser = getenv('DB_USER');
$dbPassword = getenv('DB_PASSWORD');
$dbPort = getenv('DB_PORT');
$initialAdminPassword = getenv('INITIAL_ADMIN_PASSWORD');
$initialAdminEmail = getenv('INITIAL_ADMIN_EMAIL');

define('DB_HOST', $dbHost !== false && $dbHost !== '' ? $dbHost : ($localConfig['host'] ?? 'localhost'));
define('DB_NAME', $dbName !== false && $dbName !== '' ? $dbName : ($localConfig['name'] ?? 'covid20_motors'));
define('DB_USER', $dbUser !== false && $dbUser !== '' ? $dbUser : ($localConfig['user'] ?? 'root'));
define('DB_PASSWORD', $dbPassword !== false ? $dbPassword : ($localConfig['password'] ?? ''));
define('DB_PORT', $dbPort !== false && $dbPort !== '' ? $dbPort : ($localConfig['port'] ?? '3306'));
define('APP_INITIAL_ADMIN_PASSWORD', $initialAdminPassword !== false ? $initialAdminPassword : ($localConfig['initial_admin_password'] ?? ''));
define('APP_INITIAL_ADMIN_EMAIL', $initialAdminEmail !== false ? $initialAdminEmail : ($localConfig['initial_admin_email'] ?? ''));
