<?php
// classes/Database.php

require_once __DIR__ . '/../config.php'; // Load config file

class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn;

    // Get the database connection
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $this->conn = new PDO(
                    "mysql:host={$this->host};dbname={$this->db_name}",
                    $this->username,
                    $this->password
                );
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->conn->exec("SET NAMES utf8mb4"); // Proper charset
            } catch (PDOException $exception) {
                // Database Connection Error Handling
                // What: Failed to connect to the database (PDOException)
                // Why: Wrong credentials, server down, or DB not found
                // How: Log error and show user-friendly message
                error_log('[Database Error] ' . $exception->getMessage(), 3, __DIR__ . '/../logs/error.log');
                die('A database error occurred. Please try again later.');
            }
        }

        return $this->conn;
    }
}
?>
