<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$id = (int)get('id');
$db = getDB();
$stmt = $db->prepare('SELECT s.*, g.name AS group_name, g.id AS group_id, p.name AS program_name FROM students s JOIN group_student gs ON gs.student_id = s.id AND gs.is_active = 1 JOIN groups g ON g.id = gs.group_id JOIN programs p ON p.id = g.program_id WHERE s.id = :id');
$stmt->execute(['id' => $id]);
$student = $stmt->fetch();
if (!$student) { redirect('students.php'); }
denyIfUnauthorized(canAccessGroup((int)$student['group_id']));
$stmt = $db->prepare('SELECT mf.field_label, mv.value_text, mv.value_number, mv.value_date, mv.value_boolean, mv.value_json FROM metadata_fields mf LEFT JOIN metadata_values mv ON mv.metadata_field_id = mf.id AND mv.student_id = :sid WHERE mf.program_id = :pid AND mf.is_active = 1 ORDER BY mf.sort_order, mf.id');
$stmt->execute(['sid' => $id, 'pid' => $student['group_id'] ? $db->query('SELECT program_id FROM groups WHERE id='.(int)$student['group_id'])->fetchColumn() : 0]);
$meta = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<h2>כרטיס תלמיד</h2>
<div class="card details-grid">
    <div><strong>שם מלא:</strong> <?= e($student['full_name']) ?></div>
    <div><strong>תעודת זהות:</strong> <?= e($student['national_id']) ?></div>
    <div><strong>קבוצה:</strong> <?= e($student['group_name']) ?></div>
    <div><strong>תוכנית:</strong> <?= e($student['program_name']) ?></div>
    <div><strong>סטטוס:</strong> <?= e($student['status']) ?></div>
</div>
<div class="card">
    <h3>מטה דאטה</h3>
    <ul class="meta-list">
        <?php foreach ($meta as $item): $val = $item['value_text'] ?? $item['value_number'] ?? $item['value_date'] ?? ($item['value_boolean'] !== null ? ($item['value_boolean'] ? 'כן' : 'לא') : ($item['value_json'] ? json_decode($item['value_json'], true) : '')); ?>
        <li><strong><?= e($item['field_label']) ?>:</strong> <?= e(is_string($val) ? $val : json_encode($val, JSON_UNESCAPED_UNICODE)) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
