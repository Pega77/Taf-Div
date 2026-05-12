<?php
function can_access_group(PDO $pdo, int $groupId): bool {
  $user = current_user();
  if (!$user) return false;
  if ($user['role'] === 'admin') return true;

  if ($user['role'] === 'instructor') {
    $stmt = $pdo->prepare("SELECT 1 FROM groups WHERE id = ? AND instructor_user_id = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$groupId, $user['id']]);
    return (bool)$stmt->fetchColumn();
  }

  if ($user['role'] === 'coordinator') {
    $stmt = $pdo->prepare("SELECT 1 FROM coordinator_groups WHERE group_id = ? AND coordinator_user_id = ? LIMIT 1");
    $stmt->execute([$groupId, $user['id']]);
    return (bool)$stmt->fetchColumn();
  }

  return false;
}

function accessible_groups_sql(): string {
  $user = current_user();
  if (!$user || $user['role'] === 'admin') return "1=1";
  if ($user['role'] === 'instructor') return "g.instructor_user_id = :current_user_id";
  return "g.id IN (SELECT group_id FROM coordinator_groups WHERE coordinator_user_id = :current_user_id)";
}

function bind_current_user_if_needed(PDOStatement $stmt): void {
  $user = current_user();
  if ($user && $user['role'] !== 'admin') {
    $stmt->bindValue(':current_user_id', (int)$user['id'], PDO::PARAM_INT);
  }
}
