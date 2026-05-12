<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/permissions.php';
header('Content-Type: application/json');
require_login();

$where = ["a.deleted_at IS NULL", accessible_groups_sql()];
$params = [];

$map = [
  'project_id' => ['p.id = :project_id', PDO::PARAM_INT],
  'group_id' => ['g.id = :group_id', PDO::PARAM_INT],
  'activity_type_id' => ['at.id = :activity_type_id', PDO::PARAM_INT],
  'national_id' => ['s.national_id = :national_id', PDO::PARAM_STR],
  'status' => ['ast.participation_status = :status', PDO::PARAM_STR]
];

foreach ($map as $key => [$condition, $type]) {
  if (isset($_GET[$key]) && $_GET[$key] !== '') {
    if ($key === 'group_id' && !can_access_group($pdo, (int)$_GET[$key])) {
      http_response_code(403);
      echo json_encode(['error' => 'No access to group'], JSON_UNESCAPED_UNICODE);
      exit;
    }
    $where[] = $condition;
    $params[$key] = [$_GET[$key], $type];
  }
}

if (!empty($_GET['from'])) { $where[] = 'a.activity_date >= :from_date'; $params['from_date'] = [$_GET['from'], PDO::PARAM_STR]; }
if (!empty($_GET['to'])) { $where[] = 'a.activity_date <= :to_date'; $params['to_date'] = [$_GET['to'], PDO::PARAM_STR]; }
$whereSql = 'WHERE ' . implode(' AND ', $where);

$base = "
  FROM activity_students ast
  JOIN activities a ON a.id = ast.activity_id
  JOIN groups g ON g.id = a.group_id
  JOIN programs p ON p.id = a.program_id
  JOIN activity_types at ON at.id = a.activity_type_id
  JOIN students s ON s.id = ast.student_id
  $whereSql
";

function run_count(PDO $pdo, string $sql, array $params) {
  $stmt = $pdo->prepare($sql);
  bind_current_user_if_needed($stmt);
  foreach ($params as $key => [$value, $type]) $stmt->bindValue(':' . $key, $value, $type);
  $stmt->execute();
  return (int)$stmt->fetchColumn();
}

$summary = [
  'students_count' => run_count($pdo, "SELECT COUNT(DISTINCT s.id) $base", $params),
  'activities_count' => run_count($pdo, "SELECT COUNT(DISTINCT a.id) $base", $params),
  'present_count' => run_count($pdo, "SELECT COUNT(*) $base AND ast.participation_status = 'present'", $params),
  'absent_count' => run_count($pdo, "SELECT COUNT(*) $base AND ast.participation_status = 'absent'", $params)
];

$stmt = $pdo->prepare("
  SELECT
    a.activity_date,
    a.start_time,
    p.name AS project_name,
    g.name AS group_name,
    at.name AS activity_type,
    s.id AS student_id,
    s.national_id,
    s.full_name,
    ast.participation_status AS status
  $base
  ORDER BY a.activity_date DESC, a.start_time DESC, s.full_name
  LIMIT 150
");
bind_current_user_if_needed($stmt);
foreach ($params as $key => [$value, $type]) $stmt->bindValue(':' . $key, $value, $type);
$stmt->execute();

 echo json_encode(['summary' => $summary, 'rows' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE);
