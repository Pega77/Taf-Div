<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
$db = getDB();
$id = (int)get('id', 0);
$errors = [];
$item = ['full_name' => '', 'username' => '', 'phone' => '', 'role' => 'instructor', 'is_active' => 1];
if ($id) {
    $stmt = $db->prepare('SELECT * FROM users WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $item = $stmt->fetch() ?: $item;
}
if (isPost()) {
    verifyCsrf();
    $data = [
        'full_name' => trim((string)post('full_name')),
        'username' => trim((string)post('username')),
        'phone' => trim((string)post('phone')),
        'role' => (string)post('role', 'instructor'),
        'is_active' => post('is_active') ? 1 : 0,
    ];
    $password = (string)post('password', '');
    if ($data['full_name'] === '') { $errors[] = 'יש להזין שם מלא.'; }
    if ($data['username'] === '') { $errors[] = 'יש להזין שם משתמש.'; }
    if (!in_array($data['role'], ['admin', 'coordinator', 'instructor'], true)) { $errors[] = 'יש לבחור תפקיד תקין.'; }
    if (!$id && $password === '') { $errors[] = 'יש להזין סיסמה למשתמש חדש.'; }

    $dupSql = 'SELECT COUNT(*) FROM users WHERE username = :username AND deleted_at IS NULL' . ($id ? ' AND id <> :id' : '');
    $dupStmt = $db->prepare($dupSql);
    $dupParams = ['username' => $data['username']];
    if ($id) { $dupParams['id'] = $id; }
    $dupStmt->execute($dupParams);
    if ((int)$dupStmt->fetchColumn() > 0) { $errors[] = 'שם המשתמש כבר קיים במערכת.'; }

    if (!$errors) {
        if ($id) {
            $sql = 'UPDATE users SET full_name=:full_name, username=:username, phone=:phone, role=:role, is_active=:is_active';
            $params = $data + ['id' => $id];
            if ($password !== '') {
                $sql .= ', password_hash=:password_hash';
                $params['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id=:id';
            $db->prepare($sql)->execute($params);
            logAction('עדכון משתמש', 'user', $id, ['username' => $data['username']]);
            flash('success', 'המשתמש עודכן בהצלחה.');
        } else {
            $db->prepare('INSERT INTO users (full_name, username, phone, password_hash, role, is_active) VALUES (:full_name,:username,:phone,:password_hash,:role,:is_active)')->execute($data + ['password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
            $newId = (int)$db->lastInsertId();
            logAction('יצירת משתמש', 'user', $newId, ['username' => $data['username']]);
            flash('success', 'המשתמש נוסף בהצלחה.');
        }
        redirect('users.php');
    }
    $item = $data + ['password_hash' => ''];
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $id ? 'עריכת משתמש' : 'משתמש חדש' ?></h2>
<?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post" class="card form-grid">
    <?= csrfField() ?>
    <label><span>שם מלא</span><input type="text" name="full_name" value="<?= e($item['full_name']) ?>" required></label>
    <label><span>שם משתמש</span><input type="text" name="username" value="<?= e($item['username']) ?>" required></label>
    <label><span>טלפון</span><input type="text" name="phone" value="<?= e($item['phone']) ?>"></label>
    <label><span>סיסמה <?= $id ? '(לא חובה)' : '' ?></span><input type="password" name="password" <?= $id ? '' : 'required' ?>></label>
    <label><span>תפקיד</span><select name="role"><option value="admin" <?= $item['role']==='admin'?'selected':'' ?>>מנהל</option><option value="coordinator" <?= $item['role']==='coordinator'?'selected':'' ?>>רכז</option><option value="instructor" <?= $item['role']==='instructor'?'selected':'' ?>>מדריך</option></select></label>
    <label class="checkbox-row"><input type="checkbox" name="is_active" value="1" <?= !empty($item['is_active']) ? 'checked' : '' ?>><span>משתמש פעיל</span></label>
    <button class="btn primary" type="submit">שמירה</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>
