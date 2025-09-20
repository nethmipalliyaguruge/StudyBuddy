<?php
// pages/download.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$u = current_user();
$user_id = (int)$u['id'];

$note_id = (int)($_GET['note_id'] ?? 0);
if ($note_id <= 0) { http_response_code(404); exit('Missing note_id'); }

// check entitlement: buyer (paid) OR seller of the note
$entitled = false;

// buyer?
$chk = $pdo->prepare("SELECT 1 FROM purchases WHERE buyer_id=? AND note_id=? AND status='paid' LIMIT 1");
$chk->execute([$user_id, $note_id]);
$entitled = (bool)$chk->fetchColumn();

// seller?
if (!$entitled) {
  $chk = $pdo->prepare("SELECT 1 FROM notes WHERE id=? AND seller_id=? LIMIT 1");
  $chk->execute([$note_id, $user_id]);
  $entitled = (bool)$chk->fetchColumn();
}

if (!$entitled) { http_response_code(403); exit('Not allowed'); }

// get file
$st = $pdo->prepare("SELECT file_path, title FROM notes WHERE id=? LIMIT 1");
$st->execute([$note_id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) { http_response_code(404); exit('Note not found'); }

$fp = trim((string)$row['file_path']);
$abs = realpath(__DIR__ . '/uploads/' . $fp);
if (!$abs || !is_file($abs)) { http_response_code(404); exit('File missing'); }

$fname = preg_replace('/[^A-Za-z0-9._-]+/', '_', ($row['title'] ?: ('note_'.$note_id))) . '.' . pathinfo($abs, PATHINFO_EXTENSION);

// stream
header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: attachment; filename="'. $fname .'"');
readfile($abs);
