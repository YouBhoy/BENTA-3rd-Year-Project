<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

// Determine filter type and period
$filter = $_GET['filter'] ?? 'all';
$period = $_GET['period'] ?? '';

$whereClause = 'WHERE user_id = ?';
$params = [$uid];

switch ($filter) {
    case 'day':
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
            $whereClause .= ' AND DATE(created_at) = ?';
            $params[] = $period;
        } else {
            $period = date('Y-m-d');
            $whereClause .= ' AND DATE(created_at) = ?';
            $params[] = $period;
        }
        break;
    case 'week':
        if (preg_match('/^\d{4}-W\d{2}$/', $period)) {
            $start = date('Y-m-d', strtotime($period . '1'));
            $end = date('Y-m-d', strtotime($period . '7'));
            $whereClause .= ' AND DATE(created_at) >= ? AND DATE(created_at) <= ?';
            $params[] = $start;
            $params[] = $end;
        } else {
            $period = date('Y-\WW');
            $start = date('Y-m-d', strtotime($period . '1'));
            $end = date('Y-m-d', strtotime($period . '7'));
            $whereClause .= ' AND DATE(created_at) >= ? AND DATE(created_at) <= ?';
            $params[] = $start;
            $params[] = $end;
        }
        break;
    case 'month':
        if (preg_match('/^\d{4}-\d{2}$/', $period)) {
            $start = $period . '-01';
            $end = date('Y-m-t', strtotime($start));
            $whereClause .= ' AND DATE(created_at) >= ? AND DATE(created_at) <= ?';
            $params[] = $start;
            $params[] = $end;
        } else {
            $period = date('Y-m');
            $start = $period . '-01';
            $end = date('Y-m-t', strtotime($start));
            $whereClause .= ' AND DATE(created_at) >= ? AND DATE(created_at) <= ?';
            $params[] = $start;
            $params[] = $end;
        }
        break;
}

