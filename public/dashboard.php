<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$db = getDB();
$currentUser = user();

if (isInstructor()) {
    $stmt = $db->prepare('SELECT g.*, p.name AS program_name FROM groups g JOIN programs p ON p.id = g.program_id WHERE g.instructor_user_id = :uid ORDER BY g.name');
    $stmt->execute(['uid' => $currentUser['id']]);
    $groups = $stmt->fetchAll();
    $statsStmt = $db->prepare('SELECT (SELECT COUNT(*) FROM groups WHERE instructor_user_id = :uid) AS groups_count, (SELECT COUNT(*) FROM students s JOIN group_student gs ON gs.student_id = s.id AND gs.is_active = 1 JOIN groups g ON g.id = gs.group_id WHERE g.instructor_user_id = :uid) AS students_count, (SELECT COUNT(*) FROM activities a JOIN groups g ON g.id = a.group_id WHERE g.instructor_user_id = :uid) AS activities_count');
    $statsStmt->execute(['uid' => $currentUser['id']]);
} else {
    $stmt = $db->query('SELECT g.*, p.name AS program_name FROM groups g JOIN programs p ON p.id = g.program_id ORDER BY g.name');
    $groups = $stmt->fetchAll();
    $statsStmt = $db->query('SELECT (SELECT COUNT(*) FROM groups) AS groups_count, (SELECT COUNT(*) FROM students) AS students_count, (SELECT COUNT(*) FROM activities) AS activities_count, (SELECT COUNT(*) FROM users WHERE is_active = 1) AS users_count');
}
$stats = $statsStmt->fetch() ?: ['groups_count' => 0, 'students_count' => 0, 'activities_count' => 0, 'users_count' => 0];

$recentLogs = [];
if (isAdmin() || isCoordinator()) {
    $recentLogs = $db->query('SELECT a.created_at, a.action, a.entity_type, u.full_name FROM audit_logs a JOIN users u ON u.id = a.user_id ORDER BY a.id DESC LIMIT 8')->fetchAll();
}

include __DIR__ . '/../includes/header.php';
?>
<section class="page-header">
    <h2>דשבורד</h2>
    <p>שלום <?= e($currentUser['full_name']) ?>, התפקיד שלך הוא <?= e(roleLabel($currentUser['role'])) ?>.</p>
</section>
<div class="stats-grid">
    <div class="card stat-card"><span class="stat-label">קבוצות</span><strong class="stat-value"><?= (int)$stats['groups_count'] ?></strong></div>
    <div class="card stat-card"><span class="stat-label">תלמידים</span><strong class="stat-value"><?= (int)$stats['students_count'] ?></strong></div>
    <div class="card stat-card"><span class="stat-label">פעילויות</span><strong class="stat-value"><?= (int)$stats['activities_count'] ?></strong></div>
    <?php if (!isInstructor()): ?><div class="card stat-card"><span class="stat-label">משתמשים פעילים</span><strong class="stat-value"><?= (int)$stats['users_count'] ?></strong></div><?php endif; ?>
</div>
<div class="cards-grid">
    <?php if (isAdmin() || isCoordinator()): ?><a class="card link-card" href="programs.php"><strong>תוכניות</strong><span>ניהול רשימת התוכניות</span></a><?php endif; ?>
    <a class="card link-card" href="groups.php"><strong>קבוצות</strong><span>ניהול קבוצות ומדריכים</span></a>
    <a class="card link-card" href="students.php"><strong>תלמידים</strong><span>ניהול תלמידים, חיפוש והקפאה</span></a>
    <a class="card link-card" href="activities.php"><strong>פעילויות</strong><span>יצירה, עריכה וסינון פעילויות</span></a>
    <?php if (isAdmin() || isCoordinator()): ?><a class="card link-card" href="users.php"><strong>משתמשים</strong><span>ניהול משתמשים ותפקידים</span></a><?php endif; ?>
</div>
<section>
    <h3>הקבוצות הזמינות עבורך</h3>
    <div class="table-wrap">
        <table>
            <thead><tr><th>קבוצה</th><th>תוכנית</th><th>סטטוס</th></tr></thead>
            <tbody>
            <?php foreach ($groups as $group): ?>
                <tr>
                    <td><?= e($group['name']) ?></td>
                    <td><?= e($group['program_name']) ?></td>
                    <td><span class="status-pill <?= e($group['status']) ?>"><?= e(statusLabel($group['status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$groups): ?><tr><td colspan="3" class="centered muted">אין קבוצות להצגה.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php if ($recentLogs): ?>
<section class="section-gap">
    <div class="actions-between"><h3>פעולות אחרונות במערכת</h3><a href="audit_logs.php">לצפייה ביומן המלא</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>תאריך</th><th>משתמש</th><th>פעולה</th><th>ישות</th></tr></thead>
            <tbody>
            <?php foreach ($recentLogs as $log): ?>
                <tr>
                    <td><?= e($log['created_at']) ?></td>
                    <td><?= e($log['full_name']) ?></td>
                    <td><?= e($log['action']) ?></td>
                    <td><?= e($log['entity_type']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>
