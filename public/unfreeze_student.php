<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
denyIfUnauthorized(isPost());
verifyCsrf();
$id = (int)post('id');
denyIfUnauthorized($id > 0 && canAccessStudent($id));
$db = getDB();
$stmt = $db->prepare('SELECT g.id AS group_id, s.full_name FROM group_student gs JOIN groups g ON g.id = gs.group_id JOIN students s ON s.id = gs.student_id WHERE gs.student_id = :id AND gs.is_active = 1');
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();
$groupId = (int)($row['group_id'] ?? 0);
denyIfUnauthorized($groupId && canAccessGroup($groupId));
$db->prepare("UPDATE students SET status='active', frozen_at=NULL, freeze_reason=NULL WHERE id=:id")->execute(['id' => $id]);
logAction('הפשרת תלמיד', 'student', $id, ['full_name' => $row['full_name'] ?? '']);
flash('success', 'התלמיד הופשר.');
redirect('students.php?group_id=' . $groupId);
