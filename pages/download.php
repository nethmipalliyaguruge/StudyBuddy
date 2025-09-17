<?php
// download.php?id=NOTE_ID
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

$note_id = (int)($_GET['id'] ?? 0);
if ($note_id <= 0) { http_response_code(404); exit; }

$stmt = $pdo->prepare("SELECT n.file_path, n.title FROM notes n WHERE n.id=? LIMIT 1");
$stmt->execute([$note_id]);
$note = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$note || empty($note['file_path'])) { http_response_code(404); exit; }

// TODO: Replace with your real purchase check:
function user_has_purchased($user_id, $note_id) {
  // e.g., SELECT 1 FROM orders WHERE buyer_id=? AND note_id=? AND status='paid'
  return true; // <- TEMP: allow for now; set to real logic
}

$user = current_user();
if (!user_has_purchased((int)$user['id'], $note_id)) {
  http_response_code(403);
  exit('You have not purchased this item.');
}

// Serve file from disk (keep uploads OUTSIDE web root ideally)
$UPLOAD_DIR = realpath(__DIR__ . '/uploads'); // pages/uploads (public). Move out of webroot for real security.
$disk_path  = $UPLOAD_DIR ? $UPLOAD_DIR . DIRECTORY_SEPARATOR . $note['file_path'] : null;

if (!$disk_path || !is_file($disk_path)) { http_response_code(404); exit('File missing.'); }

$filename = basename($note['file_path']);
$ext      = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
$ctype    = ($ext === 'pdf') ? 'application/pdf' : 'application/octet-stream';

header('Content-Type: ' . $ctype);
header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"');
header('Content-Length: ' . filesize($disk_path));
readfile($disk_path);
