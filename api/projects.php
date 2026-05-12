<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');
require_login();

$stmt = $pdo->query("SELECT id, name FROM programs WHERE status = 'active' AND deleted_at IS NULL ORDER BY name");
echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);
