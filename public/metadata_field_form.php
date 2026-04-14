<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
denyIfUnauthorized(isAdmin() || isCoordinator());
$db = getDB();
$programId = (int)get('program_id');
$id = (int)get('id', 0);
$field = ['field_key' => '', 'field_label' => '', 'field_type' => 'text', 'is_required' => 0, 'options_json' => '', 'sort_order' => 0, 'is_active' => 1];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM metadata_fields WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $field = $stmt->fetch() ?: $field;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'program_id' => $programId,
        'field_key' => trim((string)post('field_key')),
        'field_label' => trim((string)post('field_label')),
        'field_type' => post('field_type', 'text'),
        'is_required' => post('is_required') ? 1 : 0,
        'options_json' => trim((string)post('options_json')) ?: null,
        'sort_order' => (int)post('sort_order', 0),
        'is_active' => post('is_active') ? 1 : 0,
    ];
    if ($id) {
        $stmt = $db->prepare('UPDATE metadata_fields SET field_key=:field_key, field_label=:field_label, field_type=:field_type, is_required=:is_required, options_json=:options_json, sort_order=:sort_order, is_active=:is_active WHERE id=:id');
        $stmt->execute($data + ['id' => $id]);
    } else {
        $stmt = $db->prepare('INSERT INTO metadata_fields (program_id, field_key, field_label, field_type, is_required, options_json, sort_order, is_active) VALUES (:program_id,:field_key,:field_label,:field_type,:is_required,:options_json,:sort_order,:is_active)');
        $stmt->execute($data);
    }
    flash('success', 'שדה המטה דאטה נשמר.');
    redirect('metadata_fields.php?program_id=' . $programId);
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $id ? 'עריכת שדה מטה דאטה' : 'שדה מטה דאטה חדש' ?></h2>
<form method="post" class="card form-grid">
<label><span>מפתח שדה</span><input type="text" name="field_key" value="<?= e($field['field_key']) ?>" required></label>
<label><span>תווית</span><input type="text" name="field_label" value="<?= e($field['field_label']) ?>" required></label>
<label><span>סוג שדה</span><select name="field_type"><option value="text" <?= $field['field_type']==='text'?'selected':'' ?>>טקסט</option><option value="number" <?= $field['field_type']==='number'?'selected':'' ?>>מספר</option><option value="date" <?= $field['field_type']==='date'?'selected':'' ?>>תאריך</option><option value="boolean" <?= $field['field_type']==='boolean'?'selected':'' ?>>כן/לא</option><option value="select" <?= $field['field_type']==='select'?'selected':'' ?>>רשימה סגורה</option></select></label>
<label><span>אפשרויות JSON לרשימה</span><input type="text" name="options_json" value="<?= e($field['options_json']) ?>"></label>
<label><span>סדר תצוגה</span><input type="number" name="sort_order" value="<?= (int)$field['sort_order'] ?>"></label>
<label class="checkbox-row"><input type="checkbox" name="is_required" value="1" <?= $field['is_required'] ? 'checked' : '' ?>><span>שדה חובה</span></label>
<label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= $field['is_active'] ? 'checked' : '' ?>><span>שדה פעיל</span></label>
<button class="btn primary" type="submit">שמירה</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
