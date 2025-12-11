<?php
/**
 * DevTech Systems - Main Dashboard
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Dashboard';

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get low stock products
$low_stock = get_low_stock_products();

// Get recent sales
$recent_sales = get_recent_sales(5);

// Get top products
$top_products = get_top_products(5, 'month');
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <div class="page-actions">
        <a href="<?php echo SITE_URL; ?>/sales/new-sale.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Sale
        </a>
    </div>
</div>

<!-- Statistics Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary">
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['total_products']); ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($stats['today_sales']); ?></h3>
            <p>Today's Sales</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($stats['today_profit']); ?></h3>
            <p>Today's Profit</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($stats['low_stock']); ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
</div>

<!-- Monthly Overview -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Monthly Overview</h2>
        <span style="color: var(--text-light);"><?php echo date('F Y'); ?></span>
    </div>
    
    <div class="stats-grid">
        <div style="text-align: center;">
            <h3 style="font-size: 32px; color: var(--primary); margin-bottom: 8px;">
                <?php echo format_currency($stats['month_sales']); ?>
            </h3>
            <p style="color: var(--text-light);">Total Sales</p>
        </div>
        
        <div style="text-align: center;">
            <h3 style="font-size: 32px; color: var(--success); margin-bottom: 8px;">
                <?php echo format_currency($stats['month_profit']); ?>
            </h3>
            <p style="color: var(--text-light);">Gross Profit</p>
        </div>
        
        <div style="text-align: center;">
            <h3 style="font-size: 32px; color: var(--danger); margin-bottom: 8px;">
                <?php echo format_currency($stats['month_expenses']); ?>
            </h3>
            <p style="color: var(--text-light);">Total Expenses</p>
        </div>
        
        <div style="text-align: center;">
            <h3 style="font-size: 32px; color: <?php echo $stats['net_profit'] >= 0 ? 'var(--success)' : 'var(--danger)'; ?>; margin-bottom: 8px;">
                <?php echo format_currency($stats['net_profit']); ?>
            </h3>
            <p style="color: var(--text-light);">Net Profit</p>
        </div>
    </div>
</div>

<!-- Two Column Layout -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 20px;">
    
    <!-- Low Stock Alert -->
    <?php if (count($low_stock) > 0): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Low Stock Alert</h2>
            <a href="<?php echo SITE_URL; ?>/inventory/products.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Code</th>
                        <th>Stock</th>
                        <th>Reorder Level</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($low_stock as $product): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                        <td>
                            <span class="badge-status badge-danger">
                                <?php echo $product['quantity']; ?>
                            </span>
                        </td>
                        <td><?php echo $product['reorder_level']; ?></td>
                        <td>
                            <a href="<?php echo SITE_URL; ?>/purchases/new-purchase.php?product=<?php echo $product['product_id']; ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-shopping-cart"></i> Reorder
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Sales</h2>
            <a href="<?php echo SITE_URL; ?>/sales/sales-history.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Amount</th>
                        <th>Profit</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($recent_sales) > 0): ?>
                        <?php foreach ($recent_sales as $sale): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($sale['product_name']); ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td><?php echo format_currency($sale['total_amount']); ?></td>
                            <td>
                                <span class="badge-status badge-success">
                                    <?php echo format_currency($sale['profit']); ?>
                                </span>
                            </td>
                            <td><?php echo format_datetime($sale['sale_date']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-light);">
                                No sales recorded yet
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Top Selling Products -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Top Selling Products (This Month)</h2>
        </div>
        
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                        <th>Profit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($top_products) > 0): ?>
                        <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                <br>
                                <small style="color: var(--text-light);"><?php echo htmlspecialchars($product['product_code']); ?></small>
                            </td>
                            <td><?php echo number_format($product['total_sold']); ?></td>
                            <td><?php echo format_currency($product['total_revenue']); ?></td>
                            <td>
                                <span class="badge-status badge-success">
                                    <?php echo format_currency($product['total_profit']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-light);">
                                No sales data available
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Quick Actions</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
            <a href="<?php echo SITE_URL; ?>/sales/new-sale.php" class="btn btn-primary" style="justify-content: center;">
                <i class="fas fa-cash-register"></i> New Sale
            </a>
            
            <a href="<?php echo SITE_URL; ?>/purchases/new-purchase.php" class="btn btn-success" style="justify-content: center;">
                <i class="fas fa-shopping-cart"></i> New Purchase
            </a>
            
            <a href="<?php echo SITE_URL; ?>/inventory/add-product.php" class="btn btn-secondary" style="justify-content: center;">
                <i class="fas fa-plus"></i> Add Product
            </a>
            
            <a href="<?php echo SITE_URL; ?>/expenses/add-expense.php" class="btn btn-warning" style="justify-content: center; color: white;">
                <i class="fas fa-wallet"></i> Add Expense
            </a>
            
            <a href="<?php echo SITE_URL; ?>/reports/profit-loss.php" class="btn btn-secondary" style="justify-content: center;">
                <i class="fas fa-chart-line"></i> P&L Report
            </a>
            
            <a href="<?php echo SITE_URL; ?>/reports/stock-report.php" class="btn btn-secondary" style="justify-content: center;">
                <i class="fas fa-warehouse"></i> Stock Report
            </a>
        </div>
    </div>
</div>

<!-- Stock Value Info -->
<div class="card">
    <div style="text-align: center; padding: 20px;">
        <h3 style="font-size: 18px; color: var(--text-light); margin-bottom: 8px;">Total Stock Value</h3>
        <p style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0;">
            <?php echo format_currency($stats['stock_value']); ?>
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>