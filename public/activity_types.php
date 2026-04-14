<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
denyIfUnauthorized(isAdmin() || isCoordinator());
$types = getDB()->query('SELECT * FROM activity_types ORDER BY sort_order, id')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>סוגי פעילות</h2><a class="btn primary" href="activity_type_form.php">סוג חדש</a></section>
<div class="table-wrap"><table><thead><tr><th>שם</th><th>סדר</th><th>פעיל</th><th>פעולות</th></tr></thead><tbody>
<?php foreach ($types as $type): ?><tr><td><?= e($type['name']) ?></td><td><?= (int)$type['sort_order'] ?></td><td><?= $type['is_active'] ? 'כן' : 'לא' ?></td><td><a href="activity_type_form.php?id=<?= (int)$type['id'] ?>">עריכה</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
