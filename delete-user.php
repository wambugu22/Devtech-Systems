<?php
/**
 * DevTech Systems - Delete User
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Only admins can delete users
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['flash_message'] = "Access denied. Admin privileges required.";
    $_SESSION['flash_type'] = "danger";
    header('Location: ' . SITE_URL . '/dashboard/');
    exit();
}

$target_user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$current_user_id = $_SESSION['user_id'];

// Validation
if ($target_user_id <= 0) {
    header('Location: users.php');
    exit();
}

if ($target_user_id === $current_user_id) {
    $_SESSION['flash_message'] = "You cannot delete your own account.";
    $_SESSION['flash_type'] = "danger";
    header('Location: users.php');
    exit();
}

// Perform Deletion (with Reassignment)
global $conn;
$conn->begin_transaction();

try {
    // 1. Reassign financial records to the current admin (You)
    // This prevents deleting sales history just because a staff member left.
    execute_query("UPDATE sales SET user_id = ? WHERE user_id = ?", [$current_user_id, $target_user_id], "ii");
    execute_query("UPDATE purchases SET user_id = ? WHERE user_id = ?", [$current_user_id, $target_user_id], "ii");
    execute_query("UPDATE expenses SET user_id = ? WHERE user_id = ?", [$current_user_id, $target_user_id], "ii");
    execute_query("UPDATE stock_movements SET user_id = ? WHERE user_id = ?", [$current_user_id, $target_user_id], "ii");

    // 2. Delete Activity Logs (These are safe to delete as they are just audit trails for that specific user)
    execute_query("DELETE FROM activity_logs WHERE user_id = ?", [$target_user_id], "i");

    // 3. Delete the User
    execute_query("DELETE FROM users WHERE user_id = ?", [$target_user_id], "i");

    $conn->commit();
    
    $_SESSION['flash_message'] = "User deleted successfully. Their records have been reassigned to you.";
    $_SESSION['flash_type'] = "success";
    
    log_activity("Deleted user ID: $target_user_id", $current_user_id);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Delete User Error: " . $e->getMessage());
    $_SESSION['flash_message'] = "Failed to delete user: " . $e->getMessage();
    $_SESSION['flash_type'] = "danger";
}

header('Location: users.php');
exit();
?>
