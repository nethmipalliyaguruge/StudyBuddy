<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/helpers.php';

$title = 'Browse Materials';

/* -------------------- Inputs -------------------- */
$sort   = $_GET['sort'] ?? 'newest';   // future use
$page   = max(1, (int)($_GET['page'] ?? 1));
$per    = 9;                           // 9 notes per page
$offset = ($page - 1) * $per;

// School (array of IDs)
$school_ids = array_map('intval', $_GET['school'] ?? []);

// Level (we use logical keys L4/L5/L6 then map to ids)
$level_keys = array_values(array_intersect(['L4','L5','L6'], $_GET['level'] ?? []));
$LEVEL_MAP  = ['L4'=>[1,4,7], 'L5'=>[2,5,8], 'L6'=>[3,6,9]];
$level_ids  = [];
foreach ($level_keys as $k) { $level_ids = array_merge($level_ids, $LEVEL_MAP[$k]); }
$level_ids = array_map('intval', array_values(array_unique($level_ids)));

// File types (from file extension of notes.file_path)
$file_types = array_values(array_intersect(['pdf','doc','docx','ppt','pptx'], $_GET['ft'] ?? []));

// Price range (LKR → cents)
$min_rs = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : null;
$max_rs = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : null;
$min_cents = $min_rs !== null ? (int)round($min_rs*100) : null;
$max_cents = $max_rs !== null ? (int)round($max_rs*100) : null;

