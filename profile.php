<?php
/**
 * DevTech Systems - User Profile
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Profile';
$error = '';
$success = '';

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$user = fetch_one($sql, [$_SESSION['user_id']], "i");

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    
    if (empty($full_name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        // Check if email already exists for another user
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $existing = fetch_one($sql, [$email, $_SESSION['user_id']], "si");
        
        if ($existing) {
            $error = 'Email already in use by another account';
        } else {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
            if (execute_query($sql, [$full_name, $email, $phone, $_SESSION['user_id']], "sssi")) {
                $_SESSION['full_name'] = $full_name;
                $_SESSION['email'] = $email;
                $success = 'Profile updated successfully';
                log_activity('Updated profile');
                
                // Refresh user data
                $sql = "SELECT * FROM users WHERE user_id = ?";
                $user = fetch_one($sql, [$_SESSION['user_id']], "i");
            } else {
                $error = 'Failed to update profile';
            }
        }
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($current_password) || empty($new_password)) {
        $error = 'All password fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match';
    } elseif (strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect';
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET password = ? WHERE user_id = ?";
        if (execute_query($sql, [$hashed, $_SESSION['user_id']], "si")) {
            $success = 'Password changed successfully';
            log_activity('Changed password');
        } else {
            $error = 'Failed to change password';
        }
    }
}

// Toggle 2FA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_2fa'])) {
    $new_status = $user['two_fa_enabled'] == 1 ? 0 : 1;
    $sql = "UPDATE users SET two_fa_enabled = ? WHERE user_id = ?";
    if (execute_query($sql, [$new_status, $_SESSION['user_id']], "ii")) {
        $success = $new_status == 1 ? '2FA enabled successfully' : '2FA disabled successfully';
        log_activity($new_status == 1 ? 'Enabled 2FA' : 'Disabled 2FA');
        
        // Refresh user data
        $sql = "SELECT * FROM users WHERE user_id = ?";
        $user = fetch_one($sql, [$_SESSION['user_id']], "i");
    }
}

// Delete account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $password = $_POST['confirm_delete_password'];
    
    if (password_verify($password, $user['password'])) {
        $sql = "UPDATE users SET status = 'inactive' WHERE user_id = ?";
        if (execute_query($sql, [$_SESSION['user_id']], "i")) {
            log_activity('Account deleted');
            session_destroy();
            header('Location: ' . SITE_URL . '/auth/login.php');
            exit();
        }
    } else {
        $error = 'Incorrect password';
    }
}
?>

<div class="page-header">
    <h1 class="page-title">My Profile</h1>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px;">
    
    <!-- Profile Card -->
    <div class="card">
        <div style="text-align: center; padding: 20px;">
            <div style="width: 120px; height: 120px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; color: white; font-size: 48px;">
                <i class="fas fa-user"></i>
            </div>
            <h2 style="margin-bottom: 5px;"><?php echo htmlspecialchars($user['full_name']); ?></h2>
            <p style="color: var(--text-light); margin-bottom: 10px;">@<?php echo htmlspecialchars($user['username']); ?></p>
            <span class="badge-status badge-primary"><?php echo ucfirst($user['role']); ?></span>
        </div>
        
        <div style="padding: 20px; border-top: 1px solid var(--border);">
            <div style="margin-bottom: 15px;">
                <small style="color: var(--text-light);">Email</small>
                <p style="margin: 5px 0;"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <div style="margin-bottom: 15px;">
                <small style="color: var(--text-light);">Phone</small>
                <p style="margin: 5px 0;"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not set'; ?></p>
            </div>
            <div style="margin-bottom: 15px;">
                <small style="color: var(--text-light);">Member Since</small>
                <p style="margin: 5px 0;"><?php echo format_date($user['created_at']); ?></p>
            </div>
            <div>
                <small style="color: var(--text-light);">Last Login</small>
                <p style="margin: 5px 0;"><?php echo $user['last_login'] ? format_datetime($user['last_login']) : 'Never'; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Settings -->
    <div>
        <!-- Update Profile -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Edit Profile</h2>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="tel" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($user['phone']); ?>">
                </div>
                
                <button type="submit" name="update_profile" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Profile
                </button>
            </form>
        </div>
        
        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Change Password</h2>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" name="change_password" class="btn btn-primary">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </form>
        </div>
        
        <!-- Security Settings -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Security Settings</h2>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0;">
                <div>
                    <h3 style="margin-bottom: 5px;">Two-Factor Authentication</h3>
                    <p style="color: var(--text-light); font-size: 14px; margin: 0;">
                        <?php echo $user['two_fa_enabled'] == 1 ? 'Enabled - Your account is protected' : 'Disabled - Enable for extra security'; ?>
                    </p>
                </div>
                <form method="POST" action="" style="margin: 0;">
                    <button type="submit" name="toggle_2fa" class="btn <?php echo $user['two_fa_enabled'] == 1 ? 'btn-danger' : 'btn-success'; ?>">
                        <?php echo $user['two_fa_enabled'] == 1 ? 'Disable 2FA' : 'Enable 2FA'; ?>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Danger Zone -->
        <div class="card" style="border: 2px solid var(--danger);">
            <div class="card-header" style="background: var(--danger); color: white;">
                <h2 class="card-title" style="color: white;">Danger Zone</h2>
            </div>
            
            <div style="padding: 20px;">
                <h3 style="margin-bottom: 10px;">Delete Account</h3>
                <p style="color: var(--text-light); margin-bottom: 20px;">
                    Once you delete your account, there is no going back. Please be certain.
                </p>
                
                <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteModal').style.display='flex'">
                    <i class="fas fa-trash"></i> Delete My Account
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 30px; max-width: 500px; width: 90%;">
        <h2 style="margin-bottom: 15px; color: var(--danger);">
            <i class="fas fa-exclamation-triangle"></i> Delete Account
        </h2>
        <p style="margin-bottom: 20px;">This action cannot be undone. All your data will be permanently deleted.</p>
        
        <form method="POST" action="">
            <div class="form-group">
                <label class="form-label">Enter your password to confirm</label>
                <input type="password" name="confirm_delete_password" class="form-control" required>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('deleteModal').style.display='none'">
                    Cancel
                </button>
                <button type="submit" name="delete_account" class="btn btn-danger">
                    Yes, Delete My Account
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>