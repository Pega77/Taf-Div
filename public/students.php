<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$db = getDB();
$groupId = (int)get('group_id', 0);
$status = (string)get('status', 'all');
$search = trim((string)get('q', ''));
$deleted = (string)get('deleted', 'active');
$page = pageParam();
$perPage = perPageParam();
$params = [];
$from = ' FROM students s JOIN group_student gs ON gs.student_id = s.id AND gs.is_active = 1 JOIN groups g ON g.id = gs.group_id WHERE 1=1';
if ($groupId > 0) {
    denyIfUnauthorized(canAccessGroup($groupId));
    $from .= ' AND g.id = :group_id';
    $params['group_id'] = $groupId;
} elseif (isInstructor()) {
    $from .= ' AND g.instructor_user_id = :user_id';
    $params['user_id'] = user()['id'];
}
if (in_array($status, ['active', 'frozen'], true)) {
    $from .= ' AND s.status = :status';
    $params['status'] = $status;
}
if ($deleted === 'only') {
    $from .= ' AND s.deleted_at IS NOT NULL';
} elseif ($deleted !== 'all') {
    $from .= ' AND s.deleted_at IS NULL';
}
if ($search !== '') {
    $from .= ' AND (s.full_name LIKE :term OR s.national_id LIKE :term)';
    $params['term'] = '%' . $search . '%';
}
$countStmt = $db->prepare('SELECT COUNT(*)' . $from);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT s.*, g.name AS group_name' . $from . ' ORDER BY s.full_name LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();
if (isAjaxRequest()) {
    include __DIR__ . '/students_table.php';
    exit;
}
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>תלמידים</h2><a class="btn primary" href="student_form.php<?= $groupId ? '?group_id=' . $groupId : '' ?>">תלמיד חדש</a></section>
<form method="get" class="card filter-row search-form" data-live-search-form data-live-url="students.php" data-target="#students-table-body">
    <?php if ($groupId): ?><input type="hidden" name="group_id" value="<?= $groupId ?>"><?php endif; ?>
    <label><span>חיפוש</span><input data-live-search-input type="search" name="q" value="<?= e($search) ?>" placeholder="שם תלמיד או תעודת זהות"></label>
    <label><span>סינון לפי סטטוס</span><select name="status"><option value="all">הכל</option><option value="active" <?= $status==='active'?'selected':'' ?>>פעיל</option><option value="frozen" <?= $status==='frozen'?'selected':'' ?>>מוקפא</option></select></label>
    <label><span>מחיקות רכות</span><select name="deleted"><option value="active" <?= $deleted==='active'?'selected':'' ?>>ללא מחוקים</option><option value="all" <?= $deleted==='all'?'selected':'' ?>>כולל מחוקים</option><option value="only" <?= $deleted==='only'?'selected':'' ?>>מחוקים בלבד</option></select></label>
    <label><span>כמות בעמוד</span><select name="per_page"><option value="10" <?= $perPage===10?'selected':'' ?>>10</option><option value="20" <?= $perPage===20?'selected':'' ?>>20</option><option value="50" <?= $perPage===50?'selected':'' ?>>50</option></select></label>
    <button class="btn" type="submit">סינון</button>
</form>
<p class="page-summary">סה"כ <?= $total ?> תלמידים · עמוד <?= $page ?> מתוך <?= $totalPages ?></p>
<div class="table-wrap"><table><thead><tr><th>שם</th><th>תעודת זהות</th><th>קבוצה</th><th>סטטוס</th><th>פעולות</th></tr></thead><tbody id="students-table-body"><?php include __DIR__ . '/students_table.php'; ?></tbody></table></div>
<?= renderPagination($page, $totalPages) ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
