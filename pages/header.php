<?php
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../config/helpers.php';
$u = current_user(); // null if guest
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $title ?></title>

  <!-- Tailwind -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
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

  <!-- Icons + Font -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .card-hover { transition: all .3s ease; }
    .card-hover:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,.08); }
    .filter-checkbox:checked + label { background-color: rgba(0,102,68,.08); border-color: #006644; }
  </style>
</head>
<?php
  // header.php
  $current = basename($_SERVER['PHP_SELF']); // e.g. "materials.php", "mynotes.php"
  function navClasses($file, $current) {
    $base = "px-3 py-2 rounded-md text-sm font-medium";
    $active = "text-primary border-b-2 border-primary";
    $inactive = "text-muted-foreground hover:text-primary";
    return $base . " " . ($current === $file ? $active : $inactive);
  }
?>
<nav class="bg-white border-b border-border sticky top-0 z-50">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between h-16 items-center">
      
      <!-- Left side: Logo + Links -->
      <div class="flex items-center space-x-10">
        <!-- Logo -->
        <a href="index.php" class="flex items-center space-x-2">
          <i class="fas fa-graduation-cap text-2xl text-primary"></i>
          <h1 class="text-xl font-bold text-primary">StudyBuddy APIIT</h1>
        </a>

        <!-- Main nav -->
        <div class="hidden md:flex space-x-4">
          <a href="dashboard.php" class="<?= navClasses('dashboard.php', $current) ?>" <?= $current==='dashboard.php'?'aria-current="page"':''; ?>>Dashboard</a>
          <a href="materials.php" class="<?= navClasses('materials.php', $current) ?>" <?= $current==='materials.php'?'aria-current="page"':''; ?>>Study Materials</a>
          <a href="mynotes.php" class="<?= navClasses('mynotes.php', $current) ?>" <?= $current==='mynotes.php'?'aria-current="page"':''; ?>>My Notes</a>
          <a href="mypurchases.php" class="<?= navClasses('mypurchases.php', $current) ?>" <?= $current==='mypurchases.php'?'aria-current="page"':''; ?>>My Purchases</a>
        </div>
      </div>

      <!-- Right side: Auth -->
      <div class="flex items-center space-x-4">
        <?php if ($u): ?>
          <span class="text-sm font-medium text-slate-700">
            <?= htmlspecialchars($u['full_name'] ?? 'User') ?>
          </span>
          <a href="logout.php" class="text-sm font-medium text-slate-700 hover:text-primary transition-colors">
            Logout
          </a>
        <?php else: ?>
          <a href="login.php" class="text-sm font-medium text-slate-700 hover:text-primary transition-colors">Login</a>
          <a href="login.php?tab=register" class="text-sm font-medium text-slate-700 hover:text-primary transition-colors">Register</a>
        <?php endif; ?>
      </div>

      <?php $cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
        <a href="cart.php" class="relative inline-flex items-center gap-2 px-3 py-1.5 rounded-md border border-border hover:bg-muted">
          <i class="fas fa-shopping-cart"></i>
          <span>Cart</span>
          <?php if ($cartCount): ?>
            <span class="ml-1 inline-flex items-center justify-center text-xs font-semibold bg-primary text-white rounded-full min-w-[1.25rem] h-5 px-1">
              <?= $cartCount ?>
            </span>
          <?php endif; ?>
        </a>


    </div>
  </div>
</nav>



            <!-- Search Bar -->

            <!-- <div class="max-w-lg w-full lg:max-w-xs">
              <label for="search" class="sr-only">Search</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-search text-gray-400"></i>
                </div>
                <input id="search" name="search" type="search"
                       class="block w-full pl-10 pr-3 py-2 border border-input rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-ring focus:border-ring sm:text-sm"
                       placeholder="Search materials...">
              </div>
            </div> -->
