<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/permissions.php';
header('Content-Type: application/json');
require_login();

$groupId = (int)($_GET['group_id'] ?? 0);
if (!$groupId || !can_access_group($pdo, $groupId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No access'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $pdo->prepare("SELECT program_id FROM groups WHERE id = ?");
$stmt->execute([$groupId]);
$programId = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("
  SELECT at.id, at.name
  FROM activity_types at
  LEFT JOIN activity_type_program atp ON atp.activity_type_id = at.id
  WHERE at.is_active = 1 AND (atp.program_id = ? OR atp.program_id IS NULL)
  GROUP BY at.id, at.name, at.sort_order
  ORDER BY at.sort_order, at.name
");
$stmt->execute([$programId]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
