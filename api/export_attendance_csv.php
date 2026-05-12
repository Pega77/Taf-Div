<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/permissions.php';
require_login();

$where = ["a.deleted_at IS NULL", accessible_groups_sql()];
$params = [];

foreach (['project_id' => 'p.id', 'group_id' => 'g.id', 'activity_type_id' => 'at.id'] as $key => $column) {
  if (!empty($_GET[$key])) {
    if ($key === 'group_id' && !can_access_group($pdo, (int)$_GET[$key])) { http_response_code(403); exit('Forbidden'); }
    $where[] = "$column = :$key";
    $params[$key] = [(int)$_GET[$key], PDO::PARAM_INT];
  }
}
if (!empty($_GET['national_id'])) { $where[] = 's.national_id = :national_id'; $params['national_id'] = [$_GET['national_id'], PDO::PARAM_STR]; }
if (!empty($_GET['status'])) { $where[] = 'ast.participation_status = :status'; $params['status'] = [$_GET['status'], PDO::PARAM_STR]; }
if (!empty($_GET['from'])) { $where[] = 'a.activity_date >= :from_date'; $params['from_date'] = [$_GET['from'], PDO::PARAM_STR]; }
if (!empty($_GET['to'])) { $where[] = 'a.activity_date <= :to_date'; $params['to_date'] = [$_GET['to'], PDO::PARAM_STR]; }

$sql = "
  SELECT p.name AS project_name, g.name AS group_name, a.activity_date, a.start_time,
         at.name AS activity_type, s.national_id, s.full_name, ast.participation_status AS status
  FROM activity_students ast
  JOIN activities a ON a.id = ast.activity_id
  JOIN activity_types at ON at.id = a.activity_type_id
  JOIN groups g ON g.id = a.group_id
  JOIN programs p ON p.id = a.program_id
  JOIN students s ON s.id = ast.student_id
  WHERE " . implode(' AND ', $where) . "
  ORDER BY a.activity_date DESC, p.name, g.name, s.full_name
";
$stmt = $pdo->prepare($sql);
bind_current_user_if_needed($stmt);
foreach ($params as $key => [$value, $type]) $stmt->bindValue(':' . $key, $value, $type);
$stmt->execute();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d') . '.csv"');
echo "\xEF\xBB\xBF";
$out = fopen('php://output', 'w');
fputcsv($out, ['תוכנית','קבוצה','תאריך','שעה','סוג פעילות','תעודת זהות','שם מלא','סטטוס']);
while ($row = $stmt->fetch()) {
  fputcsv($out, [$row['project_name'],$row['group_name'],$row['activity_date'],$row['start_time'],$row['activity_type'],$row['national_id'],$row['full_name'], translateStatus($row['status'])]);
}
fclose($out);
function translateStatus($s) { return ['present'=>'נוכח','absent'=>'נעדר'][$s] ?? $s; }
