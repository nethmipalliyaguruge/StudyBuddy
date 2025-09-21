<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';

// Which tab to show initially
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'register' ? 'register' : 'login';

// --- helper for tab classes (you were using this in the markup) ---
function tabClass($isActive) {
  return $isActive
    ? 'py-2 px-4 font-medium text-primary border-b-2 border-primary focus:outline-none'
    : 'py-2 px-4 font-medium text-muted-foreground hover:text-foreground focus:outline-none';
}

// Handle POST (login/register)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();

  // LOGIN
  if (isset($_POST['__action']) && $_POST['__action'] === 'login') {
    $activeTab = 'login';
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pass === '') {
      flash('err', 'Please enter a valid email and password.');
    } else {
      $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
      $stmt->execute([$email]);
      $u = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($u && password_verify($pass, $u['password_hash'])) {
        if (!empty($u['is_blocked'])) {
          flash('err', 'Your account is blocked. Please contact admin.');
        } else {
          $_SESSION['uid'] = $u['id'];

          // 👇 use the return URL if provided, else fallback
          $to = get_return_to($u['role'] === 'admin' ? 'admin_dashboard.php' : 'dashboard.php');
          safe_redirect($to);
        }
      } else {
        flash('err', 'Invalid credentials.');
      }
    }
  }

  // REGISTER
  if (isset($_POST['__action']) && $_POST['__action'] === 'register') {
    $activeTab = 'register';
    $full_name = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['reg_email'] ?? '');
    $password  = $_POST['reg_password'] ?? '';
    $confirm   = $_POST['reg_confirm'] ?? '';
    $phone     = trim($_POST['phone'] ?? ''); // optional in your UI

    if (!$full_name || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6 || $password !== $confirm) {
      flash('err', 'Fill all fields correctly. Password must be ≥ 6 and match.');
    } else {
      try {
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role) VALUES (?, ?, ?, 'student')");
        $stmt->execute([$full_name, $email, password_hash($password, PASSWORD_BCRYPT)]);
        flash('ok', 'Account created! Please login.');
        $activeTab = 'login';
      } catch (PDOException $e) {
        // likely duplicate email
        flash('err', 'That email is already registered.');
      }
    }
  }
}
$title = "Login / Register - StudyBuddy APIIT";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>StudyBuddy APIIT - Login / Register</title>

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

  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
</head>
<body class="bg-gradient-to-br from-study-light to-white min-h-screen flex items-center justify-center p-4 text-foreground">

  <!-- Card -->
  <div class="bg-card rounded-2xl shadow-xl w-full max-w-md overflow-hidden border border-border">
    <!-- Branded header -->
    <div class="bg-study-primary py-6 px-8"><a href="index.php">
      <div class="flex items-center justify-center gap-2">
        <i class="fas fa-graduation-cap text-xl text-white"></i>
        <h1 class="text-2xl font-bold text-white text-center">StudyBuddy APIIT</h1>
      </div>
      <p class="text-white/80 text-center mt-1">Your Academic Resource Marketplace</p></a>
    </div>

    <!-- Body -->
    <div class="p-8">
      <!-- Flash messages -->
      <?php if ($m = flash('ok')): ?>
        <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200">
          <?= htmlspecialchars($m) ?>
        </div>
      <?php endif; ?>
      <?php if ($m = flash('err')): ?>
        <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 border border-red-200">
          <?= htmlspecialchars($m) ?>
        </div>
      <?php endif; ?>

      <!-- Tabs -->
      <div class="flex border-b border-border mb-6">
        <button type="button" onclick="showTab('login')"    class="<?= tabClass($activeTab==='login'); ?>"    id="loginTab">Login</button>
        <button type="button" onclick="showTab('register')" class="<?= tabClass($activeTab==='register'); ?>" id="registerTab">Register</button>
      </div>

      <!-- Login Form -->
      <form id="loginForm" class="space-y-4 <?= $activeTab==='login' ? '' : 'hidden'; ?>" method="post" action="">
        <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
        <input type="hidden" name="__action" value="login">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">

        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Email</label>
          <input name="email" type="email" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="you@apiit.lk" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Password</label>
          <input name="password" type="password" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="••••••••" required />
        </div>
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2">
            <input type="checkbox" class="h-4 w-4 text-primary focus:ring-ring border-input rounded"/>
            <span class="text-sm text-foreground">Remember me</span>
          </label>
          <a href="#" class="text-sm text-primary hover:underline">Forgot password?</a>
        </div>
        <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary/90 transition duration-200 font-medium">
          Login
        </button>
      </form>

      <!-- Register Form -->
      <form id="registerForm" class="space-y-4 <?= $activeTab==='register' ? '' : 'hidden'; ?>" method="post" action="">
        <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
        <input type="hidden" name="__action" value="register">

        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Full Name</label>
          <input name="full_name" type="text" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="John Doe" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Email</label>
          <input name="reg_email" type="email" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="you@apiit.lk" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Phone Number</label>
          <input name="phone" type="tel" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="+94 123 456 789" />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Password</label>
          <input name="reg_password" type="password" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="••••••••" required />
        </div>
        <div>
          <label class="block text-sm font-medium text-foreground mb-1">Confirm Password</label>
          <input name="reg_confirm" type="password" class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring" placeholder="••••••••" required />
        </div>
        <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary/90 transition duration-200 font-medium">
          Register
        </button>
      </form>
    </div>
  </div>

  <!-- Tabs logic -->
  <script>
    const loginTabBtn = document.getElementById('loginTab');
    const registerTabBtn = document.getElementById('registerTab');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    function setTabStyles(isLogin) {
      loginTabBtn.className = isLogin
        ? 'py-2 px-4 font-medium text-primary border-b-2 border-primary focus:outline-none'
        : 'py-2 px-4 font-medium text-muted-foreground hover:text-foreground focus:outline-none';
      registerTabBtn.className = !isLogin
        ? 'py-2 px-4 font-medium text-primary border-b-2 border-primary focus:outline-none'
        : 'py-2 px-4 font-medium text-muted-foreground hover:text-foreground focus:outline-none';
    }

    function showTab(tab) {
      const isLogin = tab === 'login';
      loginForm.classList.toggle('hidden', !isLogin);
      registerForm.classList.toggle('hidden', isLogin);
      setTabStyles(isLogin);

      // keep tab in URL on refresh
      const url = new URL(window.location);
      url.searchParams.set('tab', tab);
      history.replaceState({}, '', url);
    }

    // initial tab from PHP
    showTab('<?= $activeTab ?>');
  </script>
</body>
</html>

