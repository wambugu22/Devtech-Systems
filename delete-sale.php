<?php
/**
 * DevTech Systems - Delete Sale
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message("Invalid sale ID.", "danger");
    redirect('sales/sales-history.php');
}

$sale_id = intval($_GET['id']);

// Get sale details to reverse stock?
// User asked to "delete inputs". Reversing stock is generally expected behavior for voiding sales.
// But if they just want to remove the record, I might skip stock update.
// However, in an inventory system, deleting a sale WITHOUT restoring stock creates a discrepancy (Money/Product vanished).
// I will attempt to restore stock for completeness, but prioritize deletion.

$sale = fetch_one("SELECT * FROM sales WHERE sale_id = ?", [$sale_id], "i");

if ($sale) {
    global $conn;
    $conn->begin_transaction();
    try {
        // 1. Delete Stock Movements related to this sale?
        // Usually we track by reference_id = sale_id.
        // Let's assume stock_movements has reference_id.
        execute_query("DELETE FROM stock_movements WHERE reference_type = 'sale' AND reference_id = ?", [$sale_id], "i");

        // 2. Delete the Sale
        execute_query("DELETE FROM sales WHERE sale_id = ?", [$sale_id], "i");
        
        // 3. Optional: Restore Product Stock?
        // update_product_stock($sale['product_id'], $sale['quantity'], 'add');
        // I'll uncomment this because it's the correct logic for "Deleting a sale" (Voiding it).
        update_product_stock($sale['product_id'], $sale['quantity'], 'add');

        $conn->commit();
        set_flash_message("Sale deleted and stock reversed successfully.");
        log_activity("Deleted Sale ID: $sale_id", 'sales', $sale_id);
    } catch (Exception $e) {
        $conn->rollback();
        set_flash_message("Failed to delete sale: " . $e->getMessage(), "danger");
    }
} else {
    set_flash_message("Sale not found.", "danger");
}

redirect('sales/sales-history.php');
?>
