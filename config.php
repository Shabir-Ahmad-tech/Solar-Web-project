<?php
/**
 * KABAL SOLAR SYSTEM - DATABASE CONFIGURATION
 * 
 * This file contains database connection settings.
 * IMPORTANT: Update these values according to your local/production setup.
 */

// Prevent direct access
if (!defined('DB_CONFIG')) {
    define('DB_CONFIG', true);
}

// Error reporting
// IMPORTANT: Set ENVIRONMENT to 'production' when deploying to live server
define('ENVIRONMENT', 'development'); // Change to 'production' for live server

if (ENVIRONMENT === 'production') {
    // Production: Hide errors from users, log them instead
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/error_log.txt'); // Ensure this file is writable
} else {
    // Development: Show all errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Database configuration
// IMPORTANT: Update these values for production deployment
if (ENVIRONMENT === 'production') {
    // Production database credentials (UPDATE THESE BEFORE DEPLOYMENT)
    define('DB_HOST', 'localhost'); // Your production database host
    define('DB_NAME', 'kabal_solar'); // Your production database name
    define('DB_USER', 'your_db_username'); // CHANGE THIS
    define('DB_PASS', 'your_secure_password'); // CHANGE THIS - Use strong password
} else {
    // Development database credentials
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'kabal_solar');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}
define('DB_CHARSET', 'utf8mb4');

// Timezone setting
date_default_timezone_set('Asia/Karachi');

// Security settings
// CRITICAL: Generate a new unique key for production using openssl or random.org
// You can generate a new key by running: openssl rand -base64 32
if (ENVIRONMENT === 'production') {
    define('SECURE_KEY', 'CHANGE_THIS_TO_RANDOM_64_CHAR_STRING_FOR_PRODUCTION'); // MUST CHANGE
} else {
    define('SECURE_KEY', 'kss_dev_key_7f8a9b2c3d4e5f6g7h8i9j0k1l2m3n4o'); // Development only
}

/**
 * Get database connection using PDO
 * 
 * @return PDO Database connection object
 * @throws PDOException if connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Log error (in production, log to file instead of displaying)
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Don't expose sensitive error details to users
            throw new PDOException("Database connection failed. Please contact support.");
        }
    }
    
    return $pdo;
}

/**
 * Sanitize input data
 * 
 * @param string $data Input data to sanitize
 * @return string Sanitized data
 */
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email address
 * 
 * @param string $email Email to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate Pakistani phone number
 * 
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validatePhoneNumber($phone) {
    // Remove all non-numeric characters
    $cleanPhone = preg_replace('/\D/', '', $phone);
    
    // Check if it's a valid Pakistani number
    // Should be 10-12 digits (03XXXXXXXXX or 923XXXXXXXXX)
    if (strlen($cleanPhone) >= 10 && strlen($cleanPhone) <= 12) {
        return true;
    }
    
    return false;
}

/**
 * Send JSON response
 * 
 * @param bool $success Success status
 * @param string $message Response message
 * @param array $data Additional data (optional)
 */
function sendJSONResponse($success, $message, $data = []) {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = [
        'success' => $success,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if (!empty($data)) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Log activity to database
 * 
 * @param int $clientId Client ID
 * @param string $activityType Type of activity
 * @param string $description Activity description
 */
function logActivity($clientId, $activityType, $description) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare(
            "INSERT INTO activity_log (client_id, activity_type, description) 
             VALUES (:client_id, :activity_type, :description)"
        );
        
        $stmt->execute([
            ':client_id' => $clientId,
            ':activity_type' => $activityType,
            ':description' => $description
        ]);
    } catch (PDOException $e) {
        error_log("Activity logging error: " . $e->getMessage());
    }
}

/**
 * Simple authentication check for admin pages
 * 
 * @return bool True if authenticated
 */
function isAuthenticated() {
    session_start();
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Rate limiting function (simple implementation)
 * 
 * @param string $identifier Unique identifier (IP, email, etc.)
 * @param int $limit Maximum number of attempts
 * @param int $timeWindow Time window in seconds
 * @return bool True if within limit, false if exceeded
 */
function checkRateLimit($identifier, $limit = 5, $timeWindow = 3600) {
    $cacheFile = sys_get_temp_dir() . '/rate_limit_' . md5($identifier) . '.txt';
    
    $attempts = [];
    if (file_exists($cacheFile)) {
        $attempts = json_decode(file_get_contents($cacheFile), true);
    }
    
    // Remove old attempts
    $currentTime = time();
    $attempts = array_filter($attempts, function($timestamp) use ($currentTime, $timeWindow) {
        return ($currentTime - $timestamp) < $timeWindow;
    });
    
    // Check if limit exceeded
    if (count($attempts) >= $limit) {
        return false;
    }
    
    // Add current attempt
    $attempts[] = $currentTime;
    file_put_contents($cacheFile, json_encode($attempts));
    
    return true;
}
?>
