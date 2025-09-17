<?php 
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/helpers.php';
require_login();

$u = current_user();
if ($u['role']!=='student'){ die('Only students can upload'); }

$mods = $pdo->query("SELECT m.id, CONCAT(' - ',m.title) name FROM modules m ORDER BY m.title")->fetchAll(PDO::FETCH_ASSOC);
$schools = $pdo->query("SELECT id, name FROM schools ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$levels = $pdo->query("SELECT id, name FROM levels ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

if($_SERVER['REQUEST_METHOD']==='POST'){
  check_csrf();
  $module_id=(int)($_POST['module_id']??0);
  $title=trim($_POST['title']??'');
  $desc=trim($_POST['description']??'');
  $price_rs=(float)($_POST['price_rs']??0);
  $price_cents=(int)round($price_rs*100);
  $dir = __DIR__ . '/../pages/uploads/'; 

  if($module_id && $title && isset($_FILES['file']) && $_FILES['file']['error']===UPLOAD_ERR_OK){
    $ext=strtolower(pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['pdf','doc','docx','ppt','pptx'])){ flash('err','Only PDF allowed'); }
    else{
      $new=uniqid('note_').'.'.$ext; $dest=__DIR__.'/../uploads/'.$new;
      if(move_uploaded_file($_FILES['file']['tmp_name'],$dest)){
        $stmt=$pdo->prepare("INSERT INTO notes (seller_id,module_id,title,description,file_path,price_cents) VALUES (?,?,?,?,?,?)");
        $stmt->execute([$u['id'],$module_id,$title,$desc,$new,$price_cents]);
        flash('ok','Note uploaded!'); header('Location: manage_notes.php'); exit;
      } else { flash('err','Upload failed'); }
    }
  } else { flash('err','Fill all fields & attach a PDF'); }
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
            <input type="hidden" name="csrf" value="<?php echo csrf_token(); ?>">

            <h1 class="text-lg font-semibold">Upload Study Materials</h1>

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
                      <option value="<?php echo (int)$s['id']; ?>">
                        <?php echo htmlspecialchars($s['name']); ?>
                      </option>
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
                  <select id="moduleSelect" name="module_id"class="w-full rounded-md border border-input bg-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-ring" required disabled>
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
                  <p id="fileName" class="text-xs text-muted-foreground mt-1">PDF up to 25MB</p>
                </div>
                <input id="fileInput" name="file" type="file" class="hidden" accept="application/pdf" required>
              </label>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-3 pt-2">
              <button type="button"
                class="px-5 py-2 rounded-md bg-slate-200 text-slate-800 hover:bg-slate-300"
                onclick="window.history.back();">
                Cancel
              </button>
              <button type="submit"
                class="px-5 py-2 rounded-md bg-primary text-white hover:bg-emerald-700"><a  href ="mynotes.php">
                Upload Material</a>
              </button>
            </div>
          </form>

      </div>
    </div>
  </section>
</body>
   <script>
(() => {
  const schoolSel = document.getElementById('schoolSelect');
  const levelSel  = document.getElementById('levelSelect');
  const moduleSel = document.getElementById('moduleSelect');
  const fileInput = document.getElementById('fileInput');
  const fileName  = document.getElementById('fileName');

  // Resolve API base
  const DATA_ATTR = document.body.getAttribute('data-api-base');      // e.g. "/NoteSharing/pages/api"
  const API_BASE  = DATA_ATTR && DATA_ATTR.trim() ? DATA_ATTR.trim()  // if provided on <body>
                   : 'api';                                           // default (sibling folder)

  // Utility: reset & fill selects
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

  // Show chosen file name (optional)
  if (fileInput && fileName) {
    fileInput.addEventListener('change', () => {
      if (fileInput.files?.[0]) fileName.textContent = fileInput.files[0].name;
    });
  }

  // Load levels when a school is picked
  schoolSel.addEventListener('change', async () => {
    reset(levelSel, 'Select');
    reset(moduleSel, 'Select a module…');

    const schoolId = schoolSel.value;
    if (!schoolId) return;

    try {
      const url = `${API_BASE}/get_levels.php?school_id=${encodeURIComponent(schoolId)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      const json = await res.json();

      if (!json.ok) {
        console.error('Levels API error:', json);
        alert(json.error || 'Failed to load levels');
        return;
      }
      if (!Array.isArray(json.data)) {
        console.error('Levels API malformed data:', json);
        alert('Invalid levels response');
        return;
      }
      fill(levelSel, json.data, 'name');
    } catch (err) {
      console.error('Levels fetch error:', err);
      alert('Network error while loading levels');
    }
  });

  // Load modules when a level is picked
  levelSel.addEventListener('change', async () => {
    reset(moduleSel, 'Select a module…');

    const levelId = levelSel.value;
    if (!levelId) return;

    try {
      const url = `${API_BASE}/get_modules.php?level_id=${encodeURIComponent(levelId)}`;
      const res = await fetch(url, { credentials: 'same-origin' });
      const json = await res.json();

      if (!json.ok) {
        console.error('Modules API error:', json);
        alert(json.error || 'Failed to load modules');
        return;
      }
      if (!Array.isArray(json.data)) {
        console.error('Modules API malformed data:', json);
        alert('Invalid modules response');
        return;
      }
      fillModules(json.data);
    } catch (err) {
      console.error('Modules fetch error:', err);
      alert('Network error while loading modules');
    }
  });

  // Initial state
  reset(levelSel, 'Select');
  reset(moduleSel, 'Select a module…');

  // Quick sanity log
  console.log('[Upload] API_BASE =', API_BASE);
})();
</script>





<?php include 'footer.php'; ?>