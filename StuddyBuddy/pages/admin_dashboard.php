<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
// require_role('admin');

$tab = $_GET['tab'] ?? ($_POST['__tab'] ?? 'schools');

/* ===================== POST HANDLERS ===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $entity = $_POST['entity'] ?? '';
  $action = $_POST['action'] ?? '';
  $tab    = $_POST['__tab'] ?? $tab;

  try {
    /* ---- SCHOOLS ---- */
    if ($entity === 'school') {
      if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') throw new RuntimeException('School name is required.');
        $pdo->prepare("INSERT INTO schools (name) VALUES (?)")->execute([$name]);
        flash('ok', 'School added.');
      } elseif ($action === 'update') {
        $id   = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        if (!$id || $name === '') throw new RuntimeException('Invalid school update.');
        $pdo->prepare("UPDATE schools SET name=? WHERE id=?")->execute([$name, $id]);
        flash('ok', 'School updated.');
      } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new RuntimeException('Invalid id.');
        $pdo->prepare("DELETE FROM schools WHERE id=?")->execute([$id]);
        flash('ok', 'School deleted.');
      }
      header("Location: admin_dashboard.php?tab=schools"); exit;
    }

    /* ---- LEVELS ---- */
    if ($entity === 'level') {
      if ($action === 'create') {
        $school_id = (int)($_POST['school_id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        if (!$school_id || $name === '') throw new RuntimeException('School and level name are required.');
        $pdo->prepare("INSERT INTO levels (school_id, name) VALUES (?, ?)")->execute([$school_id, $name]);
        flash('ok', 'Level added.');
      } elseif ($action === 'update') {
        $id        = (int)($_POST['id'] ?? 0);
        $school_id = (int)($_POST['school_id'] ?? 0);
        $name      = trim($_POST['name'] ?? '');
        if (!$id || !$school_id || $name === '') throw new RuntimeException('Invalid level update.');
        $pdo->prepare("UPDATE levels SET school_id=?, name=? WHERE id=?")
            ->execute([$school_id, $name, $id]);
        flash('ok', 'Level updated.');
      } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new RuntimeException('Invalid id.');
        $pdo->prepare("DELETE FROM levels WHERE id=?")->execute([$id]);
        flash('ok', 'Level deleted.');
      }
      header("Location: admin_dashboard.php?tab=levels"); exit;
    }

    /* ---- MODULES (your table: id, level_id, code, title, status) ---- */
    if ($entity === 'module') {
      if ($action === 'create') {
        $level_id = (int)($_POST['level_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        if (!$level_id || $title === '') throw new RuntimeException('Level and title are required.');
        $pdo->prepare("INSERT INTO modules (level_id, title) VALUES (?,?)")
            ->execute([$level_id, $title]);
        flash('ok', 'Module added.');
      } elseif ($action === 'update') {
        $id       = (int)($_POST['id'] ?? 0);
        $level_id = (int)($_POST['level_id'] ?? 0);
        $title    = trim($_POST['title'] ?? '');
        if (!$id || !$level_id || $title === '') throw new RuntimeException('Invalid module update.');
        $pdo->prepare("UPDATE modules SET level_id=?, title=? WHERE id=?")
            ->execute([$level_id, $title, $id]);
        flash('ok', 'Module updated.');
      } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) throw new RuntimeException('Invalid id.');
        $pdo->prepare("DELETE FROM modules WHERE id=?")->execute([$id]);
        flash('ok', 'Module deleted.');
      }
      header("Location: admin_dashboard.php?tab=modules"); exit;
    }

    /* ===================== USERS ===================== */
if ($entity === 'user') {
  if ($action === 'update') {
    $id         = (int)($_POST['id'] ?? 0);
    $role       = trim($_POST['role'] ?? '');
    $is_blocked = isset($_POST['is_blocked']) ? 1 : 0; // checkbox

    if (!$id || !in_array($role, ['admin','student'], true)) {
      throw new RuntimeException('Invalid user update.');
    }
    $pdo->prepare("UPDATE users SET role=?, is_blocked=? WHERE id=?")
        ->execute([$role, $is_blocked, $id]);
    flash('ok', 'User updated.');
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) throw new RuntimeException('Invalid id.');
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    flash('ok', 'User deleted.');
  }
  header("Location: admin_dashboard.php?tab=users"); exit;
}

