<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
$u = current_user(); // null if guest
$current = basename($_SERVER['PHP_SELF']);

function navClasses($file, $current) {
  $base = "px-3 py-2 rounded-md text-sm font-medium";
  $active = "text-primary border-b-2 border-primary";
  $inactive = "text-muted-foreground hover:text-primary";
  return $base . " " . ($current === $file ? $active : $inactive);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $title ?? "StudyBuddy APIIT" ?></title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] },
          colors: {
            primary: '#006644',
            'primary-foreground': '#ffffff',
            secondary: '#f0f9f5',
            'secondary-foreground': '#006644',
            accent: '#4ade80',
            'accent-foreground': '#006644',
            muted: '#f1f5f9',
            'muted-foreground': '#64748b',
            background: '#ffffff',
            foreground: '#0f172a',
            card: '#ffffff',
            'card-foreground': '#0f172a',
            border: '#e2e8f0',
            input: '#e2e8f0',
            ring: '#006644',
            'study-primary': '#006644',
            'study-accent': '#4ade80',
            'study-light': '#f0f9f5',
            'study-muted': '#d1d5db'
          }
        }
      }
    }
  </script>

  <!-- Component Layer -->
  <style type="text/tailwindcss">
    @layer components {
      .btn { @apply inline-flex items-center justify-center px-4 py-2 rounded-lg font-medium transition; }
      .btn-primary { @apply btn bg-primary text-white hover:bg-primary/90; }
      .btn-outline { @apply btn border border-border text-foreground hover:bg-muted; }

      .badge { @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium; }
      .badge-primary { @apply badge bg-secondary text-secondary-foreground; }

      .card { @apply bg-card border border-border rounded-xl; }

      .nav-link { @apply text-muted-foreground hover:text-primary; }
      .nav-link.is-active { @apply text-primary border-b-2 border-primary; }

      .input { @apply w-full px-3 py-2 border border-input rounded-md focus:outline-none focus:ring-2 focus:ring-ring; }

      .truncate-3 { display:-webkit-box; -webkit-line-clamp:3; -webkit-box-orient:vertical; overflow:hidden; }
    }
  </style>
</head>
<body class="bg-background text-foreground flex flex-col min-h-screen">

<?php if ($u && $u['role'] === 'admin'): ?>
  <!-- 🔹 Admin Header -->
  <header class="bg-white border-b border-border">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <div class="h-16 flex items-center justify-between">
        <a class="flex items-center gap-2" href="admin_dashboard.php">
          <i class="fas fa-graduation-cap text-2xl text-primary"></i>
          <span class="text-xl font-bold text-primary">StudyBuddy APIIT</span>
        </a>
        <div class="flex items-center gap-3">
          <span class="text-sm text-slate-700"><?= htmlspecialchars($u['full_name'] ?? '') ?></span>
          <a href="logout.php" class="text-sm font-medium text-slate-700 hover:text-primary">
            <i class="fas fa-sign-out-alt mr-1"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </header>

<?php elseif ($current === 'index.php'): ?>
  <!-- 🔹 Public Landing Header -->
  <header class="bg-white shadow-sm border-b">
    <nav class="container mx-auto px-4 py-4 flex justify-between items-center">
      <a href="index.php" class="flex items-center space-x-2">
        <i class="fas fa-graduation-cap text-2xl text-primary"></i>
        <h1 class="text-xl font-bold text-primary">StudyBuddy APIIT</h1>
      </a>

      <!-- Desktop links -->
      <div class="hidden md:flex space-x-6">
        <a href="index.php" class="text-primary hover:text-accent">Home</a>
        <a href="materials.php" class="text-gray-600 hover:text-primary">Study Materials</a>
      </div>

      <!-- Desktop auth -->
      <div class="hidden md:flex space-x-4">
        <a href="login.php" class="px-4 py-2 text-primary border border-primary rounded-md hover:bg-primary hover:text-white">Login</a>
        <a href="login.php?tab=register" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90">Register</a>
      </div>

      <!-- Mobile hamburger -->
      <button id="mobile-menu-btn-landing" class="md:hidden text-gray-600" aria-label="Open menu" aria-expanded="false">
        <i class="fas fa-bars text-xl"></i>
      </button>
    </nav>

    <!-- Mobile menu (landing) -->
    <div id="mobile-menu-landing" class="hidden md:hidden mt-2 space-y-2 px-4 pb-4 border-t border-border">
      <a href="index.php" class="block py-2 text-primary">Home</a>
      <a href="materials.php" class="block py-2 text-gray-600">Study Materials</a>
      <div class="pt-2 flex gap-3">
        <a href="login.php" class="flex-1 text-center px-4 py-2 text-primary border border-primary rounded-md hover:bg-primary hover:text-white">Login</a>
        <a href="login.php?tab=register" class="flex-1 text-center px-4 py-2 bg-primary text-white rounded-md hover:bg-primary/90">Register</a>
      </div>
    </div>
  </header>

