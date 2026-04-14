<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
$db = getDB();
$entity = trim((string)get('entity', ''));
$userId = (int)get('user_id', 0);
$params = [];
$sql = 'SELECT a.*, u.full_name FROM audit_logs a JOIN users u ON u.id = a.user_id WHERE 1=1';
if ($entity !== '') {
    $sql .= ' AND a.entity_type = :entity';
    $params['entity'] = $entity;
}
if ($userId > 0) {
    $sql .= ' AND a.user_id = :user_id';
    $params['user_id'] = $userId;
}
$sql .= ' ORDER BY a.id DESC LIMIT 200';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();
$users = $db->query('SELECT id, full_name FROM users WHERE is_active = 1 ORDER BY full_name')->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header"><h2>יומן פעולות</h2><p class="muted">מוצגות עד 200 הפעולות האחרונות.</p></section>
<form method="get" class="card filter-row search-form">
    <label><span>ישות</span><select name="entity"><option value="">הכל</option><option value="user" <?= $entity==='user'?'selected':'' ?>>משתמש</option><option value="student" <?= $entity==='student'?'selected':'' ?>>תלמיד</option><option value="group" <?= $entity==='group'?'selected':'' ?>>קבוצה</option><option value="activity" <?= $entity==='activity'?'selected':'' ?>>פעילות</option><option value="program" <?= $entity==='program'?'selected':'' ?>>תוכנית</option></select></label>
    <label><span>משתמש</span><select name="user_id"><option value="0">הכל</option><?php foreach ($users as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $userId===(int)$u['id']?'selected':'' ?>><?= e($u['full_name']) ?></option><?php endforeach; ?></select></label>
    <button class="btn" type="submit">סינון</button>
</form>
<div class="table-wrap"><table><thead><tr><th>תאריך</th><th>משתמש</th><th>פעולה</th><th>ישות</th><th>מזהה ישות</th><th>פרטים</th></tr></thead><tbody>
<?php foreach ($logs as $log): ?>
<tr>
    <td><?= e($log['created_at']) ?></td>
    <td><?= e($log['full_name']) ?></td>
    <td><?= e($log['action']) ?></td>
    <td><?= e($log['entity_type']) ?></td>
    <td><?= (int)$log['entity_id'] ?></td>
    <td><code class="inline-code"><?= e((string)$log['details_json']) ?></code></td>
</tr>
<?php endforeach; ?>
<?php if (!$logs): ?><tr><td colspan="6" class="centered muted">לא נמצאו פעולות.</td></tr><?php endif; ?>
</tbody></table></div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
