<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$u = current_user();

$sql = "
  SELECT p.id AS purchase_id, p.created_at, p.total_paid_cents,
         n.id AS note_id, n.title, n.file_path,
         u.full_name AS seller_name
  FROM purchases p
  JOIN notes n  ON n.id = p.note_id
  JOIN users u  ON u.id = n.seller_id
  WHERE p.buyer_id = ? AND p.status='paid'
  ORDER BY p.created_at DESC, p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([(int)$u['id']]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = "My Purchases - StudyBuddy APIIT";
include 'header.php';
?>
<main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">
  <h1 class="text-2xl font-bold mb-6">My Purchases</h1>

  <?php if (isset($_GET['just_bought'])): ?>
    <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
      Purchase completed! Your downloads are ready below.
    </div>
  <?php endif; ?>

  <div class="rounded-2xl border border-border overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="bg-slate-50 text-slate-600">
          <tr class="text-left text-xs font-semibold uppercase tracking-wide">
            <th class="px-6 py-3">Date</th>
            <th class="px-6 py-3">Material</th>
            <th class="px-6 py-3">Seller</th>
            <th class="px-6 py-3">Amount</th>
            <th class="px-6 py-3 text-right">Download</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border bg-white text-sm">
          <?php if (!$rows): ?>
            <tr><td colspan="5" class="px-6 py-6 text-center text-slate-500">No purchases yet.</td></tr>
          <?php endif; ?>

          <?php foreach ($rows as $r): ?>
            <tr>
              <td class="px-6 py-4"><?= htmlspecialchars(date('Y-m-d', strtotime($r['created_at']))) ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($r['title']) ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($r['seller_name']) ?></td>
              <td class="px-6 py-4">LKR <?= number_format($r['total_paid_cents']/100, 2) ?></td>
              <td class="px-6 py-4 text-right">
                <a class="inline-flex items-center gap-2 rounded-md border border-border px-3 py-1.5 hover:bg-slate-50"
                   href="download.php?note_id=<?= (int)$r['note_id'] ?>">
                  <i class="fa-solid fa-download"></i> Download
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</main>
<?php include 'footer.php'; ?>
