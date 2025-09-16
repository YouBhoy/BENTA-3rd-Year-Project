<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

// Determine filter type and period
$filter = $_GET['filter'] ?? 'month';
$period = $_GET['period'] ?? date('Y-m');

$start = '';
$end = '';

switch ($filter) {
    case 'day':
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
            $start = $period . ' 00:00:00';
            $end = $period . ' 23:59:59';
        } else {
            $period = date('Y-m-d');
            $start = $period . ' 00:00:00';
            $end = $period . ' 23:59:59';
        }
        break;
    case 'week':
        if (preg_match('/^\d{4}-W\d{2}$/', $period)) {
            $start = date('Y-m-d', strtotime($period . '1')) . ' 00:00:00';
            $end = date('Y-m-d', strtotime($period . '7')) . ' 23:59:59';
        } else {
            $period = date('Y-\WW');
            $start = date('Y-m-d', strtotime($period . '1')) . ' 00:00:00';
            $end = date('Y-m-d', strtotime($period . '7')) . ' 23:59:59';
        }
        break;
    case 'year':
        if (preg_match('/^\d{4}$/', $period)) {
            $start = $period . '-01-01 00:00:00';
            $end = $period . '-12-31 23:59:59';
        } else {
            $period = date('Y');
            $start = $period . '-01-01 00:00:00';
            $end = $period . '-12-31 23:59:59';
        }
        break;
    case 'month':
    default:
        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            $start = $period . '-01 00:00:00';
            $end = date('Y-m-t', strtotime($start)) . ' 23:59:59';
        } else {
            $period = date('Y-m');
            $start = $period . '-01 00:00:00';
            $end = date('Y-m-t', strtotime($start)) . ' 23:59:59';
        }
        break;
}

// Income: sum of transactions in period
$qIncome = $pdo->prepare('SELECT COALESCE(SUM(total_amount),0) AS total FROM transactions WHERE user_id = ? AND created_at >= ? AND created_at <= ?');
$qIncome->execute([$uid, $start, $end]);
$income = (float)$qIncome->fetchColumn();

// Expenses: sum of expenses in period
$qExpenses = $pdo->prepare('SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id = ? AND expense_date >= ? AND expense_date <= ?');
$qExpenses->execute([$uid, substr($start, 0, 10), substr($end, 0, 10)]);
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
        <h1>Financial Reports</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="items.php" class="btn">Inventory</a>
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="expenses.php" class="btn">Expenses</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <form method="get" style="margin-bottom:16px;display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
        <label>Filter Type
            <select name="filter" onchange="updatePeriodInput()">
                <option value="day" <?php echo $filter === 'day' ? 'selected' : ''; ?>>Day</option>
                <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Week</option>
                <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Month</option>
                <option value="year" <?php echo $filter === 'year' ? 'selected' : ''; ?>>Year</option>
            </select>
        </label>
        <label id="period-label">Period
            <input type="date" name="period" id="period-input" value="<?php echo $filter === 'day' ? $period : ($filter === 'month' ? $period . '-01' : ($filter === 'year' ? $period . '-01-01' : '')); ?>">
        </label>
        <button type="submit">Generate</button>
    </form>

    <script>
    function updatePeriodInput() {
        const filter = document.querySelector('select[name="filter"]').value;
        const input = document.getElementById('period-input');
        const label = document.getElementById('period-label');
        
        switch(filter) {
            case 'day':
                input.type = 'date';
                input.name = 'period';
                label.textContent = 'Date:';
                break;
            case 'week':
                input.type = 'week';
                input.name = 'period';
                label.textContent = 'Week:';
                break;
            case 'month':
                input.type = 'month';
                input.name = 'period';
                label.textContent = 'Month:';
                break;
            case 'year':
                input.type = 'number';
                input.name = 'period';
                input.min = '2020';
                input.max = '2030';
                label.textContent = 'Year:';
                break;
        }
    }
    </script>

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
        input,select,button{padding:8px;border:1px solid #ccc;border-radius:6px}
        select{background:#fff}
        .metric{min-width:220px;padding:12px;border:1px solid #eee;border-radius:8px;background:#fafafa}
        .metric .label{color:#666;font-size:14px}
        .metric .value{font-size:22px;font-weight:bold}
        label{display:flex;flex-direction:column;gap:4px;font-weight:500}
    </style>
</body>
</html>


