<?php
/**
 * DevTech Systems - Profit & Loss Report
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Profit & Loss Report';

// Get date range
$start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : date('Y-m-d');

// Get sales data
$sales_sql = "SELECT 
    SUM(total_amount) as total_revenue,
    SUM(profit) as gross_profit,
    SUM(quantity * cost_price) as cost_of_goods
    FROM sales 
    WHERE DATE(sale_date) BETWEEN ? AND ?";
$sales_data = fetch_one($sales_sql, [$start_date, $end_date], "ss");

// Get expenses data
$expenses_sql = "SELECT 
    SUM(amount) as total_expenses,
    expense_category,
    SUM(amount) as category_total
    FROM expenses 
    WHERE expense_date BETWEEN ? AND ?
    GROUP BY expense_category
    ORDER BY category_total DESC";
$expenses_breakdown = fetch_all($expenses_sql, [$start_date, $end_date], "ss");

$total_expenses = 0;
foreach ($expenses_breakdown as $exp) {
    $total_expenses += $exp['category_total'];
}

// Calculate profit metrics
$revenue = $sales_data['total_revenue'] ?? 0;
$cogs = $sales_data['cost_of_goods'] ?? 0;
$gross_profit = $sales_data['gross_profit'] ?? 0;
$net_profit = $gross_profit - $total_expenses;
$gross_margin = $revenue > 0 ? ($gross_profit / $revenue) * 100 : 0;
$net_margin = $revenue > 0 ? ($net_profit / $revenue) * 100 : 0;
?>

<div class="page-header">
    <h1 class="page-title">Profit & Loss Statement</h1>
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

<!-- Report Period -->
<div class="card">
    <div style="text-align: center; padding: 10px;">
        <h3 style="margin: 0;">Report Period: <?php echo format_date($start_date); ?> to <?php echo format_date($end_date); ?></h3>
    </div>
</div>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($revenue); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($gross_profit); ?></h3>
            <p>Gross Profit</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-wallet"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_expenses); ?></h3>
            <p>Total Expenses</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon <?php echo $net_profit >= 0 ? 'success' : 'danger'; ?>">
            <i class="fas fa-balance-scale"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($net_profit); ?></h3>
            <p>Net Profit</p>
        </div>
    </div>
</div>

<!-- Detailed P&L Statement -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Profit & Loss Statement</h2>
    </div>
    
    <table class="table">
        <tbody>
            <!-- Revenue Section -->
            <tr style="background: var(--light); font-weight: bold;">
                <td colspan="2">REVENUE</td>
            </tr>
            <tr>
                <td style="padding-left: 30px;">Sales Revenue</td>
                <td style="text-align: right;"><strong><?php echo format_currency($revenue); ?></strong></td>
            </tr>
            <tr style="font-weight: bold; background: var(--light);">
                <td>Total Revenue</td>
                <td style="text-align: right;"><?php echo format_currency($revenue); ?></td>
            </tr>
            
            <!-- COGS Section -->
            <tr style="background: var(--light); font-weight: bold;">
                <td colspan="2" style="padding-top: 20px;">COST OF GOODS SOLD</td>
            </tr>
            <tr>
                <td style="padding-left: 30px;">Cost of Goods Sold</td>
                <td style="text-align: right;"><?php echo format_currency($cogs); ?></td>
            </tr>
            <tr style="font-weight: bold; background: var(--success); color: white;">
                <td>GROSS PROFIT</td>
                <td style="text-align: right;"><?php echo format_currency($gross_profit); ?> (<?php echo number_format($gross_margin, 1); ?>%)</td>
            </tr>
            
            <!-- Expenses Section -->
            <tr style="background: var(--light); font-weight: bold;">
                <td colspan="2" style="padding-top: 20px;">OPERATING EXPENSES</td>
            </tr>
            <?php foreach ($expenses_breakdown as $expense): ?>
            <tr>
                <td style="padding-left: 30px;"><?php echo htmlspecialchars($expense['expense_category']); ?></td>
                <td style="text-align: right;"><?php echo format_currency($expense['category_total']); ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight: bold; background: var(--light);">
                <td>Total Operating Expenses</td>
                <td style="text-align: right;"><?php echo format_currency($total_expenses); ?></td>
            </tr>
            
            <!-- Net Profit -->
            <tr style="font-weight: bold; font-size: 18px; background: <?php echo $net_profit >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; color: white;">
                <td>NET PROFIT</td>
                <td style="text-align: right;"><?php echo format_currency($net_profit); ?> (<?php echo number_format($net_margin, 1); ?>%)</td>
            </tr>
        </tbody>
    </table>
</div>

<!-- Key Metrics -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Key Performance Indicators</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; padding: 20px;">
        <div>
            <h4 style="margin-bottom: 10px; color: var(--text-light);">Gross Profit Margin</h4>
            <p style="font-size: 32px; font-weight: 700; color: var(--primary); margin: 0;">
                <?php echo number_format($gross_margin, 2); ?>%
            </p>
        </div>
        
        <div>
            <h4 style="margin-bottom: 10px; color: var(--text-light);">Net Profit Margin</h4>
            <p style="font-size: 32px; font-weight: 700; color: <?php echo $net_profit >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; margin: 0;">
                <?php echo number_format($net_margin, 2); ?>%
            </p>
        </div>
        
        <div>
            <h4 style="margin-bottom: 10px; color: var(--text-light);">Expense Ratio</h4>
            <p style="font-size: 32px; font-weight: 700; color: var(--warning); margin: 0;">
                <?php echo $revenue > 0 ? number_format(($total_expenses / $revenue) * 100, 2) : 0; ?>%
            </p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>