$stmt = $pdo->prepare("SELECT id, total_amount, created_at FROM transactions $whereClause ORDER BY created_at DESC");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate totals for the filtered period
$totalSales = array_sum(array_column($transactions, 'total_amount'));
$totalTransactions = count($transactions);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header style="display:flex;justify-content:space-between;align-items:center;margin:20px 0;">
        <h1>Sales History</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="items.php" class="btn">Inventory</a>
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="reports.php" class="btn">Reports</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <form method="get" style="margin-bottom:16px;display:flex;gap:8px;align-items:end;flex-wrap:wrap;">
        <label>Filter
            <select name="filter" onchange="updatePeriodInput()">
                <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Sales</option>
                <option value="day" <?php echo $filter === 'day' ? 'selected' : ''; ?>>Day</option>
                <option value="week" <?php echo $filter === 'week' ? 'selected' : ''; ?>>Week</option>
                <option value="month" <?php echo $filter === 'month' ? 'selected' : ''; ?>>Month</option>
            </select>
        </label>
        <label id="period-label" style="<?php echo $filter === 'all' ? 'display:none;' : ''; ?>">
            <span id="period-text">Period</span>
            <input type="date" name="period" id="period-input" value="<?php echo $filter === 'day' ? $period : ($filter === 'month' ? $period . '-01' : ($filter === 'week' ? '' : '')); ?>">
        </label>
        <button type="submit">Filter</button>
    </form>
    
    <?php if ($filter !== 'all'): ?>
        <div style="background:#f0f8ff;padding:8px;border-radius:4px;margin-bottom:16px;font-size:14px;">
            <strong>Filter Active:</strong> <?php echo ucfirst($filter); ?> 
            <?php if ($period): ?>
                - <?php echo e($period); ?>
            <?php endif; ?>
            <br><strong>Found:</strong> <?php echo $totalTransactions; ?> transactions, Total: ₱<?php echo number_format($totalSales, 2); ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding:16px;margin-bottom:16px;display:flex;gap:20px;flex-wrap:wrap;">
        <div class="metric">
            <div class="label">Total Sales</div>
            <div class="value">₱<?php echo number_format($totalSales, 2); ?></div>
        </div>
        <div class="metric">
            <div class="label">Transactions</div>
            <div class="value"><?php echo $totalTransactions; ?></div>
        </div>
        <div class="metric">
            <div class="label">Average Sale</div>
            <div class="value">₱<?php echo $totalTransactions > 0 ? number_format($totalSales / $totalTransactions, 2) : '0.00'; ?></div>
        </div>
    </div>

    <div class="card">
        <?php if (!$transactions): ?>
            <div style="text-align:center;color:#777;padding:40px;">No sales found for the selected period.</div>
        <?php else: ?>
            <?php foreach ($transactions as $tx): ?>
                <div class="transaction-card">
                    <div class="transaction-header">
                        <div>
                            <strong>Transaction #<?php echo (int)$tx['id']; ?></strong>
                            <span style="color:#666;margin-left:10px;"><?php echo date('M j, Y H:i', strtotime($tx['created_at'])); ?></span>
                        </div>
                        <div class="transaction-total">₱<?php echo number_format((float)$tx['total_amount'], 2); ?></div>
                    </div>
                    
                    <?php
                    // Get transaction items
                    $stmt = $pdo->prepare('SELECT ti.quantity, ti.unit_price, ti.line_total, i.name FROM transaction_items ti JOIN items i ON ti.item_id = i.id WHERE ti.transaction_id = ? ORDER BY ti.id');
                    $stmt->execute([$tx['id']]);
                    $items = $stmt->fetchAll();
                    ?>
                    
                    <div class="transaction-items">
                        <?php foreach ($items as $item): ?>
                            <div class="transaction-item">
                                <span class="item-name"><?php echo e($item['name']); ?></span>
                                <span class="item-qty">× <?php echo (int)$item['quantity']; ?></span>
                                <span class="item-price">@ ₱<?php echo number_format((float)$item['unit_price'], 2); ?></span>
                                <span class="item-total">₱<?php echo number_format((float)$item['line_total'], 2); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
    function updatePeriodInput() {
        const filter = document.querySelector('select[name="filter"]').value;
        const input = document.getElementById('period-input');
        const label = document.getElementById('period-label');
        const text = document.getElementById('period-text');
        
        if (filter === 'all') {
            label.style.display = 'none';
        } else {
            label.style.display = 'flex';
            
            switch(filter) {
                case 'day':
                    input.type = 'date';
                    input.name = 'period';
                    text.textContent = 'Date:';
                    if (!input.value) input.value = new Date().toISOString().split('T')[0];
                    break;
                case 'week':
                    input.type = 'week';
                    input.name = 'period';
                    text.textContent = 'Week:';
                    if (!input.value) {
                        const now = new Date();
                        const year = now.getFullYear();
                        const week = getWeekNumber(now);
                        input.value = year + '-W' + String(week).padStart(2, '0');
                    }
                    break;
                case 'month':
                    input.type = 'month';
                    input.name = 'period';
                    text.textContent = 'Month:';
                    if (!input.value) {
                        const now = new Date();
                        input.value = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
                    }
                    break;
            }
        }
    }
    
    function getWeekNumber(date) {
        const firstDayOfYear = new Date(date.getFullYear(), 0, 1);
        const pastDaysOfYear = (date - firstDayOfYear) / 86400000;
        return Math.ceil((pastDaysOfYear + firstDayOfYear.getDay() + 1) / 7);
    }
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', updatePeriodInput);
    </script>

    <style>
        body{font-family:Arial,Helvetica,sans-serif;max-width:980px;margin:20px auto;padding:0 16px}
        .btn{background:#1f7aec;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;border:0;display:inline-block}
        .btn-outline{background:#fff;color:#1f7aec;border:1px solid #1f7aec}
        .card{background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden}
        .metric{min-width:150px;padding:12px;border:1px solid #eee;border-radius:8px;background:#fafafa;text-align:center}
        .metric .label{color:#666;font-size:14px;margin-bottom:4px}
        .metric .value{font-size:20px;font-weight:bold}
        .transaction-card{border:1px solid #eee;border-radius:8px;margin-bottom:16px;overflow:hidden}
        .transaction-header{background:#f8f9fa;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #eee}
        .transaction-total{font-size:18px;font-weight:bold;color:#1f7aec}
        .transaction-items{padding:12px 16px}
        .transaction-item{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #f0f0f0}
        .transaction-item:last-child{border-bottom:none}
        .item-name{flex:1;font-weight:500}
        .item-qty{color:#666;margin:0 8px}
        .item-price{color:#666;margin:0 8px}
        .item-total{font-weight:bold;color:#0a6}
        input,select,button{padding:8px;border:1px solid #ccc;border-radius:6px}
        label{display:flex;flex-direction:column;gap:4px;font-weight:500}
    </style>
</body>
</html>


