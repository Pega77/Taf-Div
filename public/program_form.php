<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
$db = getDB();
$id = (int)get('id', 0);
$errors = [];
$program = ['name' => '', 'status' => 'active'];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM programs WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $program = $stmt->fetch() ?: $program;
}
if (isPost()) {
    verifyCsrf();
    $data = ['name' => trim((string)post('name')), 'status' => (string)post('status', 'active')];
    if ($data['name'] === '') { $errors[] = 'יש להזין שם תוכנית.'; }
    if (!in_array($data['status'], ['active', 'inactive'], true)) { $errors[] = 'סטטוס לא תקין.'; }
    if (!$errors) {
        if ($id) {
            $stmt = $db->prepare('UPDATE programs SET name = :name, status = :status WHERE id = :id');
            $stmt->execute($data + ['id' => $id]);
            logAction('עדכון תוכנית', 'program', $id, ['name' => $data['name']]);
            flash('success', 'התוכנית עודכנה בהצלחה.');
        } else {
            $stmt = $db->prepare('INSERT INTO programs (name, status) VALUES (:name, :status)');
            $stmt->execute($data);
            $newId = (int)$db->lastInsertId();
            logAction('יצירת תוכנית', 'program', $newId, ['name' => $data['name']]);
            flash('success', 'התוכנית נוספה בהצלחה.');
        }
        redirect('programs.php');
    }
    $program = $data;
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $id ? 'עריכת תוכנית' : 'תוכנית חדשה' ?></h2>
<?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label><span>שם התוכנית</span><input type="text" name="name" value="<?= e($program['name']) ?>" required></label>
    <label><span>סטטוס</span><select name="status"><option value="active" <?= $program['status']==='active'?'selected':'' ?>>פעילה</option><option value="inactive" <?= $program['status']==='inactive'?'selected':'' ?>>לא פעילה</option></select></label>
    <button class="btn primary" type="submit">שמירה</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
