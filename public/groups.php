<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$db = getDB();
if (isInstructor()) {
    $stmt = $db->prepare('SELECT g.*, p.name AS program_name, u.full_name AS instructor_name FROM groups g JOIN programs p ON p.id = g.program_id JOIN users u ON u.id = g.instructor_user_id WHERE g.instructor_user_id = :uid ORDER BY g.id DESC');
    $stmt->execute(['uid' => user()['id']]);
    $groups = $stmt->fetchAll();
} else {
    $groups = $db->query('SELECT g.*, p.name AS program_name, u.full_name AS instructor_name FROM groups g JOIN programs p ON p.id = g.program_id JOIN users u ON u.id = g.instructor_user_id ORDER BY g.id DESC')->fetchAll();
}
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>קבוצות</h2><?php if (!isInstructor()): ?><a class="btn primary" href="group_form.php">קבוצה חדשה</a><?php endif; ?></section>
<div class="table-wrap"><table><thead><tr><th>שם קבוצה</th><th>תוכנית</th><th>מדריך</th><th>סטטוס</th><th>פעולות</th></tr></thead><tbody>
<?php foreach ($groups as $group): ?>
<tr>
<td><?= e($group['name']) ?></td><td><?= e($group['program_name']) ?></td><td><?= e($group['instructor_name']) ?></td><td><?= e($group['status']) ?></td>
<td><?php if (!isInstructor()): ?><a href="group_form.php?id=<?= (int)$group['id'] ?>">עריכה</a> | <?php endif; ?><a href="students.php?group_id=<?= (int)$group['id'] ?>">תלמידים</a> | <a href="activity_form.php?group_id=<?= (int)$group['id'] ?>">פעילות חדשה</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
