<?php
// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Environment configuration
$env = $_ENV['APP_ENV'] ?? 'development';

// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'thrive_cafe_db');

// Application Configuration
define('APP_NAME', 'Thrive Cafe - Smart POS System');
define('APP_VERSION', '2.0.0');
define('APP_ENV', $env);
define('TIMEZONE', $_ENV['TIMEZONE'] ?? 'Asia/Kolkata');
define('BASE_URL', $_ENV['BASE_URL'] ?? 'http://localhost/Thrive');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('LOG_PATH', __DIR__ . '/../logs/');

// Security Configuration
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'your-super-secret-jwt-key-change-in-production');
define('SESSION_LIFETIME', 3600); // 1 hour
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes

// API Configuration
define('API_RATE_LIMIT', 100); // requests per minute
define('API_VERSION', 'v1');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Error reporting based on environment
if (APP_ENV === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', LOG_PATH . 'php_errors.log');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Database Connection Class with enhanced features
class Database {
    private static $instance = null;
    private $connection = null;
    private $transactionLevel = 0;

    private function __construct() {
        try {
            // Check if PDO and MySQL PDO driver are available
            if (!extension_loaded('pdo')) {
                throw new PDOException('PDO extension is not loaded');
            }
            if (!extension_loaded('pdo_mysql')) {
                throw new PDOException('PDO MySQL driver is not loaded');
            }

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false
            ];

            // Add MySQL specific options only if available
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci";
            }

            // SSL configuration for production
            if (APP_ENV === 'production' && isset($_ENV['DB_SSL_CA'])) {
                if (defined('PDO::MYSQL_ATTR_SSL_CA')) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $_ENV['DB_SSL_CA'];
                }
                if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }

            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                $options
            );

            // Set SQL mode for better data integrity
            $this->connection->exec("SET sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'");
            
        } catch (PDOException $e) {
            logError("Database Connection Error: " . $e->getMessage());
            if (APP_ENV === 'development') {
                die("Database connection failed: " . $e->getMessage());
            } else {
                die("Database connection failed. Please try again later.");
            }
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function beginTransaction() {
        if ($this->transactionLevel === 0) {
            $this->connection->beginTransaction();
        }
        $this->transactionLevel++;
        return $this;
    }

    public function commit() {
        if ($this->transactionLevel === 1) {
            $this->connection->commit();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        return $this;
    }

    public function rollback() {
        if ($this->transactionLevel === 1) {
            $this->connection->rollBack();
        }
        $this->transactionLevel = max(0, $this->transactionLevel - 1);
        return $this;
    }

    public function inTransaction() {
        return $this->transactionLevel > 0;
    }
}

// Enhanced Response Helper Class
class Response {
    public static function json($success, $data = null, $message = '', $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json');
        header('Cache-Control: no-cache, must-revalidate');
        
        $response = [
            'success' => $success,
            'data' => $data,
            'message' => $message,
            'timestamp' => date('c'),
            'version' => APP_VERSION
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function success($data = null, $message = 'Operation successful', $code = 200) {
        self::json(true, $data, $message, $code);
    }

    public static function error($message = 'An error occurred', $data = null, $code = 400) {
        logError($message, ['data' => $data, 'code' => $code]);
        self::json(false, $data, $message, $code);
    }

    public static function unauthorized($message = 'Unauthorized access') {
        self::json(false, null, $message, 401);
    }

    public static function forbidden($message = 'Access forbidden') {
        self::json(false, null, $message, 403);
    }

    public static function notFound($message = 'Resource not found') {
        self::json(false, null, $message, 404);
    }

    public static function serverError($message = 'Internal server error') {
        self::json(false, null, $message, 500);
    }
}

// Enhanced Validation Helper Class
class Validator {
    public static function required($value) {
        return !empty(trim($value));
    }

    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function phone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^[0-9]{10,15}$/', $phone);
    }

    public static function decimal($value, $min = 0, $max = null) {
        if (!is_numeric($value)) return false;
        $value = (float)$value;
        if ($value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    public static function integer($value, $min = null, $max = null) {
        if (filter_var($value, FILTER_VALIDATE_INT) === false) return false;
        $value = (int)$value;
        if ($min !== null && $value < $min) return false;
        if ($max !== null && $value > $max) return false;
        return true;
    }

    public static function date($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function datetime($datetime) {
        $d = DateTime::createFromFormat('Y-m-d H:i:s', $datetime);
        return $d && $d->format('Y-m-d H:i:s') === $datetime;
    }

    public static function time($time) {
        $t = DateTime::createFromFormat('H:i', $time);
        return $t && $t->format('H:i') === $time;
    }

    public static function length($value, $min = 0, $max = null) {
        $length = strlen(trim($value));
        if ($length < $min) return false;
        if ($max !== null && $length > $max) return false;
        return true;
    }

    public static function password($password) {
        return strlen($password) >= PASSWORD_MIN_LENGTH && 
               preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $password);
    }

    public static function orderStatus($status) {
        $validStatuses = ['pending', 'confirmed', 'preparing', 'ready', 'served', 'completed', 'cancelled'];
        return in_array($status, $validStatuses);
    }

    public static function paymentMethod($method) {
        $validMethods = ['cash', 'card', 'upi', 'wallet', 'online'];
        return in_array($method, $validMethods);
    }
}

// Authentication Class
class Auth {
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    public static function generateToken($userId, $role) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $userId,
            'role' => $role,
            'iat' => time(),
            'exp' => time() + SESSION_LIFETIME
        ]);
        
        $headerEncoded = base64url_encode($header);
        $payloadEncoded = base64url_encode($payload);
        
        $signature = hash_hmac('sha256', $headerEncoded . "." . $payloadEncoded, JWT_SECRET, true);
        $signatureEncoded = base64url_encode($signature);
        
        return $headerEncoded . "." . $payloadEncoded . "." . $signatureEncoded;
    }

    public static function verifyToken($token) {
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 3) return false;

        $header = base64url_decode($tokenParts[0]);
        $payload = base64url_decode($tokenParts[1]);
        $signatureProvided = $tokenParts[2];

        $expectedSignature = base64url_encode(hash_hmac('sha256', $tokenParts[0] . "." . $tokenParts[1], JWT_SECRET, true));

        if (!hash_equals($expectedSignature, $signatureProvided)) return false;

        $payloadData = json_decode($payload, true);
        if (!$payloadData || $payloadData['exp'] < time()) return false;

        return $payloadData;
    }
}