/* -------------------- Data for filters -------------------- */
$schools = $pdo->query("SELECT id, name FROM schools ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* Only show Level 4/5/6 in the UI */
$level_ui = [
  ['key'=>'L4', 'label'=>'Level 4'],
  ['key'=>'L5', 'label'=>'Level 5'],
  ['key'=>'L6', 'label'=>'Level 6'],
];

/* -------------------- Build WHERE -------------------- */
$where  = ["n.is_approved = 1"];
$params = [];

if (!empty($school_ids)) {
  $place = implode(',', array_fill(0, count($school_ids), '?'));
  $where[] = "l.school_id IN ($place)";
  array_push($params, ...$school_ids);
}

if (!empty($level_ids)) {
  $place = implode(',', array_fill(0, count($level_ids), '?'));
  $where[] = "m.level_id IN ($place)";
  array_push($params, ...$level_ids);
}

if (!empty($file_types)) {
  // Match by extension of file_path
  $place = implode(',', array_fill(0, count($file_types), '?'));
  $where[] = "LOWER(SUBSTRING_INDEX(n.file_path,'.',-1)) IN ($place)";
  array_push($params, ...$file_types);
}

if ($min_cents !== null) { $where[] = "n.price_cents >= ?"; $params[] = $min_cents; }
if ($max_cents !== null) { $where[] = "n.price_cents <= ?"; $params[] = $max_cents; }

$where_sql = 'WHERE ' . implode(' AND ', $where);

$countSql = "
  SELECT COUNT(*) AS c
  FROM notes n
  JOIN modules m ON m.id = n.module_id
  JOIN levels  l ON l.id = m.level_id
  JOIN schools s ON s.id = l.school_id
  JOIN users   u ON u.id = n.seller_id
  $where_sql
";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

// Calculate pagination properly
$totalPages = max(1, (int)ceil($total / $per));
$page = min($page, $totalPages);
$offset = ($page - 1) * $per;

/* ---------------- Sorting ---------------- */
$orderBy = "n.created_at DESC";
if ($sort === 'price_asc')  $orderBy = "n.price_cents ASC";
if ($sort === 'price_desc') $orderBy = "n.price_cents DESC";

/* -------------------- Fetch notes -------------------- */
$sql = "
SELECT
  n.id, n.title, n.description, n.file_path, n.price_cents, n.created_at,
  m.id AS module_id, m.title AS module_title,
  l.id AS level_id, l.name AS level_name,
  s.id AS school_id, s.name AS school_name,
  LOWER(SUBSTRING_INDEX(n.file_path,'.',-1)) AS ext
FROM notes n
JOIN modules m ON m.id = n.module_id
JOIN levels  l ON l.id = m.level_id
JOIN schools s ON s.id = l.school_id
$where_sql
ORDER BY $orderBy
LIMIT $per OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Note: Removed the duplicate count query since we already have $total */

/* -------------------- Helpers for checked state -------------------- */
function isCheckedKey(array $arr, string $key): string {
  return in_array($key, $arr, true) ? 'checked' : '';
}
function isChecked(array $arr, $val): string {
  return in_array((string)$val, array_map('strval',$arr), true) ? 'checked' : '';
}

include __DIR__ . '/header.php';
?>

<main class="bg-muted text-foreground">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <div class="mb-8">
      <h1 class="text-2xl font-bold text-foreground">Browse Study Materials</h1>
      <p class="mt-1 text-sm text-muted-foreground">Find and purchase high-quality study materials for your courses</p>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Filters Sidebar -->
      <aside class="w-full lg:w-1/4">
        <form class="bg-card border border-border rounded-lg p-6 sticky top-24" method="get">
          <h2 class="text-lg font-medium text-foreground mb-4">Filters</h2>

          <!-- keep sort + reset page when submitting filters -->
          <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
          <input type="hidden" name="page" value="1">

          <!-- School (checkboxes) -->
          <div class="mb-6">
            <h3 class="text-sm font-medium text-foreground mb-3">School</h3>
            <div class="space-y-2">
              <?php foreach ($schools as $s): ?>
                <?php $isSelected = isChecked($school_ids, $s['id']); ?>
                <label class="filter-option flex items-center justify-between w-full p-2 text-sm font-medium border rounded-lg cursor-pointer transition-colors <?= $isSelected ? 'bg-green-100 border-green-300 text-green-800' : 'text-foreground/80 border-border hover:bg-muted' ?>">
                  <span><?= htmlspecialchars($s['name']) ?></span>
                  <input type="checkbox"
                         name="school[]"
                         value="<?= (int)$s['id'] ?>"
                         class="sr-only filter-checkbox"
                         <?= $isSelected ?>>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Level (only L4/L5/L6, mapped) -->
          <div class="mb-6">
            <h3 class="text-sm font-medium text-foreground mb-3">Level</h3>
            <div class="space-y-2">
              <?php foreach ($level_ui as $L): ?>
                <?php $isSelected = isCheckedKey($level_keys, $L['key']); ?>
                <label class="filter-option flex items-center justify-between w-full p-2 text-sm font-medium border rounded-lg cursor-pointer transition-colors <?= $isSelected ? 'bg-green-100 border-green-300 text-green-800' : 'text-foreground/80 border-border hover:bg-muted' ?>">
                  <span><?= htmlspecialchars($L['label']) ?></span>
                  <input type="checkbox"
                         name="level[]"
                         value="<?= $L['key'] ?>"
                         class="sr-only filter-checkbox"
                         <?= $isSelected ?>>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Price -->
          <div class="mb-6">
            <h3 class="text-sm font-medium text-foreground mb-3">Price Range (LKR)</h3>
            <div class="flex items-center space-x-2">
              <input type="number" step="0.01" min="0" name="min" value="<?= $min_rs!==null?htmlspecialchars($min_rs):'' ?>"
                     class="w-full px-3 py-2 border border-input rounded-md text-sm focus:ring-2 focus:ring-ring focus:border-ring" placeholder="Min">
              <span class="text-muted-foreground">-</span>
              <input type="number" step="0.01" min="0" name="max" value="<?= $max_rs!==null?htmlspecialchars($max_rs):'' ?>"
                     class="w-full px-3 py-2 border border-input rounded-md text-sm focus:ring-2 focus:ring-ring focus:border-ring" placeholder="Max">
            </div>
          </div>

          <!-- File Type -->
          <div class="mb-6">
            <h3 class="text-sm font-medium text-foreground mb-3">File Type</h3>
            <?php $ftAll = ['pdf'=>'PDF','doc'=>'DOC','docx'=>'DOCX','ppt'=>'PPT','pptx'=>'PPTX'];
            foreach ($ftAll as $k=>$lbl): ?>
              <?php $isSelected = in_array($k,$file_types,true); ?>
              <label class="filter-option flex items-center justify-between w-full p-2 text-sm font-medium border rounded-lg cursor-pointer mb-2 transition-colors <?= $isSelected ? 'bg-green-100 border-green-300 text-green-800' : 'text-foreground/80 border-border hover:bg-muted' ?>">
                <span><?= $lbl ?></span>
                <input type="checkbox"
                       name="ft[]"
                       value="<?= $k ?>"
                       class="sr-only filter-checkbox"
                       <?= $isSelected ? 'checked' : '' ?>>
              </label>
            <?php endforeach; ?>
          </div>

          <button class="w-full bg-primary text-white py-2 px-4 rounded-md hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring">
            Apply Filters
          </button>
          <button type="button"
                  onclick="window.location.href='materials.php'"
                  class="w-full mt-2 bg-muted text-foreground py-2 px-4 rounded-md hover:bg-muted-foreground/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring">
            Remove Filters
          </button>
        </form>
      </aside>

      <!-- Results -->
      <section class="w-full lg:w-3/4">
        <div class="bg-card border border-border rounded-lg p-4 mb-6">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
            <div>
              <h1 class="text-xl font-bold text-foreground">Study Materials</h1>
              <p class="text-sm text-muted-foreground">Showing <?= count($notes) ?> of <?= $total ?> results</p>
            </div>
            <div>
              <select id="sortSelect" class="block w-full pl-3 pr-10 py-2 text-sm border border-input focus:outline-none focus:ring-ring focus:border-ring rounded-md">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Sort by: Newest</option>
                <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Materials Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          <?php if (!$notes): ?>
            <div class="col-span-full text-sm text-muted-foreground border border-dashed border-border rounded-md p-6">
              No materials match your filters.
            </div>
          <?php endif; ?>

          <?php foreach ($notes as $n): ?>
            <div class="bg-card border border-border rounded-lg overflow-hidden card-hover h-full flex flex-col">
              <div class="p-5 flex-1 flex flex-col">
                <div class="flex justify-between items-start mb-3">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-secondary text-secondary-foreground">
                    <?= htmlspecialchars($n['school_name']) ?>
                  </span>
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-accent/20 text-foreground">
                    <?= strtoupper(htmlspecialchars($n['ext'] ?: 'PDF')) ?>
                  </span>
                </div>
                <h3 class="text-lg font-medium text-foreground mb-2"><?= htmlspecialchars($n['title']) ?></h3>
                <p class="text-sm text-muted-foreground mb-4 flex-1 line-clamp-3"><?= htmlspecialchars($n['description']) ?></p>
                <div class="flex items-center justify-between mb-4 mt-auto">
                  <div>
                    <p class="text-xs text-muted-foreground"><?= htmlspecialchars($n['level_name']) ?></p>
                  </div>
                  <p class="text-lg font-bold text-foreground">LKR <?= number_format($n['price_cents']/100, 2) ?></p>
                </div>
                <a class="w-full inline-block text-center bg-primary text-white py-2 px-4 rounded-md text-sm hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring mt-auto"
                   href="note_details.php?id=<?= (int)$n['id'] ?>">
                  View Details
                </a>
                <form method="post" action="add_to_cart.php" class="mt-2">
                  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                  <input type="hidden" name="note_id" value="<?= (int)$n['id'] ?>">
                  <input type="hidden" name="from" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                  <button class="w-full inline-flex items-center justify-center gap-2 bg-secondary text-secondary-foreground py-2 px-4 rounded-md text-sm hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-ring">
                    <i class="fas fa-cart-plus"></i> Add to Cart
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-8 flex items-center justify-between">
          <?php
            $prevDisabled = $page <= 1 ? 'pointer-events-none opacity-50' : '';
            $nextDisabled = $page >= $totalPages ? 'pointer-events-none opacity-50' : '';
            
            // Build query string for pagination links
            $currentParams = $_GET;
            $currentParams['page'] = max(1, $page-1);
            $prevUrl = '?' . http_build_query($currentParams);
            
            $currentParams['page'] = min($totalPages, $page+1);
            $nextUrl = '?' . http_build_query($currentParams);
          ?>
          <a class="relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium rounded-md text-foreground bg-white hover:bg-muted <?= $prevDisabled ?>"
             href="<?= $prevUrl ?>">Previous</a>

          <div class="flex items-center space-x-2">
            <p class="text-sm text-muted-foreground">
              Page <span class="font-medium"><?= $page ?></span> of <span class="font-medium"><?= $totalPages ?></span>
            </p>
            <span class="text-sm text-muted-foreground">•</span>
            <p class="text-sm text-muted-foreground">
              Showing <?= min($per, $total - (($page - 1) * $per)) ?> of <?= $total ?> results
            </p>
          </div>

          <a class="relative inline-flex items-center px-4 py-2 border border-border text-sm font-medium rounded-md text-foreground bg-white hover:bg-muted <?= $nextDisabled ?>"
             href="<?= $nextUrl ?>">Next</a>
        </div>

      </section>
    </div>
  </div>
</main>

<script>
  // Sort control: change only the 'sort' param, reset page=1, preserve the rest.
  document.getElementById('sortSelect')?.addEventListener('change', function () {
    const u = new URL(location.href);
    u.searchParams.set('sort', this.value);
    u.searchParams.set('page', '1');
    location.href = u.toString();
  });

  // Add interactive filter styling
  document.addEventListener('DOMContentLoaded', function() {
    // Get all filter checkboxes
    const filterCheckboxes = document.querySelectorAll('.filter-checkbox');
    
    filterCheckboxes.forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        const label = this.closest('.filter-option');
        
        if (this.checked) {
          // Remove unselected classes
          label.classList.remove('text-foreground/80', 'border-border', 'hover:bg-muted');
          // Add selected classes
          label.classList.add('bg-green-100', 'border-green-300', 'text-green-800');
        } else {
          // Remove selected classes
          label.classList.remove('bg-green-100', 'border-green-300', 'text-green-800');
          // Add unselected classes
          label.classList.add('text-foreground/80', 'border-border', 'hover:bg-muted');
        }
      });
    });
  });
</script>

<?php include __DIR__ . '/footer.php'; ?>

