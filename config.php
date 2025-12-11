<?php
/**
 * DevTech Systems - Configuration File
 */

// Enable detailed MySQLi error reporting during development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection
$host = 'localhost';
$dbname = 'devtech_inventory';
$username = 'root';
$password = '';

$conn = new mysqli($host, $username, $password, $dbname);

// Connection check
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Define site URL
define('SITE_URL', 'http://localhost/devtech-systems');

/**
 * Fetch a single row from the database
 */
function fetch_one($sql, $params = [], $types = '') {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("fetch_one prepare failed: " . $conn->error);
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}

/**
 * Fetch multiple rows
 */
function fetch_all($sql, $params = [], $types = '') {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("fetch_all prepare failed: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

/**
 * Execute a query (INSERT, UPDATE, DELETE)
 */
function execute_query($sql, $params = [], $types = '') {
    global $conn;
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("execute_query prepare failed: " . $conn->error);
        return false;
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

/**
 * Log user activity
 * Supports both log_activity($msg, $userId) and log_activity($action, $table, $id) signatures
 */
function log_activity($arg1, $arg2 = null, $arg3 = null) {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Determine signature
    if ($arg3 !== null || ($arg2 !== null && !is_numeric($arg2) && $arg2 !== $user_id)) {
        // Old signature: log_activity($message, $table_name, $record_id)
        $action = $arg1;
        $table_name = $arg2;
        $record_id = $arg3;
        
        $sql = "INSERT INTO activity_logs (user_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issi", $user_id, $action, $table_name, $record_id);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        // New/Simple signature: log_activity($message, $userId) OR log_activity($message)
        // Map to old schema: action=$message, table_name=null, record_id=null
        $action = $arg1;
        // User ID override if provided
        if ($arg2 !== null && is_numeric($arg2)) {
            $user_id = $arg2;
        }

        $sql = "INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $action);
            $stmt->execute();
            $stmt->close();
        }
    }
}
?>