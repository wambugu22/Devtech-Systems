<?php
/**
 * DevTech Systems - New Purchase
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

$error = '';
$success = '';

// Handle POST request BEFORE headers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = intval($_POST['supplier_id']);
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $unit_cost = floatval($_POST['unit_cost']);
    $payment_status = sanitize_input($_POST['payment_status']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $notes = sanitize_input($_POST['notes']);
    
    // Validation
    if ($product_id <= 0 || $quantity <= 0) {
        $error = 'Please select a product and enter quantity';
    } elseif ($unit_cost <= 0) {
        $error = 'Unit cost must be greater than zero';
    } else {
        $total_cost = $quantity * $unit_cost;
        
        // Insert purchase
        $sql = "INSERT INTO purchases (supplier_id, product_id, quantity, unit_cost, total_cost, payment_status, payment_method, notes, user_id, purchase_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $result = execute_query($sql, [
            $supplier_id, $product_id, $quantity, $unit_cost, $total_cost,
            $payment_status, $payment_method, $notes, $_SESSION['user_id']
        ], "iiiddsssi");
        
        if ($result) {
            // Get insert ID correctly
            global $conn;
            $purchase_id = $conn->insert_id;
            
            // Update product quantity and cost price
            $update_sql = "UPDATE products SET quantity = quantity + ?, cost_price = ? WHERE product_id = ?";
            execute_query($update_sql, [$quantity, $unit_cost, $product_id], "idi");
            
            // Log stock movement
            $movement_sql = "INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, reference_id, user_id) 
                            VALUES (?, 'in', ?, 'purchase', ?, ?)";
            execute_query($movement_sql, [$product_id, $quantity, $purchase_id, $_SESSION['user_id']], "iiii");
            
            // Update supplier stats
            if ($supplier_id > 0) {
                $supplier_update = "UPDATE suppliers SET total_purchases = total_purchases + ? WHERE supplier_id = ?";
                execute_query($supplier_update, [$total_cost, $supplier_id], "di");
                
                if ($payment_status === 'pending' || $payment_status === 'partial') {
                    $outstanding = $payment_status === 'pending' ? $total_cost : $total_cost / 2;
                    $balance_update = "UPDATE suppliers SET outstanding_balance = outstanding_balance + ? WHERE supplier_id = ?";
                    execute_query($balance_update, [$outstanding, $supplier_id], "di");
                }
            }
            
            log_activity('Recorded purchase', 'purchases', $purchase_id);
            
            header('Location: purchase-receipt.php?id=' . $purchase_id);
            exit();
        } else {
            $error = 'Failed to record purchase';
        }
    }
}

// Include header for output
require_once __DIR__ . '/../includes/header.php';

// Get all products and suppliers
$products_sql = "SELECT * FROM products WHERE status = 'active' ORDER BY product_name ASC";
$products = fetch_all($products_sql);

$suppliers_sql = "SELECT * FROM suppliers WHERE status = 'active' ORDER BY supplier_name ASC";
$suppliers = fetch_all($suppliers_sql);

$page_title = 'New Purchase';

// Pre-select product if coming from low stock alert
$preselected_product = isset($_GET['product']) ? intval($_GET['product']) : 0;
?>

<div class="page-header">
    <h1 class="page-title">New Purchase</h1>
    <div class="page-actions">
        <a href="purchase-history.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> Purchase History
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <?php echo $error; ?>
    </div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="" data-validate>
        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
            
            <!-- Purchase Form -->
            <div>
                <h3 style="margin-bottom: 20px;">Purchase Information</h3>
                
                <div class="form-group">
                    <label class="form-label">Supplier (Optional)</label>
                    <select name="supplier_id" class="form-control">
                        <option value="0">-- No Supplier --</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['supplier_id']; ?>">
                                <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: var(--text-light);">
                        <a href="<?php echo SITE_URL; ?>/suppliers/add-supplier.php" target="_blank">Add new supplier</a>
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Product *</label>
                    <select name="product_id" id="product_id" class="form-control" required onchange="updateProductInfo()">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['product_id']; ?>" 
                                    data-current-stock="<?php echo $product['quantity']; ?>"
                                    data-reorder-level="<?php echo $product['reorder_level']; ?>"
                                    data-current-cost="<?php echo $product['cost_price']; ?>"
                                    <?php echo $product['product_id'] == $preselected_product ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($product['product_name']); ?> 
                                (<?php echo htmlspecialchars($product['product_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Quantity *</label>
                        <input type="number" name="quantity" id="quantity" class="form-control" required 
                               min="1" step="1" value="1" onchange="calculateTotal()">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Unit Cost *</label>
                        <input type="number" name="unit_cost" id="unit_cost" class="form-control" required 
                               step="0.01" min="0" onchange="calculateTotal()">
                    </div>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Payment Status *</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="paid">Paid</option>
                            <option value="pending">Pending</option>
                            <option value="partial">Partial Payment</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-control">
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" 
                              placeholder="Additional notes about this purchase"></textarea>
                </div>
            </div>
            
            <!-- Summary Panel -->
            <div>
                <div class="card" style="background: var(--light); position: sticky; top: 80px;">
                    <h3 style="margin-bottom: 20px;">Purchase Summary</h3>
                    
                    <div id="product-info" style="display: none; padding: 15px; background: white; border-radius: 8px; margin-bottom: 20px;">
                        <div style="margin-bottom: 10px;">
                            <small style="color: var(--text-light);">Current Stock</small>
                            <p style="margin: 5px 0; font-weight: 600;" id="current-stock">0</p>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <small style="color: var(--text-light);">Reorder Level</small>
                            <p style="margin: 5px 0;" id="reorder-level">0</p>
                        </div>
                        <div>
                            <small style="color: var(--text-light);">Current Cost Price</small>
                            <p style="margin: 5px 0;" id="current-cost">KES 0.00</p>
                        </div>
                    </div>
                    
                    <div style="padding: 20px; background: white; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Quantity:</span>
                            <strong id="summary-quantity">1</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Unit Cost:</span>
                            <strong id="summary-cost">KES 0.00</strong>
                        </div>
                        <div style="border-top: 2px solid var(--border); padding-top: 15px; margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 18px; font-weight: 600;">Total Cost:</span>
                                <span style="font-size: 24px; font-weight: 700; color: var(--primary);" id="total-cost">KES 0.00</span>
                            </div>
                        </div>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">New Stock Level:</span>
                                <strong style="color: var(--success);" id="new-stock">0</strong>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%;">
                        <i class="fas fa-check"></i> Record Purchase
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function updateProductInfo() {
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    const productInfo = document.getElementById('product-info');
    
    if (option.value) {
        const currentStock = option.dataset.currentStock;
        const reorderLevel = option.dataset.reorderLevel;
        const currentCost = parseFloat(option.dataset.currentCost);
        
        document.getElementById('current-stock').textContent = currentStock;
        document.getElementById('reorder-level').textContent = reorderLevel;
        // Fix: Use generic currency symbol or KES
        document.getElementById('current-cost').textContent = 'KES ' + currentCost.toFixed(2);
        document.getElementById('unit_cost').value = currentCost.toFixed(2);
        
        productInfo.style.display = 'block';
        calculateTotal();
    } else {
        productInfo.style.display = 'none';
        productInfo.style.display = 'none';
    }
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitCost = parseFloat(document.getElementById('unit_cost').value) || 0;
    const total = quantity * unitCost;
    
    document.getElementById('summary-quantity').textContent = quantity;
    document.getElementById('summary-cost').textContent = 'KES ' + unitCost.toFixed(2);
    document.getElementById('total-cost').textContent = 'KES ' + total.toFixed(2);
    
    // Calculate new stock level
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const currentStock = parseInt(option.dataset.currentStock);
        const newStock = currentStock + quantity; // Purchases ADD to stock
        document.getElementById('new-stock').textContent = newStock;
    }
}

// Initialize if product is preselected
window.onload = function() {
    const productSelect = document.getElementById('product_id');
    if (productSelect && productSelect.value) {
        updateProductInfo();
    }
};
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>