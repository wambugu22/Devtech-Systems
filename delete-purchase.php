<?php
/**
 * DevTech Systems - Delete Purchase
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message("Invalid purchase ID.", "danger");
    redirect('purchases/purchase-history.php');
}

$purchase_id = intval($_GET['id']);

// Get purchase details to reverse stock (Subtract)
$purchase = fetch_one("SELECT * FROM purchases WHERE purchase_id = ?", [$purchase_id], "i");

if ($purchase) {
    global $conn;
    $conn->begin_transaction();
    try {
        // 1. Delete Stock Movements related to this purchase
        // Assuming reference_type = 'purchase'
        execute_query("DELETE FROM stock_movements WHERE reference_type = 'purchase' AND reference_id = ?", [$purchase_id], "i");

        // 2. Delete the Purchase
        execute_query("DELETE FROM purchases WHERE purchase_id = ?", [$purchase_id], "i");
        
        // 3. Reverse (Subtract) Stock
        // update_product_stock($product_id, $qty, 'subtract');
        update_product_stock($purchase['product_id'], $purchase['quantity'], 'subtract');

        $conn->commit();
        set_flash_message("Purchase deleted and stock reversed successfully.");
        log_activity("Deleted Purchase ID: $purchase_id", 'purchases', $purchase_id);
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message("Failed to delete purchase: " . $e->getMessage(), "danger");
    }
} else {
    set_flash_message("Purchase not found.", "danger");
}

redirect('purchases/purchase-history.php');
?>
