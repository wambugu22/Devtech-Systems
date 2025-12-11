<?php
/**
 * DevTech Systems - Delete Expense
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    set_flash_message("Invalid expense ID.", "danger");
    redirect('expenses/expenses.php');
}

$expense_id = intval($_GET['id']);

// Delete expense
$sql = "DELETE FROM expenses WHERE expense_id = ?";
if (execute_query($sql, [$expense_id], "i")) {
    set_flash_message("Expense deleted successfully.");
    log_activity("Deleted expense ID: $expense_id", 'expenses', $expense_id);
} else {
    set_flash_message("Failed to delete expense.", "danger");
}

redirect('expenses/expenses.php');
?>
