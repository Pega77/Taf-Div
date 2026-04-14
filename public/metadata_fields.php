<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
denyIfUnauthorized(isAdmin() || isCoordinator());
$db = getDB();
$programId = (int)get('program_id');
$stmt = $db->prepare('SELECT * FROM programs WHERE id = :id');
$stmt->execute(['id' => $programId]);
$program = $stmt->fetch();
$fields = [];
if ($program) {
    $stmt = $db->prepare('SELECT * FROM metadata_fields WHERE program_id = :id ORDER BY sort_order, id');
    $stmt->execute(['id' => $programId]);
    $fields = $stmt->fetchAll();
}
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>שדות מטה דאטה</h2><a class="btn primary" href="metadata_field_form.php?program_id=<?= $programId ?>">שדה חדש</a></section>
<p>תוכנית: <?= e($program['name'] ?? '') ?></p>
<div class="table-wrap"><table><thead><tr><th>תווית</th><th>מפתח</th><th>סוג</th><th>חובה</th><th>פעיל</th><th>פעולות</th></tr></thead><tbody>
<?php foreach ($fields as $field): ?>
<tr><td><?= e($field['field_label']) ?></td><td><?= e($field['field_key']) ?></td><td><?= e($field['field_type']) ?></td><td><?= $field['is_required'] ? 'כן' : 'לא' ?></td><td><?= $field['is_active'] ? 'כן' : 'לא' ?></td><td><a href="metadata_field_form.php?program_id=<?= $programId ?>&id=<?= (int)$field['id'] ?>">עריכה</a></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
