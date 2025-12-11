<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php'; // Required for is_logged_in if strictly needed, but logic below just destroys session
require_once __DIR__ . '/../includes/functions.php'; // For log_activity

if (isset($_SESSION['user_id'])) {
    // Log logout activity
    if (function_exists('log_activity')) {
        log_activity('User logged out', 'users', $_SESSION['user_id']);
    }
}

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: login.php');
exit();
?>