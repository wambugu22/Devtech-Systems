<?php
/**
 * DevTech Systems - Settings
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Settings';

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$user = fetch_one($sql, [$_SESSION['user_id']], "i");

// Get system stats
$stats_sql = "SELECT 
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM sales) as total_sales,
    (SELECT COUNT(*) FROM purchases) as total_purchases,
    (SELECT COUNT(*) FROM suppliers) as total_suppliers,
    (SELECT COUNT(*) FROM users) as total_users";
$stats = fetch_one($stats_sql);
?>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>

<div style="display: grid; grid-template-columns: 250px 1fr; gap: 20px;">
    
    <!-- Settings Menu -->
    <div class="card">
        <div style="padding: 20px;">
            <h3 style="margin-bottom: 20px;">Settings Menu</h3>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                <a href="#general" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-cog"></i> General
                </a>
                <a href="#account" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-user"></i> Account
                </a>
                <a href="#system" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-server"></i> System Info
                </a>
                <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" class="btn btn-secondary" style="justify-content: flex-start;">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>
        </div>
    </div>
    
    <!-- Settings Content -->
    <div>
        
        <!-- General Settings -->
        <div class="card" id="general">
            <div class="card-header">
                <h2 class="card-title">General Settings</h2>
            </div>
            
            <div style="padding: 20px;">
                <div style="margin-bottom: 20px;">
                    <h4>Business Name</h4>
                    <p style="color: var(--text-light);">DevTech Systems</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4>Default Currency</h4>
                    <p style="color: var(--text-light);">KES (Kenyan Shilling)</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4>Timezone</h4>
                    <p style="color: var(--text-light);">Africa/Nairobi (EAT)</p>
                </div>
                
                <div style="margin-bottom: 20px;">
                    <h4>Low Stock Alert Level</h4>
                    <p style="color: var(--text-light);">Alerts are sent when product quantity reaches or falls below the reorder level</p>
                </div>
            </div>
        </div>
        
        <!-- Account Settings -->
        <div class="card" id="account">
            <div class="card-header">
                <h2 class="card-title">Account Information</h2>
            </div>
            
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="margin-bottom: 5px;">Full Name</h4>
                        <p style="color: var(--text-light);"><?php echo htmlspecialchars($user['full_name']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Username</h4>
                        <p style="color: var(--text-light);">@<?php echo htmlspecialchars($user['username']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Email</h4>
                        <p style="color: var(--text-light);"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Phone</h4>
                        <p style="color: var(--text-light);"><?php echo $user['phone'] ? htmlspecialchars($user['phone']) : 'Not set'; ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Role</h4>
                        <p style="color: var(--text-light);"><?php echo ucfirst($user['role']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">2FA Status</h4>
                        <p style="color: var(--text-light);">
                            <?php echo $user['two_fa_enabled'] == 1 ? 'Enabled' : 'Disabled'; ?>
                        </p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Member Since</h4>
                        <p style="color: var(--text-light);"><?php echo format_date($user['created_at']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Last Login</h4>
                        <p style="color: var(--text-light);"><?php echo $user['last_login'] ? format_datetime($user['last_login']) : 'Never'; ?></p>
                    </div>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Profile
                    </a>
                </div>
            </div>
        </div>
        
        <!-- System Information -->
        <div class="card" id="system">
            <div class="card-header">
                <h2 class="card-title">System Information</h2>
            </div>
            
            <div style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
                    <div style="text-align: center; padding: 20px; background: var(--light); border-radius: 8px;">
                        <h3 style="font-size: 32px; color: var(--primary); margin-bottom: 5px;">
                            <?php echo number_format($stats['total_products']); ?>
                        </h3>
                        <p style="color: var(--text-light);">Products</p>
                    </div>
                    
                    <div style="text-align: center; padding: 20px; background: var(--light); border-radius: 8px;">
                        <h3 style="font-size: 32px; color: var(--success); margin-bottom: 5px;">
                            <?php echo number_format($stats['total_sales']); ?>
                        </h3>
                        <p style="color: var(--text-light);">Sales</p>
                    </div>
                    
                    <div style="text-align: center; padding: 20px; background: var(--light); border-radius: 8px;">
                        <h3 style="font-size: 32px; color: var(--warning); margin-bottom: 5px;">
                            <?php echo number_format($stats['total_purchases']); ?>
                        </h3>
                        <p style="color: var(--text-light);">Purchases</p>
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div>
                        <h4 style="margin-bottom: 5px;">System Version</h4>
                        <p style="color: var(--text-light);">DevTech Systems v1.0.0</p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Database</h4>
                        <p style="color: var(--text-light);">MySQL (<?php echo DB_NAME; ?>)</p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">PHP Version</h4>
                        <p style="color: var(--text-light);"><?php echo phpversion(); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Server</h4>
                        <p style="color: var(--text-light);"><?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Total Suppliers</h4>
                        <p style="color: var(--text-light);"><?php echo number_format($stats['total_suppliers']); ?></p>
                    </div>
                    
                    <div>
                        <h4 style="margin-bottom: 5px;">Total Users</h4>
                        <p style="color: var(--text-light);"><?php echo number_format($stats['total_users']); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- About -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">About DevTech Systems</h2>
            </div>
            
            <div style="padding: 20px;">
                <p style="margin-bottom: 15px;">
                    DevTech Systems is a comprehensive inventory management solution designed to help businesses 
                    efficiently manage their stock, sales, purchases, and finances.
                </p>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px;">Key Features:</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Product & Inventory Management</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Sales & Purchase Tracking</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Expense Management</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Supplier Management</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Comprehensive Reports</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Profit & Loss Analysis</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Low Stock Alerts</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Two-Factor Authentication</li>
                    <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--success); margin-right: 10px;"></i> Mobile Responsive Design</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>