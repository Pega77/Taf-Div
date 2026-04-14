<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
$db = getDB();
$id = (int)get('id', 0);
$errors = [];
$group = ['program_id' => '', 'name' => '', 'instructor_user_id' => '', 'status' => 'active'];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM groups WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $group = $stmt->fetch() ?: $group;
}
$programs = $db->query('SELECT id, name FROM programs ORDER BY name')->fetchAll();
$instructors = $db->query("SELECT id, full_name FROM users WHERE role = 'instructor' AND is_active = 1 ORDER BY full_name")->fetchAll();
if (isPost()) {
    verifyCsrf();
    $data = [
        'program_id' => (int)post('program_id'),
        'name' => trim((string)post('name')),
        'instructor_user_id' => (int)post('instructor_user_id'),
        'status' => (string)post('status', 'active'),
    ];
    if ($data['program_id'] <= 0) { $errors[] = 'יש לבחור תוכנית.'; }
    if ($data['name'] === '') { $errors[] = 'יש להזין שם קבוצה.'; }
    if ($data['instructor_user_id'] <= 0) { $errors[] = 'יש לבחור מדריך.'; }
    if (!in_array($data['status'], ['active', 'inactive'], true)) { $errors[] = 'סטטוס קבוצה אינו תקין.'; }
    if (!$errors) {
        if ($id) {
            $stmt = $db->prepare('UPDATE groups SET program_id=:program_id,name=:name,instructor_user_id=:instructor_user_id,status=:status WHERE id=:id');
            $stmt->execute($data + ['id' => $id]);
            logAction('עדכון קבוצה', 'group', $id, ['name' => $data['name']]);
            flash('success', 'הקבוצה עודכנה.');
        } else {
            $stmt = $db->prepare('INSERT INTO groups (program_id,name,instructor_user_id,status) VALUES (:program_id,:name,:instructor_user_id,:status)');
            $stmt->execute($data);
            $newId = (int)$db->lastInsertId();
            logAction('יצירת קבוצה', 'group', $newId, ['name' => $data['name']]);
            flash('success', 'הקבוצה נוספה.');
        }
        redirect('groups.php');
    }
    $group = $data;
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $id ? 'עריכת קבוצה' : 'קבוצה חדשה' ?></h2>
<?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label><span>תוכנית</span><select name="program_id" required><option value="">בחר תוכנית</option><?php foreach ($programs as $program): ?><option value="<?= (int)$program['id'] ?>" <?= (string)$group['program_id']===(string)$program['id']?'selected':'' ?>><?= e($program['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>שם קבוצה</span><input type="text" name="name" value="<?= e($group['name']) ?>" required></label>
    <label><span>מדריך</span><select name="instructor_user_id" required><option value="">בחר מדריך</option><?php foreach ($instructors as $inst): ?><option value="<?= (int)$inst['id'] ?>" <?= (string)$group['instructor_user_id']===(string)$inst['id']?'selected':'' ?>><?= e($inst['full_name']) ?></option><?php endforeach; ?></select></label>
    <label><span>סטטוס</span><select name="status"><option value="active" <?= $group['status']==='active'?'selected':'' ?>>פעילה</option><option value="inactive" <?= $group['status']==='inactive'?'selected':'' ?>>לא פעילה</option></select></label>
    <button class="btn primary" type="submit">שמירה</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
