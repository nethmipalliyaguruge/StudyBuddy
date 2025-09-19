<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();
check_csrf();

$buyer = current_user();
$ids = $_SESSION['cart'] ?? [];

if (!$ids) {
  flash('err','Your cart is empty.');
  header('Location: cart.php'); exit;
}

$in = implode(',', array_fill(0,count($ids),'?'));
$sql = "SELECT id, seller_id, price_cents FROM notes WHERE id IN ($in)";
$stmt = $pdo->prepare($sql);
$stmt->execute($ids);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pdo->beginTransaction();
try {
  $ins = $pdo->prepare("
    INSERT INTO purchases
      (buyer_id, note_id, base_price_cents, fee_cents, total_paid_cents, seller_earnings_cents, status, created_at)
    VALUES
      (?,?,?,?,?,?, 'completed', NOW())
  ");

  foreach ($notes as $n) {
    $base = (int)$n['price_cents'];
    $fee  = fee_5pct($base);
    $tot  = $base + $fee;
    $earn = $base; // you deduct fee from seller after settlement

    $ins->execute([$buyer['id'], (int)$n['id'], $base, $fee, $tot, $earn]);
  }

  $pdo->commit();
  unset($_SESSION['cart']);
  flash('ok','Purchase successful! You can download your files from My Purchases.');
  header('Location: mypurchases.php');
} catch (Throwable $e) {
  $pdo->rollBack();
  error_log('Checkout error: '.$e->getMessage());
  flash('err','Failed to complete purchase.');
  header('Location: cart.php');
}
