<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
denyIfUnauthorized(isAdmin() || isCoordinator());
$db = getDB();
$programs = $db->query('SELECT * FROM programs ORDER BY id DESC')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>תוכניות</h2><a class="btn primary" href="program_form.php">תוכנית חדשה</a></section>
<div class="table-wrap"><table><thead><tr><th>#</th><th>שם</th><th>סטטוס</th><th>פעולות</th></tr></thead><tbody>
<?php foreach ($programs as $program): ?>
<tr><td><?= (int)$program['id'] ?></td><td><?= e($program['name']) ?></td><td><?= e($program['status']) ?></td><td><a href="program_form.php?id=<?= (int)$program['id'] ?>">עריכה</a> | <a href="metadata_fields.php?program_id=<?= (int)$program['id'] ?>">מטה דאטה</a></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
