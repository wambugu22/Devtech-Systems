<?php
/**
 * DevTech Systems - Purchase History
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Purchase History';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

// Build query
$sql = "SELECT pu.*, p.product_name, p.product_code, s.supplier_name 
        FROM purchases pu
        JOIN products p ON pu.product_id = p.product_id 
        LEFT JOIN suppliers s ON pu.supplier_id = s.supplier_id 
        WHERE DATE(pu.purchase_date) BETWEEN ? AND ?
        ORDER BY pu.purchase_date DESC";

$purchases = fetch_all($sql, [$start_date, $end_date], "ss");

// Calculate totals
$total_purchases = 0;
foreach ($purchases as $purchase) {
    $total_purchases += $purchase['total_cost'];
}
?>

<div class="page-header">
    <h1 class="page-title">Purchase History</h1>
    <div class="page-actions">
        <a href="new-purchase.php" class="btn btn-success">
            <i class="fas fa-plus"></i> New Purchase
        </a>
    </div>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo count($purchases); ?></h3>
            <p>Total Purchases</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_purchases); ?></h3>
            <p>Total Amount</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <form method="GET" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr auto; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </form>
</div>

<!-- Purchases Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>Supplier</th>
                    <th>Quantity</th>
                    <th>Unit Cost</th>
                    <th>Total Cost</th>
                    <th>Payment Status</th>
                    <th>Notes</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($purchases) > 0): ?>
                    <?php foreach ($purchases as $purchase): ?>
                        <tr>
                            <td><?php echo format_datetime($purchase['purchase_date']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($purchase['product_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-light);"><?php echo htmlspecialchars($purchase['product_code']); ?></small>
                            </td>
                            <td><?php echo $purchase['supplier_name'] ? htmlspecialchars($purchase['supplier_name']) : 'N/A'; ?></td>
                            <td><?php echo $purchase['quantity']; ?></td>
                            <td><?php echo format_currency($purchase['unit_cost']); ?></td>
                            <td><strong><?php echo format_currency($purchase['total_cost']); ?></strong></td>
                            <td>
                                <?php
                                $badge_class = 'badge-success';
                                if ($purchase['payment_status'] === 'pending') $badge_class = 'badge-danger';
                                elseif ($purchase['payment_status'] === 'partial') $badge_class = 'badge-warning';
                                ?>
                                <span class="badge-status <?php echo $badge_class; ?>">
                                    <?php echo ucfirst($purchase['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo $purchase['notes'] ? htmlspecialchars($purchase['notes']) : '-'; ?></td>
                            <td>
                                <a href="delete-purchase.php?id=<?php echo $purchase['purchase_id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this purchase? This will reduce stock.');" 
                                   title="Delete Purchase">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-light); padding: 40px;">
                            No purchases found for the selected period
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>