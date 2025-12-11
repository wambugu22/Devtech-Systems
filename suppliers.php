<?php
require_once __DIR__ . '/../includes/header.php';

$suppliers = fetch_all("SELECT * FROM suppliers ORDER BY supplier_name ASC");
?>

<div class="page-header">
    <h1 class="page-title">Suppliers</h1>
    <div class="page-actions">
        <a href="add-supplier.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Supplier
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Suppliers</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($suppliers)): ?>
                    <?php foreach ($suppliers as $supplier): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                            <td><?php echo htmlspecialchars($supplier['contact_person'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($supplier['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($supplier['phone'] ?? '-'); ?></td>
                            <td>
                                <a href="edit-supplier.php?id=<?php echo $supplier['supplier_id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete-supplier.php?id=<?php echo $supplier['supplier_id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this supplier?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No suppliers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
