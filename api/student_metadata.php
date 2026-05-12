<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');
require_login();

$studentId = (int)($_GET['student_id'] ?? 0);
$programId = (int)($_GET['project_id'] ?? $_GET['program_id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT mf.field_key, mf.field_label, mf.field_type,
         COALESCE(mv.value_text, CAST(mv.value_number AS CHAR), CAST(mv.value_date AS CHAR), CAST(mv.value_boolean AS CHAR), JSON_UNQUOTE(mv.value_json)) AS value
  FROM metadata_fields mf
  LEFT JOIN metadata_values mv ON mv.metadata_field_id = mf.id AND mv.student_id = ?
  WHERE mf.program_id = ? AND mf.is_active = 1
  ORDER BY mf.sort_order, mf.field_label
");
$stmt->execute([$studentId, $programId]);
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
