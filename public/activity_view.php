<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$id = (int)get('id');
$db = getDB();
$stmt = $db->prepare('SELECT a.*, g.name AS group_name, at.name AS type_name, u.full_name AS creator_name FROM activities a JOIN groups g ON g.id = a.group_id JOIN activity_types at ON at.id = a.activity_type_id JOIN users u ON u.id = a.created_by_user_id WHERE a.id = :id');
$stmt->execute(['id' => $id]);
$activity = $stmt->fetch();
if (!$activity) { redirect('activities.php'); }
denyIfUnauthorized(canAccessGroup((int)$activity['group_id']));
$stmt = $db->prepare('SELECT s.full_name, ast.participation_status FROM activity_students ast JOIN students s ON s.id = ast.student_id WHERE ast.activity_id = :id ORDER BY s.full_name');
$stmt->execute(['id' => $id]);
$students = $stmt->fetchAll();
$presentCount = count(array_filter($students, fn($row) => $row['participation_status'] === 'present'));
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between">
    <h2>צפייה בפעילות</h2>
    <a class="btn" href="activity_form.php?id=<?= (int)$activity['id'] ?>">עריכת פעילות</a>
</section>
<div class="stats-grid compact-stats">
    <div class="card stat-card"><span class="stat-label">נוכחים</span><strong class="stat-value"><?= $presentCount ?></strong></div>
    <div class="card stat-card"><span class="stat-label">סה"כ תלמידים</span><strong class="stat-value"><?= count($students) ?></strong></div>
</div>
<div class="card details-grid section-gap-small">
    <div><strong>קבוצה:</strong> <?= e($activity['group_name']) ?></div>
    <div><strong>סוג פעילות:</strong> <?= e($activity['type_name']) ?></div>
    <div><strong>תאריך:</strong> <?= e($activity['activity_date']) ?></div>
    <div><strong>יוצר:</strong> <?= e($activity['creator_name']) ?></div>
    <div><strong>הערות:</strong> <?= e($activity['notes']) ?: 'ללא הערות' ?></div>
</div>
<div class="table-wrap"><table><thead><tr><th>תלמיד</th><th>סטטוס השתתפות</th></tr></thead><tbody>
<?php foreach ($students as $student): ?><tr><td><?= e($student['full_name']) ?></td><td><span class="status-pill <?= $student['participation_status']==='present' ? 'active' : 'inactive' ?>"><?= e(statusLabel($student['participation_status'])) ?></span></td></tr><?php endforeach; ?>
<?php if (!$students): ?><tr><td colspan="2" class="centered muted">אין רשומות תלמידים לפעילות זו.</td></tr><?php endif; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
