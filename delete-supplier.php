<?php
/**
 * DevTech Systems - Delete Supplier
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message("Invalid supplier ID.", "danger");
    redirect('suppliers/suppliers.php');
}

$supplier_id = intval($_GET['id']);

// Delete supplier
// Note: This might fail if products or purchases are linked. 
// User asked for capability to delete "inputs including sales, suppliers...".
// I will attempt simple delete. If foreign keys exist (e.g. purchases linked to supplier), it might crash.
// Ideally I'd cascade delete purchases too, but "Inputs" implies the inputs themselves.
// If I assume "delete supplier" means "remove this supplier record", I'll try simple delete.
// If it fails, I'll catch it.
// BUT, if user wants "completely delete", and they have purchases...
// I'll stick to simple delete for now. If it crashes, I'll add cascade later or warn user.

try {
    $sql = "DELETE FROM suppliers WHERE supplier_id = ?";
    if (execute_query($sql, [$supplier_id], "i")) {
        set_flash_message("Supplier deleted successfully.");
        log_activity("Deleted supplier ID: $supplier_id", 'suppliers', $supplier_id);
    } else {
        throw new Exception("Delete operation failed.");
    }
} catch (mysqli_sql_exception $e) {
    set_flash_message("Cannot delete supplier: They might be linked to existing products or purchases.", "danger");
} catch (Exception $e) {
    set_flash_message("Failed to delete supplier.", "danger");
}

redirect('suppliers/suppliers.php');
?>
