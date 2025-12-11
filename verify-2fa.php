<?php
/**
 * DevTech Systems - 2FA Verification
 */

require_once __DIR__ . '/../config/config.php';

// Check if temp user ID is set
if (!isset($_SESSION['temp_user_id'])) {
    header('Location: login.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = sanitize_input($_POST['code']);
    $user_id = $_SESSION['temp_user_id'];
    
    if (verify_2fa_code($user_id, $code)) {
        // Get user details
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $user = fetch_one($sql, [$user_id], "i");
        
        if ($user) {
            // Login successful
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['last_activity'] = time();
            unset($_SESSION['temp_user_id']);
            
            // Update last login
            $sql = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            execute_query($sql, [$user['user_id']], "i");
            
            // Log activity
            log_activity('User logged in with 2FA');
            
            header('Location: ' . SITE_URL . '/dashboard/');
            exit();
        }
    } else {
        $error = 'Invalid or expired verification code';
    }
}

$page_title = '2FA Verification';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - DevTech Systems</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        
        .auth-logo {
            font-size: 64px;
            color: var(--primary);
            margin-bottom: 20px;
        }
        
        .auth-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
        }
        
        .auth-subtitle {
            color: var(--text-light);
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .code-input {
            font-size: 32px;
            text-align: center;
            letter-spacing: 10px;
            padding: 15px;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">
                <i class="fas fa-shield-alt"></i>
            </div>
            <h1 class="auth-title">Two-Factor Authentication</h1>
            <p class="auth-subtitle">Enter the 6-digit code sent to your email</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <input type="text" name="code" class="form-control code-input" required 
                           maxlength="6" pattern="[0-9]{6}" placeholder="000000"
                           autocomplete="off" autofocus>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-check"></i>
                    Verify Code
                </button>
            </form>
            
            <div style="margin-top: 20px;">
                <a href="login.php" style="color: var(--text-light); text-decoration: none; font-size: 14px;">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</body>
</html>