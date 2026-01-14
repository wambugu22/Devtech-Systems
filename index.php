<?php
/**
 * DevTech Systems - Main Entry Point
 * Redirects to appropriate page based on login status
 */

// Include auth helper
require_once __DIR__ . '/includes/auth.php';

// Check if user is logged in
if (is_logged_in()) {
    header('Location: dashboard/');
} else {
    header('Location: auth/login.php');
}

exit();
?>
