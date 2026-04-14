<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
if (!isPost()) { redirect('users.php'); }
verifyCsrf();
$id = (int)post('id', 0);
$db = getDB();
$db->prepare('UPDATE users SET deleted_at = NULL, is_active = 1 WHERE id = :id')->execute(['id' => $id]);
logAction('שחזור משתמש', 'user', $id);
flash('success', 'המשתמש שוחזר בהצלחה.');
redirect('users.php?deleted=all');
