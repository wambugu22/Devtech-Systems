<?php
/**
 * DevTech Inventory - Database Connection & Query Helpers
 */

$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'devtech_inventory';

// Create connection
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

// Check connection
if ($mysqli->connect_errno) {
    die("Database connection failed: " . $mysqli->connect_error);
}

// --- QUERY HELPERS ---

/**
 * Fetch a single row
 */
function fetch_one($sql, $params = [], $types = "") {
    global $mysqli;
    $stmt = $mysqli->prepare($sql);
    if ($params && $types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Fetch multiple rows
 */
function fetch_all($sql, $params = [], $types = "") {
    global $mysqli;
    $stmt = $mysqli->prepare($sql);
    if ($params && $types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Execute a query (INSERT, UPDATE, DELETE)
 */
function execute_query($sql, $params = [], $types = "") {
    global $mysqli;
    $stmt = $mysqli->prepare($sql);
    if ($params && $types) {
        $stmt->bind_param($types, ...$params);
    }
    return $stmt->execute();
}

/**
 * Log activity (optional)
 */
function log_activity($message, $userId = null) {
    global $mysqli;
    $sql = "INSERT INTO activity_logs (user_id, message, created_at) VALUES (?, ?, NOW())";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("is", $userId, $message);
    $stmt->execute();
}
