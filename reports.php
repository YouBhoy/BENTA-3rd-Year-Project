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
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header style="display:flex;justify-content:space-between;align-items:center;margin:20px 0;">
        <h1>Monthly Report</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="items.php" class="btn">Inventory</a>
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="expenses.php" class="btn">Expenses</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <form method="get" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;">
        <label>Month
            <input type="month" name="ym" value="<?php echo e($ym); ?>">
        </label>
        <button type="submit">Generate</button>
    </form>

    <section class="card" style="padding:16px;">
        <div style="display:flex;gap:20px;flex-wrap:wrap;">
            <div class="metric"><div class="label">Total Income</div><div class="value">₱<?php echo number_format($income, 2); ?></div></div>
            <div class="metric"><div class="label">Total Expenses</div><div class="value">₱<?php echo number_format($expenses, 2); ?></div></div>
            <div class="metric"><div class="label">Net Revenue</div><div class="value" style="color:<?php echo $net>=0?'#0a0':'#a00'; ?>;">₱<?php echo number_format($net, 2); ?></div></div>
        </div>
    </section>

    <style>
        body{font-family:Arial,Helvetica,sans-serif;max-width:980px;margin:20px auto;padding:0 16px}
        .btn{background:#1f7aec;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;border:0;display:inline-block}
        .btn-outline{background:#fff;color:#1f7aec;border:1px solid #1f7aec}
        .card{background:#fff;border:1px solid #eee;border-radius:8px}
        input,button{padding:8px;border:1px solid #ccc;border-radius:6px}
        .metric{min-width:220px;padding:12px;border:1px solid #eee;border-radius:8px;background:#fafafa}
        .metric .label{color:#666;font-size:14px}
        .metric .value{font-size:22px;font-weight:bold}
    </style>
</body>
</html>