// Utility Functions
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatCurrency($amount, $symbol = '₹') {
    return $symbol . number_format((float)$amount, 2, '.', ',');
}

function generateOrderNumber($prefix = 'TC') {
    return $prefix . date('ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function generateSKU($category, $name) {
    $categoryCode = strtoupper(substr($category, 0, 2));
    $nameCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $name), 0, 4));
    return $categoryCode . $nameCode . rand(100, 999);
}

function logError($message, $context = []) {
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('c'),
        'level' => 'ERROR',
        'message' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    
    $logFile = LOG_PATH . 'app_' . date('Y-m-d') . '.log';
    error_log(json_encode($logEntry) . PHP_EOL, 3, $logFile);
}

function logActivity($userId, $action, $details = []) {
    if (!is_dir(LOG_PATH)) {
        mkdir(LOG_PATH, 0755, true);
    }
    
    $logEntry = [
        'timestamp' => date('c'),
        'user_id' => $userId,
        'action' => $action,
        'details' => $details,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ];
    
    $logFile = LOG_PATH . 'activity_' . date('Y-m-d') . '.log';
    error_log(json_encode($logEntry) . PHP_EOL, 3, $logFile);
}

function checkDatabaseConnection() {
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT 1");
        return true;
    } catch (Exception $e) {
        logError("Database health check failed: " . $e->getMessage());
        return false;
    }
}

function getSystemSettings($key = null) {
    static $settings = null;
    
    if ($settings === null) {
        try {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->query("SELECT setting_key, setting_value, setting_type FROM system_settings WHERE is_public = TRUE OR 1=1");
            $settings = [];
            
            while ($row = $stmt->fetch()) {
                $value = $row['setting_value'];
                
                switch ($row['setting_type']) {
                    case 'number':
                        $value = (float)$value;
                        break;
                    case 'boolean':
                        $value = $value === 'true' || $value === '1';
                        break;
                    case 'json':
                        $value = json_decode($value, true);
                        break;
                }
                
                $settings[$row['setting_key']] = $value;
            }
        } catch (Exception $e) {
            logError("Failed to load system settings: " . $e->getMessage());
            $settings = [];
        }
    }
    
    return $key ? ($settings[$key] ?? null) : $settings;
}

function corsHeaders() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}

function rateLimit($identifier, $limit = API_RATE_LIMIT, $window = 60) {
    $key = 'rate_limit_' . md5($identifier);
    $file = sys_get_temp_dir() . '/' . $key;
    
    $current_time = time();
    $requests = [];
    
    if (file_exists($file)) {
        $data = json_decode(file_get_contents($file), true);
        $requests = $data['requests'] ?? [];
    }
    
    // Remove old requests outside the window
    $requests = array_filter($requests, function($timestamp) use ($current_time, $window) {
        return $current_time - $timestamp < $window;
    });
    
    if (count($requests) >= $limit) {
        Response::error('Rate limit exceeded. Please try again later.', null, 429);
    }
    
    $requests[] = $current_time;
    file_put_contents($file, json_encode(['requests' => $requests]));
}

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

// Initialize upload directories
if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
    mkdir(UPLOAD_PATH . 'products/', 0755, true);
    mkdir(UPLOAD_PATH . 'receipts/', 0755, true);
}

// Set CORS headers for API requests
if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') !== false) {
    corsHeaders();
}
?>
