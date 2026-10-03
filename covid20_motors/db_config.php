<?php
require_once __DIR__ . '/database_config.php';

class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASSWORD);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            return null;
        }
        return $this->conn;
    }
}
?>