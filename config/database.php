<?php
/**
 * Database Configuration
 * 
 * @project BugTracker by GoodStufForDev
 * @description Database connection and helper functions
 */

// Start session for user authentication
session_start();

// Database configuration constants
define('DB_HOST', 'localhost');
define('DB_NAME', 'bug_tracker');
define('DB_USER', 'root');
define('DB_PASS', 'root');

/**
 * PDO Database Connection
 */
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Connection error. Please check your database configuration.");
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to another page
 * @param string $page Target page
 */
function redirect($page) {
    header("Location: $page");
    exit();
}

/**
 * Set flash message in session
 * @param string $message Message content
 * @param string $type Message type (success|error)
 */
function flashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Display and clear flash message
 */
function displayFlash() {
    if (isset($_SESSION['flash_message'])) {
        $type = $_SESSION['flash_type'] ?? 'success';
        $class = $type === 'success' ? 'success' : 'error';
        echo "<div class='alert alert-{$class}'>{$_SESSION['flash_message']}</div>";
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}

/**
 * Get priority label from numeric value
 * @param int $priority Priority value (0, 1, 2)
 * @return string Priority label
 */
function getPriorityLabel($priority) {
    $labels = [
        0 => 'Low',
        1 => 'Standard',
        2 => 'High'
    ];
    return $labels[$priority] ?? 'Standard';
}

/**
 * Get status label from numeric value
 * @param int $status Status value (0, 1, 2)
 * @return string Status label
 */
function getStatusLabel($status) {
    $labels = [
        0 => 'Open',
        1 => 'In Progress',
        2 => 'Closed'
    ];
    return $labels[$status] ?? 'Open';
}

/**
 * Get CSS class for priority badge
 * @param int $priority Priority value
 * @return string CSS class name
 */
function getPriorityClass($priority) {
    $classes = [
        0 => 'low',
        1 => 'standard',
        2 => 'high'
    ];
    return $classes[$priority] ?? 'standard';
}

/**
 * Get CSS class for status badge
 * @param int $status Status value
 * @return string CSS class name
 */
function getStatusClass($status) {
    $classes = [
        0 => 'open',
        1 => 'progress',
        2 => 'closed'
    ];
    return $classes[$status] ?? 'open';
}

/**
 * Sanitize user input
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 * @param string $email Email address
 * @return bool
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
?>