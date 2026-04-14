<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
requireAdminOrCoordinator();
$db = getDB();
$role = trim((string)get('role', ''));
$q = trim((string)get('q', ''));
$deleted = (string)get('deleted', 'active');
$page = pageParam();
$perPage = perPageParam();
$params = [];
$from = ' FROM users WHERE 1=1';
if (in_array($role, ['admin', 'coordinator', 'instructor'], true)) {
    $from .= ' AND role = :role';
    $params['role'] = $role;
}
if ($deleted === 'only') {
    $from .= ' AND deleted_at IS NOT NULL';
} elseif ($deleted !== 'all') {
    $from .= ' AND deleted_at IS NULL';
}
if ($q !== '') {
    $from .= ' AND (full_name LIKE :term OR username LIKE :term OR phone LIKE :term)';
    $params['term'] = '%' . $q . '%';
}
$countStmt = $db->prepare('SELECT COUNT(*)' . $from);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$stmt = $db->prepare('SELECT *' . $from . ' ORDER BY id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset);
$stmt->execute($params);
$users = $stmt->fetchAll();
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>משתמשים</h2><a class="btn primary" href="user_form.php">משתמש חדש</a></section>
<form method="get" class="card filter-row search-form">
    <label><span>חיפוש</span><input type="search" name="q" value="<?= e($q) ?>" placeholder="שם, שם משתמש או טלפון"></label>
    <label><span>תפקיד</span><select name="role"><option value="">הכל</option><option value="admin" <?= $role==='admin'?'selected':'' ?>>מנהל</option><option value="coordinator" <?= $role==='coordinator'?'selected':'' ?>>רכז</option><option value="instructor" <?= $role==='instructor'?'selected':'' ?>>מדריך</option></select></label>
    <label><span>מחיקות רכות</span><select name="deleted"><option value="active" <?= $deleted==='active'?'selected':'' ?>>ללא מחוקים</option><option value="all" <?= $deleted==='all'?'selected':'' ?>>כולל מחוקים</option><option value="only" <?= $deleted==='only'?'selected':'' ?>>מחוקים בלבד</option></select></label>
    <label><span>כמות בעמוד</span><select name="per_page"><option value="10" <?= $perPage===10?'selected':'' ?>>10</option><option value="20" <?= $perPage===20?'selected':'' ?>>20</option><option value="50" <?= $perPage===50?'selected':'' ?>>50</option></select></label>
    <button class="btn" type="submit">סינון</button>
</form>
<p class="page-summary">סה"כ <?= $total ?> משתמשים · עמוד <?= $page ?> מתוך <?= $totalPages ?></p>
<div class="table-wrap"><table><thead><tr><th>שם</th><th>שם משתמש</th><th>טלפון</th><th>תפקיד</th><th>סטטוס</th><th>פעולות</th></tr></thead><tbody>
<?php foreach ($users as $row): ?>
<tr class="<?= !empty($row['deleted_at']) ? 'deleted-row' : '' ?>">
    <td><?= e($row['full_name']) ?></td>
    <td><?= e($row['username']) ?></td>
    <td><?= e($row['phone']) ?></td>
    <td><?= e(roleLabel($row['role'])) ?></td>
    <td><?php if (!empty($row['deleted_at'])): ?><span class="status-pill deleted">נמחק רכות</span><?php else: ?><span class="status-pill <?= $row['is_active'] ? 'active' : 'inactive' ?>"><?= $row['is_active'] ? 'פעיל' : 'לא פעיל' ?></span><?php endif; ?></td>
    <td class="table-actions"><a href="user_form.php?id=<?= (int)$row['id'] ?>">עריכה</a><?php if (empty($row['deleted_at'])): ?> | <form method="post" action="delete_user.php" class="inline-form"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="link-button confirm-action" type="submit">מחיקה</button></form><?php else: ?> | <form method="post" action="restore_user.php" class="inline-form"><?= csrfField() ?><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button class="link-button" type="submit">שחזור</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
<?php if (!$users): ?><tr><td colspan="6" class="centered muted">לא נמצאו משתמשים.</td></tr><?php endif; ?>
</tbody></table></div>
<?= renderPagination($page, $totalPages) ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
