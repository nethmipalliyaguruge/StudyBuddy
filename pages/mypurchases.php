<?php 
require_once __DIR__."/../config/config.php";
require_once __DIR__."/../config/helpers.php";
require_login();

$user = current_user();
$buyer_id = (int)$user['id'];

/* ===================== STATS ===================== */
$statsSql = "
  SELECT 
    COUNT(*) AS total_orders,
    COALESCE(SUM(CASE WHEN status IN ('paid','completed') THEN total_paid_cents ELSE 0 END),0) AS total_spent_cents,
    COALESCE(SUM(CASE WHEN status IN ('paid','completed') THEN 1 ELSE 0 END),0)              AS completed_orders
  FROM purchases
  WHERE buyer_id = ?
";
$st = $pdo->prepare($statsSql);
$st->execute([$buyer_id]);
$stats = $st->fetch(PDO::FETCH_ASSOC) ?: ['total_orders'=>0,'total_spent_cents'=>0,'completed_orders'=>0];

$total_orders      = (int)$stats['total_orders'];
$total_spent_rs    = number_format(((int)$stats['total_spent_cents'])/100, 2);
$completed_orders  = (int)$stats['completed_orders'];

/* ===================== ORDERS LIST ===================== */
$listSql = "
  SELECT 
    p.id              AS order_id,
    p.created_at      AS ordered_at,
    p.total_paid_cents,
    p.status,
    n.id              AS note_id,
    n.title           AS note_title,
    u.full_name       AS seller_name
  FROM purchases p
  JOIN notes n   ON n.id = p.note_id
  LEFT JOIN users u ON u.id = n.seller_id
  WHERE p.buyer_id = ?
  ORDER BY p.created_at DESC, p.id DESC
";
$st2 = $pdo->prepare($listSql);
$st2->execute([$buyer_id]);
$orders = $st2->fetchAll(PDO::FETCH_ASSOC);

$title = "My Purchases - StudyBuddy APIIT";
include 'header.php'; 
?>
<!-- Top summary cards -->
<section class="bg-slate-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-6">
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
      <!-- Total Orders -->
      <div class="card rounded-xl border border-border bg-gradient-to-br from-blue-50 to-white p-5">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-blue-600/10 text-blue-600 flex items-center justify-center">
            <i class="fa-solid fa-cart-shopping"></i>
          </div>
          <div>
            <p class="text-slate-500 text-sm">Total Orders</p>
            <p class="text-2xl font-bold"><?= (int)$total_orders ?></p>
          </div>
        </div>
      </div>

      <!-- Total Spent -->
      <div class="card rounded-xl border border-border bg-gradient-to-br from-emerald-50 to-white p-5">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-emerald-600/10 text-emerald-700 flex items-center justify-center">
            <i class="fa-solid fa-dollar-sign"></i>
          </div>
          <div>
            <p class="text-slate-500 text-sm">Total Spent</p>
            <p class="text-2xl font-bold">LKR <?= $total_spent_rs ?></p>
          </div>
        </div>
      </div>

      <!-- Completed Orders -->
      <div class="card rounded-xl border border-border bg-gradient-to-br from-purple-50 to-white p-5">
        <div class="flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-purple-600/10 text-purple-700 flex items-center justify-center">
            <i class="fa-regular fa-clipboard"></i>
          </div>
          <div>
            <p class="text-slate-500 text-sm">Completed Orders</p>
            <p class="text-2xl font-bold"><?= (int)$completed_orders ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Order History -->
<main class="max-w-6xl mx-auto px-4 sm:px-6 pb-16">
  <div class="mb-4">
    <h2 class="text-xl sm:text-2xl font-extrabold">Order History</h2>
    <p class="text-slate-600">View and download your purchased study materials</p>
  </div>

  <div class="rounded-2xl border border-border overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="bg-slate-50 text-slate-600">
          <tr class="text-left text-xs font-semibold uppercase tracking-wide">
            <th class="px-6 py-3">Order ID</th>
            <th class="px-6 py-3">Material</th>
            <th class="px-6 py-3">Seller</th>
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">Amount</th>
            <th class="px-6 py-3">Status</th>
            <th class="px-6 py-3 text-right">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border bg-white text-sm">
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="7" class="px-6 py-10 text-center text-slate-500">
                You haven’t purchased any materials yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $o): 
              $oid        = (int)$o['order_id'];
              $noteId     = (int)$o['note_id'];
              $amount_rs  = number_format(((int)$o['total_paid_cents'])/100, 2);
              $ordered_at = $o['ordered_at'] ? date('Y-m-d', strtotime($o['ordered_at'])) : '—';
              $status     = strtolower(trim((string)$o['status']));
              $is_done    = in_array($status, ['paid','completed'], true);
            ?>
            <tr>
              <td class="px-6 py-4">
                <span class="text-primary font-medium">ORD-<?= str_pad((string)$oid, 3, '0', STR_PAD_LEFT) ?></span>
              </td>
              <td class="px-6 py-4"><?= htmlspecialchars($o['note_title'] ?? '—') ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($o['seller_name'] ?? '—') ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($ordered_at) ?></td>
              <td class="px-6 py-4">LKR <?= $amount_rs ?></td>
              <td class="px-6 py-4">
                <?php if ($is_done): ?>
                  <span class="inline-flex items-center rounded-full bg-primary text-white px-2.5 py-1 text-xs font-semibold">Completed</span>
                <?php elseif (in_array($status, ['processing','pending','created'], true)): ?>
                  <span class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2.5 py-1 text-xs font-semibold ring-1 ring-amber-200">Processing</span>
                <?php else: ?>
                  <span class="inline-flex items-center rounded-full bg-slate-100 text-slate-700 px-2.5 py-1 text-xs font-semibold">Unknown</span>
                <?php endif; ?>
              </td>
              <td class="px-6 py-4 text-right">
                <?php if ($is_done): ?>
                  <a href="download.php?id=<?= $noteId ?>" class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 hover:bg-slate-50">
                    <i class="fa-solid fa-download"></i> Download
                  </a>
                <?php else: ?>
                  <button class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 text-slate-400 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-download"></i> Download
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include 'footer.php'; ?>
