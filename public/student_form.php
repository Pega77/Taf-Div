<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$db = getDB();
$id = (int)get('id', 0);
$groupId = (int)get('group_id', 0);
$errors = [];
$student = ['national_id' => '', 'full_name' => '', 'gender' => '', 'birth_date' => '', 'status' => 'active'];
$activeGroupId = $groupId;
if ($id) {
    denyIfUnauthorized(canAccessStudent($id));
    $stmt = $db->prepare('SELECT * FROM students WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $student = $stmt->fetch() ?: $student;
    $stmt = $db->prepare('SELECT group_id FROM group_student WHERE student_id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $id]);
    $activeGroupId = (int)$stmt->fetchColumn();
}
$groupsSql = isInstructor()
    ? 'SELECT id, name, program_id FROM groups WHERE instructor_user_id = :uid ORDER BY name'
    : 'SELECT id, name, program_id FROM groups ORDER BY name';
$stmt = $db->prepare($groupsSql);
$stmt->execute(isInstructor() ? ['uid' => user()['id']] : []);
$groups = $stmt->fetchAll();
$programId = 0;
foreach ($groups as $g) {
    if ((int)$g['id'] === $activeGroupId) { $programId = (int)$g['program_id']; break; }
}
$fields = [];
if ($programId > 0) {
    $stmt = $db->prepare('SELECT * FROM metadata_fields WHERE program_id = :pid AND is_active = 1 ORDER BY sort_order, id');
    $stmt->execute(['pid' => $programId]);
    $fields = $stmt->fetchAll();
}
$fieldValues = [];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM metadata_values WHERE student_id = :sid');
    $stmt->execute(['sid' => $id]);
    foreach ($stmt->fetchAll() as $row) {
        $fieldValues[$row['metadata_field_id']] = $row;
    }
}
if (isPost()) {
    verifyCsrf();
    $data = [
        'national_id' => trim((string)post('national_id')),
        'full_name' => trim((string)post('full_name')),
        'gender' => post('gender') ?: null,
        'birth_date' => post('birth_date') ?: null,
        'status' => post('status', 'active'),
    ];
    $selectedGroupId = (int)post('group_id');
    denyIfUnauthorized(canAccessGroup($selectedGroupId));
    if ($data['full_name'] === '') { $errors[] = 'יש להזין שם מלא.'; }
    if (!validateNationalId($data['national_id'])) { $errors[] = 'יש להזין תעודת זהות תקינה.'; }
    if (!in_array($data['status'], ['active', 'frozen'], true)) { $errors[] = 'סטטוס תלמיד אינו תקין.'; }
    $dupSql = 'SELECT COUNT(*) FROM students WHERE national_id = :nid' . ($id ? ' AND id <> :id' : '');
    $dupStmt = $db->prepare($dupSql);
    $dupParams = ['nid' => $data['national_id']];
    if ($id) { $dupParams['id'] = $id; }
    $dupStmt->execute($dupParams);
    if ((int)$dupStmt->fetchColumn() > 0) { $errors[] = 'כבר קיים תלמיד עם תעודת זהות זו.'; }

    $stmt = $db->prepare('SELECT program_id FROM groups WHERE id = :id');
    $stmt->execute(['id' => $selectedGroupId]);
    $programId = (int)$stmt->fetchColumn();
    $stmt = $db->prepare('SELECT * FROM metadata_fields WHERE program_id = :pid AND is_active = 1 ORDER BY sort_order, id');
    $stmt->execute(['pid' => $programId]);
    $activeFields = $stmt->fetchAll();
    foreach ($activeFields as $field) {
        $input = post('meta_' . $field['id']);
        if ((int)$field['is_required'] === 1 && ($input === null || $input === '')) {
            $errors[] = 'השדה "' . $field['field_label'] . '" הוא שדה חובה.';
        }
    }

    if (!$errors) {
        if ($id) {
            $stmt = $db->prepare('UPDATE students SET national_id=:national_id, full_name=:full_name, gender=:gender, birth_date=:birth_date, status=:status WHERE id=:id');
            $stmt->execute($data + ['id' => $id]);
            $studentId = $id;
            getDB()->prepare('UPDATE group_student SET is_active = 0, left_at = CURDATE() WHERE student_id = :sid AND is_active = 1')->execute(['sid' => $studentId]);
            logAction('עדכון תלמיד', 'student', $studentId, ['full_name' => $data['full_name']]);
        } else {
            $stmt = $db->prepare('INSERT INTO students (national_id, full_name, gender, birth_date, status) VALUES (:national_id,:full_name,:gender,:birth_date,:status)');
            $stmt->execute($data);
            $studentId = (int)$db->lastInsertId();
            logAction('יצירת תלמיד', 'student', $studentId, ['full_name' => $data['full_name']]);
        }
        $db->prepare('INSERT INTO group_student (group_id, student_id, is_active, joined_at) VALUES (:gid,:sid,1,CURDATE())')->execute(['gid' => $selectedGroupId, 'sid' => $studentId]);

        foreach ($activeFields as $field) {
            $input = post('meta_' . $field['id']);
            $payload = [
                'student_id' => $studentId,
                'metadata_field_id' => $field['id'],
                'value_text' => null,
                'value_number' => null,
                'value_date' => null,
                'value_boolean' => null,
                'value_json' => null,
            ];
            switch ($field['field_type']) {
                case 'number': $payload['value_number'] = $input !== '' ? $input : null; break;
                case 'date': $payload['value_date'] = $input ?: null; break;
                case 'boolean': $payload['value_boolean'] = $input ? 1 : 0; break;
                case 'select': $payload['value_json'] = $input ? json_encode($input, JSON_UNESCAPED_UNICODE) : null; break;
                default: $payload['value_text'] = $input ?: null; break;
            }
            $db->prepare('INSERT INTO metadata_values (student_id, metadata_field_id, value_text, value_number, value_date, value_boolean, value_json) VALUES (:student_id,:metadata_field_id,:value_text,:value_number,:value_date,:value_boolean,:value_json) ON DUPLICATE KEY UPDATE value_text=VALUES(value_text), value_number=VALUES(value_number), value_date=VALUES(value_date), value_boolean=VALUES(value_boolean), value_json=VALUES(value_json)')->execute($payload);
        }
        flash('success', 'התלמיד נשמר בהצלחה.');
        redirect('students.php?group_id=' . $selectedGroupId);
    }
    $student = $data;
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $id ? 'עריכת תלמיד' : 'תלמיד חדש' ?></h2>
<?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" class="card form-grid" id="student-form">
<?= csrfField() ?>
<label><span>קבוצה</span><select name="group_id" required><option value="">בחר קבוצה</option><?php foreach ($groups as $group): ?><option value="<?= (int)$group['id'] ?>" <?= $activeGroupId===(int)$group['id']?'selected':'' ?>><?= e($group['name']) ?></option><?php endforeach; ?></select></label>
<label><span>שם מלא</span><input type="text" name="full_name" value="<?= e(old('full_name', $student['full_name'])) ?>" required></label>
<label><span>תעודת זהות</span><input type="text" name="national_id" value="<?= e(old('national_id', $student['national_id'])) ?>" required inputmode="numeric" maxlength="9"></label>
<label><span>מגדר</span><select name="gender"><option value="">לא נבחר</option><option value="male" <?= old('gender', $student['gender'])==='male'?'selected':'' ?>>זכר</option><option value="female" <?= old('gender', $student['gender'])==='female'?'selected':'' ?>>נקבה</option><option value="other" <?= old('gender', $student['gender'])==='other'?'selected':'' ?>>אחר</option></select></label>
<label><span>תאריך לידה</span><input type="date" name="birth_date" value="<?= e(old('birth_date', $student['birth_date'])) ?>"></label>
<label><span>סטטוס</span><select name="status"><option value="active" <?= old('status', $student['status'])==='active'?'selected':'' ?>>פעיל</option><option value="frozen" <?= old('status', $student['status'])==='frozen'?'selected':'' ?>>מוקפא</option></select></label>
<?php foreach ($fields as $field): $value = $fieldValues[$field['id']] ?? []; $current = old('meta_' . $field['id'], selectedValue($value, $field['field_type'])); ?>
<label>
    <span><?= e($field['field_label']) ?><?= (int)$field['is_required'] === 1 ? ' *' : '' ?></span>
    <?php if ($field['field_type'] === 'select'): $opts = json_decode($field['options_json'] ?: '[]', true) ?: []; ?>
        <select name="meta_<?= (int)$field['id'] ?>"><option value="">בחר</option><?php foreach ($opts as $opt): ?><option value="<?= e($opt) ?>" <?= $current===$opt?'selected':'' ?>><?= e($opt) ?></option><?php endforeach; ?></select>
    <?php elseif ($field['field_type'] === 'date'): ?>
        <input type="date" name="meta_<?= (int)$field['id'] ?>" value="<?= e($current) ?>">
    <?php elseif ($field['field_type'] === 'number'): ?>
        <input type="number" name="meta_<?= (int)$field['id'] ?>" value="<?= e($current) ?>">
    <?php elseif ($field['field_type'] === 'boolean'): ?>
        <label class="checkbox-row inline-checkbox"><input type="checkbox" name="meta_<?= (int)$field['id'] ?>" value="1" <?= $current==='1'?'checked':'' ?>><span>מסומן</span></label>
    <?php else: ?>
        <input type="text" name="meta_<?= (int)$field['id'] ?>" value="<?= e($current) ?>">
    <?php endif; ?>
</label>
<?php endforeach; ?>
<button class="btn primary" type="submit">שמירה</button>
</form>
<script src="../assets/js/student-form.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
