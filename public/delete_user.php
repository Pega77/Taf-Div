<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
if (!isPost()) { redirect('users.php'); }
verifyCsrf();
$id = (int)post('id', 0);
if ($id <= 0 || $id === (int)user()['id']) { flash('error', 'לא ניתן למחוק את המשתמש הנוכחי.'); redirect('users.php'); }
$db = getDB();
$db->prepare('UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = :id')->execute(['id' => $id]);
logAction('מחיקה רכה של משתמש', 'user', $id);
flash('success', 'המשתמש הועבר למחיקה רכה.');
redirect('users.php');
