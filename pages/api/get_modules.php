<?php
// pages/api/get_modules.php
declare(strict_types=1);

ini_set('display_errors','0');
ini_set('html_errors','0');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/config.php';

try {
  $level_id = isset($_GET['level_id']) ? (int)$_GET['level_id'] : 0;
  if ($level_id <= 0) {
    throw new RuntimeException('Invalid level_id');
  }

  // Keep it simple: select only columns that definitely exist
  $stmt = $pdo->prepare("SELECT id, title FROM modules WHERE level_id = ? ORDER BY title");
  $stmt->execute([$level_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // If you DO have a 'code' column, you can switch to:
  // SELECT id, title, code FROM modules WHERE level_id = ? ORDER BY title

  echo json_encode(['ok'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
