<?php
/**
 * DevTech Systems - Sales Report
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Sales Report';

// Get date range
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

// Daily sales summary
$daily_sql = "SELECT 
    DATE(sale_date) as sale_day,
    COUNT(*) as transactions,
    SUM(quantity) as total_quantity,
    SUM(total_amount) as total_sales,
    SUM(profit) as total_profit
    FROM sales 
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY sale_date DESC";
$daily_sales = fetch_all($daily_sql, [$start_date, $end_date], "ss");

// Payment method breakdown
$payment_sql = "SELECT 
    payment_method,
    COUNT(*) as transactions,
    SUM(total_amount) as total_amount
    FROM sales 
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY payment_method";
$payment_breakdown = fetch_all($payment_sql, [$start_date, $end_date], "ss");

// Top products
$top_products = get_top_products(10, 'custom');

// Calculate totals
$total_sales = 0;
$total_profit = 0;
$total_transactions = 0;

foreach ($daily_sales as $day) {
    $total_sales += $day['total_sales'];
    $total_profit += $day['total_profit'];
    $total_transactions += $day['transactions'];
}
?>

<div class="page-header">
    <h1 class="page-title">Sales Report</h1>
    <div class="page-actions">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- Date Filter -->
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
                <i class="fas fa-filter"></i> Generate Report
            </button>
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($total_transactions); ?></h3>
            <p>Total Transactions</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
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
    
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-calculator"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo $total_transactions > 0 ? format_currency($total_sales / $total_transactions) : format_currency(0); ?></h3>
            <p>Avg Transaction</p>
        </div>
    </div>
</div>

<!-- Daily Sales Breakdown -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Daily Sales Summary</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transactions</th>
                    <th>Items Sold</th>
                    <th>Sales Amount</th>
                    <th>Profit</th>
                    <th>Avg Sale</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($daily_sales) > 0): ?>
                    <?php foreach ($daily_sales as $day): ?>
                        <tr>
                            <td><strong><?php echo format_date($day['sale_day']); ?></strong></td>
                            <td><?php echo number_format($day['transactions']); ?></td>
                            <td><?php echo number_format($day['total_quantity']); ?></td>
                            <td><strong><?php echo format_currency($day['total_sales']); ?></strong></td>
                            <td>
                                <span class="badge-status badge-success">
                                    <?php echo format_currency($day['total_profit']); ?>
                                </span>
                            </td>
                            <td><?php echo format_currency($day['total_sales'] / $day['transactions']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-light); padding: 40px;">
                            No sales data for selected period
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <tfoot style="font-weight: bold; background: var(--light);">
                <tr>
                    <td>TOTAL</td>
                    <td><?php echo number_format($total_transactions); ?></td>
                    <td><?php echo number_format(array_sum(array_column($daily_sales, 'total_quantity'))); ?></td>
                    <td><?php echo format_currency($total_sales); ?></td>
                    <td><?php echo format_currency($total_profit); ?></td>
                    <td>-</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Payment Method Breakdown -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Payment Methods</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th>Transactions</th>
                    <th>Total Amount</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payment_breakdown as $payment): ?>
                    <tr>
                        <td><strong><?php echo ucfirst($payment['payment_method']); ?></strong></td>
                        <td><?php echo number_format($payment['transactions']); ?></td>
                        <td><?php echo format_currency($payment['total_amount']); ?></td>
                        <td>
                            <span class="badge-status badge-primary">
                                <?php echo $total_sales > 0 ? number_format(($payment['total_amount'] / $total_sales) * 100, 1) : 0; ?>%
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>