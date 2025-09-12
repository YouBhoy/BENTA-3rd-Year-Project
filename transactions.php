<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

$stmt = $pdo->prepare('SELECT id, total_amount, created_at FROM transactions WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$uid]);
$rows = $stmt->fetchAll();

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
        <h1>Sales</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="items.php" class="btn">Inventory</a>
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="reports.php" class="btn">Reports</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$rows): ?>
                    <tr><td colspan="3" style="text-align:center;color:#777;">No sales yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>#<?php echo (int)$r['id']; ?></td>
                        <td><?php echo e($r['created_at']); ?></td>
                        <td>₱<?php echo number_format((float)$r['total_amount'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <style>
        body{font-family:Arial,Helvetica,sans-serif;max-width:980px;margin:20px auto;padding:0 16px}
        .btn{background:#1f7aec;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;border:0;display:inline-block}
        .btn-outline{background:#fff;color:#1f7aec;border:1px solid #1f7aec}
        .card{background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden}
        .table{width:100%;border-collapse:collapse}
        .table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left}
        .table th{background:#fafafa}
    </style>
</body>
</html>


