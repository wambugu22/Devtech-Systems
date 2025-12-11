<?php
// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require config and auth if not already loaded (prevent double loading)
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// Enforce login for all pages that include header (except explicitly public ones)
require_login();

$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_name = $_SESSION['full_name'] ?? 'Guest';
$user_role = $_SESSION['role'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DevTech Systems - Inventory Management</title>
    
    <!-- CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>

    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="<?php echo SITE_URL; ?>/dashboard/">
                    <i class="fas fa-cubes"></i>
                    <span>DevTech Systems</span>
                </a>
            </div>



            <div class="nav-menu">
                <div class="nav-actions">
                    <button class="nav-btn" id="themeToggle" title="Toggle Dark Mode">
                        <i class="fas fa-moon"></i>
                    </button>

                    
                    <div class="dropdown">
                        <button class="user-btn">
                            <?php $first_name = explode(' ', trim($user_name))[0]; ?>
                            <div class="notification-icon" style="width:32px;height:32px;background:#e0f2fe;color:#0284c7;">
                                <?php echo substr($first_name, 0, 1); ?>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($first_name); ?></span>
                            <i class="fas fa-chevron-down" style="font-size:12px;"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <div class="dropdown-header">
                                <?php echo htmlspecialchars($user_name); ?>
                                <small><?php echo htmlspecialchars($user_role); ?></small>
                            </div>
                            <a href="<?php echo SITE_URL; ?>/dashboard/profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profile
                            </a>
                            <a href="<?php echo SITE_URL; ?>/dashboard/settings.php" class="dropdown-item">
                                <i class="fas fa-cog"></i> Settings
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="dropdown-item" style="color:var(--danger);">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-menu">
            <div class="menu-section">Main</div>
            
            <a href="<?php echo SITE_URL; ?>/dashboard/" class="menu-item <?php echo $current_page == 'index' ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
            
            <div class="menu-section">Inventory</div>
            
            <a href="<?php echo SITE_URL; ?>/inventory/products.php" class="menu-item <?php echo $current_page == 'products' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i>
                <span>Products</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/inventory/add-product.php" class="menu-item <?php echo $current_page == 'add-product' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Product</span>
            </a>

            <div class="menu-section">Sales</div>

            <a href="<?php echo SITE_URL; ?>/sales/new-sale.php" class="menu-item <?php echo $current_page == 'new-sale' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart"></i>
                <span>New Sale</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/sales/sales-history.php" class="menu-item <?php echo $current_page == 'sales-history' ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i>
                <span>Sales History</span>
            </a>

            <div class="menu-section">Purchases</div>

            <a href="<?php echo SITE_URL; ?>/purchases/new-purchase.php" class="menu-item <?php echo $current_page == 'new-purchase' ? 'active' : ''; ?>">
                <i class="fas fa-shopping-bag"></i>
                <span>New Purchase</span>
            </a>
            <a href="<?php echo SITE_URL; ?>/purchases/purchase-history.php" class="menu-item <?php echo $current_page == 'purchase-history' ? 'active' : ''; ?>">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Purchase History</span>
            </a>

             <div class="menu-section">Finance</div>

            <a href="<?php echo SITE_URL; ?>/expenses/expenses.php" class="menu-item <?php echo $current_page == 'expenses' ? 'active' : ''; ?>">
                <i class="fas fa-money-bill-wave"></i>
                <span>Expenses</span>
            </a>
             <a href="<?php echo SITE_URL; ?>/suppliers/suppliers.php" class="menu-item <?php echo $current_page == 'suppliers' ? 'active' : ''; ?>">
                <i class="fas fa-truck"></i>
                <span>Suppliers</span>
            </a>

            <div class="menu-section">System</div>
            
            <?php if ($user_role === 'admin'): ?>
            <a href="<?php echo SITE_URL; ?>/users/users.php" class="menu-item <?php echo $current_page == 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>
            <?php endif; ?>
            
            <a href="<?php echo SITE_URL; ?>/auth/logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Wrapper -->
    <main class="main-content">
        <!-- Flash Messages -->
        <?php display_flash_message(); ?>

<script>
    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const icon = themeToggle.querySelector('i');

    // Check saved preference
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        body.classList.add('dark-mode');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
    }

    themeToggle.addEventListener('click', () => {
        body.classList.toggle('dark-mode');
        
        if (body.classList.contains('dark-mode')) {
            localStorage.setItem('theme', 'dark');
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            localStorage.setItem('theme', 'light');
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    });
</script>
