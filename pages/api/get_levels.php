<?php
// pages/api/get_levels.php
declare(strict_types=1);

// Make sure we ONLY ever output JSON
ini_set('display_errors','0');
ini_set('html_errors','0');
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../../config/config.php';

try {
  $school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
  if ($school_id <= 0) {
    throw new RuntimeException('Invalid school_id');
  }

  $stmt = $pdo->prepare("SELECT id, name FROM levels WHERE school_id = ? ORDER BY name");
  $stmt->execute([$school_id]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

  echo json_encode(['ok'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(400);
  echo json_encode(['ok'=>false, 'error'=>$e->getMessage()]);
}
