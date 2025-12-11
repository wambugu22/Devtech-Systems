<?php
/**
 * DevTech Systems - User Management
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Access Control
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['flash_message'] = "Access denied. Admin privileges required.";
    $_SESSION['flash_type'] = "danger";
    header('Location: ' . SITE_URL . '/dashboard/');
    exit();
}

$page_title = 'User Management';
require_once __DIR__ . '/../includes/header.php';

// Get all users
$users = fetch_all("SELECT * FROM users ORDER BY created_at DESC");
?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <div class="page-actions">
        <!-- Add User button could go here -->
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; background: var(--light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: bold;">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <?php echo htmlspecialchars($user['full_name']); ?>
                            <?php if ($user['user_id'] == $_SESSION['user_id']): ?>
                                <span class="badge" style="background: var(--light); color: var(--text-light);">You</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="badge" style="background: <?php echo $user['role'] == 'admin' ? '#e0f2fe' : '#f3f4f6'; ?>; color: <?php echo $user['role'] == 'admin' ? '#0284c7' : '#374151'; ?>;">
                            <?php echo ucfirst($user['role']); ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge" style="background: #dcfce7; color: #166534;">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                    <td>
                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                            <a href="delete-user.php?id=<?php echo $user['user_id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Are you sure you want to delete this user? ALL their records (Sales, Purchases) will be reassigned to YOU.');">
                                <i class="fas fa-trash"></i>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
