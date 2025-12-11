<?php
/**
 * DevTech Systems - Add Expense
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Handle form submission BEFORE headers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_date = clean_data($_POST['expense_date']);
    
    // Configured mapping based on actual schema:
    // Schema: expense_category, amount, description, vendor_name, payment_method, expense_date, user_id
    
    $category = clean_data($_POST['category']); // Maps to expense_category
    $amount = floatval($_POST['amount']);
    
    $reference_no = clean_data($_POST['reference_no']);
    $notes_input = clean_data($_POST['notes']);
    
    // Combine Reference and Notes into Description
    $description = "";
    if ($reference_no) {
        $description .= "Ref: $reference_no. ";
    }
    $description .= $notes_input;
    
    $user_id = $_SESSION['user_id'] ?? 0;

    if (empty($expense_date) || empty($amount) || empty($category)) {
        set_flash_message("Please fill in all required fields.", "danger");
    } else {
        $sql = "INSERT INTO expenses (expense_date, expense_category, amount, description, user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        
        $params = [$expense_date, $category, $amount, $description, $user_id];
        
        if (execute_query($sql, $params, "ssdsi")) {
            set_flash_message("Expense recorded successfully!");
            global $conn;
            log_activity("Recorded expense: $amount ($category)", 'expenses', $conn->insert_id ?? 0);
            redirect('expenses/expenses.php');
        } else {
            set_flash_message("Failed to record expense.", "danger");
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1 class="page-title">Record Expense</h1>
    <div class="page-actions">
        <a href="expenses.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="card" style="max-width: 600px; margin: 0 auto;">
    <div class="card-header">
        <h3 class="card-title">Expense Details</h3>
    </div>
    
    <form method="POST" action="" data-validate>
        <div class="form-group">
            <label class="form-label">Date <span class="text-danger">*</span></label>
            <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>

        <div class="form-group">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category" class="form-control" required>
                <option value="">Select Category</option>
                <option value="Rent">Rent</option>
                <option value="Utilities">Utilities (Water/Electricity/Internet)</option>
                <option value="Salaries">Salaries & Wages</option>
                <option value="Maintenance">Maintenance & Repairs</option>
                <option value="Transportation">Transportation</option>
                <option value="Office Supplies">Office Supplies</option>
                <option value="Marketing">Marketing_Advertisement</option>
                <option value="Miscellaneous">Miscellaneous</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label">Amount <span class="text-danger">*</span></label>
            <input type="number" name="amount" class="form-control" step="0.01" required data-currency>
        </div>

        <div class="form-group">
            <label class="form-label">Reference No.</label>
            <input type="text" name="reference_no" class="form-control" placeholder="Receipt or Invoice No.">
        </div>

        <div class="form-group">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="3"></textarea>
        </div>

        <div style="text-align: right;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Record
            </button>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
