<?php
require_once __DIR__ . '/../includes/header.php';

// Fetch products
$products = fetch_all("SELECT * FROM products ORDER BY product_name ASC");
?>

<div class="page-header">
    <h1 class="page-title">Inventory Products</h1>
    <div class="page-actions">
        <a href="add-product.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Product
        </a>
        <button class="btn btn-secondary" onclick="window.print()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Products</h3>
        <div class="card-tools">
            <input type="text" id="tableSearch" class="form-control" placeholder="Search products..." style="width: 250px;">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" id="productsTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Cost</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_code']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td><?php echo format_currency($product['cost_price']); ?></td>
                            <td><?php echo format_currency($product['unit_price']); ?></td>
                            <td>
                                <?php echo $product['quantity']; ?> <?php echo htmlspecialchars($product['unit'] ?? ''); ?>
                                <?php if ($product['quantity'] <= $product['reorder_level']): ?>
                                    <span class="badge badge-danger" title="Low Stock"><i class="fas fa-exclamation-triangle"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['status'] === 'active'): ?>
                                    <span class="badge-status badge-success">Active</span>
                                <?php else: ?>
                                    <span class="badge-status badge-secondary"><?php echo ucfirst($product['status']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit-product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-danger" onclick="if(confirm('Are you sure?')) location.href='delete-product.php?id=<?php echo $product['product_id']; ?>'" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('tableSearch').addEventListener('keyup', function() {
    let filter = this.value.toUpperCase();
    let rows = document.querySelector("#productsTable tbody").rows;
    
    for (let i = 0; i < rows.length; i++) {
        let cells = rows[i].getElementsByTagName("td");
        let found = false;
        for (let j = 0; j < cells.length; j++) {
            if (cells[j]) {
                let txtValue = cells[j].textContent || cells[j].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    found = true;
                    break;
                }
            }
        }
        rows[i].style.display = found ? "" : "none";
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
