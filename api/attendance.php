<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/permissions.php';
header('Content-Type: application/json');
require_role(['admin', 'coordinator', 'instructor']);

$data = json_decode(file_get_contents('php://input'), true);
$activityId = (int)($data['activity_id'] ?? 0);

$stmt = $pdo->prepare("SELECT group_id FROM activities WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$activityId]);
$groupId = (int)$stmt->fetchColumn();

if (!$groupId || !can_access_group($pdo, $groupId)) {
  http_response_code(403);
  echo json_encode(['error' => 'No access'], JSON_UNESCAPED_UNICODE);
  exit;
}

$pdo->beginTransaction();
$stmt = $pdo->prepare("
  INSERT INTO activity_students (activity_id, student_id, participation_status)
  VALUES (?, ?, ?)
  ON DUPLICATE KEY UPDATE participation_status = VALUES(participation_status)
");

foreach (($data['attendance'] ?? []) as $row) {
  $stmt->execute([$activityId, (int)$row['student_id'], $row['status']]);
}
$pdo->commit();

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
