<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$buyer      = current_user();
$buyer_id   = (int)$buyer['id'];

// -------- make sure no stray output corrupts JSON ----------
ini_set('display_errors', 0);        // don’t print notices/warnings into output
ini_set('html_errors', 0);

// Helper to flush any buffered output before we emit JSON
function _ob_kill() {
  while (ob_get_level() > 0) { ob_end_clean(); }
}

// Decide if this call wants JSON (single-note / AJAX) or a normal redirect (cart)
$accept   = $_SERVER['HTTP_ACCEPT'] ?? '';
$xhr      = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
$noteOne  = (int)($_POST['note_id'] ?? $_GET['note_id'] ?? 0);
$WANTS_JSON = $noteOne > 0 || stripos($accept, 'application/json') !== false || $xhr !== '';

function respond_and_exit(array $payload, bool $asJson) {
  if ($asJson) {
    _ob_kill();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload);
  } else {
    $to = $payload['redirect'] ?? 'mypurchases.php';
    header('Location: ' . $to);
  }
  exit;
}

session_start();

// CSRF check for POST only
if ($_SERVER['REQUEST_METHOD'] === 'POST') { check_csrf(); }

// Collect note IDs to buy
$noteIds = [];
if ($noteOne > 0) {
  $noteIds = [$noteOne];                 // modal purchase
} else {
  $noteIds = array_map('intval', $_SESSION['cart'] ?? []);  // cart purchase
}

$noteIds = array_values(array_unique(array_filter($noteIds)));
if (!$noteIds) {
  respond_and_exit(['ok'=>false,'error'=>'Cart empty or no note specified','redirect'=>'cart.php?err=empty'],$WANTS_JSON);
}

// Load notes
$place = implode(',', array_fill(0, count($noteIds), '?'));
$stmt  = $pdo->prepare("SELECT id, seller_id, price_cents FROM notes WHERE id IN ($place) AND is_approved=1");
$stmt->execute($noteIds);
$notes = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $notes[(int)$r['id']] = $r;
if (!$notes) {
  respond_and_exit(['ok'=>false,'error'=>'No valid notes found','redirect'=>'cart.php?err=invalid'],$WANTS_JSON);
}

// Already owned?
$stmt = $pdo->prepare("SELECT note_id FROM purchases WHERE buyer_id=? AND status='paid' AND note_id IN ($place)");
$params = array_merge([$buyer_id], $noteIds);
$stmt->execute($params);
$owned = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN, 0)));

$report = [];

$pdo->beginTransaction();
try {
  $ins = $pdo->prepare("
    INSERT INTO purchases
      (buyer_id, note_id, base_price_cents, fee_cents, total_paid_cents, seller_earnings_cents, status, created_at)
    VALUES
      (?, ?, ?, ?, ?, ?, 'paid', NOW())
  ");

  foreach ($noteIds as $nid) {
    if (isset($owned[$nid])) { $report[]=['note_id'=>$nid,'already_owned'=>true]; continue; }
    if (!isset($notes[$nid])) { $report[]=['note_id'=>$nid,'error'=>'not_found']; continue; }

    $price = (int)$notes[$nid]['price_cents'];
    $fee   = (int)fee_5pct($price);
    $total = $price + $fee;
    $seller_earn = $price;

    $ins->execute([$buyer_id, $nid, $price, $fee, $total, $seller_earn]);
    $report[] = ['note_id'=>$nid,'created'=>true];
  }

  $pdo->commit();
} catch (Throwable $e) {
  $pdo->rollBack();
  respond_and_exit(['ok'=>false,'error'=>'Checkout failed','redirect'=>'cart.php?err=checkout'],$WANTS_JSON);
}

// Remove purchased from cart
if (!empty($_SESSION['cart'])) {
  $purchasedSet = [];
  foreach ($report as $it) if (!empty($it['created'])) $purchasedSet[(int)$it['note_id']] = true;
  $_SESSION['cart'] = array_values(array_filter(array_map('intval', $_SESSION['cart']), fn($id)=>!isset($purchasedSet[$id])));
}

// Success
respond_and_exit([
  'ok'       => true,
  'items'    => $report,
  'redirect' => 'mypurchases.php?just_bought=1'
], $WANTS_JSON);
