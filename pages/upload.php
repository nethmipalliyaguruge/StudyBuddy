<?php 
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/helpers.php';
require_login();

$u = current_user();
if (!isset($u['role']) || $u['role'] !== 'student') {
  die('Only students can upload');
}

/* ---------- DB lookups for selects ---------- */
$mods    = $pdo->query("SELECT m.id, CONCAT(' - ',m.title) name FROM modules m ORDER BY m.title")->fetchAll(PDO::FETCH_ASSOC);
$schools = $pdo->query("SELECT id, name FROM schools ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels  = $pdo->query("SELECT id, name FROM levels ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- helper: create 3 preview images (PDF or Office → PDF) ---------- */
function sb_generate_previews_for_note(int $note_id, string $savedFilename): void {
  $uploadsDir = realpath(__DIR__ . '/uploads');
  if (!$uploadsDir) return;

  $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $savedFilename;
  if (!is_file($filePath)) return;

  $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
  $pdfPath = $filePath;

  // Convert Office -> PDF with LibreOffice
  if (in_array($ext, ['doc','docx','ppt','pptx'])) {
    $cmd = "libreoffice --headless --convert-to pdf --outdir " . escapeshellarg($uploadsDir) . " " . escapeshellarg($filePath);
    @exec($cmd);
    $pdfGuess = $uploadsDir . DIRECTORY_SEPARATOR . preg_replace('/\.(docx?|pptx?)$/i', '.pdf', basename($filePath));
    if (is_file($pdfGuess)) {
      $pdfPath = $pdfGuess;
    } else {
      // couldn't convert; skip previews
      return;
    }
  }

  if (!class_exists('Imagick') || !is_file($pdfPath)) return;

  $previewDir = __DIR__ . "/previews/note_" . $note_id;
  if (!is_dir($previewDir)) @mkdir($previewDir, 0777, true);

  try {
    $im = new Imagick();
    $im->setResolution(150, 150);
    $im->readImage($pdfPath."[0-2]"); // first 3 pages

    $i = 1;
    foreach ($im as $page) {
      $page->setImageFormat('jpeg');
      $page->setImageCompressionQuality(82);
      $page->scaleImage(1400, 0);
      // watermark "PREVIEW"
      $draw = new ImagickDraw();
      $draw->setFillColor(new ImagickPixel('rgba(0,0,0,0.12)'));
      $draw->setFontSize(72);
      $draw->setGravity(Imagick::GRAVITY_CENTER);
      $page->annotateImage($draw, 0, 0, -28, 'PREVIEW');
      $page->writeImage($previewDir . "/page{$i}.jpg");
      $i++;
    }
    $im->clear(); $im->destroy();
  } catch (Throwable $e) {
    error_log("Preview generation failed for note {$note_id}: " . $e->getMessage());
  }
}

/* ---------- POST: handle upload ---------- */
if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();

  $module_id = (int)($_POST['module_id'] ?? 0);
  $title     = trim($_POST['title'] ?? '');
  $desc      = trim($_POST['description'] ?? '');
  $price_rs  = (float)($_POST['price_rs'] ?? 0);
  $price_cents = (int)round($price_rs * 100);

  // uploads dir under /pages/uploads
  $uploadsDir = __DIR__ . '/uploads';
  if (!is_dir($uploadsDir)) @mkdir($uploadsDir, 0777, true);

  if ($module_id && $title && isset($_FILES['file']) && $_FILES['file']['error']===UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    // allow PDF + Office docs
    $allowed = ['pdf','doc','docx','ppt','pptx'];
    if (!in_array($ext, $allowed, true)) {
      flash('err','Only PDF, DOC, DOCX, PPT, PPTX are allowed.');
    } else {
      $newName = uniqid('note_', true) . '.' . $ext;
      $dest    = $uploadsDir . DIRECTORY_SEPARATOR . $newName;

      if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        // insert into notes
        $stmt = $pdo->prepare("
          INSERT INTO notes (seller_id, module_id, title, description, file_path, price_cents)
          VALUES (?,?,?,?,?,?)
        ");
        $stmt->execute([$u['id'], $module_id, $title, $desc, $newName, $price_cents]);
        $note_id = (int)$pdo->lastInsertId();

        // generate previews (non-blocking best effort)
        sb_generate_previews_for_note($note_id, $newName);

        flash('ok','Note uploaded!');
        header('Location: mynotes.php');
        exit;
      } else {
        flash('err','Upload failed. Please try again.');
      }
    }
  } else {
    flash('err','Please fill all fields and attach a file.');
  }
}

