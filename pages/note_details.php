<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login(); // only logged-in users can view details

$note_id = (int)($_GET['id'] ?? 0);
if ($note_id <= 0) { http_response_code(404); exit('Note not found.'); }

$sql = "
  SELECT
    n.id, n.title, n.description, n.file_path, n.price_cents, n.created_at, n.is_approved,
    u.id   AS seller_id, u.full_name AS seller_name,
    m.id   AS module_id, m.title     AS module_title,
    l.id   AS level_id,  l.name      AS level_name,
    s.id   AS school_id, s.name      AS school_name,
    LOWER(SUBSTRING_INDEX(n.file_path,'.',-1)) AS ext
  FROM notes n
  LEFT JOIN users   u ON u.id = n.seller_id
  LEFT JOIN modules m ON m.id = n.module_id
  LEFT JOIN levels  l ON l.id = m.level_id
  LEFT JOIN schools s ON s.id = l.school_id
  WHERE n.id = ?
  LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$note_id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$note) {
  http_response_code(404);
  exit('Note not found.');
}

/* ===================== Pricing ===================== */
$price_cents = (int)$note['price_cents'];
$fee_cents   = fee_5pct($price_cents);       // 5% platform fee
$total_cents = $price_cents + $fee_cents;    // buyer pays price + fee

$price_rs = number_format($price_cents / 100, 2);
$fee_rs   = number_format($fee_cents   / 100, 2);
$total_rs = number_format($total_cents / 100, 2);

/* ===================== File URL & Existence ===================== */
$raw_fp = trim((string)($note['file_path'] ?? ''));

// Where uploads live on disk and on the web:
$UPLOAD_DIR = realpath(__DIR__ . '/uploads');               // e.g. /.../pages/uploads
$scriptDir  = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/'); // e.g. /NoteSharing/pages
$UPLOAD_WEB = $scriptDir . '/uploads/';                     // e.g. /NoteSharing/pages/uploads/

// Build the public URL:
if ($raw_fp === '') {
  $file_url = '';
} elseif (preg_match('#^https?://#i', $raw_fp) || str_starts_with($raw_fp, '/')) {
  $file_url = $raw_fp;
} else {
  $file_url = $UPLOAD_WEB . rawurlencode($raw_fp);
}

// Check file existence on disk
$exists_on_disk = false;
if ($raw_fp !== '' && is_dir($UPLOAD_DIR)) {
  $disk_candidate = $UPLOAD_DIR . DIRECTORY_SEPARATOR . $raw_fp;
  $exists_on_disk = file_exists($disk_candidate);
}

// Normalize extension (for info chips only)
$ext = strtolower((string)($note['ext'] ?? ''));

/* ===================== Preview Images (user-provided) ===================== */
$preview_dir_disk = __DIR__ . "/uploads/previews/note_" . $note_id;
$preview_dir_web  = $UPLOAD_WEB . "previews/note_" . $note_id . '/';

$preview_images = [];
if (is_dir($preview_dir_disk)) {
  // load page1, page2, page3 if present (jpg/png/webp)
  foreach ([1,2,3] as $i) {
    foreach (['jpg','jpeg','png','webp'] as $imgExt) {
      $fn = "page{$i}.{$imgExt}";
      $disk = $preview_dir_disk . '/' . $fn;
      if (is_file($disk)) {
        $preview_images[] = $preview_dir_web . $fn;
        break;
      }
    }
  }
}

