<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

require_login(); // ensure you are logged in, or comment temporarily for testing
header('Content-Type: application/json');

$school_id = (int)($_GET['school_id'] ?? 0);
if (!$school_id) { echo json_encode(['ok'=>false,'error'=>'Missing school_id']); exit; }

try {
  $stmt = $pdo->prepare("SELECT id, name FROM levels WHERE school_id = ? ORDER BY name");
  $stmt->execute([$school_id]);
  echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'DB error']);
}
