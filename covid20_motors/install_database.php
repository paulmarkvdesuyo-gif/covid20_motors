<?php
// =============================================
// Database Installation Script
// Run this file once to set up your database
// =============================================

require_once __DIR__ . '/database_config.php';
require_once __DIR__ . '/auth_security.php';

// Create connection without database
// connect without selecting a database so we can create it
$conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, (int)DB_PORT);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Read SQL file and run as multi-query for reliability
$sqlFile = file_get_contents('database.sql');
if ($sqlFile === false) {
    die('Failed to read database.sql');
}

if ($conn->multi_query($sqlFile)) {
    // flush all results
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());

    $lockoutMigration = file_get_contents(__DIR__ . '/auth_login_attempts.sql');
    if ($lockoutMigration === false || !$conn->query($lockoutMigration)) {
        $conn->close();
        die('Database was installed, but login protection could not be initialized.');
    }

    try {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        provisionInitialAdmin($pdo);
    } catch (Throwable $error) {
        $conn->close();
        die('Database was installed, but initial admin provisioning failed. Check INITIAL_ADMIN_PASSWORD and database access.');
    }
} else {
    echo "Error executing SQL file: " . $conn->error;
    $conn->close();
    exit;
}

echo "<h2 style='color: green;'>✅ Database installed successfully!</h2>";
echo "<p>Database: <strong>covid20_motors</strong> created with all tables, views, procedures, and sample data.</p>";
echo "<p>Configure INITIAL_ADMIN_PASSWORD on the server (minimum 12 characters) to create the first admin account.</p>";
echo "<p><a href='index.html'>Go to Application</a></p>";

$conn->close();
?>