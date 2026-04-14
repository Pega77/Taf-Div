<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
if (!isPost()) { redirect('students.php'); }
verifyCsrf();
$id = (int)post('id', 0);
if ($id <= 0 || !canAccessStudent($id)) { redirect('unauthorized.php'); }
$db = getDB();
$db->prepare('UPDATE students SET deleted_at = NULL WHERE id = :id')->execute(['id' => $id]);
logAction('שחזור תלמיד', 'student', $id);
flash('success', 'התלמיד שוחזר בהצלחה.');
redirect('students.php?deleted=all');
