<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/lib/utils.php';
require_once __DIR__ . '/lib/auth.php';

ensure_authenticated();
require_post();

$pdo = get_pdo();
$uid = current_user_id();
$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // Only delete if it belongs to the user and not referenced in transaction_items
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('SELECT id FROM items WHERE id = ? AND user_id = ? FOR UPDATE');
        $stmt->execute([$id, $uid]);
        $exists = $stmt->fetch();
        if ($exists) {
            $ref = $pdo->prepare('SELECT COUNT(*) AS c FROM transaction_items WHERE item_id = ?');
            $ref->execute([$id]);
            $row = $ref->fetch();
            if ((int)$row['c'] === 0) {
                $del = $pdo->prepare('DELETE FROM items WHERE id = ? AND user_id = ?');
                $del->execute([$id, $uid]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
    }
}

redirect('items.php');


