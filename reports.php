<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

// Determine month selection (YYYY-MM)
$ym = $_GET['ym'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
    $ym = date('Y-m');
}

// Compute first and last day
$start = $ym . '-01';
$end = date('Y-m-t', strtotime($start));

// Income: sum of transactions in month
$qIncome = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE user_id = ? AND created_at >= ? AND created_at <= ?');
$qIncome->execute([$uid, $start . ' 00:00:00', $end . ' 23:59:59']);
$income = (float)$qIncome->fetchColumn();

// Expenses: sum of expenses in month
$qExpenses = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id = ? AND expense_date >= ? AND expense_date <= ?');
$qExpenses->execute([$uid, $start, $end]);
$expenses = (float)$qExpenses->fetchColumn();

$net = $income - $expenses;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - BENTA</title>

</head>
<body>
    <header>
        <h1>Monthly Report</h1>
    <nav>
            <a href="items.php" class="btn">Inventory</a>
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="expenses.php" class="btn">Expenses</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <form method="get">
        <label>Month
            <input type="month" name="ym" value="<?php echo e($ym); ?>">
        </label>
        <button type="submit">Generate</button>
    </form>

    <section class="card">
    <div>
            <div class="metric"><div class="label">Total Income</div><div class="value">₱<?php echo number_format($income, 2); ?></div></div>
            <div class="metric"><div class="label">Total Expenses</div><div class="value">₱<?php echo number_format($expenses, 2); ?></div></div>
            <div class="metric"><div class="label">Net Revenue</div><div class="value">₱<?php echo number_format($net, 2); ?></div></div>
        </div>
    </section>

    