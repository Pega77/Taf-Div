<?php
session_start();

function current_user() {
  return $_SESSION['user'] ?? null;
}

function require_login() {
  if (!current_user()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

function require_role(array $roles) {
  require_login();
  if (!in_array(current_user()['role'], $roles, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}
