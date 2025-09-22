<?php
// config/helpers.php

// ---------- SESSION GUARD ----------
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// ---------- URL + REDIRECT HELPERS ----------

if (!function_exists('current_url')) {
  /**
   * Build the full current URL (path + query) for return URLs.
   */
  function current_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $uri    = $_SERVER['REQUEST_URI'] ?? '/';
    return $scheme . '://' . $host . $uri;
  }
}

if (!function_exists('sanitize_return_to')) {
  /**
   * Allow only safe, same-site relative URLs to prevent open redirects.
   * Accepts:
   *   - absolute path like /pages/note_details.php?id=5
   *   - full URL only if host matches current host
   */
  function sanitize_return_to(string $url, string $fallback = 'index.php'): string {
    $url = trim($url);

    if ($url === '') return $fallback;

    // Disallow CRLF
    if (preg_match('/[\r\n]/', $url)) return $fallback;

    // If it's a full URL, require same host
    if (preg_match('#^https?://#i', $url)) {
      $hostNow = $_SERVER['HTTP_HOST'] ?? '';
      $hostUrl = parse_url($url, PHP_URL_HOST);
      if (!$hostUrl || !hash_equals($hostNow, $hostUrl)) return $fallback;
      return $url;
    }

    // Otherwise allow only absolute paths like /something
    if ($url[0] === '/' && (substr($url, 0, 2) !== '//')) {
      return $url;
    }

    // As a small convenience, accept a relative file like "materials.php"
    // and turn it into an absolute path.
    if ($url[0] !== '/') {
      $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
      return ($base === '' ? '' : $base) . '/' . ltrim($url, '/');
    }

    return $fallback;
  }
}

if (!function_exists('get_return_to')) {
  /**
   * Read return URL from GET/POST and sanitize it.
   */
  function get_return_to(string $fallback = 'index.php'): string {
    $raw = $_POST['redirect'] ?? $_GET['redirect'] ?? '';
    return sanitize_return_to($raw, $fallback);
  }
}

if (!function_exists('login_url')) {
  /**
   * Build login.php with ?redirect=...
   */
  function login_url(?string $returnTo = null, string $loginPath = 'login.php'): string {
    $returnTo = $returnTo ?? current_url();
    $returnTo = sanitize_return_to($returnTo, 'index.php');
    $glue = str_contains($loginPath, '?') ? '&' : '?';
    return $loginPath . $glue . 'redirect=' . rawurlencode($returnTo);
  }
}

if (!function_exists('safe_redirect')) {
  function safe_redirect(string $to): void {
    header('Location: ' . $to);
    exit;
  }
}

// ---------- AUTH ----------

if (!function_exists('is_logged_in')) {
  function is_logged_in(): bool { return !empty($_SESSION['uid']); }
}

if (!function_exists('current_user')) {
  function current_user(): ?array {
    global $pdo;
    if (empty($_SESSION['uid'])) return null;
    static $cached = null;
    if ($cached !== null) return $cached;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['uid']]);
    $cached = $stmt->fetch() ?: null;
    return $cached;
  }
}

if (!function_exists('require_login')) {
  /**
   * If not logged in, send to login.php with ?redirect=<current page or given URL>
   */
  function require_login(?string $redirect = null, string $loginPath = 'login.php'): void {
    if (is_logged_in()) return;
    $returnTo = $redirect ? sanitize_return_to($redirect, 'index.php') : current_url();
    $login = login_url($returnTo, $loginPath);
    header('Location: ' . $login);
    exit;
  }
}

if (!function_exists('require_role')) {
  function require_role($roles, string $loginRedirect='login.php', string $deniedRedirect='index.php'): void {
    $u = current_user();
    if (!$u) {
      // include current URL in login redirect
      $login = login_url(current_url(), $loginRedirect);
      header("Location: {$login}");
      exit;
    }
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($u['role'] ?? '', $roles, true)) {
      header("Location: {$deniedRedirect}");
      exit;
    }
  }
}

// ---------- FLASH ----------

if (!function_exists('flash')) {
  /**
   * flash('ok','Saved!') to set; flash('ok') to read & clear
   */
  function flash(string $key, ?string $msg = null) {
    if ($msg !== null) { $_SESSION['flash'][$key] = $msg; return; }
    $m = $_SESSION['flash'][$key] ?? null;
    if ($m !== null) unset($_SESSION['flash'][$key]);
    return $m;
  }
}

// ---------- CSRF ----------

if (!function_exists('csrf_token')) {
  function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
  }
}
if (!function_exists('check_csrf')) {
  function check_csrf(): void {
    if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '')) {
      http_response_code(400);
      exit('Invalid CSRF token.');
    }
  }
}

// ---------- MONEY HELPERS ----------

if (!function_exists('money_rs')) {
  function money_rs(int $cents): string {
    return 'Rs. ' . number_format($cents / 100, 2);
  }
}
if (!function_exists('fee_5pct')) {
  function fee_5pct(int $base_cents): int {
    return (int) round($base_cents * 0.05);
  }
}

function hidden_inputs_from_current_get(array $exclude = ['q','page']) {
  foreach ($_GET as $k => $v) {
    if (in_array($k, $exclude, true)) continue;
    if (is_array($v)) {
      foreach ($v as $vv) {
        echo '<input type="hidden" name="'.htmlspecialchars($k).'[]" value="'.htmlspecialchars($vv).'">' . "\n";
      }
    } else {
      echo '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'">' . "\n";
    }
  }
}

