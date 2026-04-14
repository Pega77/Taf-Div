<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
if (!isPost()) { redirect('activities.php'); }
verifyCsrf();
$id = (int)post('id', 0);
if ($id <= 0 || !canAccessActivity($id)) { redirect('unauthorized.php'); }
$db = getDB();
$db->prepare('UPDATE activities SET deleted_at = NOW() WHERE id = :id')->execute(['id' => $id]);
logAction('מחיקה רכה של פעילות', 'activity', $id);
flash('success', 'הפעילות הועברה למחיקה רכה.');
redirect('activities.php');
