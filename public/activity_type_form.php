<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
$db = getDB();
$id = (int)get('id', 0);
$errors = [];
$type = ['name' => '', 'sort_order' => 0, 'is_active' => 1];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM activity_types WHERE id=:id');
    $stmt->execute(['id' => $id]);
    $type = $stmt->fetch() ?: $type;
}
if (isPost()) {
    verifyCsrf();
    $data = ['name' => trim((string)post('name')), 'sort_order' => (int)post('sort_order', 0), 'is_active' => post('is_active') ? 1 : 0];
    if ($data['name'] === '') { $errors[] = 'יש להזין שם לסוג הפעילות.'; }
    if (!$errors) {
        if ($id) {
            $db->prepare('UPDATE activity_types SET name=:name, sort_order=:sort_order, is_active=:is_active WHERE id=:id')->execute($data + ['id' => $id]);
            logAction('עדכון סוג פעילות', 'activity_type', $id, ['name' => $data['name']]);
        } else {
            $db->prepare('INSERT INTO activity_types (name, sort_order, is_active) VALUES (:name,:sort_order,:is_active)')->execute($data);
            $newId = (int)$db->lastInsertId();
            logAction('יצירת סוג פעילות', 'activity_type', $newId, ['name' => $data['name']]);
        }
        flash('success', 'סוג הפעילות נשמר.');
        redirect('activity_types.php');
    }
    $type = $data;
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $id ? 'עריכת סוג פעילות' : 'סוג פעילות חדש' ?></h2>
<?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" class="card form-grid">
<?= csrfField() ?>
<label><span>שם סוג פעילות</span><input type="text" name="name" value="<?= e($type['name']) ?>" required></label>
<label><span>סדר תצוגה</span><input type="number" name="sort_order" value="<?= (int)$type['sort_order'] ?>"></label>
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= $type['is_active'] ? 'checked' : '' ?>><span>פעיל</span></label>
<button class="btn primary" type="submit">שמירה</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