/* Page title for header */
$title = "Note • " . htmlspecialchars($note['title']);
include __DIR__ . '/header.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <!-- Breadcrumb -->
  <nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="flex items-center space-x-4">
      <li>
        <a href="index.php" class="text-gray-400 hover:text-gray-500">
          <i class="fas fa-home"></i><span class="sr-only">Home</span>
        </a>
      </li>
      <li>
        <div class="flex items-center">
          <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
          <a href="materials.php" class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">Browse</a>
        </div>
      </li>
      <li>
        <div class="flex items-center">
          <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
          <span class="ml-4 text-sm font-medium text-gray-500"><?= htmlspecialchars($note['school_name']) ?></span>
        </div>
      </li>
      <li>
        <div class="flex items-center">
          <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
          <span class="ml-4 text-sm font-medium text-gray-500"><?= htmlspecialchars($note['level_name']) ?></span>
        </div>
      </li>
      <li>
        <div class="flex items-center">
          <i class="fas fa-chevron-right text-gray-400 text-xs"></i>
          <span class="ml-4 text-sm font-medium text-gray-900"><?= htmlspecialchars($note['title']) ?></span>
        </div>
      </li>
    </ol>
  </nav>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Main Content -->
    <div class="lg:col-span-2">
      <!-- Title + Info -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <div class="flex justify-between items-start">
          <div>
            <div class="flex items-center space-x-2 mb-2">
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <?= htmlspecialchars($note['school_name']) ?>
              </span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                <?= strtoupper(htmlspecialchars($ext ?: 'PDF')) ?>
              </span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <?= htmlspecialchars($note['level_name']) ?>
              </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-1">
              <?= htmlspecialchars($note['title']) ?>
            </h1>
            <p class="text-sm text-gray-500">Module: <?= htmlspecialchars($note['module_title']) ?></p>
          </div>
          <div class="text-right">
            <p class="text-3xl font-bold text-gray-900">LKR <?= $price_rs ?></p>
            <p class="text-sm text-gray-500">One-time purchase</p>
          </div>
        </div>
      </div>

      <!-- Preview -->
      <div class="bg-white shadow rounded-lg mb-6">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex" aria-label="Tabs">
            <button class="tab-btn border-blue-500 text-blue-600 whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm" data-tab="preview">
              <i class="fas fa-eye mr-2"></i> Preview
            </button>
          </nav>
        </div>

        <div class="p-6">
          <div id="preview" class="tab-content active">
            <?php if (count($preview_images) === 3): ?>
              <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                <div class="flex items-center">
                  <i class="fas fa-info-circle text-yellow-600 mr-2"></i>
                  <span class="text-sm text-yellow-800">Preview shows only the first 3 pages (as provided by the seller).</span>
                </div>
              </div>

              <div class="space-y-4">
                <?php foreach ($preview_images as $i => $imgUrl): ?>
                  <div class="border border-gray-200 rounded-lg overflow-hidden bg-white relative">
                    <div class="absolute top-2 left-2 bg-blue-600 text-white px-2 py-1 rounded text-xs">Page <?= $i+1 ?></div>
                    <div class="absolute top-2 right-2 bg-red-100 text-red-800 px-2 py-1 rounded text-xs">Preview Only</div>
                    <img src="<?= htmlspecialchars($imgUrl) ?>" alt="Preview page <?= $i+1 ?>" class="w-full h-auto max-h-[38rem] object-contain select-none pointer-events-none">
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="border border-dashed border-gray-300 rounded-lg p-6 text-center">
                <i class="fas fa-image text-4xl text-gray-400 mb-4"></i>
                <p class="text-sm text-gray-600">No preview images were provided by the seller.</p>
                <p class="text-xs text-gray-500 mt-1">Full document available after purchase.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Description -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Description</h3>
        <div class="prose prose-sm max-w-none text-gray-700">
          <?= nl2br(htmlspecialchars($note['description'])) ?>
        </div>
      </div>
    </div>

    <!-- Sidebar -->
    <div class="lg:col-span-1">
      <!-- Seller -->
      <div class="bg-white shadow rounded-lg p-6 mb-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Seller Information</h3>
        <div class="flex items-center mb-4">
          <div class="ml-0">
            <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($note['seller_name']) ?></p>
            <p class="text-sm text-gray-500">Verified seller</p>
          </div>
        </div>
        <div class="space-y-3">
          <div class="flex justify-between"><span class="text-sm text-gray-600">Module</span><span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($note['module_title']) ?></span></div>
          <div class="flex justify-between"><span class="text-sm text-gray-600">Level</span><span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($note['level_name']) ?></span></div>
          <div class="flex justify-between"><span class="text-sm text-gray-600">School</span><span class="text-sm font-medium text-gray-900"><?= htmlspecialchars($note['school_name']) ?></span></div>
        </div>
      </div>

      <!-- Purchase -->
      <div class="bg-white shadow rounded-lg p-6 mb-6 sticky top-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Purchase Details</h3>
        <div class="space-y-3 mb-4">
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Material Price</span>
            <span class="text-sm font-medium text-gray-900">LKR <?= $price_rs ?></span>
          </div>
          <div class="flex justify-between">
            <span class="text-sm text-gray-600">Platform Fee (5%)</span>
            <span class="text-sm font-medium text-gray-900">LKR <?= $fee_rs ?></span>
          </div>
          <div class="flex justify-between pt-3 border-t border-gray-200">
            <span class="text-base font-medium text-gray-900">Total</span>
            <span class="text-base font-bold text-gray-900">LKR <?= $total_rs ?></span>
          </div>
        </div>
        <p class="text-xs text-gray-500 mb-4">
          You pay the Material Price + Platform Fee. (The fee is deducted from the seller's payout.)
        </p>
        <button class="w-full bg-primary text-white py-3 px-4 rounded-md text-sm font-medium hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-3"
                onclick="showPurchaseModal()">Purchase Now</button>

        <form method="post" action="add_to_cart.php" class="mt-2">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="note_id" value="<?= (int)$note['id'] ?>">
          <input type="hidden" name="from" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
          <button class="w-full inline-flex items-center justify-center gap-2 bg-secondary text-secondary-foreground py-2 px-4 rounded-md text-sm hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring">
            <i class="fas fa-cart-plus"></i> Add to Cart
          </button>
        </form>

        <div class="mt-4 pt-4 border-t border-gray-200">
          <div class="flex items-center text-sm text-gray-600">
            <i class="fas fa-shield-alt text-green-500 mr-2"></i>
            <span>Secure purchase with money-back guarantee</span>
          </div>
          <div class="flex items-center text-sm text-gray-600 mt-2">
            <i class="fas fa-download text-blue-500 mr-2"></i>
            <span>Instant download after purchase</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>

<!-- Purchase Modal -->
<div id="purchaseModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
    <div class="p-6">
      <div class="flex justify-between items-start">
        <h3 class="text-lg font-medium text-gray-900">Confirm Purchase</h3>
        <button type="button" class="text-gray-400 hover:text-gray-500" onclick="closePurchaseModal()"><i class="fas fa-times"></i></button>
      </div>

      <div class="mt-4">
        <p class="text-sm text-gray-900 font-medium"><?= htmlspecialchars($note['title']) ?></p>
        <p class="text-sm text-gray-500">By <?= htmlspecialchars($note['seller_name']) ?></p>
        <div class="mt-4 bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-2">Payment Summary</h4>
          <div class="space-y-2">
            <div class="flex justify-between"><span class="text-sm text-gray-600">Material Price</span><span class="text-sm font-medium text-gray-900">LKR <?= $price_rs ?></span></div>
            <div class="flex justify-between"><span class="text-sm text-gray-600">Platform Fee (5%)</span><span class="text-sm font-medium text-gray-900">LKR <?= $fee_rs ?></span></div>
            <div class="flex justify-between pt-2 border-t border-gray-200"><span class="text-base font-medium text-gray-900">Total</span><span class="text-base font-bold text-gray-900">LKR <?= $total_rs ?></span></div>
          </div>
        </div>
        <div class="mt-6">
          <label for="paymentMethod" class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
          <select id="paymentMethod" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <option>Credit Card</option>
            <option>Debit Card</option>
            <option>Mobile Wallet</option>
          </select>
        </div>
      </div>

      <div class="mt-6 flex justify-end space-x-3">
        <button type="button" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="closePurchaseModal()">Cancel</button>
        <button type="button" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" onclick="completePurchase()">Complete Purchase</button>
      </div>
    </div>
  </div>
</div>

<script>
// Basic modal helpers
function showPurchaseModal(){ document.getElementById('purchaseModal').classList.remove('hidden'); }
function closePurchaseModal(){ document.getElementById('purchaseModal').classList.add('hidden'); }
function completePurchase(){
  // TODO: integrate payment + purchase record
  closePurchaseModal();
  alert('Purchase flow placeholder');
}
</script>

<?php include __DIR__ . '/footer.php'; ?>
