<?php
/**
 * DevTech Systems - Delete Product
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message("Invalid product ID.", "danger");
    redirect('inventory/products.php');
}

$product_id = intval($_GET['id']);

// Check if product exists
$product = fetch_one("SELECT product_name FROM products WHERE product_id = ?", [$product_id], "i");

if (!$product) {
    set_flash_message("Product not found.", "danger");
    redirect('inventory/products.php');
}

// Check for dependencies (e.g. sales)
// Ideally we should soft-delete (status='deleted') or check key constraints.
// For now, let's try to soft delete by setting status to 'discontinued' or 'deleted'.
// If the user expects standard "Delete", we might try DELETE and let constraints fail if any.
// BUT, to be safe and preserve history, updating status is better.
// HOWEVER, typically "Delete" button implies removal.
// Let's try DELETE, and if it fails (due to sales history), we set to discontinued and warn user.
// actually, I'll implement soft delete as it's safer for an inventory system. (Update status to 'deleted')
// But wait, my products page filters by active? No, `SELECT * FROM products`. It lists all.
// So soft delete (status=deleted) works if I update `products.php` to filter them out?
// `products.php` query: `SELECT * FROM products ORDER BY product_name ASC`
// It shows status.
// If I use DELETE query, it will fail if there are sales.
// I will try DELETE.

// Hard Delete Implementation: Manually delete dependencies first
try {
    global $conn;
    $conn->begin_transaction();

    // 1. Delete Sales records related to this product
    execute_query("DELETE FROM sales WHERE product_id = ?", [$product_id], "i");

    // 2. Delete Stock Movements
    execute_query("DELETE FROM stock_movements WHERE product_id = ?", [$product_id], "i");
    
    // 3. Delete Purchase records (Fixed: Added this step)
    execute_query("DELETE FROM purchases WHERE product_id = ?", [$product_id], "i");
    
    // 4. Delete the product
    $sql = "DELETE FROM products WHERE product_id = ?";
    if (execute_query($sql, [$product_id], "i")) {
        $conn->commit();
        set_flash_message("Product and all its history completely deleted.");
        log_activity("Hard deleted product: {$product['product_name']}", 'products', $product_id);
    } else {
        throw new Exception("Could not delete product record.");
    }

} catch (Exception $e) {
    if (isset($conn)) $conn->rollback();
    set_flash_message("Failed to delete product: " . $e->getMessage(), "danger");
}

redirect('inventory/products.php');
?>