$title = "Upload Notes - StudyBuddy APIIT";
include 'header.php';
?>

<body data-api-base="/NoteSharing/pages/api">
<section class="relative">
  <div class="h-56 w-full overflow-hidden">
    <img
      src="https://images.unsplash.com/photo-1513475382585-d06e58bcb0ea?q=80&w=1600&auto=format&fit=crop"
      alt="Study background"
      class="w-full h-full object-cover"
    />
  </div>

  <!-- Centered form card -->
  <div class="max-w-3xl mx-auto px-4 sm:px-6">
    <div class="-mt-24 mb-16 bg-card border border-border rounded-xl card">
      <form id="uploadForm" method="post" enctype="multipart/form-data" class="p-6 sm:p-8 space-y-6">
        <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

        <h1 class="text-lg font-semibold">Upload Study Materials</h1>

        <!-- Flash -->
        <?php if ($m = flash('ok')): ?>
          <div class="p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>
        <?php if ($m = flash('err')): ?>
          <div class="p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
        <?php endif; ?>

        <!-- Material Details -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Material Details</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm text-slate-600 mb-1">Title</label>
              <input type="text" name="title"
                class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring"
                placeholder="e.g. Web Development Module 3 Notes" required>
            </div>
            <div>
              <label class="block text-sm text-slate-600 mb-1">Price (LKR)</label>
              <input type="number" name="price_rs" min="0" step="0.01"
                class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring"
                placeholder="e.g. 750" required>
            </div>
          </div>

          <div class="mt-4">
            <label class="block text-sm text-slate-600 mb-1">Description</label>
            <textarea name="description" rows="3"
              class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring"
              placeholder="Short summary of what is included..." required></textarea>
          </div>
        </div>

        <!-- Categorization -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Categorization</h2>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- School -->
            <div>
              <label class="block text-sm text-slate-600 mb-1">School</label>
              <select id="schoolSelect"
                      class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring" required>
                <option value="" selected disabled>Select</option>
                <?php foreach ($schools as $s): ?>
                  <option value="<?= (int)$s['id']; ?>"><?= htmlspecialchars($s['name']); ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Level -->
            <div>
              <label class="block text-sm text-slate-600 mb-1">Level</label>
              <select id="levelSelect"
                      class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring" required disabled>
                <option value="" selected disabled>Select</option>
              </select>
            </div>

            <!-- Module -->
            <div>
              <label class="block text-sm text-slate-600 mb-1">Module</label>
              <select id="moduleSelect" name="module_id"
                      class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring" required disabled>
                <option value="" selected disabled>Select a module…</option>
              </select>
            </div>
          </div>
        </div>

        <!-- Upload -->
        <div>
          <h2 class="text-sm font-semibold text-slate-700 mb-3">Upload File</h2>
          <label for="fileInput"
            class="group flex items-center justify-center w-full h-28 rounded-md border-2 border-dashed border-input bg-white hover:border-ring transition cursor-pointer">
            <div class="text-center">
              <div class="mx-auto w-10 h-10 rounded-full border border-slate-300 flex items-center justify-center mb-2">
                <i class="fa-solid fa-cloud-arrow-up text-slate-500 group-hover:text-primary"></i>
              </div>
              <p class="text-sm text-slate-600">Click to upload or drag & drop</p>
              <p id="fileName" class="text-xs text-muted-foreground mt-1">PDF, DOC, DOCX, PPT, PPTX (max 25MB)</p>
            </div>
            <!-- Accept common MIME types; browsers still allow selecting by extension if missing -->
            <input id="fileInput" name="file" type="file" class="hidden"
                   accept=".pdf,application/pdf,.doc,application/msword,.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document,.ppt,application/vnd.ms-powerpoint,.pptx,application/vnd.openxmlformats-officedocument.presentationml.presentation"
                   required>
          </label>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-3 pt-2">
          <button type="button"
            class="px-5 py-2 rounded-md bg-slate-200 text-slate-800 hover:bg-slate-300"
            onclick="window.history.back();">
            Cancel
          </button>
          <button type="submit" class="px-5 py-2 rounded-md bg-primary text-white hover:bg-emerald-700">
            Upload Material
          </button>
        </div>
      </form>
    </div>
  </div>