/* ===================== MATERIALS ===================== */
if ($entity === 'material') {
  if ($action === 'update') {
    $id          = (int)($_POST['id'] ?? 0);
    $module_id   = (int)($_POST['module_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $price_rs    = (float)($_POST['price_rs'] ?? 0);
    $is_approved = isset($_POST['is_approved']) ? 1 : 0; // checkbox
    $price_cents = (int)round($price_rs * 100);

    if (!$id || !$module_id || $title === '') {
      throw new RuntimeException('Invalid material update.');
    }

    $pdo->prepare("UPDATE notes SET module_id=?, title=?, price_cents=?, is_approved=? WHERE id=?")
        ->execute([$module_id, $title, $price_cents, $is_approved, $id]);

    flash('ok', 'Material updated.');
  } elseif ($action === 'quick') {
    $id = (int)($_POST['id'] ?? 0);
    $to = (int)($_POST['to'] ?? 0); // 1 approve, 0 unapprove
    if (!$id || !in_array($to, [0,1], true)) throw new RuntimeException('Invalid action.');
    $pdo->prepare("UPDATE notes SET is_approved=? WHERE id=?")->execute([$to, $id]);
    flash('ok', $to ? 'Approved.' : 'Marked as pending.');
  } elseif ($action === 'delete') {
    $id = (int)$_POST['id'];
    if (!$id) throw new RuntimeException('Invalid id.');
    $pdo->prepare("DELETE FROM notes WHERE id=?")->execute([$id]);
    flash('ok', 'Material deleted.');
  }
  header("Location: admin_dashboard.php?tab=materials"); exit;
}


  } catch (Throwable $e) {
    flash('err', $e->getMessage());
    header("Location: admin_dashboard.php?tab={$tab}"); exit;
  }
}

