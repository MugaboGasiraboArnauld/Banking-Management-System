<?php
/**
 * Bank Management System - Configuration
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bank_management_system');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application configuration
define('APP_NAME', 'Bank Management System');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost');

// Session configuration
define('SESSION_TIMEOUT', 3600); // 1 hour

// Security configuration
define('HASH_COST', 12);

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Database connection class
 */
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper functions
 */

// Start secure session
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Check if user is logged in
function isLoggedIn() {
    startSession();
    return isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

// Check user role
function hasRole($role) {
    startSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Generate unique ID
function generateUniqueId($prefix = '') {
    return $prefix . strtoupper(substr(uniqid(), -8)) . date('ymd');
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitize($value);
        }
        return $input;
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Format currency
function formatCurrency($amount, $currency = 'USD') {
    return number_format($amount, 2) . ' ' . $currency;
}

// Format date
function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

// JSON response
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Password hash
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Log activity
function logActivity($conn, $userId, $action, $table = null, $recordId = null, $oldValue = null, $newValue = null) {
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, table_affected, record_id, old_value, new_value, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $action,
        $table,
        $recordId,
        $oldValue,
        $newValue,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}
