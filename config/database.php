<?php
/**
 * Database Configuration
 * Smart Attendance Automation System
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // Automatically detect environment
        $host = $_SERVER['HTTP_HOST'] ?? '';
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        if ($host === 'localhost' || in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
            // Local Development (XAMPP)
            $this->host = 'localhost';
            $this->db_name = 'attendance_system';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Production (InfinityFree)
            $this->host = getenv('DB_HOST') ?: 'DB_HOST_PLACEHOLDER';
            $this->db_name = getenv('DB_NAME') ?: 'DB_NAME_PLACEHOLDER';
            $this->username = getenv('DB_USERNAME') ?: 'DB_USERNAME_PLACEHOLDER';
            $this->password = getenv('DB_PASSWORD') ?: 'DB_PASSWORD_PLACEHOLDER';
        }
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            // In production, we might want to log this instead of echo
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }
}