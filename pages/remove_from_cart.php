<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();
check_csrf();

$note_id = (int)($_POST['note_id'] ?? 0);

if (!empty($_SESSION['cart'])) {
  $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], fn($id) => (int)$id !== $note_id));
  flash('ok', 'Item removed.');
}

header('Location: cart.php');
