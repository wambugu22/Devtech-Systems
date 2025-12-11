<?php
/**
 * DevTech Systems - Registration Page
 */

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include dependencies
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $username = sanitize_input($_POST['username'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $full_name = sanitize_input($_POST['full_name'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        // Check availability
        $sql = "SELECT user_id FROM users WHERE username = ? OR email = ?";
        $existing = fetch_one($sql, [$username, $email], "ss");

        if ($existing) {
            $error = 'Username or email already exists';
        } else {
            // Register user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'user', NOW())";
            $result = execute_query($sql, [$username, $email, $hashed_password, $full_name, $phone], "sssss");

            if ($result) {
                // Log activity
                // Assuming we can get the new ID. execute_query in config returns true/false, not ID.
                // We'd need insert_id. But for now, we'll just log without ID or fetch it.
                $newUser = fetch_one("SELECT user_id FROM users WHERE email = ?", [$email], "s");
                if ($newUser) {
                    log_activity("New user registered: {$username}", $newUser['user_id']);
                }
                
                header('Location: login.php?registered=1');
                exit();
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}

$page_title = 'Register';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($page_title); ?> - DevTech Systems</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(-45deg, #ff9a9e, #fad0c4, #fbc2eb, #a6c1ee);
      background-size: 400% 400%;
      animation: gradientBG 15s ease infinite;
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      color: #fff;
    }
    @keyframes gradientBG {
      0% {background-position: 0% 50%;}
      50% {background-position: 100% 50%;}
      100% {background-position: 0% 50%;}
    }
    .auth-card {
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
      border-radius: 20px;
      padding: 40px;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.3);
      animation: fadeIn 1s ease;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(20px);}
      to {opacity: 1; transform: translateY(0);}
    }
    .auth-header {text-align: center; margin-bottom: 30px;}
    .auth-logo {font-size: 60px; color: #ffdd00; text-shadow: 0 0 20px #ffdd00;}
    .auth-title {font-size: 28px; font-weight: bold; margin: 10px 0; color: #fff;}
    .auth-subtitle {font-size: 14px; color: #e0e0e0;}
    .form-group {margin-bottom: 20px; position: relative;}
    .form-label {display: block; margin-bottom: 8px; font-weight: 500;}
    .form-control {
      width: 100%; padding: 12px; border-radius: 10px; border: none;
      background: rgba(255,255,255,0.2); color: #fff; font-size: 16px;
      transition: background 0.3s, box-shadow 0.3s;
    }
    .form-control:focus {background: rgba(255,255,255,0.3); box-shadow: 0 0 10px #00c6ff;}
    .btn {
      background: linear-gradient(135deg, #ff512f, #dd2476);
      border: none; padding: 14px; border-radius: 12px;
      font-size: 16px; font-weight: bold; color: #fff;
      cursor: pointer; width: 100%; transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn:hover {transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.4);}
    .alert {padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 500;}
    .alert-danger {background: rgba(255,0,0,0.2); color: #ffdddd; border: 1px solid #ff4444;}
    .auth-footer {text-align: center; margin-top: 20px; font-size: 14px;}
    .auth-link {color: #ffdd00; text-decoration: none; font-weight: bold;}
    .auth-link:hover {text-decoration: underline;}
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo"><i class="fas fa-sun"></i></div>
      <h1 class="auth-title">Create Account</h1>
      <p class="auth-subtitle">Register for DevTech Systems</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="">
      <div class="form-group">
        <label class="form-label">Full Name *</label>
        <input type="text" name="full_name" class="form-control" required placeholder="Enter your full name"
               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Username *</label>
        <input type="text" name="username" class="form-control" required placeholder="Choose a username"
               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="tel" name="phone" class="form-control" placeholder="Enter phone number"
               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Email *</label>
        <input type="email" name="email" class="form-control" required placeholder="Enter your email"
               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" name="password" class="form-control" required placeholder="Create a password">
      </div>
      <div class="form-group">
        <label class="form-label">Confirm Password *</label>
        <input type="password" name="confirm_password" class="form-control" required placeholder="Repeat password">
      </div>
      
      <button type="submit" class="btn">Register</button>
    </form>
    
    <div class="auth-footer">
      <p>Already have an account? <a href="login.php" class="auth-link">Login here</a></p>
    </div>
  </div>
</body>
</html>