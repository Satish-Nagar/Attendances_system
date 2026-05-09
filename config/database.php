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
        if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
            // Local Development (XAMPP)
            $this->host = 'localhost';
            $this->db_name = 'attendance_system';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Production (InfinityFree)
            $this->host = 'sql100.infinityfree.com';
            $this->db_name = 'if0_41739228_QuickMark';
            $this->username = 'if0_41739228';
            $this->password = 'WQOv4JcPEOCEDEe';
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