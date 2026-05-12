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

$stmt = $pdo->prepare("
  SELECT s.id, s.national_id, s.full_name
  FROM students s
  JOIN group_student gs ON gs.student_id = s.id
  WHERE gs.group_id = ? AND gs.is_active = 1 AND s.deleted_at IS NULL
  ORDER BY s.full_name
");
$stmt->execute([$groupId]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
