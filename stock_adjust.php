<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
require_post();

$pdo = get_pdo();
$uid = current_user_id();

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';
$quantity = (int)($_POST['quantity'] ?? 0);

if ($id <= 0 || $quantity <= 0 || !in_array($action, ['add', 'remove'])) {
    redirect('items.php');
}

try {
    $pdo->beginTransaction();
    
    // Lock the item for update and verify ownership
    $stmt = $pdo->prepare('SELECT id, stock FROM items WHERE id = ? AND user_id = ? FOR UPDATE');
    $stmt->execute([$id, $uid]);
    $item = $stmt->fetch();
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    $currentStock = (int)$item['stock'];
    $newStock = $currentStock;
    
    if ($action === 'add') {
        $newStock = $currentStock + $quantity;
    } else if ($action === 'remove') {
        if ($currentStock < $quantity) {
            throw new Exception('Cannot remove more stock than available');
        }
        $newStock = $currentStock - $quantity;
    }
    
    // Update stock and timestamp
    $stmt = $pdo->prepare('UPDATE items SET stock = ?, last_stock_update = NOW() WHERE id = ? AND user_id = ?');
    $stmt->execute([$newStock, $id, $uid]);
    
    $pdo->commit();
    
    // Redirect back to inventory with success message
    $_SESSION['stock_success'] = ucfirst($action) . 'ed ' . $quantity . ' units successfully';
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['stock_error'] = $e->getMessage();
}

redirect('items.php');
