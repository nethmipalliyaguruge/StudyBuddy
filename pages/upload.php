<?php
// pages/upload.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$u = current_user();
if (!isset($u['role']) || $u['role'] !== 'student') {
  http_response_code(403);
  exit('Only students can upload');
}

// Load schools for the first select (levels/modules are fetched via API)
$schools = $pdo->query("SELECT id, name FROM schools ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

function sb_is_valid_preview_upload(array $f): bool {
  if (!isset($f['error']) || $f['error'] !== UPLOAD_ERR_OK) return false;
  if ($f['size'] <= 0) return false;
  $ext = strtolower(pathinfo($f['name'] ?? '', PATHINFO_EXTENSION));
  if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) return false;
  // quick mime check
  if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
      $mime = @finfo_file($fi, $f['tmp_name']);
      @finfo_close($fi);
      if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) return false;
    }
  }
  return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  $module_id   = (int)($_POST['module_id'] ?? 0);
  $title       = trim((string)($_POST['title'] ?? ''));
  $desc        = trim((string)($_POST['description'] ?? ''));
  $price_rs    = (float)($_POST['price_rs'] ?? 0);
  $price_cents = (int)round($price_rs * 100);

  if (!$module_id || $title === '' || $desc === '' || $price_cents < 0) {
    flash('err', 'Please fill all required fields.');
    header('Location: upload.php'); exit;
  }
  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    flash('err', 'Please attach your material file.');
    header('Location: upload.php'); exit;
  }

  $okExt = ['pdf','doc','docx','ppt','pptx'];
  $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $okExt, true)) {
    flash('err', 'Only PDF, DOC, DOCX, PPT, PPTX are allowed.');
    header('Location: upload.php'); exit;
  }

  // Require 3 preview images
  for ($i=1; $i<=3; $i++) {
    if (!isset($_FILES["preview{$i}"]) || !sb_is_valid_preview_upload($_FILES["preview{$i}"])) {
      flash('err', 'Please upload 3 valid preview images (JPG, PNG, WEBP).');
      header('Location: upload.php'); exit;
    }
  }

  // Save main file
  $uploadsDir = __DIR__ . '/uploads';
  if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0777, true);

  $newName = uniqid('note_', true) . '.' . $ext;
  $dest    = $uploadsDir . DIRECTORY_SEPARATOR . $newName;
  if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    flash('err','Upload failed. Please try again.');
    header('Location: upload.php'); exit;
  }

  // Insert into notes
  $stmt = $pdo->prepare("
    INSERT INTO notes (seller_id, module_id, title, description, file_path, price_cents)
    VALUES (?,?,?,?,?,?)
  ");
  $stmt->execute([$u['id'], $module_id, $title, $desc, $newName, $price_cents]);
  $note_id = (int)$pdo->lastInsertId();

  // Save previews: /pages/uploads/previews/note_{id}/page{1..3}.jpg|png|webp
  $previewRoot = $uploadsDir . '/previews';
  if (!is_dir($previewRoot)) @mkdir($previewRoot, 0777, true);
  $notePrevDir = $previewRoot . "/note_{$note_id}";
  if (!is_dir($notePrevDir)) @mkdir($notePrevDir, 0777, true);

  for ($i=1; $i<=3; $i++) {
    $f = $_FILES["preview{$i}"];
    $extP = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $target = $notePrevDir . "/page{$i}." . $extP;
    move_uploaded_file($f['tmp_name'], $target);
  }

  flash('ok','Note uploaded successfully!');
  header('Location: mynotes.php'); exit;
}