<?php else: ?>

  <!-- 🔹 Default Header (materials.php, mynotes.php, mypurchases.php, etc.) -->
  <nav class="bg-white border-b border-border sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between h-16 items-center">

        <!-- Brand -->
        <a href="index.php" class="flex items-center space-x-2">
          <i class="fas fa-graduation-cap text-2xl text-primary"></i>
          <h1 class="text-xl font-bold text-primary">StudyBuddy APIIT</h1>
        </a>

        <!-- Desktop nav -->
        <div class="hidden md:flex space-x-4">
          <a href="dashboard.php"   class="<?= navClasses('dashboard.php', $current) ?>">Dashboard</a>
          <a href="materials.php"   class="<?= navClasses('materials.php', $current) ?>">Study Materials</a>
          <a href="mynotes.php"     class="<?= navClasses('mynotes.php', $current) ?>">My Notes</a>
          <a href="mypurchases.php" class="<?= navClasses('mypurchases.php', $current) ?>">My Purchases</a>
        </div>

        <!-- Desktop right -->
        <div class="hidden md:flex items-center space-x-4">
          <?php if ($u): ?>
            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($u['full_name']) ?></span>
            <a href="profile.php" class="text-sm font-medium text-slate-700 hover:text-primary">Profile</a>
            <a href="logout.php"  class="text-sm font-medium text-slate-700 hover:text-primary">Logout</a>
          <?php else: ?>
            <a href="login.php" class="text-sm font-medium text-slate-700 hover:text-primary">Login</a>
            <a href="login.php?tab=register" class="text-sm font-medium text-slate-700 hover:text-primary">Register</a>
          <?php endif; ?>

          <?php $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
          <a href="cart.php" class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-border hover:bg-muted">
            <i class="fas fa-shopping-cart"></i>
            <span>Cart</span>
            <?php if ($cartCount): ?>
              <span class="ml-1 inline-flex items-center justify-center text-xs font-semibold bg-primary text-white rounded-full min-w-[1.25rem] h-5 px-1">
                <?= (int)$cartCount ?>
              </span>
            <?php endif; ?>
          </a>
        </div>

        <!-- Mobile hamburger (default header) -->
        <button id="mobile-menu-btn-main" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-gray-600 hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring" aria-label="Open menu" aria-expanded="false">
          <i class="fas fa-bars text-xl"></i>
        </button>
      </div>
    </div>

    <!-- Mobile menu (default header) -->
    <div id="mobile-menu-main" class="md:hidden hidden border-t border-border">
      <div class="px-4 py-3 space-y-1">
        <a href="dashboard.php"   class="block px-2 py-2 rounded-md text-sm hover:bg-muted <?= $current==='dashboard.php'   ? 'text-primary' : 'text-slate-700' ?>">Dashboard</a>
        <a href="materials.php"   class="block px-2 py-2 rounded-md text-sm hover:bg-muted <?= $current==='materials.php'   ? 'text-primary' : 'text-slate-700' ?>">Study Materials</a>
        <a href="mynotes.php"     class="block px-2 py-2 rounded-md text-sm hover:bg-muted <?= $current==='mynotes.php'     ? 'text-primary' : 'text-slate-700' ?>">My Notes</a>
        <a href="mypurchases.php" class="block px-2 py-2 rounded-md text-sm hover:bg-muted <?= $current==='mypurchases.php' ? 'text-primary' : 'text-slate-700' ?>">My Purchases</a>

        <div class="my-2 border-t border-border"></div>

        <?php if ($u): ?>
          <div class="flex items-center justify-between">
            <span class="text-sm font-medium text-slate-700"><?= htmlspecialchars($u['full_name']) ?></span>
            <a href="profile.php" class="text-sm font-medium text-primary">Profile</a>
          </div>
          <a href="logout.php" class="mt-2 block w-full text-center px-3 py-2 rounded-md border border-border hover:bg-muted text-sm font-medium text-slate-700">Logout</a>
        <?php else: ?>
          <div class="flex gap-2 pt-1">
            <a href="login.php" class="flex-1 text-center px-3 py-2 rounded-md border border-primary text-primary hover:bg-primary hover:text-white text-sm">Login</a>
            <a href="login.php?tab=register" class="flex-1 text-center px-3 py-2 rounded-md bg-primary text-white hover:bg-primary/90 text-sm">Register</a>
          </div>
        <?php endif; ?>

        <?php $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
        <a href="cart.php" class="mt-2 relative flex items-center justify-between px-3 py-2 rounded-md border border-border hover:bg-muted text-sm">
          <span class="inline-flex items-center gap-2">
            <i class="fas fa-shopping-cart"></i> Cart
          </span>
          <?php if ($cartCount): ?>
            <span class="ml-2 inline-flex items-center justify-center text-xs font-semibold bg-primary text-white rounded-full min-w-[1.25rem] h-5 px-1"><?= (int)$cartCount ?></span>
          <?php endif; ?>
        </a>
      </div>
    </div>
  </nav>
<?php endif; ?>

<script>
  // Landing header mobile toggle
  (function(){
    const btn = document.getElementById('mobile-menu-btn-landing');
    const menu = document.getElementById('mobile-menu-landing');
    if (btn && menu) {
      btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));
        menu.classList.toggle('hidden');
      });
    }
  })();

  // Default header mobile toggle
  (function(){
    const btn = document.getElementById('mobile-menu-btn-main');
    const menu = document.getElementById('mobile-menu-main');
    if (btn && menu) {
      btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));
        menu.classList.toggle('hidden');
      });
    }
  })();

  <script>
// Reusable password toggler
document.addEventListener('DOMContentLoaded', function () {
  // Attach to any button with data-password-toggle
  document.querySelectorAll('[data-password-toggle]').forEach(function(btn){
    const inputId = btn.getAttribute('data-password-toggle');
    const input   = document.getElementById(inputId);
    if (!input) return;

    // Set initial ARIA and icon state
    const icon = btn.querySelector('i');
    const setState = (show) => {
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      if (icon) {
        icon.classList.toggle('fa-eye', !show);
        icon.classList.toggle('fa-eye-slash', show);
      }
    };

    setState(false); // default hidden

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const showing = input.type === 'text';
      const pos = input.selectionStart; // keep caret position
      setState(!showing);
      // restore focus/caret for nicer UX
      input.focus();
      try { input.setSelectionRange(pos, pos); } catch(_) {}
    });
  });
});
</script>

