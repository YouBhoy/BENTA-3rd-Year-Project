<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $date = $_POST['expense_date'] ?? '';

    if ($category === '') { $errors[] = 'Category is required'; }
    if ($amount <= 0) { $errors[] = 'Amount must be greater than 0'; }
    if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { $errors[] = 'Valid date is required'; }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO expenses (user_id, category, description, amount, expense_date) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$uid, $category, $description ?: null, $amount, $date]);
        header('Location: expenses.php');
        exit;
    }
}

// Fetch recent expenses
$stmt = $pdo->prepare('SELECT id, category, description, amount, expense_date, created_at FROM expenses WHERE user_id = ? ORDER BY expense_date DESC, created_at DESC LIMIT 100');
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expenses - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header style="display:flex;justify-content:space-between;align-items:center;margin:20px 0;">
        <h1>Expenses</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="items.php" class="btn">Inventory</a>
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="reports.php" class="btn">Reports</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <section class="card" style="padding:16px;margin-bottom:16px;">
        <h2 style="margin-top:0;">Add Expense</h2>
        <?php if ($errors): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo e($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            <label>Category
                <input type="text" name="category" required>
            </label>
            <label>Date
                <input type="date" name="expense_date" required>
            </label>
            <label style="grid-column: span 2;">Description (optional)
                <input type="text" name="description">
            </label>
            <label>Amount
                <input type="number" step="0.01" name="amount" min="0.01" required>
            </label>
            <div style="display:flex;align-items:end;gap:8px;">
                <button type="submit">Add</button>
            </div>
        </form>
    </section>

    <section class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Description</th>
                        <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="4" style="text-align:center;color:#777;">No expenses recorded.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?php echo e($r['expense_date']); ?></td>
                        <td><?php echo e($r['category']); ?></td>
                        <td><?php echo e($r['description'] ?? ''); ?></td>
                            <td>₱<?php echo number_format((float)$r['amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>


</body>
</html>


