<?php
/**
 * DevTech Systems - Sales History
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Sales History';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? sanitize_input($_GET['payment_method']) : '';

// Build query
$sql = "SELECT s.*, p.product_name, p.product_code, u.full_name as sold_by 
        FROM sales s 
        JOIN products p ON s.product_id = p.product_id 
        LEFT JOIN users u ON s.user_id = u.user_id 
        WHERE DATE(s.sale_date) BETWEEN ? AND ?";

$params = [$start_date, $end_date];
$types = "ss";

if (!empty($payment_method)) {
    $sql .= " AND s.payment_method = ?";
    $params[] = $payment_method;
    $types .= "s";
}

$sql .= " ORDER BY s.sale_date DESC";

$sales = fetch_all($sql, $params, $types);

// Calculate totals
$total_sales = 0;
$total_profit = 0;
foreach ($sales as $sale) {
    $total_sales += $sale['total_amount'];
    $total_profit += $sale['profit'];
}
?>

<div class="page-header">
    <h1 class="page-title">Sales History</h1>
    <div class="page-actions">
        <a href="new-sale.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Sale
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
            <h3><?php echo count($sales); ?></h3>
            <p>Total Transactions</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_sales); ?></h3>
            <p>Total Sales</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_profit); ?></h3>
            <p>Total Profit</p>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card">
    <form method="GET" action="">
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" 
                       value="<?php echo $start_date; ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" 
                       value="<?php echo $end_date; ?>">
            </div>
            
            <div class="form-group" style="margin: 0;">
                <label class="form-label">Payment Method</label>
                <select name="payment_method" class="form-control">
                    <option value="">All Methods</option>
                    <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                    <option value="mpesa" <?php echo $payment_method === 'mpesa' ? 'selected' : ''; ?>>M-Pesa</option>
                    <option value="credit" <?php echo $payment_method === 'credit' ? 'selected' : ''; ?>>Credit Card</option>
                    <option value="bank" <?php echo $payment_method === 'bank' ? 'selected' : ''; ?>>Bank Transfer</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="sales-history.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </div>
    </form>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="table-responsive">
        <table class="table" id="salesTable">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Unit Price</th>
                    <th>Total</th>
                    <th>Profit</th>
                    <th>Payment</th>
                    <th>Customer</th>
                    <th>Sold By</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($sales) > 0): ?>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?php echo format_datetime($sale['sale_date']); ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($sale['product_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-light);"><?php echo htmlspecialchars($sale['product_code']); ?></small>
                            </td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td><?php echo format_currency($sale['unit_price']); ?></td>
                            <td><strong><?php echo format_currency($sale['total_amount']); ?></strong></td>
                            <td>
                                <span class="badge-status badge-success">
                                    <?php echo format_currency($sale['profit']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-status badge-primary">
                                    <?php echo ucfirst($sale['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($sale['customer_name']): ?>
                                    <?php echo htmlspecialchars($sale['customer_name']); ?>
                                    <?php if ($sale['customer_phone']): ?>
                                        <br><small><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-light);">Walk-in</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($sale['sold_by'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="receipt.php?id=<?php echo $sale['sale_id']; ?>" 
                                   class="btn btn-secondary btn-sm" target="_blank" title="View Receipt">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <a href="delete-sale.php?id=<?php echo $sale['sale_id']; ?>" 
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Are you sure you want to delete this sale? This will NOT restore stock automatically (unless implemented).');" 
                                   title="Delete Sale">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-light); padding: 40px;">
                            <i class="fas fa-shopping-cart" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                            No sales found for the selected period
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Export Button -->
<?php if (count($sales) > 0): ?>
<div style="text-align: right; margin-top: 15px;">
    <button onclick="exportToExcel('salesTable', 'sales_history')" class="btn btn-success">
        <i class="fas fa-file-excel"></i> Export to Excel
    </button>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>