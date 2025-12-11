<?php
/**
 * DevTech Systems - Edit Product
 */

require_once __DIR__ . '/../includes/header.php';
require_login();

$page_title = 'Edit Product';
$error = '';
$success = '';

// Get product ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: products.php');
    exit();
}

$product_id = intval($_GET['id']);

// Get product data
$sql = "SELECT * FROM products WHERE product_id = ?";
$product = fetch_one($sql, [$product_id], "i");

if (!$product) {
    header('Location: products.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = sanitize_input($_POST['product_name']);
    $product_code = sanitize_input($_POST['product_code']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $unit_price = floatval($_POST['unit_price']);
    $cost_price = floatval($_POST['cost_price']);
    $reorder_level = intval($_POST['reorder_level']);
    $unit = sanitize_input($_POST['unit']);
    $status = sanitize_input($_POST['status']);
    
    // Validation
    if (empty($product_name) || empty($product_code)) {
        $error = 'Product name and code are required';
    } elseif ($unit_price <= 0 || $cost_price <= 0) {
        $error = 'Prices must be greater than zero';
    } else {
        // Check if product code already exists for another product
        $check_sql = "SELECT product_id FROM products WHERE product_code = ? AND product_id != ?";
        $existing = fetch_one($check_sql, [$product_code, $product_id], "si");
        
        if ($existing) {
            $error = 'Product code already exists';
        } else {
            // Update product
            $sql = "UPDATE products SET 
                    product_name = ?, product_code = ?, category = ?, description = ?, 
                    unit_price = ?, cost_price = ?, reorder_level = ?, unit = ?, status = ?
                    WHERE product_id = ?";
            
            $result = execute_query($sql, [
                $product_name, $product_code, $category, $description, 
                $unit_price, $cost_price, $reorder_level, $unit, $status, $product_id
            ], "ssssddissi");
            
            if ($result) {
                log_activity('Updated product', 'products', $product_id, $product_name);
                $success = 'Product updated successfully';
                
                // Refresh product data
                $product = fetch_one("SELECT * FROM products WHERE product_id = ?", [$product_id], "i");
            } else {
                $error = 'Failed to update product';
            }
        }
    }
}

// Handle stock adjustment
if (isset($_POST['adjust_stock'])) {
    $adjustment = intval($_POST['adjustment']);
    $adjustment_type = $_POST['adjustment_type'];
    $notes = sanitize_input($_POST['notes']);
    
    if ($adjustment != 0) {
        if ($adjustment_type === 'add') {
            $sql = "UPDATE products SET quantity = quantity + ? WHERE product_id = ?";
            $movement_type = 'in';
        } else {
            // Check if we have enough stock
            if ($product['quantity'] >= $adjustment) {
                $sql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ?";
                $movement_type = 'out';
            } else {
                $error = 'Insufficient stock for this adjustment';
                $sql = null;
            }
        }
        
        if ($sql) {
            execute_query($sql, [abs($adjustment), $product_id], "ii");
            
            // Log stock movement
            $movement_sql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, notes, user_id) 
                            VALUES (?, ?, ?, 'adjustment', ?, ?)";
            execute_query($movement_sql, [$product_id, $movement_type, abs($adjustment), $notes, $_SESSION['user_id']], "isisi");
            
            log_activity('Stock adjusted', 'products', $product_id);
            $success = 'Stock adjusted successfully';
            
            // Refresh product data
            $product = fetch_one("SELECT * FROM products WHERE product_id = ?", [$product_id], "i");
        }
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Edit Product</h1>
    <div class="page-actions">
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i>
        <?php echo $success; ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
    
    <!-- Product Details -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Product Information</h2>
            </div>
            
            <form method="POST" action="" data-validate>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Product Name *</label>
                        <input type="text" name="product_name" class="form-control" required 
                               value="<?php echo htmlspecialchars($product['product_name']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Product Code *</label>
                        <input type="text" name="product_code" class="form-control" required 
                               value="<?php echo htmlspecialchars($product['product_code']); ?>">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-control" 
                               list="categories"
                               value="<?php echo htmlspecialchars($product['category']); ?>">
                        <datalist id="categories">
                            <?php
                            $cat_sql = "SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''";
                            $cats = fetch_all($cat_sql);
                            foreach ($cats as $cat):
                            ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>">
                            <?php endforeach; ?>
                        </datalist>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Unit</label>
                        <select name="unit" class="form-control">
                            <option value="pieces" <?php echo $product['unit'] === 'pieces' ? 'selected' : ''; ?>>Pieces</option>
                            <option value="boxes" <?php echo $product['unit'] === 'boxes' ? 'selected' : ''; ?>>Boxes</option>
                            <option value="kg" <?php echo $product['unit'] === 'kg' ? 'selected' : ''; ?>>Kilograms (kg)</option>
                            <option value="liters" <?php echo $product['unit'] === 'liters' ? 'selected' : ''; ?>>Liters</option>
                            <option value="meters" <?php echo $product['unit'] === 'meters' ? 'selected' : ''; ?>>Meters</option>
                            <option value="sets" <?php echo $product['unit'] === 'sets' ? 'selected' : ''; ?>>Sets</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                    <div class="form-group">
                        <label class="form-label">Unit Price *</label>
                        <input type="number" name="unit_price" class="form-control" required 
                               step="0.01" min="0" value="<?php echo $product['unit_price']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Cost Price *</label>
                        <input type="number" name="cost_price" class="form-control" required 
                               step="0.01" min="0" value="<?php echo $product['cost_price']; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" name="reorder_level" class="form-control" 
                               min="0" value="<?php echo $product['reorder_level']; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="active" <?php echo $product['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="discontinued" <?php echo $product['status'] === 'discontinued' ? 'selected' : ''; ?>>Discontinued</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Product
                </button>
            </form>
        </div>
    </div>
    
    <!-- Stock & Quick Actions -->
    <div>
        <!-- Current Stock -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Current Stock</h2>
            </div>
            
            <div style="text-align: center; padding: 20px;">
                <h1 style="font-size: 48px; margin-bottom: 10px; color: <?php echo $product['quantity'] <= $product['reorder_level'] ? 'var(--danger)' : 'var(--success)'; ?>;">
                    <?php echo $product['quantity']; ?>
                </h1>
                <p style="color: var(--text-light);"><?php echo $product['unit']; ?></p>
                
                <?php if ($product['quantity'] <= $product['reorder_level']): ?>
                    <div class="alert alert-warning" style="margin-top: 15px;">
                        <i class="fas fa-exclamation-triangle"></i>
                        Low stock! Reorder recommended
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Stock Adjustment -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Adjust Stock</h2>
            </div>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label">Adjustment Type</label>
                    <select name="adjustment_type" class="form-control" required>
                        <option value="add">Add Stock</option>
                        <option value="remove">Remove Stock</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantity</label>
                    <input type="number" name="adjustment" class="form-control" required min="1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <input type="text" name="notes" class="form-control" 
                           placeholder="Reason for adjustment">
                </div>
                
                <button type="submit" name="adjust_stock" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-sync-alt"></i> Adjust Stock
                </button>
            </form>
        </div>
        
        <!-- Product Info -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Product Details</h2>
            </div>
            
            <div style="padding: 15px;">
                <div style="margin-bottom: 15px;">
                    <small style="color: var(--text-light);">Profit Margin</small>
                    <p style="margin: 5px 0; font-weight: 600;">
                        <?php 
                        $margin = calculate_profit_margin($product['unit_price'], $product['cost_price']);
                        echo number_format($margin, 2); 
                        ?>%
                    </p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <small style="color: var(--text-light);">Stock Value</small>
                    <p style="margin: 5px 0; font-weight: 600;">
                        <?php echo format_currency($product['quantity'] * $product['cost_price']); ?>
                    </p>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <small style="color: var(--text-light);">Added On</small>
                    <p style="margin: 5px 0;"><?php echo format_date($product['created_at']); ?></p>
                </div>
                
                <div>
                    <small style="color: var(--text-light);">Last Updated</small>
                    <p style="margin: 5px 0;"><?php echo format_datetime($product['updated_at']); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>