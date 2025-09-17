<?php
// config/helpers.php

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
  function require_login(string $redirect = 'login.php'): void {
    if (!is_logged_in()) { header("Location: {$redirect}"); exit; }
  }
}

if (!function_exists('require_role')) {
  function require_role($roles, string $loginRedirect='login.php', string $deniedRedirect='index.php'): void {
    $u = current_user();
    if (!$u) { header("Location: {$loginRedirect}"); exit; }
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

// ---------- MONEY HELPERS (optional) ----------
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
