<?php
// pages/download.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/helpers.php';
require_login();

function resolve_to_fs(string $dbPath): ?string {
  $p = trim($dbPath);
  if ($p === '') return null;

  // Normalise slashes
  $p = str_replace('\\', '/', $p);

  // Full URL? -> use the path part as a web path
  if (preg_match('#^https?://#i', $p)) {
    $path = parse_url($p, PHP_URL_PATH) ?? '';
    $p = $path ?: '';
    if ($p === '') return null;
  }

  // Windows (C:/...) or Unix absolute (/var/...) filesystem path?
  if (preg_match('#^[A-Za-z]:/#', $p) || preg_match('#^//[A-Za-z]#', $p)) {
    $abs = realpath($p);
    return ($abs && is_file($abs)) ? $abs : null;
  }

  // Web-root absolute path starting with "/" -> map to document root
  if (isset($p[0]) && $p[0] === '/') {
    $docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    if ($docRoot !== '') {
      $abs = realpath($docRoot . $p);
      if ($abs && is_file($abs)) return $abs;
    }
    // If docroot mapping failed, try project root as a fallback
    $projectRoot = realpath(__DIR__ . '/..');
    if ($projectRoot) {
      $abs = realpath($projectRoot . $p);
      if ($abs && is_file($abs)) return $abs;
    }
    return null;
  }

  // Relative path (e.g., "uploads/notes/file.pdf") -> project root
  $projectRoot = realpath(__DIR__ . '/..'); // pages/.. = project root
  if ($projectRoot) {
    $abs = realpath($projectRoot . '/' . ltrim($p, '/'));
    if ($abs && is_file($abs)) return $abs;
  }

  // Last try: join with document root
  $docRoot = rtrim(str_replace('\\','/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  if ($docRoot !== '') {
    $abs = realpath($docRoot . '/' . ltrim($p, '/'));
    if ($abs && is_file($abs)) return $abs;
  }

  return null;
}

/** Guess a MIME type (safe fallback to octet-stream). */
function guess_mime(string $path): string {
  if (function_exists('mime_content_type')) {
    $m = @mime_content_type($path);
    if ($m && $m !== 'application/octet-stream') return $m;
  }
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  $map = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'txt'  => 'text/plain',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'png'  => 'image/png',
    'webp' => 'image/webp',
  ];
  return $map[$ext] ?? 'application/octet-stream';
}

/* ------------------ entitlement ------------------ */

$u = current_user();
$user_id = (int)$u['id'];
$note_id = (int)($_GET['note_id'] ?? 0);
if ($note_id <= 0) {
  http_response_code(404);
  exit('Missing note_id');
}

// buyer (paid)?
$entitled = false;
$chk = $pdo->prepare("SELECT 1 FROM purchases WHERE buyer_id=? AND note_id=? AND status='paid' LIMIT 1");
$chk->execute([$user_id, $note_id]);
$entitled = (bool)$chk->fetchColumn();

// seller?
if (!$entitled) {
  $chk = $pdo->prepare("SELECT 1 FROM notes WHERE id=? AND seller_id=? LIMIT 1");
  $chk->execute([$note_id, $user_id]);
  $entitled = (bool)$chk->fetchColumn();
}

if (!$entitled) {
  http_response_code(403);
  exit('Not allowed');
}

/* ------------------ fetch note & resolve file ------------------ */

$st = $pdo->prepare("SELECT file_path, title FROM notes WHERE id=? LIMIT 1");
$st->execute([$note_id]);
$row = $st->fetch(PDO::FETCH_ASSOC);
if (!$row) {
  http_response_code(404);
  exit('Note not found');
}

$dbPath = trim((string)$row['file_path']);
$abs = resolve_to_fs($dbPath);

if (!$abs) {
  http_response_code(404);
  exit('File missing. Stored path: ' . htmlspecialchars($dbPath));
}

/* ------------------ stream file ------------------ */

$niceName = preg_replace('/[^A-Za-z0-9._-]+/', '_', ($row['title'] ?: ('note_'.$note_id)));
$ext = pathinfo($abs, PATHINFO_EXTENSION);
if ($ext !== '') $niceName .= '.' . $ext;

$mime = guess_mime($abs);
$size = filesize($abs);

header('Content-Type: ' . $mime);
header('Content-Length: ' . $size);
header('Content-Disposition: attachment; filename="' . $niceName . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=0, must-revalidate');

readfile($abs);
exit;
