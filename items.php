<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

// Fetch items for current user
$stmt = $pdo->prepare('SELECT id, name, sku, price, stock, created_at, updated_at FROM items WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$uid]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header style="display:flex;justify-content:space-between;align-items:center;margin:20px 0;">
        <h1>Inventory</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="transaction_new.php" class="btn">New Sale</a>
            <a href="expenses.php" class="btn">Expenses</a>
            <a href="reports.php" class="btn">Reports</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <div style="margin-bottom:16px;">
        <a class="btn" href="item_new.php">Add Item</a>
    </div>

    <div class="card">
        <table class="table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="5" style="text-align:center;color:#777;">No items yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?php echo e($it['name']); ?></td>
                        <td><?php echo e($it['sku'] ?? ''); ?></td>
                        <td>₱<?php echo number_format((float)$it['price'], 2); ?></td>
                        <td><?php echo (int)$it['stock']; ?></td>
                        <td>
                            <a class="btn btn-small" href="item_edit.php?id=<?php echo (int)$it['id']; ?>">Edit</a>
                            <form method="post" action="item_delete.php" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                <button class="btn btn-small btn-danger" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="assets/app.js"></script>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;max-width:980px;margin:20px auto;padding:0 16px}
        .btn{background:#1f7aec;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;border:0;display:inline-block}
        .btn-small{padding:6px 10px;font-size:14px}
        .btn-outline{background:#fff;color:#1f7aec;border:1px solid #1f7aec}
        .btn-danger{background:#e22;color:#fff}
        .card{background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden}
        .table{width:100%;border-collapse:collapse}
        .table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left}
        .table th{background:#fafafa}
    </style>
</body>
</html>


