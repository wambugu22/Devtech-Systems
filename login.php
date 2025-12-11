<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = '';
$success = '';

// Check if registered
if (isset($_GET['registered'])) {
    set_flash_message("Registration successful! Please login.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $sql = "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'";
        $user = fetch_one($sql, [$username, $username], "ss");

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();

            execute_query("UPDATE users SET last_login = NOW() WHERE user_id = ?", [$user['user_id']], "i");
            log_activity("User logged in", $user['user_id']);

            header('Location: ' . SITE_URL . '/dashboard/');
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Login - DevTech Systems</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
  margin: 0;
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(-45deg, #43cea2, #185a9d, #ff512f, #dd2476);
  background-size: 400% 400%;
  animation: gradientBG 12s ease infinite;
  display: flex; justify-content: center; align-items: center; min-height: 100vh;
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
  max-width: 400px;
  width: 100%;
  box-shadow: 0 10px 40px rgba(0,0,0,0.3);
  animation: fadeIn 1s ease;
}
@keyframes fadeIn {from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.auth-header {text-align:center;margin-bottom:20px;}
.auth-logo {font-size:50px;color:#00ffcc;text-shadow:0 0 20px #00ffcc;}
.form-group {margin-bottom:20px;position:relative;}
.form-label {color:#fff;margin-bottom:6px;display:block;}
.form-control {
  width:100%;padding:12px;border-radius:10px;border:none;
  background:rgba(255,255,255,0.2);color:#fff;font-size:16px;
}
.form-control:focus {background:rgba(255,255,255,0.3);box-shadow:0 0 10px #00ffcc;}
.btn {
  width:100%;padding:12px;border:none;border-radius:10px;
  background:linear-gradient(135deg,#43cea2,#185a9d);color:#fff;font-weight:bold;cursor:pointer;
}
.btn:hover {transform:translateY(-2px);box-shadow:0 8px 20px rgba(0,0,0,0.4);}
.alert {padding:10px;border-radius:8px;margin-bottom:15px;text-align:center;font-weight:bold;}
.alert-danger {background:rgba(255,0,0,0.3);color:#fff; border: 1px solid #ff4444;}
.alert-success {background:rgba(0,255,0,0.2);color:#fff; border: 1px solid #00ff00;}
.auth-footer {text-align:center;margin-top:15px;color:#fff;}
.auth-link {color:#ffdd00;text-decoration:none;font-weight:bold;}
.auth-link:hover {text-decoration:underline;}
</style>
</head>
<body>
<div class="auth-card">
  <div class="auth-header">
    <div class="auth-logo"><i class="fas fa-moon"></i></div>
    <h2>Login</h2>
  </div>
  
  <?php display_flash_message(); ?>

  <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
  
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Username or Email</label>
      <input type="text" name="username" class="form-control" required>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn"><i class="fas fa-sign-in-alt"></i> Login</button>
  </form>
  <div class="auth-footer">
    <p>Don't have an account? <a href="register.php" class="auth-link">Register here</a></p>
  </div>
</div>
</body>
</html>
