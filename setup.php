<?php
/**
 * DevTech Systems - Installer
 */
session_start();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $host = $_POST['host'] ?? 'localhost';
    $user = $_POST['user'] ?? 'root';
    $pass = $_POST['pass'] ?? '';
    $dbname = $_POST['dbname'] ?? 'devtech_inventory';
    $site_url = $_POST['site_url'] ?? 'http://localhost/devtech-systems';
    
    // Admin credentials
    $admin_name = $_POST['admin_name'] ?? 'System Admin';
    $admin_email = $_POST['admin_email'] ?? 'admin@devtech.com';
    $admin_pass = $_POST['admin_pass'] ?? 'admin123';

    // 1. Try connecting to MySQL
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        $message = "Connection failed: " . $conn->connect_error;
        $messageType = "danger";
    } else {
        // 2. Create Database
        $sql = "CREATE DATABASE IF NOT EXISTS `$dbname`";
        if ($conn->query($sql)) {
            $conn->select_db($dbname);
            
            // 3. Create Tables
            $tables_sql = [
                // Users
                "CREATE TABLE IF NOT EXISTS `users` (
                    `user_id` int(11) NOT NULL AUTO_INCREMENT,
                    `full_name` varchar(100) NOT NULL,
                    `email` varchar(100) NOT NULL UNIQUE,
                    `password` varchar(255) NOT NULL,
                    `role` enum('admin','manager','staff') DEFAULT 'staff',
                    `status` enum('active','inactive') DEFAULT 'active',
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`user_id`)
                )",
                
                // Suppliers
                "CREATE TABLE IF NOT EXISTS `suppliers` (
                    `supplier_id` int(11) NOT NULL AUTO_INCREMENT,
                    `supplier_name` varchar(100) NOT NULL,
                    `contact_person` varchar(100),
                    `email` varchar(100),
                    `phone` varchar(20),
                    `address` text,
                    `total_purchases` decimal(10,2) DEFAULT 0.00,
                    `outstanding_balance` decimal(10,2) DEFAULT 0.00,
                    `status` enum('active','inactive') DEFAULT 'active',
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`supplier_id`)
                )",

                // Products
                "CREATE TABLE IF NOT EXISTS `products` (
                    `product_id` int(11) NOT NULL AUTO_INCREMENT,
                    `product_name` varchar(100) NOT NULL,
                    `product_code` varchar(50) NOT NULL UNIQUE,
                    `description` text,
                    `category` varchar(50),
                    `quantity` int(11) DEFAULT 0,
                    `unit_price` decimal(10,2) NOT NULL,
                    `cost_price` decimal(10,2) NOT NULL,
                    `reorder_level` int(11) DEFAULT 10,
                    `status` enum('active','discontinued','archived') DEFAULT 'active',
                    `image` varchar(255),
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`product_id`)
                )",

                // Sales
                "CREATE TABLE IF NOT EXISTS `sales` (
                    `sale_id` int(11) NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `quantity` int(11) NOT NULL,
                    `unit_price` decimal(10,2) NOT NULL,
                    `total_amount` decimal(10,2) NOT NULL,
                    `profit` decimal(10,2) NOT NULL,
                    `payment_method` varchar(50) DEFAULT 'cash',
                    `customer_name` varchar(100),
                    `customer_phone` varchar(20),
                    `sale_date` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`sale_id`),
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`),
                    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
                )",

                // Purchases
                "CREATE TABLE IF NOT EXISTS `purchases` (
                    `purchase_id` int(11) NOT NULL AUTO_INCREMENT,
                    `supplier_id` int(11) DEFAULT 0,
                    `product_id` int(11) NOT NULL,
                    `user_id` int(11) NOT NULL,
                    `quantity` int(11) NOT NULL,
                    `unit_cost` decimal(10,2) NOT NULL,
                    `total_cost` decimal(10,2) NOT NULL,
                    `payment_status` enum('paid','pending','partial') DEFAULT 'pending',
                    `payment_method` varchar(50),
                    `notes` text,
                    `purchase_date` datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`purchase_id`),
                    FOREIGN KEY (`product_id`) REFERENCES `products`(`product_id`)
                )",

                // Expenses
                "CREATE TABLE IF NOT EXISTS `expenses` (
                    `expense_id` int(11) NOT NULL AUTO_INCREMENT,
                    `expense_category` varchar(50) NOT NULL,
                    `amount` decimal(10,2) NOT NULL,
                    `description` text,
                    `user_id` int(11) DEFAULT 0,
                    `expense_date` date NOT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`expense_id`)
                )",

                // Stock Movements
                "CREATE TABLE IF NOT EXISTS `stock_movements` (
                    `movement_id` int(11) NOT NULL AUTO_INCREMENT,
                    `product_id` int(11) NOT NULL,
                    `movement_type` enum('in','out','adjustment') NOT NULL,
                    `quantity` int(11) NOT NULL,
                    `reference_type` varchar(50),
                    `reference_id` int(11),
                    `user_id` int(11) NOT NULL,
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`movement_id`)
                )",
                
                // Activity Logs
                "CREATE TABLE IF NOT EXISTS `activity_logs` (
                    `log_id` int(11) NOT NULL AUTO_INCREMENT,
                    `user_id` int(11) DEFAULT NULL,
                    `action` text NOT NULL,
                    `table_name` varchar(50),
                    `record_id` int(11),
                    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`log_id`)
                )"
            ];

            $errors = [];
            foreach ($tables_sql as $query) {
                if (!$conn->query($query)) {
                    $errors[] = $conn->error;
                }
            }

            if (empty($errors)) {
                // 4. Create Admin User
                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $check_admin = $conn->query("SELECT * FROM users WHERE email = '$admin_email'");
                if ($check_admin->num_rows == 0) {
                    $admin_sql = "INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'admin')";
                    $stmt = $conn->prepare($admin_sql);
                    $stmt->bind_param("sss", $admin_name, $admin_email, $hashed_pass);
                    $stmt->execute();
                }

                // 5. Write Config File
                $config_content = "<?php
/**
 * DevTech Systems - Configuration File
 */

// Enable detailed MySQLi error reporting during development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Database connection
\$host = '$host';
\$dbname = '$dbname';
\$username = '$user';
\$password = '$pass';

\$conn = new mysqli(\$host, \$username, \$password, \$dbname);

// Connection check
if (\$conn->connect_error) {
    die('Database connection failed: ' . \$conn->connect_error);
}

// Define site URL
define('SITE_URL', '$site_url');

/**
 * Fetch a single row from the database
 */
function fetch_one(\$sql, \$params = [], \$types = '') {
    global \$conn;
    \$stmt = \$conn->prepare(\$sql);
    if (!\$stmt) {
        error_log(\"fetch_one prepare failed: \" . \$conn->error);
        return false;
    }

    if (!empty(\$params)) {
        \$stmt->bind_param(\$types, ...\$params);
    }

    \$stmt->execute();
    \$result = \$stmt->get_result();
    \$row = \$result->fetch_assoc();
    \$stmt->close();
    return \$row;
}

/**
 * Fetch multiple rows
 */
function fetch_all(\$sql, \$params = [], \$types = '') {
    global \$conn;
    \$stmt = \$conn->prepare(\$sql);
    if (!\$stmt) {
        error_log(\"fetch_all prepare failed: \" . \$conn->error);
        return [];
    }

    if (!empty(\$params)) {
        \$stmt->bind_param(\$types, ...\$params);
    }

    \$stmt->execute();
    \$result = \$stmt->get_result();
    \$rows = \$result->fetch_all(MYSQLI_ASSOC);
    \$stmt->close();
    return \$rows;
}

/**
 * Execute a query (INSERT, UPDATE, DELETE)
 */
function execute_query(\$sql, \$params = [], \$types = '') {
    global \$conn;
    \$stmt = \$conn->prepare(\$sql);
    if (!\$stmt) {
        error_log(\"execute_query prepare failed: \" . \$conn->error);
        return false;
    }

    if (!empty(\$params)) {
        \$stmt->bind_param(\$types, ...\$params);
    }

    \$success = \$stmt->execute();
    \$stmt->close();
    return \$success;
}

/**
 * Log user activity
 * Supports both log_activity(\$msg, \$userId) and log_activity(\$action, \$table, \$id) signatures
 */
function log_activity(\$arg1, \$arg2 = null, \$arg3 = null) {
    global \$conn;
    \$user_id = \$_SESSION['user_id'] ?? null;
    
    // Determine signature
    if (\$arg3 !== null || (\$arg2 !== null && !is_numeric(\$arg2) && \$arg2 !== \$user_id)) {
        // Old signature: log_activity(\$message, \$table_name, \$record_id)
        \$action = \$arg1;
        \$table_name = \$arg2;
        \$record_id = \$arg3;
        
        \$sql = \"INSERT INTO activity_logs (user_id, action, table_name, record_id, created_at) VALUES (?, ?, ?, ?, NOW())\";
        \$stmt = \$conn->prepare(\$sql);
        if (\$stmt) {
            \$stmt->bind_param(\"issi\", \$user_id, \$action, \$table_name, \$record_id);
            \$stmt->execute();
            \$stmt->close();
        }
    } else {
        // New/Simple signature: log_activity(\$message, \$userId) OR log_activity(\$message)
        // Map to old schema: action=\$message, table_name=null, record_id=null
        \$action = \$arg1;
        // User ID override if provided
        if (\$arg2 !== null && is_numeric(\$arg2)) {
            \$user_id = \$arg2;
        }

        \$sql = \"INSERT INTO activity_logs (user_id, action, created_at) VALUES (?, ?, NOW())\";
        \$stmt = \$conn->prepare(\$sql);
        if (\$stmt) {
            \$stmt->bind_param(\"is\", \$user_id, \$action);
            \$stmt->execute();
            \$stmt->close();
        }
    }
}
?>";
                
                // Write config file to ../config/config.php
                file_put_contents(__DIR__ . '/../config/config.php', $config_content);
                
                $message = "Installation successful! <a href='../index.php'>Login here</a>";
                $messageType = "success";
            } else {
                $message = "Database errors: " . implode(", ", $errors);
                $messageType = "danger";
            }
        } else {
            $message = "Could not create database: " . $conn->error;
            $messageType = "danger";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install DevTech Systems</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <style>
        body { background: #f8f9fa; padding-top: 50px; }
        .install-card { max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); background: white; }
    </style>
</head>
<body>
    <div class="container">
        <div class="install-card">
            <h2 class="text-center mb-4">DevTech Systems Installer</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <?php if ($messageType !== 'success'): ?>
            <form method="POST">
                <h5 class="mb-3">Database Settings</h5>
                <div class="mb-3">
                    <label>Database Host</label>
                    <input type="text" name="host" class="form-control" value="localhost" required>
                </div>
                <div class="mb-3">
                    <label>Database User</label>
                    <input type="text" name="user" class="form-control" value="root" required>
                </div>
                <div class="mb-3">
                    <label>Database Password</label>
                    <input type="password" name="pass" class="form-control">
                </div>
                <div class="mb-3">
                    <label>Database Name</label>
                    <input type="text" name="dbname" class="form-control" value="devtech_inventory" required>
                </div>
                <div class="mb-3">
                    <label>Site URL</label>
                    <input type="text" name="site_url" class="form-control" value="http://localhost/devtech-systems" required>
                </div>

                <h5 class="mb-3 mt-4">Admin Account</h5>
                <div class="mb-3">
                    <label>Full Name</label>
                    <input type="text" name="admin_name" class="form-control" value="System Admin" required>
                </div>
                <div class="mb-3">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" class="form-control" value="admin@devtech.com" required>
                </div>
                <div class="mb-3">
                    <label>Admin Password</label>
                    <input type="password" name="admin_pass" class="form-control" value="admin123" required>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">Install System</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
