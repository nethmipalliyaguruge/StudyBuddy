<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

// camelCase wrappers (use these in templates; they call your existing helpers)
function isLoggedIn()  { return is_logged_in(); }
function currentUser() { return current_user(); }
function requireLogin(){ return require_login(); }
function requireRole($role){ return require_role($role); }
function flashSet($k,$v){ return flash($k,$v); }
function flashGet($k){ return flash($k); }
function moneyRs($cents){ return money_rs($cents); }

// gate: must be logged in
requireLogin();

$user = currentUser();            // ['id','full_name','role', ...]
$role = $user['role'] ?? 'student';

// OPTIONAL: if this page is student-only, uncomment below to bounce admins
// if ($role !== 'student') { header('Location: admin_dashboard.php'); exit; }

include 'header.php';
$title = "Dashboard - StudyBuddy APIIT";
?>

<!-- small welcome / role
    <div class="mt-4 text-sm text-slate-600">
      Signed in as <span class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></span>
      • Role: <span class="font-medium"><?php echo htmlspecialchars($role); ?></span>
    </div> -->

<!-- Hero / Title -->
<section class="bg-slate-50">
  <div class="max-w-6xl mx-auto px-4 sm:px-6 py-12 sm:py-16 text-center">
    <h1 class="text-3xl sm:text-4xl font-extrabold">Note Management</h1>
    <p class="mt-2 text-slate-600 text-base sm:text-lg">
      Choose an action to manage your products
    </p>

  
    <!-- flashes -->
    <?php if ($m = flashGet('ok')): ?>
      <div class="mx-auto mt-4 max-w-xl p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
        <?php echo htmlspecialchars($m); ?>
      </div>
    <?php endif; ?>
    <?php if ($m = flashGet('err')): ?>
      <div class="mx-auto mt-4 max-w-xl p-3 rounded-md bg-red-100 text-red-800 border border-red-200">
        <?php echo htmlspecialchars($m); ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Action Cards -->
<main class="max-w-6xl mx-auto px-4 sm:px-6 pb-16">
  <div class="bg-white border border-border rounded-xl shadow-sm p-6 sm:p-8">
    <div class="grid gap-6 md:grid-cols-3">
      <!-- Create Note -->
      <a href="upload.php"
         class="card rounded-xl p-6 sm:p-8 bg-emerald-300/60 border border-emerald-200">
        <div class="flex flex-col items-center text-center">
          <div class="w-12 h-12 rounded-full border border-emerald-900/30 flex items-center justify-center text-2xl text-emerald-900 mb-4">
            <i class="fa-solid fa-plus"></i>
          </div>
          <h3 class="text-xl font-extrabold text-slate-900">Create Note</h3>
          <p class="text-slate-700 mt-1">Add New note</p>
        </div>
      </a>

      <!-- My Notes -->
      <a href="mynotes.php"
         class="card rounded-xl p-6 sm:p-8 bg-emerald-200/60 border border-emerald-200">
        <div class="flex flex-col items-center text-center">
          <div class="w-12 h-12 rounded-full border border-emerald-900/30 flex items-center justify-center text-2xl text-emerald-900 mb-4">
            <i class="fa-regular fa-file-lines"></i><span class="sr-only">My Notes</span>
          </div>
          <h3 class="text-xl font-extrabold text-slate-900">My Notes</h3>
          <p class="text-slate-700 mt-1">Browse and manage all notes</p>
        </div>
      </a>

      <!-- My Transactions -->
      <a href="mypurchases.php"
         class="card rounded-xl p-6 sm:p-8 bg-indigo-300/60 border border-indigo-200">
        <div class="flex flex-col items-center text-center">
          <div class="w-12 h-12 rounded-full border border-indigo-900/30 flex items-center justify-center text-2xl text-indigo-900 mb-4">
            <i class="fa-solid fa-wallet"></i>
          </div>
          <h3 class="text-xl font-extrabold text-slate-900">My Transactions</h3>
          <p class="text-slate-700 mt-1">Download and manage all<br/>Transactions</p>
        </div>
      </a>
    </div>

    <?php if ($role === 'admin'): ?>
      <!-- optional admin card if an admin opens this page -->
      <div class="grid gap-6 md:grid-cols-3 mt-6">
        <a href="admin_dashboard.php"
           class="card rounded-xl p-6 sm:p-8 bg-yellow-200/60 border border-yellow-300">
          <div class="flex flex-col items-center text-center">
            <div class="w-12 h-12 rounded-full border border-yellow-900/30 flex items-center justify-center text-2xl text-yellow-900 mb-4">
              <i class="fa-solid fa-shield-halved"></i>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900">Admin Panel</h3>
            <p class="text-slate-700 mt-1">Moderate uploads & users</p>
          </div>
        </a>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include 'footer.php'; ?>
