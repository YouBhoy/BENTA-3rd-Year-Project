<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

// Fetch items for current user
$stmt = $pdo->prepare('SELECT id, name, sku, price, stock, created_at, updated_at, last_stock_update FROM items WHERE user_id = ? ORDER BY created_at DESC');
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

    <?php if (isset($_SESSION['stock_success'])): ?>
        <div class="alert alert-success" style="background:#e8f5e8;border:1px solid #4caf50;padding:10px;border-radius:6px;margin:12px 0;">
            <?php echo e($_SESSION['stock_success']); unset($_SESSION['stock_success']); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['stock_error'])): ?>
        <div class="alert alert-error" style="background:#ffe8e8;border:1px solid #ff9b9b;padding:10px;border-radius:6px;margin:12px 0;">
            <?php echo e($_SESSION['stock_error']); unset($_SESSION['stock_error']); ?>
        </div>
    <?php endif; ?>

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
                    <th>Last Stock Update</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$items): ?>
                    <tr><td colspan="6" style="text-align:center;color:#777;">No items yet.</td></tr>
                <?php endif; ?>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><?php echo e($it['name']); ?></td>
                        <td><?php echo e($it['sku'] ?? ''); ?></td>
                        <td>₱<?php echo number_format((float)$it['price'], 2); ?></td>
                        <td><?php echo (int)$it['stock']; ?></td>
                        <td><?php echo $it['last_stock_update'] ? date('M j, Y H:i', strtotime($it['last_stock_update'])) : 'Never'; ?></td>
                        <td>
                            <div style="display:flex;gap:4px;align-items:center;flex-wrap:wrap;">
                                <form method="post" action="stock_adjust.php" style="display:inline-flex;gap:2px;align-items:center;" onsubmit="return confirm('Add stock?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <input type="number" name="quantity" min="1" value="1" style="width:60px;padding:4px;" required>
                                    <button class="btn btn-small btn-success" type="submit">+</button>
                                </form>
                                <form method="post" action="stock_adjust.php" style="display:inline-flex;gap:2px;align-items:center;" onsubmit="return confirm('Remove stock?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="number" name="quantity" min="1" max="<?php echo (int)$it['stock']; ?>" value="1" style="width:60px;padding:4px;" required>
                                    <button class="btn btn-small btn-warning" type="submit">-</button>
                                </form>
                                <a class="btn btn-small" href="item_edit.php?id=<?php echo (int)$it['id']; ?>">Edit</a>
                                <form method="post" action="item_delete.php" style="display:inline;" onsubmit="return confirm('Delete this item?');">
                                    <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                                    <button class="btn btn-small btn-danger" type="submit">Delete</button>
                                </form>
                            </div>
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
        .btn-success{background:#0a6;color:#fff}
        .btn-warning{background:#f80;color:#fff}
        .card{background:#fff;border:1px solid #eee;border-radius:8px;overflow:hidden}
        .table{width:100%;border-collapse:collapse}
        .table th,.table td{padding:10px;border-bottom:1px solid #eee;text-align:left}
        .table th{background:#fafafa}
    </style>
</body>
</html>


