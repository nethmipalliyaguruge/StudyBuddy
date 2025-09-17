<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/helpers.php';

require_login();
header('Content-Type: application/json');

$level_id = (int)($_GET['level_id'] ?? 0);
if (!$level_id) { echo json_encode(['ok'=>false,'error'=>'Missing level_id']); exit; }

try {
  $stmt = $pdo->prepare("SELECT id, COALESCE(id,'') AS id, title
                         FROM modules WHERE level_id = ? ORDER BY title");
  $stmt->execute([$level_id]);
  echo json_encode(['ok'=>true,'data'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Throwable $e) {
  echo json_encode(['ok'=>false,'error'=>'DB error']);
}
