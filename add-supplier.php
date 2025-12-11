<?php
/**
 * DevTech Systems - Add Supplier
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_name = sanitize_input($_POST['supplier_name']);
    $contact_person = sanitize_input($_POST['contact_person']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $payment_terms = sanitize_input($_POST['payment_terms']);
    
    if (empty($supplier_name)) {
        $error = 'Supplier name is required';
    } else {
        $sql = "INSERT INTO suppliers (supplier_name, contact_person, email, phone, address, payment_terms, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $result = execute_query($sql, [
            $supplier_name, $contact_person, $email, $phone, $address, $payment_terms
        ], "ssssss");
        
        if ($result) {
            global $conn;
            log_activity('Added supplier', 'suppliers', $conn->insert_id);
            header('Location: suppliers.php?added=1');
            exit();
        } else {
            $error = 'Failed to add supplier';
        }
    }
}

// Include header for output
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Add Supplier';
?>

<div class="page-header">
    <h1 class="page-title">Add Supplier</h1>
    <div class="page-actions">
        <a href="suppliers.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
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
        <div class="form-group">
            <label class="form-label">Supplier Name *</label>
            <input type="text" name="supplier_name" class="form-control" required 
                   placeholder="Enter supplier name">
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label class="form-label">Contact Person</label>
                <input type="text" name="contact_person" class="form-control" 
                       placeholder="Contact person name">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone</label>
                <input type="tel" name="phone" class="form-control" 
                       placeholder="Phone number">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" 
                   placeholder="Email address">
        </div>
        
        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3" 
                      placeholder="Physical address"></textarea>
        </div>
        
        <div class="form-group">
            <label class="form-label">Payment Terms</label>
            <input type="text" name="payment_terms" class="form-control" 
                   placeholder="e.g., Net 30 days">
        </div>
        
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Add Supplier
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>