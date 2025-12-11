<?php
/**
 * DevTech Systems - Add Product
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Handle form submission BEFORE headers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_code = clean_data($_POST['product_code']);
    $product_name = clean_data($_POST['product_name']);
    $category = clean_data($_POST['category']);
    $unit_price = floatval($_POST['unit_price']);
    $cost_price = floatval($_POST['cost_price']);
    $quantity = intval($_POST['quantity']);
    $reorder_level = intval($_POST['reorder_level']);
    $unit = clean_data($_POST['unit']);
    $description = clean_data($_POST['description']);
    $status = 'active';

    if (empty($product_name) || empty($unit_price)) {
        set_flash_message("Please fill in required fields.", "danger");
    } else {
        // Check for duplicate code
        $check = fetch_one("SELECT product_id FROM products WHERE product_code = ?", [$product_code], "s");
        
        if ($check) {
            set_flash_message("Product code already exists!", "danger");
        } else {
            $sql = "INSERT INTO products (product_code, product_name, category, cost_price, unit_price, quantity, reorder_level, unit, description, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $params = [$product_code, $product_name, $category, $cost_price, $unit_price, $quantity, $reorder_level, $unit, $description, $status];
            
            // sssddissss
            if (execute_query($sql, $params, "sssddissss")) {
                set_flash_message("Product added successfully!");
                // Try logging activity if function exists
                global $conn; // Just in case, though execute_query handles DB. logic_activity handles logs.
                if (function_exists('log_activity')) {
                     // Since we don't have insert_id easily correctly from execute_query without modification, and product addition isn't critical for ID log
                     // We can fetch it or just log generic. Or use global conn
                     $new_id = $conn->insert_id;
                     log_activity("Added new product: $product_name", 'products', $new_id);
                }
                redirect('inventory/products.php');
            } else {
                set_flash_message("Failed to add product. Please try again.", "danger");
            }
        }
    }
}

// Include header which sends output
require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Add New Product</h1>
    <div class="page-actions">
        <a href="products.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Product Details</h3>
    </div>
    
    <form method="POST" action="" data-validate>
        <div class="row" style="display:flex; gap:20px;">
            <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="product_name" class="form-control" required>
                </div>
            </div>
            <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Product Code / SKU <span class="text-danger">*</span></label>
                    <input type="text" name="product_code" class="form-control" required>
                </div>
            </div>
        </div>

        <div class="row" style="display:flex; gap:20px;">
             <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Category</label>
                    <input type="text" name="category" class="form-control" list="categoryList">
                    <datalist id="categoryList">
                        <option value="Electronics">
                        <option value="Computers">
                        <option value="Accessories">
                        <option value="Services">
                    </datalist>
                </div>
            </div>
             <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Quantity <span class="text-danger">*</span></label>
                    <input type="number" name="quantity" class="form-control" value="0" min="0" required>
                </div>
            </div>
             <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Unit</label>
                    <select name="unit" class="form-control">
                        <option value="pieces">Pieces</option>
                        <option value="boxes">Boxes</option>
                        <option value="kg">Kg</option>
                        <option value="sets">Sets</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row" style="display:flex; gap:20px;">
            <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Cost Price</label>
                    <input type="number" name="cost_price" class="form-control" step="0.01" data-currency required>
                </div>
            </div>
            <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Selling Unit Price <span class="text-danger">*</span></label>
                    <input type="number" name="unit_price" class="form-control" step="0.01" required data-currency>
                </div>
            </div>
        </div>

        <div class="row" style="display:flex; gap:20px;">
             <div class="col" style="flex:1;">
                <div class="form-group">
                    <label class="form-label">Reorder Level</label>
                    <input type="number" name="reorder_level" class="form-control" value="5">
                    <small class="text-muted">Notify when stock drops below this</small>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>

        <div style="margin-top:20px; text-align:right;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Product
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
