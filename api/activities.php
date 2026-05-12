<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/permissions.php';
header('Content-Type: application/json');
require_role(['admin', 'coordinator', 'instructor']);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  $from = $_GET['from'] ?? date('Y-m-01');
  $to = $_GET['to'] ?? date('Y-m-t');

  $sql = "
    SELECT
      a.id,
      a.program_id AS project_id,
      a.group_id,
      g.name AS group_name,
      p.name AS project_name,
      a.activity_type_id,
      at.name AS activity_type,
      a.activity_date,
      a.start_time,
      a.end_time,
      a.notes,
      a.personal_student_id,
      ps.full_name AS personal_student_name,
      ps.national_id AS personal_student_national_id
    FROM activities a
    JOIN groups g ON g.id = a.group_id
    JOIN programs p ON p.id = a.program_id
    JOIN activity_types at ON at.id = a.activity_type_id
    LEFT JOIN students ps ON ps.id = a.personal_student_id
    WHERE a.deleted_at IS NULL
      AND a.activity_date BETWEEN :from_date AND :to_date
      AND " . accessible_groups_sql() . "
    ORDER BY a.activity_date, a.start_time, a.id
  ";

  $stmt = $pdo->prepare($sql);
  $stmt->bindValue(':from_date', $from);
  $stmt->bindValue(':to_date', $to);
  bind_current_user_if_needed($stmt);
  $stmt->execute();
  echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  $groupId = (int)($data['group_id'] ?? 0);
  $activityTypeId = (int)($data['activity_type_id'] ?? 0);

  if (!$groupId || !can_access_group($pdo, $groupId)) {
    http_response_code(403);
    echo json_encode(['error' => 'No access to group'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $stmt = $pdo->prepare("SELECT program_id FROM groups WHERE id = ? AND status = 'active'");
  $stmt->execute([$groupId]);
  $programId = (int)$stmt->fetchColumn();
  if (!$programId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid group'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $personalStudentId = null;
  if (!empty($data['personal_student_id'])) {
    $personalStudentId = (int)$data['personal_student_id'];
    $stmt = $pdo->prepare("SELECT 1 FROM group_student WHERE group_id = ? AND student_id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$groupId, $personalStudentId]);
    if (!$stmt->fetchColumn()) {
      http_response_code(400);
      echo json_encode(['error' => 'Student is not active in this group'], JSON_UNESCAPED_UNICODE);
      exit;
    }
  }

  $stmt = $pdo->prepare("
    INSERT INTO activities
      (program_id, group_id, activity_type_id, activity_date, start_time, end_time, personal_student_id, notes, created_by_user_id)
    VALUES
      (?, ?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $stmt->execute([
    $programId,
    $groupId,
    $activityTypeId,
    $data['activity_date'],
    $data['start_time'] ?: null,
    $data['end_time'] ?: null,
    $personalStudentId,
    $data['notes'] ?? null,
    current_user()['id']
  ]);

  echo json_encode(['id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
  exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
