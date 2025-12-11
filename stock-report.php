<?php
/**
 * DevTech Systems - Stock Report
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Stock Report';

// Get all products
$sql = "SELECT * FROM products WHERE status = 'active' ORDER BY product_name ASC";
$products = fetch_all($sql);

// Calculate totals
$total_items = count($products);
$total_value = 0;
$total_selling_value = 0;
$low_stock_count = 0;

foreach ($products as $product) {
    $total_value += $product['quantity'] * $product['cost_price'];
    $total_selling_value += $product['quantity'] * $product['unit_price'];
    if ($product['quantity'] <= $product['reorder_level']) {
        $low_stock_count++;
    }
}

$potential_profit = $total_selling_value - $total_value;
?>

<div class="page-header">
    <h1 class="page-title">Stock Report</h1>
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
            <i class="fas fa-box"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($total_items); ?></h3>
            <p>Total Products</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon success">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_value); ?></h3>
            <p>Stock Value (Cost)</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon warning">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_selling_value); ?></h3>
            <p>Potential Revenue</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo number_format($low_stock_count); ?></h3>
            <p>Low Stock Items</p>
        </div>
    </div>
</div>

<!-- Stock Status Distribution -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Stock Status Overview</h2>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; padding: 20px;">
        <div style="text-align: center;">
            <h4 style="color: var(--text-light); margin-bottom: 10px;">In Stock</h4>
            <p style="font-size: 36px; font-weight: 700; color: var(--success); margin: 0;">
                <?php 
                $in_stock = 0;
                foreach ($products as $p) {
                    if ($p['quantity'] > $p['reorder_level']) $in_stock++;
                }
                echo $in_stock; 
                ?>
            </p>
        </div>
        
        <div style="text-align: center;">
            <h4 style="color: var(--text-light); margin-bottom: 10px;">Low Stock</h4>
            <p style="font-size: 36px; font-weight: 700; color: var(--danger); margin: 0;">
                <?php echo $low_stock_count; ?>
            </p>
        </div>
        
        <div style="text-align: center;">
            <h4 style="color: var(--text-light); margin-bottom: 10px;">Potential Profit</h4>
            <p style="font-size: 36px; font-weight: 700; color: var(--primary); margin: 0;">
                <?php echo format_currency($potential_profit); ?>
            </p>
        </div>
    </div>
</div>

<!-- Detailed Stock List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Stock Details</h2>
    </div>
    
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Reorder Level</th>
                    <th>Cost Price</th>
                    <th>Selling Price</th>
                    <th>Stock Value</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($product['product_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category'] ?? 'N/A'); ?></td>
                        <td><?php echo $product['quantity']; ?> <?php echo $product['unit']; ?></td>
                        <td><?php echo $product['reorder_level']; ?></td>
                        <td><?php echo format_currency($product['cost_price']); ?></td>
                        <td><?php echo format_currency($product['unit_price']); ?></td>
                        <td><strong><?php echo format_currency($product['quantity'] * $product['cost_price']); ?></strong></td>
                        <td>
                            <?php if ($product['quantity'] <= $product['reorder_level']): ?>
                                <span class="badge-status badge-danger">Low Stock</span>
                            <?php elseif ($product['quantity'] <= ($product['reorder_level'] * 2)): ?>
                                <span class="badge-status badge-warning">Medium</span>
                            <?php else: ?>
                                <span class="badge-status badge-success">Good</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot style="font-weight: bold; background: var(--light);">
                <tr>
                    <td colspan="7" style="text-align: right;">TOTAL STOCK VALUE:</td>
                    <td><?php echo format_currency($total_value); ?></td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>