/* ===================== DATA FOR TABLES ===================== */
$schools = $pdo->query("SELECT * FROM schools ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$levels  = $pdo->query("
  SELECT l.*, s.name AS school_name
  FROM levels l
  JOIN schools s ON s.id = l.school_id
  ORDER BY s.name, l.name
")->fetchAll(PDO::FETCH_ASSOC);

/* School of a module comes via the level */
$modules = $pdo->query("
  SELECT 
    m.id, m.level_id, m.title, m.status,
    l.name AS level_name,  l.school_id,
    s.name AS school_name
  FROM modules m
  JOIN levels  l ON l.id = m.level_id
  JOIN schools s ON s.id = l.school_id
  ORDER BY s.name, l.name, m.title
")->fetchAll(PDO::FETCH_ASSOC);

/* USERS data */
$users = $pdo->query("
  SELECT id, full_name, email, role, is_blocked, created_at
  FROM users
  ORDER BY created_at DESC, id DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* MATERIALS data (joins for display only; uses your column names) */
$materials = $pdo->query("
  SELECT 
    m.id, m.seller_id, m.module_id, m.title, m.description, m.file_path,
    m.price_cents, m.is_approved, m.created_at,
    u.full_name AS uploader_name, u.email AS uploader_email,
    mo.title AS module_title,
    l.name  AS level_name,
    s.name  AS school_name
  FROM notes m
  JOIN users   u  ON u.id = m.seller_id
  LEFT JOIN modules mo ON mo.id = m.module_id
  LEFT JOIN levels  l  ON l.id = mo.level_id
  LEFT JOIN schools s  ON s.id = l.school_id
  ORDER BY m.created_at DESC, m.id DESC
")->fetchAll(PDO::FETCH_ASSOC);

function money_rs($cents){ return number_format(((int)$cents)/100, 2); }


function sel($a,$b){ return (string)$a===(string)$b?'selected':''; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard - StudyBuddy APIIT</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: { extend: {
        colors: {
          primary:'#006644','primary-foreground':'#fff',
          secondary:'#f0f9f5','secondary-foreground':'#006644',
          accent:'#4ade80','accent-foreground':'#006644',
          muted:'#f1f5f9','muted-foreground':'#64748b',
          background:'#ffffff', foreground:'#0f172a',
          card:'#ffffff','card-foreground':'#0f172a',
          border:'#e2e8f0', input:'#e2e8f0', ring:'#006644'
        }
      }}
    }
  </script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, sans-serif; }
    .tab-btn-active { color:#006644; border-color:#006644; }
  </style>
</head>
<body class="bg-muted text-foreground">

  <!-- Header -->
  <header class="bg-white border-b border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="h-16 flex items-center justify-between">
        <a class="flex items-center gap-2" href="index.php">
          <i class="fas fa-graduation-cap text-2xl text-primary"></i>
          <span class="text-xl font-bold text-primary">StudyBuddy APIIT</span>
        </a>
        <div class="flex items-center gap-3">
          <span class="text-sm text-slate-700"><?= htmlspecialchars((current_user()['full_name'] ?? '')) ?></span>
          <a href="logout.php" class="text-sm font-medium text-slate-700 hover:text-primary transition-colors">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <!-- Flash -->
    <?php if ($m = flash('ok')): ?>
      <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('err')): ?>
      <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center gap-4">
          <div class="bg-secondary p-3 rounded-md"><i class="fas fa-users text-primary"></i></div>
          <div>
            <p class="text-sm text-muted-foreground">Total Users</p>
            <p class="text-2xl font-semibold"><?= number_format(count($users)) ?></p>
          </div>
        </div>
      </div>
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center gap-4">
          <div class="bg-secondary p-3 rounded-md"><i class="fas fa-file-alt text-primary"></i></div>
          <div>
            <p class="text-sm text-muted-foreground">Total Materials</p>
            <p class="text-2xl font-semibold"><?= number_format(count($materials)) ?></p>
          </div>
        </div>
      </div>
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center gap-4">
          <div class="bg-secondary p-3 rounded-md"><i class="fas fa-coins text-primary"></i></div>
          <div>
            <p class="text-sm text-muted-foreground">Revenue</p>
            <p class="text-2xl font-semibold">—</p>
          </div>
        </div>
      </div>
      <div class="bg-card border border-border rounded-lg p-5">
        <div class="flex items-center gap-4">
          <div class="bg-secondary p-3 rounded-md"><i class="fas fa-exclamation-circle text-primary"></i></div>
          <div>
            <p class="text-sm text-muted-foreground">Pending Approvals</p>
            <p class="text-2xl font-semibold"><?= number_format(array_reduce($materials, fn($c,$r)=>$c + ($r['is_approved'] ? 0 : 1), 0)) ?></p>
          </div>
        </div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="bg-card border border-border rounded-lg mb-8">
      <div class="border-b border-border">
        <nav class="-mb-px flex flex-wrap" aria-label="Tabs">
          <a href="?tab=schools"   class="tab-btn whitespace-nowrap py-4 px-5 border-b-2 font-medium text-sm <?= $tab==='schools'  ? 'tab-btn-active' : 'border-transparent text-muted-foreground' ?>"><i class="fas fa-school mr-2"></i>Manage Schools</a>
          <a href="?tab=levels"    class="tab-btn whitespace-nowrap py-4 px-5 border-b-2 font-medium text-sm <?= $tab==='levels'   ? 'tab-btn-active' : 'border-transparent text-muted-foreground' ?>"><i class="fas fa-layer-group mr-2"></i>Manage Levels</a>
          <a href="?tab=modules"   class="tab-btn whitespace-nowrap py-4 px-5 border-b-2 font-medium text-sm <?= $tab==='modules'  ? 'tab-btn-active' : 'border-transparent text-muted-foreground' ?>"><i class="fas fa-book mr-2"></i>Manage Modules</a>
          <a href="?tab=users"     class="tab-btn whitespace-nowrap py-4 px-5 border-b-2 font-medium text-sm <?= $tab==='users'    ? 'tab-btn-active' : 'border-transparent text-muted-foreground' ?>"><i class="fas fa-users mr-2"></i>Manage Users</a>
          <a href="?tab=materials" class="tab-btn whitespace-nowrap py-4 px-5 border-b-2 font-medium text-sm <?= $tab==='materials'? 'tab-btn-active' : 'border-transparent text-muted-foreground' ?>"><i class="fas fa-file-lines mr-2"></i>Manage Materials</a>
        </nav>
      </div>

      <div class="p-6 space-y-10">

        <!-- Schools -->
        <?php if ($tab === 'schools'): ?>
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Schools</h2>
            <form method="post" class="flex items-center gap-2">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="entity" value="school">
              <input type="hidden" name="action" value="create">
              <input type="hidden" name="__tab"  value="schools">
              <input name="name" class="rounded-md border border-input px-3 py-2" placeholder="School name" required>
              <button class="bg-primary text-white px-4 py-2 rounded-md hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i>Add</button>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-muted">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
              </tr>
              </thead>
              <tbody class="bg-white divide-y divide-border">
              <?php foreach ($schools as $s): ?>
                <tr>
                  <td class="px-6 py-3 text-sm font-medium"><?= htmlspecialchars($s['name']) ?></td>
                  <td class="px-6 py-3 text-right text-sm">
                    <form method="post" class="inline-flex gap-2 items-center">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="entity" value="school">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="__tab"  value="schools">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <input name="name" class="border rounded px-2 py-1 w-48" value="<?= htmlspecialchars($s['name']) ?>">
                      <button class="px-3 py-1 bg-secondary text-secondary-foreground rounded">Save</button>
                    </form>
                    <form method="post" class="inline" onsubmit="return confirm('Delete this school?');">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="entity" value="school">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="__tab"  value="schools">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <button class="ml-2 px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php endif; ?>

        <!-- Levels -->
        <?php if ($tab === 'levels'): ?>
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Levels</h2>
            <form method="post" class="flex items-center gap-2">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="entity" value="level">
              <input type="hidden" name="action" value="create">
              <input type="hidden" name="__tab"  value="levels">
              <select name="school_id" class="rounded-md border border-input px-3 py-2" required>
                <option value="">School…</option>
                <?php foreach ($schools as $s): ?>
                  <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <input name="name" class="rounded-md border border-input px-3 py-2" placeholder="Level name (e.g., Level 4)" required>
              <button class="bg-primary text-white px-4 py-2 rounded-md hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i>Add</button>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-muted">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">School</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Level</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
              </tr>
              </thead>
              <tbody class="bg-white divide-y divide-border">
              <?php foreach ($levels as $l): ?>
                <tr>
                  <td class="px-6 py-3 text-sm"><?= htmlspecialchars($l['school_name']) ?></td>
                  <td class="px-6 py-3 text-sm font-medium"><?= htmlspecialchars($l['name']) ?></td>
                  <td class="px-6 py-3 text-right text-sm">
                    <form method="post" class="inline-flex gap-2 items-center">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="entity" value="level">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="__tab"  value="levels">
                      <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                      <select name="school_id" class="border rounded px-2 py-1">
                        <?php foreach ($schools as $s): ?>
                          <option value="<?= (int)$s['id'] ?>" <?= sel($s['id'], $l['school_id']) ?>><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <input name="name" class="border rounded px-2 py-1 w-56" value="<?= htmlspecialchars($l['name']) ?>">
                      <button class="px-3 py-1 bg-secondary text-secondary-foreground rounded">Save</button>
                    </form>
                    <form method="post" class="inline" onsubmit="return confirm('Delete this level?');">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="entity" value="level">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="__tab"  value="levels">
                      <input type="hidden" name="id" value="<?= (int)$l['id'] ?>">
                      <button class="ml-2 px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php endif; ?>

        <!-- Modules -->
        <?php if ($tab === 'modules'): ?>
        <section>
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-medium">Modules</h2>
            <form method="post" class="flex flex-wrap items-center gap-2">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="entity" value="module">
              <input type="hidden" name="action" value="create">
              <input type="hidden" name="__tab"  value="modules">
              <select name="level_id" class="rounded-md border border-input px-3 py-2" required>
                <option value="">Level…</option>
                <?php foreach ($levels as $l): ?>
                  <option value="<?= (int)$l['id'] ?>">[<?= htmlspecialchars($l['school_name']) ?>] <?= htmlspecialchars($l['name']) ?></option>
                <?php endforeach; ?>
              </select>
              <input name="title" class="rounded-md border border-input px-3 py-2" placeholder="Module title" required>
              <button class="bg-primary text-white px-4 py-2 rounded-md hover:bg-emerald-700"><i class="fas fa-plus mr-1"></i>Add</button>
            </form>
          </div>

          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-border">
              <thead class="bg-muted">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">School</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Level</th>
                <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Title</th>
                <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
              </tr>
              </thead>
              <tbody class="bg-white divide-y divide-border">
              <?php foreach ($modules as $m): ?>
                <tr>
                  <td class="px-6 py-3 text-sm"><?= htmlspecialchars($m['school_name']) ?></td>
                  <td class="px-6 py-3 text-sm"><?= htmlspecialchars($m['level_name']) ?></td>
                  <td class="px-6 py-3 text-sm font-medium"><?= htmlspecialchars($m['title']) ?></td>
                  <td class="px-6 py-3 text-right text-sm">
                    <form method="post" class="inline-flex flex-wrap gap-2 items-center">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="entity" value="module">
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="__tab"  value="modules">
                      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">

                      <select name="level_id" class="border rounded px-2 py-1">
                        <?php foreach ($levels as $l): ?>
                          <option value="<?= (int)$l['id'] ?>" <?= sel($l['id'], $m['level_id']) ?>>
                            [<?= htmlspecialchars($l['school_name']) ?>] <?= htmlspecialchars($l['name']) ?>
                          </option>
                        <?php endforeach; ?>
                      </select>

                      <input name="title" class="border rounded px-2 py-1 w-56" value="<?= htmlspecialchars($m['title']) ?>" placeholder="Title">

                      <button class="px-3 py-1 bg-secondary text-secondary-foreground rounded">Save</button>
                    </form>

                    <form method="post" class="inline" onsubmit="return confirm('Delete this module?');">
                      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                      <input type="hidden" name="entity" value="module">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="__tab"  value="modules">
                      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                      <button class="ml-2 px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php endif; ?>

        <?php if ($tab === 'users'): ?>
          <section>
            <h2 class="text-lg font-medium mb-4">Users</h2>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Name</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Email</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Role</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Blocked?</th>
                  <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border">
                <?php foreach ($users as $u): ?>
                  <tr>
                    <td class="px-6 py-3 text-sm font-medium"><?= htmlspecialchars($u['full_name']) ?></td>
                    <td class="px-6 py-3 text-sm"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="px-6 py-3 text-sm">
                      <form method="post" class="inline-flex items-center gap-2">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="entity" value="user">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="__tab"  value="users">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">

                        <select name="role" class="border rounded px-2 py-1">
                          <option value="student" <?= sel('student',$u['role']) ?>>student</option>
                          <option value="admin"   <?= sel('admin',  $u['role']) ?>>admin</option>
                        </select>
                    </td>
                    <td class="px-6 py-3 text-sm">
                        <label class="inline-flex items-center gap-2 text-sm">
                          <input type="checkbox" name="is_blocked" <?= ((int)$u['is_blocked']===1?'checked':'') ?>>
                          <span><?= (int)$u['is_blocked'] ? 'Yes' : 'No' ?></span>
                        </label>
                    </td>
                    <td class="px-6 py-3 text-right text-sm">
                        <button class="px-3 py-1 bg-secondary text-secondary-foreground rounded">Save</button>
                      </form>

                      <form method="post" class="inline" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                        <input type="hidden" name="entity" value="user">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="__tab"  value="users">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button class="ml-2 px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>


        <?php if ($tab === 'materials'): ?>
          <section>
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-lg font-medium">Materials</h2>
              <p class="text-sm text-muted-foreground">Approve, mark pending, or delete uploaded study materials.</p>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-border">
                <thead class="bg-muted">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Title</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Uploader</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">School / Level / Module</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Price (Rs)</th>
                  <th class="px-6 py-3 text-left text-xs font-semibold text-muted-foreground uppercase">Approved?</th>
                  <th class="px-6 py-3 text-right text-xs font-semibold text-muted-foreground uppercase">Actions</th>
                </tr>
                </thead>
                <tbody class="bg-white divide-y divide-border">
                <?php foreach ($materials as $m): ?>
                  <tr>
                    <!-- Title + file + description -->
                    <td class="px-6 py-3 text-sm font-medium">
                      <div class="flex flex-col">
                        <span><?= htmlspecialchars($m['title']) ?></span>
                        <?php if (!empty($m['file_path'])): ?>
                          <a class="text-xs text-primary underline" href="<?= htmlspecialchars($m['file_path']) ?>" target="_blank"><i class="fa-solid fa-link mr-1"></i>Open file</a>
                        <?php endif; ?>
                        <?php if (!empty($m['description'])): ?>
                          <span class="text-xs text-muted-foreground mt-1 line-clamp-2"><?= htmlspecialchars($m['description']) ?></span>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Uploader -->
                    <td class="px-6 py-3 text-sm">
                      <div class="flex flex-col">
                        <span><?= htmlspecialchars($m['uploader_name']) ?></span>
                        <span class="text-xs text-muted-foreground"><?= htmlspecialchars($m['uploader_email']) ?></span>
                      </div>
                    </td>

                    <!-- School / Level / Module -->
                    <td class="px-6 py-3 text-sm">
                      <span class="text-xs text-muted-foreground">
                        <?= htmlspecialchars($m['school_name'] ?? '—') ?> / 
                        <?= htmlspecialchars($m['level_name'] ?? '—') ?> / 
                        <?= htmlspecialchars($m['module_title'] ?? '—') ?>
                      </span>
                    </td>

                    <!-- Price -->
                    <td class="px-6 py-3 text-sm">
                      Rs <?= htmlspecialchars(money_rs($m['price_cents'])) ?>
                    </td>

                    <!-- Approval status -->
                    <td class="px-6 py-3 text-sm">
                      <?php if ((int)$m['is_approved'] === 1): ?>
                        <span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800 text-xs font-medium">Approved</span>
                      <?php else: ?>
                        <span class="px-2 py-1 rounded bg-amber-100 text-amber-800 text-xs font-medium">Pending</span>
                      <?php endif; ?>
                    </td>

                    <!-- Action buttons -->
                    <td class="px-6 py-3 text-right text-sm">
                      <div class="flex flex-row gap-2 justify-end items-center">
                        <form method="post" class="inline" onsubmit="return confirm('Approve this material?');">
                          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                          <input type="hidden" name="entity" value="material">
                          <input type="hidden" name="action" value="quick">
                          <input type="hidden" name="__tab"  value="materials">
                          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                          <input type="hidden" name="to" value="1">
                          <button class="px-3 py-1 bg-emerald-600 text-white rounded">Approve</button>
                        </form>

                        <form method="post" class="inline" onsubmit="return confirm('Mark as pending?');">
                          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                          <input type="hidden" name="entity" value="material">
                          <input type="hidden" name="action" value="quick">
                          <input type="hidden" name="__tab"  value="materials">
                          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                          <input type="hidden" name="to" value="0">
                          <button class="px-3 py-1 bg-amber-600 text-white rounded">Pending</button>
                        </form>

                        <form method="post" class="inline" onsubmit="return confirm('Delete this material?');">
                          <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                          <input type="hidden" name="entity" value="material">
                          <input type="hidden" name="action" value="delete">
                          <input type="hidden" name="__tab"  value="materials">
                          <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                          <button class="px-3 py-1 bg-red-600 text-white rounded">Delete</button>
                        </form>
                      </div>
                    </td>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          </section>
        <?php endif; ?>



      </div>
    </div>
  </main>
</body>
</html>
