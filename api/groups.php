<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/permissions.php';
header('Content-Type: application/json');
require_login();

$sql = "
  SELECT g.id, g.name, g.program_id AS project_id, p.name AS project_name, g.instructor_user_id
  FROM groups g
  JOIN programs p ON p.id = g.program_id
  WHERE g.status = 'active' AND " . accessible_groups_sql() . "
  ORDER BY p.name, g.name
";
$stmt = $pdo->prepare($sql);
bind_current_user_if_needed($stmt);
$stmt->execute();
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
