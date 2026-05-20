<?php
/**
 * Database Configuration
 * Smart Attendance Automation System
 *
 * Local (XAMPP): uses quickmark on localhost automatically.
 * Production (InfinityFree): placeholders are replaced during GitHub deploy.
 * Optional overrides: copy config/local.example.php to config/local.php (gitignored).
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        if ($this->loadLocalOverrides()) {
            return;
        }

        if ($this->isLocalEnvironment()) {
            $this->host = 'localhost';
            $this->db_name = 'quickmark';
            $this->username = 'root';
            $this->password = '';
            return;
        }

        // Production (InfinityFree) — values injected by GitHub Actions on deploy
        $this->host = getenv('DB_HOST') ?: 'DB_HOST_PLACEHOLDER';
        $this->db_name = getenv('DB_NAME') ?: 'DB_NAME_PLACEHOLDER';
        $this->username = getenv('DB_USERNAME') ?: 'DB_USERNAME_PLACEHOLDER';
        $this->password = getenv('DB_PASSWORD') ?: 'DB_PASSWORD_PLACEHOLDER';
    }

    private function isLocalEnvironment(): bool {
        $host = strtolower($_SERVER['HTTP_HOST'] ?? '');
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';

        if (in_array($remoteAddr, ['127.0.0.1', '::1'], true)) {
            return true;
        }

        if ($host === '' && PHP_SAPI === 'cli') {
            return true;
        }

        $localHosts = ['localhost', '127.0.0.1', '::1'];
        foreach ($localHosts as $local) {
            if ($host === $local || str_starts_with($host, $local . ':')) {
                return true;
            }
        }

        return false;
    }

    private function loadLocalOverrides(): bool {
        $localFile = __DIR__ . '/local.php';
        if (!is_file($localFile)) {
            return false;
        }

        $config = require $localFile;
        if (!is_array($config)) {
            return false;
        }

        $this->host = $config['host'] ?? 'localhost';
        $this->db_name = $config['db_name'] ?? 'quickmark';
        $this->username = $config['username'] ?? 'root';
        $this->password = $config['password'] ?? '';

        return true;
    }

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $exception) {
            error_log("Connection error: " . $exception->getMessage());
            die("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }
}
