<?php
require_once __DIR__ . '/../includes/header.php';

// Fetch expenses
$expenses = fetch_all("SELECT * FROM expenses ORDER BY expense_date DESC");

// Calculate total for this month
$current_month = date('Y-m');
$total_expenses = 0;
foreach ($expenses as $expense) {
    if (strpos($expense['expense_date'], $current_month) === 0) {
        $total_expenses += $expense['amount'];
    }
}
?>

<div class="page-header">
    <h1 class="page-title">Expenses</h1>
    <div class="page-actions">
        <a href="add-expense.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Record Expense
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon danger">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo format_currency($total_expenses); ?></h3>
            <p>Total Expenses (This Month)</p>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Expense History</h3>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                    <th>Amount</th>
                    <th>User</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($expenses)): ?>
                    <?php foreach ($expenses as $expense): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                            <td><?php echo htmlspecialchars($expense['expense_category']); ?></td>
                            <td><?php echo htmlspecialchars($expense['description']); ?></td>
                            <td><?php echo format_currency($expense['amount']); ?></td>
                            <td><?php echo htmlspecialchars($expense['user_id'] ?? 'System'); ?></td>
                            <td>
                                <a href="delete-expense.php?id=<?php echo $expense['expense_id']; ?>" 
                                   class="btn btn-sm btn-danger"
                                   onclick="return confirm('Are you sure you want to delete this expense?');">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No expenses recorded.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