$title = "Upload Notes - StudyBuddy APIIT";
include __DIR__ . '/header.php';
?>
<main class="relative">
  <div class="h-56 w-full overflow-hidden">
    <img src="https://images.unsplash.com/photo-1513475382585-d06e58bcb0ea?q=80&w=1600&auto=format&fit=crop"
         alt="Study background" class="w-full h-full object-cover" />
  </div>

  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="-mt-24 mb-16 bg-white border border-gray-200 rounded-xl shadow-sm">
      <form method="post" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6" id="uploadForm">
        <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

        <h1 class="text-lg font-semibold">Upload Study Materials</h1>

        <?php if ($m = flash('ok')): ?>
          <div class="p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>
        <?php if ($m = flash('err')): ?>
          <div class="p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <!-- Material details -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Material Details</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-600 mb-1">Title</label>
              <input type="text" name="title" required
                     class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                     placeholder="e.g. Web Development Module 3 Notes">
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">Price (LKR)</label>
              <input type="number" name="price_rs" min="0" step="0.01" required
                     class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                     placeholder="e.g. 750">
            </div>
          </div>

          <div class="mt-4">
            <label class="block text-sm text-slate-600 mb-1">Description</label>
            <textarea name="description" rows="3" required
                      class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                      placeholder="Short summary of what is included..."></textarea>
          </div>
        </div>

        <!-- Categorization -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Categorization</h2>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm text-slate-600 mb-1">School</label>
              <select id="schoolSelect" required
                      class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="" selected disabled>Select</option>
                <?php foreach ($schools as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div>
              <label class="block text-sm text-slate-600 mb-1">Level</label>
              <select id="levelSelect" required disabled
                      class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="" selected disabled>Select</option>
              </select>
            </div>

            <div>
              <label class="block text-sm text-slate-600 mb-1">Module</label>
              <select id="moduleSelect" name="module_id" required disabled
                      class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                <option value="" selected disabled>Select a module…</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Main file -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Upload File</h2>
          <label for="fileInput"
                 class="group flex items-center justify-center w-full h-28 rounded-md border-2 border-dashed border-gray-300 bg-white hover:border-emerald-500 transition cursor-pointer">
            <div class="text-center">
              <div class="mx-auto w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center mb-2">
                <i class="fa-solid fa-cloud-arrow-up text-slate-500 group-hover:text-emerald-600"></i>
              </div>
              <p class="text-sm text-slate-600">Click to upload or drag & drop</p>
              <p id="fileName" class="text-xs text-slate-500 mt-1">PDF, DOC, DOCX, PPT, PPTX</p>
            </div>
            <input id="fileInput" name="file" type="file" class="hidden" required
                   accept=".pdf,application/pdf,.doc,application/msword,.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.ppt,application/vnd.ms-powerpoint,.pptx,application/vnd.openxmlformats-officedocument.presentationml.presentation">
          </label>
        </div>

        <!-- Preview images (exact UI you asked for) -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Preview Images (Required)</h2>
          <p class="text-xs text-slate-500 mb-2">
            Upload clear images of the first 3 pages to show buyers a preview. Accepted: JPG, PNG, WEBP.
          </p>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <?php for ($i=1; $i<=3; $i++): ?>
              <div>
                <label class="block text-sm text-slate-600 mb-1">Page <?= $i ?></label>
                <input type="file" name="preview<?= $i ?>" required
                       accept="image/jpeg,image/png,image/webp"
                       class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-500">
              </div>
            <?php endfor; ?>
          </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button" class="px-5 py-2 rounded-md bg-slate-200 text-slate-800 hover:bg-slate-300"
                  onclick="window.history.back();">Cancel</button>
          <button type="submit" class="px-5 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-700">
            Upload Material
          </button>
        </div>
      </form>
    </div>
  </div>
</main>

<script>
// Where the API lives (relative to this page): /.../pages/api/
const API_BASE = new URL('./api/', window.location.href).pathname;

// show chosen filename
document.getElementById('fileInput')?.addEventListener('change', function(){
  const out = document.getElementById('fileName');
  if (this.files && this.files[0] && out) out.textContent = this.files[0].name;
});

const schoolSel = document.getElementById('schoolSelect');
const levelSel  = document.getElementById('levelSelect');
const moduleSel = document.getElementById('moduleSelect');

function reset(sel, ph='Select') {
  sel.innerHTML = `<option value="" selected disabled>${ph}</option>`;
  sel.disabled = true;
}
function fill(sel, items, textKey='name') {
  reset(sel);
  for (const it of items) {
    const opt = document.createElement('option');
    opt.value = it.id;
    opt.textContent = it[textKey];
    sel.appendChild(opt);
  }
  sel.disabled = items.length === 0;
}
function fillModules(items) {
  reset(moduleSel, 'Select a module…');
  for (const m of items) {
    const opt = document.createElement('option');
    const code = (m.code || '').toString().trim();
    opt.value = m.id;
    opt.textContent = code ? `${code} - ${m.title}` : m.title;
    moduleSel.appendChild(opt);
  }
  moduleSel.disabled = items.length === 0;
}

schoolSel.addEventListener('change', async () => {
  reset(levelSel, 'Select'); reset(moduleSel, 'Select a module…');
  const url = `${API_BASE}get_levels.php?school_id=${encodeURIComponent(schoolSel.value)}`;
  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();
    try { var json = JSON.parse(text); } catch(e) {
      console.error('Levels raw response:', text);
      throw new Error('Invalid JSON from levels API');
    }
    if (!json.ok || !Array.isArray(json.data)) throw new Error(json.error || 'Levels API error');
    fill(levelSel, json.data, 'name');
  } catch (err) {
    console.error(err);
    alert('Network error while loading levels');
  }
});

levelSel.addEventListener('change', async () => {
  reset(moduleSel, 'Select a module…');
  const url = `${API_BASE}get_modules.php?level_id=${encodeURIComponent(levelSel.value)}`;
  try {
    const res = await fetch(url, { credentials: 'same-origin' });
    const text = await res.text();
    try { var json = JSON.parse(text); } catch(e) {
      console.error('Modules raw response:', text);
      throw new Error('Invalid JSON from modules API');
    }
    if (!json.ok || !Array.isArray(json.data)) throw new Error(json.error || 'Modules API error');
    fillModules(json.data);
  } catch (err) {
    console.error(err);
    alert('Network error while loading modules');
  }
});

// initial
reset(levelSel, 'Select');
reset(moduleSel, 'Select a module…');
</script>

<?php include __DIR__ . '/footer.php'; ?>
