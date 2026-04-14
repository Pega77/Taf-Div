<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$db = getDB();
$groupId = (int)get('group_id', 0);
$typeId = (int)get('type_id', 0);
$dateFrom = trim((string)get('date_from', ''));
$dateTo = trim((string)get('date_to', ''));
$q = trim((string)get('q', ''));
$deleted = (string)get('deleted', 'active');
$page = pageParam();
$perPage = perPageParam();
$params = [];
$from = ' FROM activities a JOIN groups g ON g.id = a.group_id JOIN activity_types at ON at.id = a.activity_type_id JOIN users u ON u.id = a.created_by_user_id WHERE 1=1';
if ($groupId > 0) {
    denyIfUnauthorized(canAccessGroup($groupId));
    $from .= ' AND g.id = :gid';
    $params['gid'] = $groupId;
} elseif (isInstructor()) {
    $from .= ' AND g.instructor_user_id = :uid';
    $params['uid'] = user()['id'];
}
if ($typeId > 0) {
    $from .= ' AND a.activity_type_id = :type_id';
    $params['type_id'] = $typeId;
}
if ($dateFrom !== '') {
    $from .= ' AND a.activity_date >= :date_from';
    $params['date_from'] = $dateFrom;
}
if ($dateTo !== '') {
    $from .= ' AND a.activity_date <= :date_to';
    $params['date_to'] = $dateTo;
}
if ($deleted === 'only') {
    $from .= ' AND a.deleted_at IS NOT NULL';
} elseif ($deleted !== 'all') {
    $from .= ' AND a.deleted_at IS NULL';
}
if ($q !== '') {
    $from .= ' AND (g.name LIKE :term OR at.name LIKE :term OR a.notes LIKE :term)';
    $params['term'] = '%' . $q . '%';
}
$countStmt = $db->prepare('SELECT COUNT(*)' . $from);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$sql = 'SELECT a.*, g.name AS group_name, at.name AS type_name, u.full_name AS creator_name,
        (SELECT COUNT(*) FROM activity_students ast WHERE ast.activity_id = a.id AND ast.participation_status = "present") AS present_count,
        (SELECT COUNT(*) FROM activity_students ast WHERE ast.activity_id = a.id) AS total_count'
        . $from . ' ORDER BY a.activity_date DESC, a.id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
$stmt = $db->prepare($sql);
$stmt->execute($params);
$activities = $stmt->fetchAll();
$types = $db->query('SELECT id, name FROM activity_types WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order, id')->fetchAll();
$groupsSql = isInstructor()
    ? 'SELECT id, name FROM groups WHERE instructor_user_id = :uid AND deleted_at IS NULL ORDER BY name'
    : 'SELECT id, name FROM groups WHERE deleted_at IS NULL ORDER BY name';
$groupsStmt = $db->prepare($groupsSql);
$groupsStmt->execute(isInstructor() ? ['uid' => user()['id']] : []);
$groups = $groupsStmt->fetchAll();
if (isAjaxRequest()) {
    include __DIR__ . '/activities_table.php';
    exit;
}
include __DIR__ . '/../includes/header.php';
?>
<section class="page-header actions-between"><h2>פעילויות</h2><a class="btn primary" href="activity_form.php">פעילות חדשה</a></section>
<form method="get" class="card filter-row search-form" data-live-search-form data-live-url="activities.php" data-target="#activities-table-body">
    <label><span>חיפוש</span><input data-live-search-input type="search" name="q" value="<?= e($q) ?>" placeholder="קבוצה, סוג או הערה"></label>
    <label><span>קבוצה</span><select name="group_id"><option value="0">הכל</option><?php foreach ($groups as $group): ?><option value="<?= (int)$group['id'] ?>" <?= $groupId===(int)$group['id']?'selected':'' ?>><?= e($group['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>סוג פעילות</span><select name="type_id"><option value="0">הכל</option><?php foreach ($types as $type): ?><option value="<?= (int)$type['id'] ?>" <?= $typeId===(int)$type['id']?'selected':'' ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>מתאריך</span><input type="date" name="date_from" value="<?= e($dateFrom) ?>"></label>
    <label><span>עד תאריך</span><input type="date" name="date_to" value="<?= e($dateTo) ?>"></label>
    <label><span>מחיקות רכות</span><select name="deleted"><option value="active" <?= $deleted==='active'?'selected':'' ?>>ללא מחוקים</option><option value="all" <?= $deleted==='all'?'selected':'' ?>>כולל מחוקים</option><option value="only" <?= $deleted==='only'?'selected':'' ?>>מחוקים בלבד</option></select></label>
    <label><span>כמות בעמוד</span><select name="per_page"><option value="10" <?= $perPage===10?'selected':'' ?>>10</option><option value="20" <?= $perPage===20?'selected':'' ?>>20</option><option value="50" <?= $perPage===50?'selected':'' ?>>50</option></select></label>
    <button class="btn" type="submit">סינון</button>
</form>
<p class="page-summary">סה"כ <?= $total ?> פעילויות · עמוד <?= $page ?> מתוך <?= $totalPages ?></p>
<div class="table-wrap"><table><thead><tr><th>תאריך</th><th>קבוצה</th><th>סוג</th><th>משתתפים</th><th>יוצר</th><th>הערות</th><th>פעולות</th></tr></thead><tbody id="activities-table-body"><?php include __DIR__ . '/activities_table.php'; ?></tbody></table></div>
<?= renderPagination($page, $totalPages) ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
