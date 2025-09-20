<?php 
require_once __DIR__."/../config/config.php";
require_once __DIR__."/../config/helpers.php";
require_login();

$u = current_user();

if (!function_exists('money_rs')) {
  function money_rs($cents){ return number_format(((int)$cents)/100, 2); }
}

/* ===================== POST HANDLERS ===================== */
if ($_SERVER['REQUEST_METHOD']==='POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';
  $id     = (int)($_POST['id'] ?? 0);

  try {
    // Ensure ownership
    if ($id) {
      $own = $pdo->prepare("SELECT id, title, is_approved FROM notes WHERE id=? AND seller_id=?");
      $own->execute([$id, (int)$u['id']]);
      $noteData = $own->fetch(PDO::FETCH_ASSOC);
      if (!$noteData) throw new RuntimeException('Not found or not yours.');
    }

    if ($action==='update') {
      $title       = trim($_POST['title'] ?? '');
      $description = trim($_POST['description'] ?? '');
      $module_id   = (int)($_POST['module_id'] ?? 0);
      $price_rs    = (float)($_POST['price_rs'] ?? 0);
      if ($title==='' || !$module_id) throw new RuntimeException('Title & module required.');
      $price_cents = (int)round($price_rs*100);

      $stmt = $pdo->prepare("UPDATE notes 
                               SET title=?, description=?, module_id=?, price_cents=? 
                             WHERE id=? AND seller_id=?");
      $stmt->execute([$title, $description, $module_id, $price_cents, $id, (int)$u['id']]);
      flash('ok','Note updated.');
    } elseif ($action==='delete') {
      // Check if note has been purchased
      $purchaseCheck = $pdo->prepare("SELECT COUNT(*) as purchase_count FROM purchases WHERE note_id = ?");
      $purchaseCheck->execute([$id]);
      $purchaseCount = $purchaseCheck->fetchColumn();
      
      if ($purchaseCount > 0) {
        // Soft delete: mark as inactive instead of deleting
        $stmt = $pdo->prepare("UPDATE notes SET is_approved = -1, title = CONCAT('[DELETED] ', title) WHERE id=? AND seller_id=?");
        $stmt->execute([$id, (int)$u['id']]);
        flash('ok', 'Note "' . htmlspecialchars($noteData['title']) . '" has been deactivated (it cannot be fully deleted because it has been purchased).');
      } else {
        // Hard delete: actually remove from database
        $pdo->prepare("DELETE FROM notes WHERE id=? AND seller_id=?")
            ->execute([$id, (int)$u['id']]);
        flash('ok','Note deleted.');
      }
    } elseif ($action==='restore') {
      // Restore soft-deleted note
      if ((int)$noteData['is_approved'] === -1) {
        // Remove [DELETED] prefix from title if it exists
        $originalTitle = preg_replace('/^\[DELETED\]\s*/', '', $noteData['title']);
        
        $stmt = $pdo->prepare("UPDATE notes SET is_approved = 0, title = ? WHERE id=? AND seller_id=?");
        $stmt->execute([$originalTitle, $id, (int)$u['id']]);
        flash('ok', 'Note "' . htmlspecialchars($originalTitle) . '" has been restored and is pending approval.');
      } else {
        throw new RuntimeException('This note is not deleted and cannot be restored.');
      }
    } else {
      throw new RuntimeException('Invalid action.');
    }
  } catch (Throwable $e) {
    if (strpos($e->getMessage(), 'foreign key constraint') !== false || strpos($e->getMessage(), '1451') !== false) {
      flash('err','Cannot delete this note because it has been purchased by students. The note has been deactivated instead.');
    } else {
      flash('err',$e->getMessage());
    }
  }

  header("Location: mynotes.php"); exit;
}

/* ===================== DATA QUERIES ===================== */
// Show all notes including soft-deleted ones (is_approved = -1)
$notes = $pdo->prepare("
  SELECT 
    n.id, n.title, n.description, n.file_path, n.price_cents, n.is_approved, n.created_at,
    mo.id AS mo_id, mo.title AS module_title,
    l.name AS level_name,
    s.name AS school_name
  FROM notes n
  LEFT JOIN modules mo ON mo.id = n.module_id
  LEFT JOIN levels  l  ON l.id = mo.level_id
  LEFT JOIN schools s ON s.id = l.school_id
  WHERE n.seller_id = ?
  ORDER BY n.created_at DESC, n.id DESC
");
$notes->execute([(int)$u['id']]);
$notes = $notes->fetchAll(PDO::FETCH_ASSOC);

$modules = $pdo->query("
  SELECT mo.id, mo.title AS module_title, l.name AS level_name, s.name AS school_name
  FROM modules mo
  JOIN levels  l ON l.id = mo.level_id
  JOIN schools s ON s.id = l.school_id
  ORDER BY s.name, l.name, mo.title
")->fetchAll(PDO::FETCH_ASSOC);

/* Stats (replace with real sales/ratings when you add those tables) */
$total_notes   = count($notes);
$total_sales   = 0;
$total_earn_rs = 0.00;

$title = "My Notes - StudyBuddy APIIT";
include 'header.php'; 
?>
<body class="bg-muted text-foreground min-h-screen flex flex-col">
  <main class="flex-1 max-w-6xl mx-auto px-4 sm:px-6 py-8">
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex justify-between items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-foreground">My Notes</h1>
        <p class="mt-1 text-sm text-muted-foreground">Manage and track your uploaded study materials</p>
      </div>
      <a href="upload.php">
        <button class="bg-primary text-white px-4 py-2 rounded-md text-sm hover:bg-primary/90">
          <i class="fas fa-plus mr-1"></i> Upload New Note
        </button>
      </a>
    </div>

    <!-- Flash messages -->
    <?php if ($m = flash('ok')): ?>
      <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('err')): ?>
      <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <!-- Stats Overview -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-secondary rounded-md p-3">
            <i class="fas fa-file-alt text-study-primary"></i>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dt class="text-sm font-medium text-muted-foreground truncate">Active Notes</dt>
            <dd class="text-2xl font-semibold text-foreground"><?= number_format($total_notes) ?></dd>
          </div>
        </div>
      </div>
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-secondary rounded-md p-3">
            <i class="fas fa-shopping-cart text-study-primary"></i>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dt class="text-sm font-medium text-muted-foreground truncate">Total Sales</dt>
            <dd class="text-2xl font-semibold text-foreground"><?= number_format($total_sales) ?></dd>
          </div>
        </div>
      </div>
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center">
          <div class="flex-shrink-0 bg-secondary rounded-md p-3">
            <i class="fas fa-coins text-study-primary"></i>
          </div>
          <div class="ml-5 w-0 flex-1">
            <dt class="text-sm font-medium text-muted-foreground truncate">Total Earnings</dt>
            <dd class="text-2xl font-semibold text-foreground"><?= number_format($total_earn_rs,2) ?></dd>
          </div>
        </div>
      </div>
    </div>

    <!-- Notes List -->
    <div class="bg-card border border-border rounded-lg overflow-hidden">
      <div class="px-4 py-5 sm:px-6 border-b border-border">
        <h3 class="text-lg leading-6 font-medium text-foreground">Your Uploaded Notes</h3>
      </div>
      <div class="px-4 py-5 sm:p-6">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-border">
            <thead class="bg-muted">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Title</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Category</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Price</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-muted-foreground uppercase tracking-wider">Status</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-muted-foreground uppercase tracking-wider">Actions</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-border">
              <?php if (!$notes): ?>
                <tr>
                  <td colspan="5" class="px-6 py-6 text-center text-sm text-muted-foreground">
                    You haven't uploaded any notes yet.
                  </td>
                </tr>
              <?php endif; ?>
              <?php foreach ($notes as $n): 
                    $uploaded = date('Y-m-d', strtotime($n['created_at']));
                    $isDeleted = (int)$n['is_approved'] === -1;
                    
                    if ($isDeleted) {
                      $badge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Deactivated</span>';
                    } elseif ((int)$n['is_approved'] === 1) {
                      $badge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800">Approved</span>';
                    } else {
                      $badge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Pending</span>';
                    }
                    
                    $rowClass = $isDeleted ? 'opacity-60 bg-gray-50' : '';
              ?>
                <tr class="<?= $rowClass ?>">
                  <td class="px-6 py-4">
                    <div class="text-sm font-medium text-foreground"><?= htmlspecialchars($n['title']) ?></div>
                    <div class="text-sm text-muted-foreground">Uploaded on <?= $uploaded ?></div>
                    <?php if ($n['file_path'] && !$isDeleted): ?>
                      <a class="text-xs text-primary underline" href="<?= htmlspecialchars($n['file_path']) ?>" target="_blank"><i class="fa-solid fa-link mr-1"></i>Open file</a>
                    <?php endif; ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-foreground">
                    <?= htmlspecialchars($n['school_name'] ?? '—') ?> /
                    <?= htmlspecialchars($n['level_name'] ?? '—') ?> /
                    <?= htmlspecialchars($n['module_title'] ?? '—') ?>
                  </td>
                  <td class="px-6 py-4 text-sm text-foreground">
                    <?= money_rs($n['price_cents']) ?>
                  </td>
                  <td class="px-6 py-4"><?= $badge ?></td>
                  <td class="px-6 py-4 text-right text-sm font-medium">
                    <?php if ($isDeleted): ?>
                      <!-- Restore button for deactivated notes -->
                      <button class="text-green-600 hover:underline"
                        onclick='confirmRestore(<?= json_encode([
                          "id"=>$n["id"],
                          "title"=>$n["title"],
                        ]) ?>)'>
                        <i class="fas fa-undo"></i> Restore
                      </button>
                    <?php else: ?>
                      <!-- Edit and Delete buttons for active notes -->
                      <button class="text-primary hover:underline mr-3"
                        onclick='showEditModal(<?= json_encode([
                          "id"=>$n["id"],
                          "title"=>$n["title"],
                          "description"=>$n["description"],
                          "module_id"=>$n["mo_id"],
                          "price_rs"=>money_rs($n["price_cents"])
                        ]) ?>)'>
                        <i class="fas fa-edit"></i> Edit
                      </button>
                      <button class="text-red-600 hover:underline"
                        onclick='confirmDelete(<?= json_encode([
                          "id"=>$n["id"],
                          "title"=>$n["title"]
                        ]) ?>)'>
                        <i class="fas fa-trash-alt"></i> Delete
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>

  <!-- Edit Note Modal -->
  <div id="editModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
    <div class="bg-card border border-border rounded-lg shadow-xl max-w-2xl w-full mx-4">
      <div class="p-6">
        <div class="flex justify-between items-start">
          <h3 class="text-lg font-medium text-foreground">Edit Note</h3>
          <button type="button" class="text-muted-foreground hover:text-foreground" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>

        <form class="mt-6 space-y-4" method="post">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="update">
          <input type="hidden" id="editId" name="id" value="">

          <div>
            <label for="editTitle" class="block text-sm font-medium text-foreground mb-1">Title</label>
            <input type="text" id="editTitle" name="title" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" required>
          </div>

          <div>
            <label for="editDescription" class="block text-sm font-medium text-foreground mb-1">Description</label>
            <textarea id="editDescription" name="description" rows="3" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"></textarea>
          </div>

          <div>
            <label for="editModule" class="block text-sm font-medium text-foreground mb-1">Module</label>
            <select id="editModule" name="module_id" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" required>
              <option value="">-- Select Module --</option>
              <?php foreach ($modules as $mo): ?>
                <option value="<?= (int)$mo['id'] ?>">
                  [<?= htmlspecialchars($mo['school_name']) ?>] <?= htmlspecialchars($mo['level_name']) ?> → <?= htmlspecialchars($mo['module_title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label for="editPrice" class="block text-sm font-medium text-foreground mb-1">Price (LKR)</label>
            <input type="number" id="editPrice" name="price_rs" step="0.01" min="0" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" required>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button type="button" class="bg-white py-2 px-4 border border-input rounded-md text-sm font-medium text-foreground hover:bg-muted" onclick="closeEditModal()">Cancel</button>
            <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent rounded-md text-sm font-medium text-white bg-primary hover:bg-primary/90">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
    <div class="bg-card border border-border rounded-lg shadow-xl max-w-md w-full mx-4">
      <div class="p-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
          <i class="fas fa-exclamation-triangle text-red-600"></i>
        </div>
        <h3 class="mt-4 text-lg font-medium text-foreground">Delete Note</h3>
        <p class="mt-2 text-sm text-muted-foreground">Are you sure you want to delete "<span id="deleteNoteTitle" class="font-medium">Note Title</span>"?</p>
        <p class="mt-1 text-xs text-muted-foreground">If this note has been purchased, it will be deactivated instead of deleted.</p>
        <form method="post" id="deleteForm" class="mt-6 flex justify-center space-x-3">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" id="deleteId" value="">
          <button type="button" class="bg-white py-2 px-4 border border-input rounded-md text-sm font-medium text-foreground hover:bg-muted" onclick="closeDeleteModal()">Cancel</button>
          <button type="submit" class="inline-flex justify-center py-2 px-4 rounded-md text-sm font-medium text-white bg-red-600 hover:bg-red-700">Delete</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Restore Confirmation Modal -->
  <div id="restoreModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
    <div class="bg-card border border-border rounded-lg shadow-xl max-w-md w-full mx-4">
      <div class="p-6 text-center">
        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
          <i class="fas fa-undo text-green-600"></i>
        </div>
        <h3 class="mt-4 text-lg font-medium text-foreground">Restore Note</h3>
        <p class="mt-2 text-sm text-muted-foreground">Are you sure you want to restore "<span id="restoreNoteTitle" class="font-medium">Note Title</span>"?</p>
        <p class="mt-1 text-xs text-muted-foreground">This will make the note active again and pending for approval.</p>
        <form method="post" id="restoreForm" class="mt-6 flex justify-center space-x-3">
          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
          <input type="hidden" name="action" value="restore">
          <input type="hidden" name="id" id="restoreId" value="">
          <button type="button" class="bg-white py-2 px-4 border border-input rounded-md text-sm font-medium text-foreground hover:bg-muted" onclick="closeRestoreModal()">Cancel</button>
          <button type="submit" class="inline-flex justify-center py-2 px-4 rounded-md text-sm font-medium text-white bg-green-600 hover:bg-green-700">Restore Note</button>
        </form>
      </div>
    </div>
  </div>
</main>
  <script>
    function showEditModal(data) {
      document.getElementById('editId').value = data.id || '';
      document.getElementById('editTitle').value = data.title || '';
      document.getElementById('editDescription').value = data.description || '';
      document.getElementById('editPrice').value = data.price_rs || '';
      
      // Reset and set module selection
      const sel = document.getElementById('editModule');
      sel.selectedIndex = 0; // Reset to "-- Select Module --"
      if (sel && data.module_id) {
        [...sel.options].forEach(o => { 
          o.selected = (parseInt(o.value,10) === parseInt(data.module_id,10)); 
        });
      }
      document.getElementById('editModal').classList.remove('hidden');
    }
    
    function closeEditModal() {
      document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(data) {
      document.getElementById('deleteNoteTitle').textContent = data.title || 'Note';
      document.getElementById('deleteId').value = data.id || '';
      document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
      document.getElementById('deleteModal').classList.add('hidden');
    }

    function confirmRestore(data) {
      document.getElementById('restoreNoteTitle').textContent = data.title || 'Note';
      document.getElementById('restoreId').value = data.id || '';
      document.getElementById('restoreModal').classList.remove('hidden');
    }
    
    function closeRestoreModal() {
      document.getElementById('restoreModal').classList.add('hidden');
    }
  </script>
</body>
<?php include 'footer.php'; ?>