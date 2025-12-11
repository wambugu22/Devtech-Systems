<?php
/**
 * DevTech Systems - Supplier Report
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Supplier Report';

// Get all suppliers with purchase stats
$sql = "SELECT 
    s.*,
    COUNT(DISTINCT p.purchase_id) as total_orders,
    COALESCE(SUM(p.total_cost), 0) as total_spent,
    COALESCE(MAX(p.purchase_date), NULL) as last_purchase
    FROM suppliers s
    LEFT JOIN purchases p ON s.supplier_id = p.supplier_id
    GROUP BY s.supplier_id
    ORDER BY total_spent DESC";
$suppliers = fetch_all($sql);

// Calculate totals
$total_suppliers = count($suppliers);
$total_purchases = 0;
$total_outstanding = 0;

foreach ($suppliers as $supplier) {
    $total_purchases += $supplier['total_spent'];
    $total_outstanding += $supplier['outstanding_balance'];
}
?>

<div class="page-header">
    <h1 class="page-title">Supplier Report</h1>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-building"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($total_suppliers); ?></h3>
            <p>Total Suppliers</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_purchases); ?></h3>
            <p>Total Purchases</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_outstanding); ?></h3>
            <p>Outstanding Balance</p>
        </div>
    </div>
</div>

<!-- Supplier Details -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Supplier Performance</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Contact</th>
                    <th>Total Orders</th>
                    <th>Total Spent</th>
                    <th>Outstanding</th>
                    <th>Last Purchase</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($suppliers) > 0): ?>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($supplier['supplier_name']); ?></strong>
                                <?php if ($supplier['contact_person']): ?>
                                    <br><small style="color: var(--text-light);">
                                        <?php echo htmlspecialchars($supplier['contact_person']); ?>
                                    </small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($supplier['phone']): ?>
                                    <i class="fas fa-phone"></i> <?php echo htmlspecialchars($supplier['phone']); ?><br>
                                <?php endif; ?>
                                <?php if ($supplier['email']): ?>
                                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($supplier['email']); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo number_format($supplier['total_orders']); ?></td>
                            <td><strong><?php echo format_currency($supplier['total_spent']); ?></strong></td>
                            <td>
                                <?php if ($supplier['outstanding_balance'] > 0): ?>
                                    <span class="badge-status badge-danger">
                                        <?php echo format_currency($supplier['outstanding_balance']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-success">Cleared</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $supplier['last_purchase'] ? format_date($supplier['last_purchase']) : 'N/A'; ?>
                            </td>
                            <td>
                                <?php if ($supplier['status'] === 'active'): ?>
                                    <span class="badge-status badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge-status badge-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-light); padding: 40px;">
                            No suppliers found
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>