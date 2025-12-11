<?php
/**
 * DevTech Systems - Reset Users Script
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if username column exists, if not add it
$check_col = $conn->query("SHOW COLUMNS FROM users LIKE 'username'");
if ($check_col->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) AFTER full_name");
}

$conn->begin_transaction();

try {
    // 1. Create the new Admin User (or get ID if acts weird context)
    // We want the final ID to be 1 ideally, but if auto-increment is high, we just insert.
    // Actually, to be clean, let's insert the new admin first so we get an ID.
    
    $username = 'admin';
    $email = 'admin@devtech.com';
    $password = password_hash('admin123', PASSWORD_DEFAULT);
    $full_name = 'System Administrator';
    $role = 'admin';
    
    // Insert new admin
    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, password, role, status) VALUES (?, ?, ?, ?, ?, 'active')");
    $stmt->bind_param("sssss", $full_name, $username, $email, $password, $role);
    $stmt->execute();
    $new_admin_id = $conn->insert_id;
    
    if (!$new_admin_id) {
        throw new Exception("Failed to create new admin user.");
    }
    
    // 2. Reassign ALL records from ALL other users to this new admin
    // We update everything where user_id != new_admin_id
    execute_query("UPDATE sales SET user_id = ? WHERE user_id != ?", [$new_admin_id, $new_admin_id], "ii");
    execute_query("UPDATE purchases SET user_id = ? WHERE user_id != ?", [$new_admin_id, $new_admin_id], "ii");
    execute_query("UPDATE expenses SET user_id = ? WHERE user_id != ?", [$new_admin_id, $new_admin_id], "ii");
    execute_query("UPDATE stock_movements SET user_id = ? WHERE user_id != ?", [$new_admin_id, $new_admin_id], "ii");
    
    // 3. Delete Activity Logs (easier to wipe than reassign for audit reasons)
    execute_query("DELETE FROM activity_logs");
    
    // 4. Delete ALL other users
    execute_query("DELETE FROM users WHERE user_id != ?", [$new_admin_id], "i");
    
    // Optional: Reset Auto Increment if possible (not strictly needed)
    
    $conn->commit();
    echo "<h1>Success!</h1>";
    echo "<p>All users have been deleted.</p>";
    echo "<p>New Admin Created:</p>";
    echo "<ul><li>Username: <strong>admin</strong></li><li>Password: <strong>admin123</strong></li></ul>";
    echo "<p>All previous sales/purchases have been reassigned to this account.</p>";
    echo "<a href='index.php'>Login Now</a>";
    
} catch (Exception $e) {
    $conn->rollback();
    echo "<h1>Error</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
