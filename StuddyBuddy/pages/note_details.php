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


/* Prices */
$price_cents = (int)$note['price_cents'];
$user_pays_cents = $price_cents;            // buyer pays listed price
$fee_cents  = fee_5pct($price_cents);       // platform fee (deducted from seller)
$price_rs   = number_format($price_cents / 100, 2);
$fee_rs     = number_format($fee_cents   / 100, 2);
$total_rs   = number_format($user_pays_cents / 100, 2);

/* Build a safe file URL for preview */
$fp = $note['file_path'] ?? '';
// If your uploader puts files in /pages/uploads, uncomment the next line:
// $fp = (strpos($fp, '/') !== false) ? $fp : ('uploads/' . $fp);
$file_url = htmlspecialchars($fp);

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
                <?= strtoupper(htmlspecialchars($note['ext'] ?: 'PDF')) ?>
              </span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                <?= htmlspecialchars($note['level_name']) ?>
              </span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
              <?= htmlspecialchars($note['title']) ?>
            </h1>
            <p class="text-gray-600">
              <?= nl2br(htmlspecialchars($note['description'])) ?>
            </p>
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
            <?php if ($note['ext'] === 'pdf'): ?>
              <!-- Very simple preview: show first 3 pages using the browser PDF viewer -->
              <div class="space-y-4">
                <div class="rounded border border-gray-200 overflow-hidden">
                  <iframe src="<?= $file_url ?>#page=1&zoom=page-width" class="w-full" style="height: 480px;"></iframe>
                </div>
                <div class="rounded border border-gray-200 overflow-hidden">
                  <iframe src="<?= $file_url ?>#page=2&zoom=page-width" class="w-full" style="height: 480px;"></iframe>
                </div>
                <div class="rounded border border-gray-200 overflow-hidden">
                  <iframe src="<?= $file_url ?>#page=3&zoom=page-width" class="w-full" style="height: 480px;"></iframe>
                </div>
              </div>
              <p class="mt-3 text-xs text-gray-500">Preview shows only the first 3 pages. Full document available after purchase.</p>
            <?php else: ?>
              <div class="border border-dashed border-gray-300 rounded-lg p-6 text-sm text-gray-600">
                Preview is available for PDF files only. This file is <strong><?= strtoupper(htmlspecialchars($note['ext'])) ?></strong>.
              </div>
            <?php endif; ?>
          </div>
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
        <!-- (Optional) You can compute real totals from DB later -->
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
          Note: The platform fee is deducted from the seller's earnings. You pay the listed price.
        </p>
        <button class="w-full bg-blue-600 text-white py-3 px-4 rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 mb-3"
                onclick="showPurchaseModal()">Purchase Now</button>
        <button class="w-full bg-white py-2 px-4 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 flex items-center justify-center">
          <i class="fas fa-heart mr-2"></i> Add to Wishlist
        </button>
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

<!-- Success Modal -->
<div id="successModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
    <div class="p-6">
      <div class="text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
          <i class="fas fa-check text-green-600"></i>
        </div>
        <h3 class="mt-4 text-lg font-medium text-gray-900">Purchase Successful!</h3>
        <p class="mt-2 text-sm text-gray-500">Your material has been purchased and is now available for download.</p>
        <div class="mt-6">
          <a href="<?= $file_url ?>" class="inline-flex justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Download Material
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// very small helpers for your existing modals
function showPurchaseModal(){ document.getElementById('purchaseModal').classList.remove('hidden'); }
function closePurchaseModal(){ document.getElementById('purchaseModal').classList.add('hidden'); }
function completePurchase(){
  // TODO: POST to purchases table, validate payment, then:
  closePurchaseModal();
  document.getElementById('successModal').classList.remove('hidden');
}
function closeSuccessModal(){ document.getElementById('successModal').classList.add('hidden'); }
</script>

<?php include __DIR__ . '/footer.php'; ?>