</section>

<script>
(() => {
  const schoolSel = document.getElementById('schoolSelect');
  const levelSel  = document.getElementById('levelSelect');
  const moduleSel = document.getElementById('moduleSelect');
  const fileInput = document.getElementById('fileInput');
  const fileName  = document.getElementById('fileName');

  // Resolve API base (from body attribute or default 'api')
  const DATA_ATTR = document.body.getAttribute('data-api-base');
  const API_BASE  = DATA_ATTR && DATA_ATTR.trim() ? DATA_ATTR.trim() : 'api';

  function reset(selectEl, placeholder = 'Select') {
    selectEl.innerHTML = `<option value="" selected disabled>${placeholder}</option>`;
    selectEl.disabled = true;
  }
  function fill(selectEl, items, textKey = 'name') {
    reset(selectEl);
    for (const it of items) {
      const opt = document.createElement('option');
      opt.value = it.id;
      opt.textContent = it[textKey];
      selectEl.appendChild(opt);
    }
    selectEl.disabled = items.length === 0;
  }
  function fillModules(items) {
    reset(moduleSel, 'Select a module…');
    for (const m of items) {
      const opt = document.createElement('option');
      const code = (m.code || '').trim();
      opt.value = m.id;
      opt.textContent = code ? `${code} - ${m.title}` : m.title;
      moduleSel.appendChild(opt);
    }
    moduleSel.disabled = items.length === 0;
  }

  if (fileInput && fileName) {
    fileInput.addEventListener('change', () => {
      if (fileInput.files?.[0]) fileName.textContent = fileInput.files[0].name;
    });
  }

  schoolSel.addEventListener('change', async () => {
    reset(levelSel, 'Select');
    reset(moduleSel, 'Select a module…');

    const schoolId = schoolSel.value;
    if (!schoolId) return;

    try {
      const url = `${API_BASE}/get_levels.php?school_id=${encodeURIComponent(schoolId)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      const json = await res.json();
      if (!json.ok || !Array.isArray(json.data)) throw new Error('Invalid levels response');
      fill(levelSel, json.data, 'name');
    } catch (e) {
      console.error(e);
      alert('Failed to load levels');
    }
  });

  levelSel.addEventListener('change', async () => {
    reset(moduleSel, 'Select a module…');

    const levelId = levelSel.value;
    if (!levelId) return;

    try {
      const url = `${API_BASE}/get_modules.php?level_id=${encodeURIComponent(levelId)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      const json = await res.json();
      if (!json.ok || !Array.isArray(json.data)) throw new Error('Invalid modules response');
      fillModules(json.data);
    } catch (e) {
      console.error(e);
      alert('Failed to load modules');
    }
  });

  reset(levelSel, 'Select');
  reset(moduleSel, 'Select a module…');

  console.log('[Upload] API_BASE =', API_BASE);
})();
</script>

<?php include 'footer.php'; ?>
