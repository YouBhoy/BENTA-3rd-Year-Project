<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

$errors = [];
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $sku = trim($_POST['sku'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);

    if ($name === '') { $errors[] = 'Name is required'; }
    if ($price < 0) { $errors[] = 'Price must be non-negative'; }
    if ($stock < 0) { $errors[] = 'Stock must be non-negative'; }

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO items (user_id, name, sku, price, stock, last_stock_update) VALUES (?, ?, ?, ?, ?, NOW())');
        $stmt->execute([$uid, $name, $sku ?: null, $price, $stock]);
        redirect('items.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Item - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <h1>Add Item</h1>
    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post">
        <label>Name
            <input type="text" name="name" required>
        </label>
        <label>SKU (optional)
            <input type="text" name="sku">
        </label>
        <label>Price
            <input type="number" step="0.01" name="price" min="0" value="0" required>
        </label>
        <label>Stock
            <input type="number" name="stock" min="0" value="0" required>
        </label>
        <button type="submit">Save</button>
        <a class="btn btn-outline" href="items.php">Cancel</a>
    </form>
    <style>
        body{font-family:Arial,Helvetica,sans-serif;max-width:600px;margin:30px auto;padding:0 16px}
        form{display:flex;flex-direction:column;gap:12px}
        label{display:flex;flex-direction:column;gap:6px}
        input{padding:10px;border:1px solid #ccc;border-radius:6px}
        button{padding:10px 14px;border:0;background:#1f7aec;color:#fff;border-radius:6px;cursor:pointer}
        .btn{background:#1f7aec;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;border:0;display:inline-block}
        .btn-outline{background:#fff;color:#1f7aec;border:1px solid #1f7aec}
        .alert-error{background:#ffe8e8;border:1px solid #ff9b9b;padding:10px;border-radius:6px;margin-bottom:12px}
    </style>
</body>
</html>


