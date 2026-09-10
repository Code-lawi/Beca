<?php
// ============================================
// SCI-CALC Database Configuration
// Reads environment variables from .env file
// ============================================

// ===== LOAD ENVIRONMENT VARIABLES =====
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    
    if (!file_exists($envFile)) {
        die("❌ ERROR: .env file not found!<br>
             Please create .env file in the root directory.<br>
             Copy .env.example and update your settings.");
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (strpos($value, '"') === 0 || strpos($value, "'") === 0) {
                $value = substr($value, 1, -1);
            }
            
            $env[$key] = $value;
        }
    }
    
    $required = ['DB_HOST', 'DB_USER', 'DB_NAME', 'APP_URL'];
    foreach ($required as $key) {
        if (!isset($env[$key]) || empty($env[$key])) {
            die("❌ ERROR: '$key' is missing in .env file!");
        }
    }
    
    return $env;
}

// Load environment variables
$env = loadEnv();

// ===== DATABASE CONNECTION =====
$db_host = $env['DB_HOST'] ?? 'localhost';
$db_port = $env['DB_PORT'] ?? 3306;  // DEFAULT PORT: 3306
$db_user = $env['DB_USER'] ?? 'root';
$db_pass = $env['DB_PASS'] ?? '';
$db_name = $env['DB_NAME'] ?? 'sci_calc';

// DEBUG: Show connection info (only in development)
if (($env['APP_DEBUG'] ?? 'false') === 'true') {
    echo "<!-- Connection Info: Host=$db_host, Port=$db_port, User=$db_user, Database=$db_name -->\n";
}

// ===== CREATE CONNECTION =====
// First attempt: Try with specified port
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, (int)$db_port);

// If connection fails, try without port (fallback)
if ($conn->connect_error) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
}

// Final check
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8mb4");

// ===== START SESSION =====
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== SET TIMEZONE =====
$timezone = $env['TIMEZONE'] ?? 'Africa/Dar_es_Salaam';
date_default_timezone_set($timezone);

// ===== APPLICATION CONSTANTS =====
define('BASE_URL', rtrim($env['APP_URL'], '/') . '/');
define('ADMIN_URL', BASE_URL . 'admin/');

define('APP_ENV', $env['APP_ENV'] ?? 'production');
define('APP_DEBUG', ($env['APP_DEBUG'] ?? 'false') === 'true');

$root_path = realpath(__DIR__ . '/../');
define('ROOT_PATH', $root_path . '/');
define('UPLOAD_PATH', ROOT_PATH . 'uploads/');
define('PRODUCT_IMG_PATH', UPLOAD_PATH . 'products/');
define('VIDEO_PATH', UPLOAD_PATH . 'videos/');

if (!file_exists(PRODUCT_IMG_PATH)) {
    mkdir(PRODUCT_IMG_PATH, 0777, true);
}
if (!file_exists(VIDEO_PATH)) {
    mkdir(VIDEO_PATH, 0777, true);
}

define('WHATSAPP_NUMBER', $env['WHATSAPP_NUMBER'] ?? '');
define('INSTAGRAM_LINK', $env['INSTAGRAM_LINK'] ?? '');

// ============================================
// DATABASE FUNCTIONS
// ============================================

function getSettings($conn) {
    $sql = "SELECT * FROM settings LIMIT 1";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

function getProducts($conn, $limit = null, $status = 1) {
    $sql = "SELECT * FROM products";
    
    if ($status !== null && $status !== '') {
        $sql .= " WHERE status = " . intval($status);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $conn->query($sql);
    $products = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

function getVideos($conn, $limit = null, $status = 1, $homepage = null) {
    $sql = "SELECT * FROM videos";
    
    $conditions = [];
    if ($status !== null && $status !== '') {
        $conditions[] = "status = " . intval($status);
    }
    if ($homepage !== null && $homepage !== '') {
        $conditions[] = "show_on_homepage = " . intval($homepage);
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    
    $result = $conn->query($sql);
    $videos = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $videos[] = $row;
        }
    }
    return $videos;
}

// ===== ORDER FUNCTIONS =====

function getPendingOrders($conn, $limit = null) {
    $sql = "SELECT * FROM pending_orders ORDER BY created_at DESC";
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    return $orders;
}

function getApprovedOrders($conn, $limit = null) {
    $sql = "SELECT * FROM approved_orders ORDER BY created_at DESC";
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    return $orders;
}

function getRejectedOrders($conn, $limit = null) {
    $sql = "SELECT * FROM rejected_orders ORDER BY created_at DESC";
    if ($limit !== null && $limit > 0) {
        $sql .= " LIMIT " . intval($limit);
    }
    $result = $conn->query($sql);
    $orders = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
    }
    return $orders;
}

function getOrderCounts($conn) {
    $counts = [];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM pending_orders");
    $counts['pending'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM approved_orders");
    $counts['approved'] = $result->fetch_assoc()['count'] ?? 0;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM rejected_orders");
    $counts['rejected'] = $result->fetch_assoc()['count'] ?? 0;
    
    return $counts;
}

function generateOrderNumber() {
    $year = date('Y');
    $prefix = "SC-$year-";
    
    // Get last order number
    global $conn;
    $result = $conn->query("SELECT order_number FROM pending_orders WHERE order_number LIKE '$prefix%' ORDER BY id DESC LIMIT 1");
    if ($result && $result->num_rows > 0) {
        $last = $result->fetch_assoc()['order_number'];
        $num = intval(substr($last, -4)) + 1;
    } else {
        $num = 1;
    }
    
    return $prefix . str_pad($num, 4, '0', STR_PAD_LEFT);
}

// Admin functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function getImageUrl($folder, $filename) {
    if (!empty($filename) && file_exists(UPLOAD_PATH . $folder . '/' . $filename)) {
        return BASE_URL . 'uploads/' . $folder . '/' . $filename;
    }
    return BASE_URL . 'assets/images/default.png';
}

function getVideoUrl($filename) {
    if (!empty($filename) && file_exists(VIDEO_PATH . $filename)) {
        return BASE_URL . 'uploads/videos/' . $filename;
    }
    return '';
}

function debug($data) {
    if (APP_DEBUG) {
        echo '<pre>';
        print_r($data);
        echo '</pre>';
    }
}

function logError($message) {
    $logFile = ROOT_PATH . 'logs/error.log';
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message" . PHP_EOL, FILE_APPEND);
}

// ============================================
// ENVIRONMENT CHECK
// ============================================

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
?>