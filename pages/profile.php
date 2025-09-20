<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$u = current_user();
$title = "My Profile - StudyBuddy APIIT";

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $full_name = trim($_POST['full_name'] ?? '');
  $phone     = trim($_POST['phone'] ?? '');

  if ($full_name === '') {
    flash('err', 'Full name is required.');
  } else {
    $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?");
    $stmt->execute([$full_name, $phone, (int)$u['id']]);
    flash('ok', 'Profile updated successfully.');
    // refresh session data
    $_SESSION['uid'] = $u['id'];
    header("Location: profile.php");
    exit;
  }
}

include 'header.php';
?>
<body class="min-h-screen flex flex-col">
    <main class="flex-1 max-w-6xl mx-auto px-4 sm:px-6 py-8">
  <h1 class="text-2xl font-bold text-foreground mb-6">My Profile</h1>

  <!-- Flash messages -->
  <?php if ($m = flash('ok')): ?>
    <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>
  <?php if ($m = flash('err')): ?>
    <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
  <?php endif; ?>

  <div class="card p-6">
    <form method="post" class="space-y-5">
      <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">

      <div>
        <label class="block text-sm font-medium text-muted-foreground mb-1">Full Name</label>
        <input type="text" name="full_name" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>"
          class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring">
      </div>

      <div>
        <label class="block text-sm font-medium text-muted-foreground mb-1">Email</label>
        <input type="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" disabled
          class="w-full px-4 py-2 border border-input rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
      </div>

      <div>
        <label class="block text-sm font-medium text-muted-foreground mb-1">Phone</label>
        <input type="tel" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>"
          class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring">
      </div>

      <div class="flex justify-end space-x-3">
        <a href="dashboard.php" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</a>
        <button type="submit" class="btn bg-primary text-white hover:bg-primary/90">Save Changes</button>
      </div>
    </form>
  </div>
</main>
<?php include 'footer.php'; ?>
