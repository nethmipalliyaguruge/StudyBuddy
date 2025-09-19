<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

check_csrf();

$note_id = (int)($_POST['note_id'] ?? 0);
$from    = $_POST['from'] ?? 'materials.php';

if ($note_id <= 0) {
  flash('err', 'Invalid item.');
  header("Location: {$from}"); exit;
}

// make sure note exists & approved
$stmt = $pdo->prepare("SELECT id FROM notes WHERE id=? AND is_approved=1 LIMIT 1");
$stmt->execute([$note_id]);
if (!$stmt->fetchColumn()) {
  flash('err', 'Item not available.');
  header("Location: {$from}"); exit;
}

$_SESSION['cart'] = $_SESSION['cart'] ?? [];
// prevent duplicates (1 per digital product)
if (!in_array($note_id, $_SESSION['cart'], true)) {
  $_SESSION['cart'][] = $note_id;
  flash('ok', 'Added to cart.');
} else {
  flash('ok', 'Already in cart.');
}

header("Location: {$from}");
