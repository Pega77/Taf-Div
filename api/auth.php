<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  echo json_encode(['user' => current_user()], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'POST') {
  $data = json_decode(file_get_contents('php://input'), true);
  $username = trim($data['username'] ?? '');
  $password = $data['password'] ?? '';

  $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1 AND deleted_at IS NULL LIMIT 1");
  $stmt->execute([$username]);
  $user = $stmt->fetch();

  if (!$user || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'שם משתמש או סיסמה שגויים'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $_SESSION['user'] = [
    'id' => (int)$user['id'],
    'full_name' => $user['full_name'],
    'username' => $user['username'],
    'role' => $user['role']
  ];

  echo json_encode(['user' => $_SESSION['user']], JSON_UNESCAPED_UNICODE);
  exit;
}

if ($method === 'DELETE') {
  session_destroy();
  echo json_encode(['success' => true]);
}
