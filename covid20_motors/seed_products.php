<?php
// Seeder: executes relevant statements from database.sql to populate products
require_once __DIR__ . '/db_connection.php';

$path = __DIR__ . '/database.sql';
if (!file_exists($path)) {
    echo "database.sql not found at $path\n";
    exit(1);
}

$sql = file_get_contents($path);
// Remove DOS line endings
$sql = str_replace("\r\n", "\n", $sql);

// Ensure the database exists before running seed SQL
$pdo = Database::getPdo();
$pdo->exec('CREATE DATABASE IF NOT EXISTS `covid20_motors` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `covid20_motors`');

// Create the products table if it does not exist
$pdo->exec(
    'CREATE TABLE IF NOT EXISTS products (
        id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        product_id INT UNSIGNED DEFAULT NULL,
        product_name VARCHAR(255) NOT NULL,
        name VARCHAR(255) DEFAULT NULL,
        price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        selling_price DECIMAL(10,2) DEFAULT NULL,
        stock INT NOT NULL DEFAULT 0,
        stock_quantity INT DEFAULT NULL,
        category VARCHAR(100) DEFAULT "General",
        status VARCHAR(50) DEFAULT "active",
        image VARCHAR(255) DEFAULT NULL,
        low_stock_threshold INT DEFAULT 5,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
);

// Split by semicolon followed by newline to avoid splitting inside definitions
$parts = preg_split('/;\s*\n/', $sql);
$executed = 0;

foreach ($parts as $part) {
    $stmt = trim($part);
    if ($stmt === '') continue;
    // remove SQL comments starting with --
    $stmt = preg_replace('/^--.*$/m', '', $stmt);
    $stmt = trim($stmt);
    if ($stmt === '') continue;

    $first = strtoupper(strtok($stmt, " \t\n\r\0\x0B("));
    // Only run schema/data modification statements
    $allowed = ['USE','TRUNCATE','ALTER','INSERT','CREATE','DROP','SET','ALTER TABLE','ALTER DATABASE'];
    if (!in_array($first, $allowed)) {
        // skip SELECTs and verification queries
        continue;
    }

    try {
        if (in_array($first, ['USE','ALTER','TRUNCATE','CREATE','DROP','SET'])) {
            $pdo->exec($stmt);
        } else {
            // INSERT, etc.
            $pdo->exec($stmt);
        }
        $executed++;
    } catch (PDOException $e) {
        echo "Failed: " . $first . " - " . $e->getMessage() . "\n";
    }
}

echo "Seeder finished. Executed statements: $executed\n";
