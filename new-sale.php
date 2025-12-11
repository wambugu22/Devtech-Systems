<?php
/**
 * DevTech Systems - New Sale
 */

require_once __DIR__ . '/../config/config.php';
// Include functions explicitly to be safe, though header usually does it.
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Check login before anything
require_login();

// Handle form submission BEFORE sending headers
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $unit_price = floatval($_POST['unit_price']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $customer_name = sanitize_input($_POST['customer_name']);
    $customer_phone = sanitize_input($_POST['customer_phone']);
    
    // Validation
    if ($product_id <= 0 || $quantity <= 0) {
        $error = 'Please select a product and enter quantity';
    } elseif ($unit_price <= 0) {
        $error = 'Unit price must be greater than zero';
    } else {
        // Get product details
        $product_sql = "SELECT * FROM products WHERE product_id = ?";
        $product = fetch_one($product_sql, [$product_id], "i");
        
        if (!$product) {
            $error = 'Product not found';
        } elseif ($product['quantity'] < $quantity) {
            $error = 'Insufficient stock. Available: ' . $product['quantity'];
        } else {
            // Calculate totals
            $total_amount = $quantity * $unit_price;
            $cost_total = $quantity * $product['cost_price'];
            $profit = $total_amount - $cost_total;
            
            // Insert sale
            $sql = "INSERT INTO sales (product_id, quantity, unit_price, total_amount, cost_price, profit, payment_method, customer_name, customer_phone, user_id, sale_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $result = execute_query($sql, [
                $product_id, $quantity, $unit_price, $total_amount, 
                $product['cost_price'], $profit, $payment_method, 
                $customer_name, $customer_phone, $_SESSION['user_id']
            ], "iiddddsssi");
            
            if ($result) {
                // Get insert_id from global connection
                global $conn;
                $sale_id = $conn->insert_id;
                
                // Update product quantity
                update_product_stock($product_id, $quantity, 'subtract');
                
                // Check if stock is low
                $updated_product = fetch_one($product_sql, [$product_id], "i");
                if ($updated_product['quantity'] <= $updated_product['reorder_level']) {
                    send_notification(
                        $_SESSION['user_id'], 
                        'low_stock', 
                        "Low stock alert: {$updated_product['product_name']} has only {$updated_product['quantity']} {$updated_product['unit']} left"
                    );
                }
                
                log_activity('Recorded sale', 'sales', $sale_id);
                
                // Redirect to receipt
                header('Location: receipt.php?id=' . $sale_id);
                exit();
            } else {
                $error = 'Failed to record sale';
            }
        }
    }
}

// NOW include header which sends output
require_once __DIR__ . '/../includes/header.php';

$page_title = 'New Sale';

// Get all active products
$products_sql = "SELECT * FROM products WHERE status = 'active' AND quantity > 0 ORDER BY product_name ASC";
$products = fetch_all($products_sql);
?>

<div class="page-header">
    <h1 class="page-title">New Sale</h1>
    <div class="page-actions">
        <a href="sales-history.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> Sales History
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
            
            <!-- Sale Form -->
            <div>
                <h3 style="margin-bottom: 20px;">Sale Information</h3>
                
                <div class="form-group">
                    <label class="form-label">Select Product *</label>
                    <select name="product_id" id="product_id" class="form-control" required onchange="updateProductInfo()">
                        <option value="">-- Select Product --</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['product_id']; ?>" 
                                    data-price="<?php echo $product['unit_price']; ?>"
                                    data-cost="<?php echo $product['cost_price']; ?>"
                                    data-stock="<?php echo $product['quantity']; ?>"
                                    data-unit="<?php echo $product['unit']; ?>">
                                <?php echo htmlspecialchars($product['product_name']); ?> 
                                (<?php echo htmlspecialchars($product['product_code']); ?>) - 
                                Stock: <?php echo $product['quantity']; ?>
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
                        <label class="form-label">Unit Price *</label>
                        <input type="number" name="unit_price" id="unit_price" class="form-control" required 
                               step="0.01" min="0" readonly>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Method *</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="cash">Cash</option>
                        <option value="mpesa">M-Pesa</option>
                        <option value="credit">Credit/Debit Card</option>
                        <option value="bank">Bank Transfer</option>
                    </select>
                </div>
                
                <h3 style="margin: 30px 0 20px;">Customer Information (Optional)</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" 
                               placeholder="Enter customer name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Customer Phone</label>
                        <input type="tel" name="customer_phone" class="form-control" 
                               placeholder="Enter phone number">
                    </div>
                </div>
            </div>
            
            <!-- Summary Panel -->
            <div>
                <div class="card" style="background: var(--light); position: sticky; top: 80px;">
                    <h3 style="margin-bottom: 20px;">Sale Summary</h3>
                    
                    <div id="product-info" style="display: none; padding: 15px; background: white; border-radius: 8px; margin-bottom: 20px;">
                        <div style="margin-bottom: 10px;">
                            <small style="color: var(--text-light);">Available Stock</small>
                            <p style="margin: 5px 0; font-weight: 600;" id="available-stock">0</p>
                        </div>
                        <div style="margin-bottom: 10px;">
                            <small style="color: var(--text-light);">Cost Price</small>
                            <p style="margin: 5px 0;" id="cost-price">KES 0.00</p>
                        </div>
                        <div>
                            <small style="color: var(--text-light);">Selling Price</small>
                            <p style="margin: 5px 0; font-weight: 600;" id="selling-price">KES 0.00</p>
                        </div>
                    </div>
                    
                    <div style="padding: 20px; background: white; border-radius: 8px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Quantity:</span>
                            <strong id="summary-quantity">1</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Unit Price:</span>
                            <strong id="summary-price">KES 0.00</strong>
                        </div>
                        <div style="border-top: 2px solid var(--border); padding-top: 15px; margin-top: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-size: 18px; font-weight: 600;">Total Amount:</span>
                                <span style="font-size: 24px; font-weight: 700; color: var(--primary);" id="total-amount">KES 0.00</span>
                            </div>
                        </div>
                        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border);">
                            <div style="display: flex; justify-content: space-between;">
                                <span style="color: var(--text-light);">Estimated Profit:</span>
                                <strong style="color: var(--success);" id="profit-amount">KES 0.00</strong>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <i class="fas fa-check"></i> Complete Sale
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
        const price = parseFloat(option.dataset.price);
        const cost = parseFloat(option.dataset.cost);
        const stock = option.dataset.stock;
        const unit = option.dataset.unit;
        
        document.getElementById('unit_price').value = price.toFixed(2);
        document.getElementById('available-stock').textContent = stock + ' ' + unit;
        document.getElementById('cost-price').textContent = 'KES ' + cost.toFixed(2);
        document.getElementById('selling-price').textContent = 'KES ' + price.toFixed(2);
        
        productInfo.style.display = 'block';
        calculateTotal();
    } else {
        productInfo.style.display = 'none';
        productInfo.style.display = 'none';
    }
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = quantity * unitPrice;
    
    document.getElementById('summary-quantity').textContent = quantity;
    document.getElementById('summary-price').textContent = 'KES ' + unitPrice.toFixed(2);
    document.getElementById('total-amount').textContent = 'KES ' + total.toFixed(2);
    
    // Calculate profit
    const select = document.getElementById('product_id');
    const option = select.options[select.selectedIndex];
    if (option.value) {
        const cost = parseFloat(option.dataset.cost);
        const profit = (unitPrice - cost) * quantity;
        document.getElementById('profit-amount').textContent = 'KES ' + profit.toFixed(2);
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>