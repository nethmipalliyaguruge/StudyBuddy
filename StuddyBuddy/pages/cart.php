<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$title = "My Cart - StudyBuddy APIIT";

// items in session
$ids = $_SESSION['cart'] ?? [];
$items = [];
$total_price_cents = 0;
$total_fee_cents   = 0;
$total_pay_cents   = 0;

if ($ids) {
  $in = implode(',', array_fill(0,count($ids),'?'));
  $sql = "
    SELECT 
      n.id, n.title, n.price_cents, n.file_path,
      u.full_name AS seller_name,
      m.title AS module_title,
      l.name  AS level_name,
      s.name  AS school_name,
      LOWER(SUBSTRING_INDEX(n.file_path,'.',-1)) AS ext
    FROM notes n
    JOIN users   u ON u.id = n.seller_id
    JOIN modules m ON m.id = n.module_id
    JOIN levels  l ON l.id = m.level_id
    JOIN schools s ON s.id = l.school_id
    WHERE n.id IN ($in)
  ";
  $stmt = $pdo->prepare($sql);
  $stmt->execute($ids);
  $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

  foreach ($items as $it) {
    $price = (int)$it['price_cents'];
    $fee   = fee_5pct($price);          // your helper
    $pay   = $price + $fee;

    $total_price_cents += $price;
    $total_fee_cents   += $fee;
    $total_pay_cents   += $pay;
  }
}

include __DIR__ . '/header.php';
?>
<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-foreground">My Cart</h1>
    <p class="text-sm text-muted-foreground">Review your items and proceed to checkout</p>
  </div>

  <?php if ($m = flash('ok')): ?>
    <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Items -->
    <section class="lg:col-span-2">
      <div class="bg-card border border-border rounded-lg overflow-hidden">
        <div class="px-4 py-3 border-b border-border">
          <h3 class="text-lg font-medium">Cart Items (<?= count($items) ?>)</h3>
        </div>
        <?php if (!$items): ?>
          <div class="p-6 text-sm text-muted-foreground">Your cart is empty.</div>
        <?php else: ?>
          <ul class="divide-y divide-border">
            <?php foreach ($items as $it): 
              $price = (int)$it['price_cents'];
              $fee   = fee_5pct($price);
              $pay   = $price + $fee;
            ?>
              <li class="p-4 flex items-start justify-between gap-4">
                <div>
                  <div class="flex items-center gap-2 text-xs mb-1">
                    <span class="px-2 py-0.5 rounded-full bg-secondary text-secondary-foreground"><?= htmlspecialchars($it['school_name']) ?></span>
                    <span class="px-2 py-0.5 rounded-full bg-accent/20"><?= strtoupper(htmlspecialchars($it['ext'] ?: 'PDF')) ?></span>
                    <span class="text-muted-foreground"><?= htmlspecialchars($it['level_name']) ?></span>
                  </div>
                  <p class="font-medium"><?= htmlspecialchars($it['title']) ?></p>
                  <p class="text-sm text-muted-foreground">By <?= htmlspecialchars($it['seller_name']) ?> • <?= htmlspecialchars($it['module_title']) ?></p>
                </div>
                <div class="text-right">
                  <div class="text-sm text-muted-foreground">Price: LKR <?= number_format($price/100,2) ?></div>
                  <div class="text-sm text-muted-foreground">Fee (5%): LKR <?= number_format($fee/100,2) ?></div>
                  <div class="font-semibold">Total: LKR <?= number_format($pay/100,2) ?></div>
                  <form method="post" action="remove_from_cart.php" class="mt-2">
                    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                    <input type="hidden" name="note_id" value="<?= (int)$it['id'] ?>">
                    <button class="text-red-600 text-sm hover:underline"><i class="fas fa-times mr-1"></i>Remove</button>
                  </form>
                </div>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </section>

    <!-- Summary -->
    <aside>
      <div class="bg-card border border-border rounded-lg p-5 sticky top-24">
        <h3 class="text-lg font-medium mb-4">Order Summary</h3>
        <div class="space-y-2 text-sm">
          <div class="flex justify-between"><span>Materials</span><span>LKR <?= number_format($total_price_cents/100,2) ?></span></div>
          <div class="flex justify-between"><span>Platform Fee (5%)</span><span>LKR <?= number_format($total_fee_cents/100,2) ?></span></div>
          <div class="flex justify-between pt-2 border-t border-border font-semibold">
            <span>Total to Pay</span><span>LKR <?= number_format($total_pay_cents/100,2) ?></span>
          </div>
        </div>

        <div class="mt-5 space-y-2">
          <form method="post" action="checkout.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <button <?= $items ? '' : 'disabled' ?>
              class="w-full bg-primary text-white py-2 rounded-md text-sm hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed">
              Proceed to Checkout
            </button>
          </form>

          <form method="post" action="clear_cart.php">
            <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
            <button <?= $items ? '' : 'disabled' ?>
              class="w-full bg-muted text-foreground py-2 rounded-md text-sm hover:bg-muted/80 disabled:opacity-50 disabled:cursor-not-allowed">
              Clear Cart
            </button>
          </form>
        </div>
      </div>
    </aside>
  </div>
</main>
<?php include __DIR__ . '/footer.php'; ?>
