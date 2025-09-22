<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$title = "My Profile - StudyBuddy APIIT";

/** Fetch fresh user each request */
function profile_current_user(PDO $pdo) {
  $id = (int)(current_user()['id'] ?? 0);
  $stmt = $pdo->prepare("SELECT id, full_name, email, phone, password_hash FROM users WHERE id=? LIMIT 1");
  $stmt->execute([$id]);
  return $stmt->fetch(PDO::FETCH_ASSOC);
}

$u = profile_current_user($pdo);

/** ---------- POST handlers ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $action = $_POST['action'] ?? '';

  try {
    if ($action === 'update_profile') {
      $full_name = trim($_POST['full_name'] ?? '');
      $phone     = trim($_POST['phone'] ?? '');

      if ($full_name === '') {
        throw new RuntimeException('Full name is required.');
      }

      // (Optional) lightweight phone validation — accept empty or +digits/space/- up to 20 chars
      if ($phone !== '' && !preg_match('/^[\+\d][\d\s\-]{6,19}$/', $phone)) {
        throw new RuntimeException('Please enter a valid phone number (or leave it blank).');
      }

      $stmt = $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?");
      $stmt->execute([$full_name, $phone, (int)$u['id']]);

      flash('ok', 'Profile updated successfully.');
      // refresh user snapshot
      $u = profile_current_user($pdo);

    } elseif ($action === 'change_password') {
      $current = $_POST['current_password'] ?? '';
      $new     = $_POST['new_password'] ?? '';
      $confirm = $_POST['confirm_password'] ?? '';

      if ($current === '' || $new === '' || $confirm === '') {
        throw new RuntimeException('Please fill all password fields.');
      }
      if (!password_verify($current, $u['password_hash'])) {
        throw new RuntimeException('Current password is incorrect.');
      }
      if (strlen($new) < 6) {
        throw new RuntimeException('New password must be at least 6 characters.');
      }
      if ($new !== $confirm) {
        throw new RuntimeException('New password and confirmation do not match.');
      }

      $newHash = password_hash($new, PASSWORD_BCRYPT);
      $stmt = $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?");
      $stmt->execute([$newHash, (int)$u['id']]);

      flash('ok', 'Password changed successfully.');

    } else {
      throw new RuntimeException('Invalid action.');
    }

    header("Location: profile.php");
    exit;

  } catch (Throwable $e) {
    flash('err', $e->getMessage());
    header("Location: profile.php");
    exit;
  }
}

include 'header.php';
?>

<main class="bg-muted text-foreground flex-1">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-foreground mb-6">My Profile</h1>

    <!-- Flash messages -->
    <?php if ($m = flash('ok')): ?>
      <div class="mb-4 p-3 rounded-md bg-emerald-100 text-emerald-800 border border-emerald-200"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>
    <?php if ($m = flash('err')): ?>
      <div class="mb-4 p-3 rounded-md bg-red-100 text-red-800 border border-red-200"><?= htmlspecialchars($m) ?></div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Profile details -->
      <div class="card p-6 border border-border rounded-lg bg-card">
        <h2 class="text-lg font-semibold mb-4">Profile Details</h2>
        <form method="post" class="space-y-5">
          <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
          <input type="hidden" name="action" value="update_profile">

          <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1" for="prof_fullname">Full Name</label>
            <input id="prof_fullname" type="text" name="full_name" value="<?= htmlspecialchars($u['full_name'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring">
          </div>

          <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1" for="prof_email">Email</label>
            <input id="prof_email" type="email" value="<?= htmlspecialchars($u['email'] ?? '') ?>" disabled
                   class="w-full px-4 py-2 border border-input rounded-lg bg-gray-100 text-gray-500 cursor-not-allowed">
          </div>

          <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1" for="prof_phone">Phone</label>
            <input id="prof_phone" type="tel" name="phone" value="<?= htmlspecialchars($u['phone'] ?? '') ?>"
                   class="w-full px-4 py-2 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                   placeholder="+94 71 234 5678">
          </div>

          <div class="flex justify-end gap-3">
            <a href="dashboard.php" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">Cancel</a>
            <button type="submit" class="btn bg-primary text-white hover:bg-primary/90">Save Changes</button>
          </div>
        </form>
      </div>

      <!-- Change password -->
      <div class="card p-6 border border-border rounded-lg bg-card">
        <h2 class="text-lg font-semibold mb-4">Change Password</h2>
        <form method="post" class="space-y-5">
          <input type="hidden" name="csrf" value="<?= csrf_token(); ?>">
          <input type="hidden" name="action" value="change_password">

          <!-- Current password -->
          <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1" for="cur_pass">Current Password</label>
            <div class="relative">
              <input id="cur_pass" type="password" name="current_password" autocomplete="current-password"
                     class="w-full px-4 py-2 pr-10 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                     placeholder="••••••••">
              <div class="absolute inset-y-0 right-2 flex items-center">
                <button type="button"
                        class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring"
                        data-password-toggle="cur_pass" aria-label="Show password" aria-pressed="false">
                  <i class="fas fa-eye text-muted-foreground"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- New password -->
          <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1" for="new_pass">New Password</label>
            <div class="relative">
              <input id="new_pass" type="password" name="new_password" autocomplete="new-password"
                     class="w-full px-4 py-2 pr-10 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                     placeholder="At least 6 characters">
              <div class="absolute inset-y-0 right-2 flex items-center">
                <button type="button"
                        class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring"
                        data-password-toggle="new_pass" aria-label="Show password" aria-pressed="false">
                  <i class="fas fa-eye text-muted-foreground"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Confirm new password -->
          <div>
            <label class="block text-sm font-medium text-muted-foreground mb-1" for="new_pass2">Confirm New Password</label>
            <div class="relative">
              <input id="new_pass2" type="password" name="confirm_password" autocomplete="new-password"
                     class="w-full px-4 py-2 pr-10 border border-input rounded-lg focus:ring-2 focus:ring-ring focus:border-ring"
                     placeholder="Repeat new password">
              <div class="absolute inset-y-0 right-2 flex items-center">
                <button type="button"
                        class="inline-flex items-center justify-center w-8 h-8 rounded hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring"
                        data-password-toggle="new_pass2" aria-label="Show password" aria-pressed="false">
                  <i class="fas fa-eye text-muted-foreground"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit" class="btn bg-primary text-white hover:bg-primary/90">Update Password</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</main>

<!-- Password Show/Hide: attaches to any button with [data-password-toggle] -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function(btn){
      const inputId = btn.getAttribute('data-password-toggle');
      const input   = document.getElementById(inputId);
      if (!input) return;

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

      setState(false); // start hidden

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const showing = input.type === 'text';
        const pos = input.selectionStart; // keep caret position if supported
        setState(!showing);
        input.focus();
        try { input.setSelectionRange(pos, pos); } catch(_) {}
      });
    });
  });
</script>

<?php include 'footer.php'; ?>
