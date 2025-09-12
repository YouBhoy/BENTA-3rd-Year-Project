<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
$pdo = get_pdo();
$uid = current_user_id();

// Load items for selection
$stmt = $pdo->prepare('SELECT id, name, price, stock FROM items WHERE user_id = ? ORDER BY name ASC');
$stmt->execute([$uid]);
$items = $stmt->fetchAll();

$errors = [];
$success = false;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $itemIds = $_POST['item_id'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    $lines = [];
    for ($i = 0; $i < count($itemIds); $i++) {
        $iid = (int)$itemIds[$i];
        $qty = (int)$quantities[$i];
        if ($iid > 0 && $qty > 0) {
            $lines[] = ['item_id' => $iid, 'quantity' => $qty];
        }
    }

    if (!$lines) {
        $errors[] = 'Add at least one item with quantity.';
    }

    if (!$errors) {
        try {
            $pdo->beginTransaction();

            // Lock selected items for update and validate stock
            $total = 0.00;
            $resolved = [];
            foreach ($lines as $ln) {
                $q = $pdo->prepare('SELECT id, name, price, stock FROM items WHERE id = ? AND user_id = ? FOR UPDATE');
                $q->execute([$ln['item_id'], $uid]);
                $it = $q->fetch();
                if (!$it) {
                    throw new Exception('Invalid item selected.');
                }
                if ((int)$it['stock'] < (int)$ln['quantity']) {
                    throw new Exception('Insufficient stock for ' . $it['name']);
                }
                $lineTotal = (float)$it['price'] * (int)$ln['quantity'];
                $total += $lineTotal;
                $resolved[] = [
                    'item_id' => (int)$it['id'],
                    'name' => $it['name'],
                    'unit_price' => (float)$it['price'],
                    'quantity' => (int)$ln['quantity'],
                    'line_total' => $lineTotal,
                ];
            }

            // Insert transaction
            $ins = $pdo->prepare('INSERT INTO transactions (user_id, total_amount) VALUES (?, ?)');
            $ins->execute([$uid, $total]);
            $txId = (int)$pdo->lastInsertId();

            // Insert lines and deduct stock
            $insLine = $pdo->prepare('INSERT INTO transaction_items (transaction_id, item_id, quantity, unit_price, line_total) VALUES (?, ?, ?, ?, ?)');
            $updStock = $pdo->prepare('UPDATE items SET stock = stock - ? WHERE id = ? AND user_id = ?');
            foreach ($resolved as $r) {
                $insLine->execute([$txId, $r['item_id'], $r['quantity'], $r['unit_price'], $r['line_total']]);
                $updStock->execute([$r['quantity'], $r['item_id'], $uid]);
            }

            $pdo->commit();
            $success = true;
            header('Location: transactions.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $errors[] = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Sale - BENTA</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <header style="display:flex;justify-content:space-between;align-items:center;margin:20px 0;">
        <h1>New Sale</h1>
        <nav style="display:flex;gap:10px;align-items:center;">
            <a href="items.php" class="btn">Inventory</a>
            <a href="transactions.php" class="btn">Sales</a>
            <a href="logout.php" class="btn btn-outline">Logout</a>
        </nav>
    </header>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?php echo e($err); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="post" id="sale-form">
        <div id="lines"></div>
        <div style="margin:10px 0;">
            <button type="button" class="btn" onclick="addLine()">Add Item</button>
        </div>
        <div class="card" style="padding:12px;margin:12px 0;display:flex;justify-content:space-between;">
            <strong>Total:</strong>
            <strong id="total">₱0.00</strong>
        </div>
        <button type="submit">Save Sale</button>
        <a class="btn btn-outline" href="items.php">Cancel</a>
    </form>

    <template id="line-template">
        <div class="line" style="display:flex;gap:8px;align-items:center;margin-bottom:8px;">
            <select name="item_id[]" class="item-select" required>
                <option value="">Select item</option>
                <?php foreach ($items as $it): ?>
                    <option value="<?php echo (int)$it['id']; ?>" data-price="<?php echo number_format((float)$it['price'], 2, '.', ''); ?>" data-stock="<?php echo (int)$it['stock']; ?>">
                        <?php echo e($it['name']); ?> (₱<?php echo number_format((float)$it['price'],2); ?>, stock: <?php echo (int)$it['stock']; ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="quantity[]" class="qty" min="1" value="1" required style="width:90px">
            <span class="line-total">₱0.00</span>
            <button type="button" class="btn btn-outline" onclick="removeLine(this)">Remove</button>
        </div>
    </template>

    <script>
    const lines = document.getElementById('lines');
    const tmpl = document.getElementById('line-template');

    function peso(n){ return '₱' + Number(n).toFixed(2); }
    function addLine(){
        const clone = tmpl.content.cloneNode(true);
        lines.appendChild(clone);
        recalc();
    }
    function removeLine(btn){
        btn.closest('.line').remove();
        recalc();
    }
    function recalc(){
        let total = 0;
        document.querySelectorAll('.line').forEach(line => {
            const sel = line.querySelector('.item-select');
            const qtyEl = line.querySelector('.qty');
            const price = parseFloat(sel.selectedOptions[0]?.dataset.price || '0');
            const stock = parseInt(sel.selectedOptions[0]?.dataset.stock || '0');
            let qty = parseInt(qtyEl.value || '0');
            if (qty > stock) { qty = stock; qtyEl.value = stock; }
            const lt = price * qty;
            line.querySelector('.line-total').textContent = peso(lt);
            total += lt;
        });
        document.getElementById('total').textContent = peso(total);
    }
    document.addEventListener('change', e => {
        if (e.target.matches('.item-select,.qty')) recalc();
    });
    // initialize with one line
    addLine();
    </script>

    <style>
        body{font-family:Arial,Helvetica,sans-serif;max-width:900px;margin:20px auto;padding:0 16px}
        .btn{background:#1f7aec;color:#fff;padding:8px 12px;border-radius:6px;text-decoration:none;border:0;display:inline-block}
        .btn-outline{background:#fff;color:#1f7aec;border:1px solid #1f7aec}
        .card{background:#fff;border:1px solid #eee;border-radius:8px}
        .alert-error{background:#ffe8e8;border:1px solid #ff9b9b;padding:10px;border-radius:6px;margin-bottom:12px}
        select,input{padding:8px;border:1px solid #ccc;border-radius:6px}
    </style>
</body>
</html>


