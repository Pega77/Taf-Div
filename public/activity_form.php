<?php
require_once __DIR__ . '/../includes/permissions.php';
requireLogin();
$db = getDB();
$id = (int)get('id', 0);
$editing = $id > 0;
if ($editing) {
    denyIfUnauthorized(canAccessActivity($id));
}
$groupId = (int)get('group_id', 0);
$errors = [];
$groupParams = [];
$groupSql = 'SELECT g.*, p.name AS program_name FROM groups g JOIN programs p ON p.id = g.program_id WHERE g.deleted_at IS NULL';
if (isInstructor()) {
    $groupSql .= ' AND g.instructor_user_id = :uid';
    $groupParams['uid'] = user()['id'];
}
$groupSql .= ' ORDER BY g.name';
$stmt = $db->prepare($groupSql);
$stmt->execute($groupParams);
$groups = $stmt->fetchAll();
$types = $db->query('SELECT * FROM activity_types WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order, id')->fetchAll();
$activity = ['group_id' => $groupId, 'activity_type_id' => 0, 'activity_date' => today(), 'notes' => ''];
$selectedIds = [];
if ($editing) {
    $stmt = $db->prepare('SELECT * FROM activities WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['id' => $id]);
    $activity = $stmt->fetch() ?: $activity;
    $groupId = (int)$activity['group_id'];
    $stmt = $db->prepare('SELECT student_id FROM activity_students WHERE activity_id = :id AND participation_status = "present"');
    $stmt->execute(['id' => $id]);
    $selectedIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
if (!$groupId && $groups) { $groupId = (int)$groups[0]['id']; }
if ($groupId) { denyIfUnauthorized(canAccessGroup($groupId)); }
$students = [];
$programId = 0;
if ($groupId) {
    $stmt = $db->prepare('SELECT s.id, s.full_name, s.status, g.program_id FROM students s JOIN group_student gs ON gs.student_id = s.id AND gs.is_active = 1 JOIN groups g ON g.id = gs.group_id WHERE g.id = :gid AND s.status = "active" AND s.deleted_at IS NULL ORDER BY s.full_name');
    $stmt->execute(['gid' => $groupId]);
    $students = $stmt->fetchAll();
    $programId = isset($students[0]['program_id']) ? (int)$students[0]['program_id'] : (int)($db->query('SELECT program_id FROM groups WHERE id=' . $groupId)->fetchColumn());
}
if (isPost()) {
    verifyCsrf();
    $groupId = (int)post('group_id');
    denyIfUnauthorized(canAccessGroup($groupId));
    $stmt = $db->prepare('SELECT program_id FROM groups WHERE id = :id AND deleted_at IS NULL');
    $stmt->execute(['id' => $groupId]);
    $programId = (int)$stmt->fetchColumn();
    $activityTypeId = (int)post('activity_type_id');
    $activityDate = (string)post('activity_date', today());
    $notes = trim((string)post('notes'));
    if ($activityTypeId <= 0) { $errors[] = 'יש לבחור סוג פעילות.'; }
    if (!$activityDate || !preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $activityDate)) { $errors[] = 'יש להזין תאריך פעילות תקין.'; }
    $validStmt = $db->prepare('SELECT s.id FROM students s JOIN group_student gs ON gs.student_id = s.id AND gs.is_active = 1 WHERE gs.group_id = :gid AND s.status = "active" AND s.deleted_at IS NULL');
    $validStmt->execute(['gid' => $groupId]);
    $validIds = array_map('intval', $validStmt->fetchAll(PDO::FETCH_COLUMN));
    $selected = array_map('intval', post('student_ids', []));
    $selected = array_values(array_intersect($selected, $validIds));
    if (!$errors) {
        if ($editing) {
            $stmt = $db->prepare('UPDATE activities SET program_id=:program_id, group_id=:group_id, activity_type_id=:activity_type_id, activity_date=:activity_date, notes=:notes WHERE id=:id');
            $stmt->execute([
                'program_id' => $programId,
                'group_id' => $groupId,
                'activity_type_id' => $activityTypeId,
                'activity_date' => $activityDate,
                'notes' => $notes ?: null,
                'id' => $id,
            ]);
            $activityId = $id;
            $db->prepare('DELETE FROM activity_students WHERE activity_id = :id')->execute(['id' => $activityId]);
            logAction('עדכון פעילות', 'activity', $activityId, ['group_id' => $groupId, 'type_id' => $activityTypeId]);
        } else {
            $stmt = $db->prepare('INSERT INTO activities (program_id, group_id, activity_type_id, activity_date, notes, created_by_user_id) VALUES (:program_id,:group_id,:activity_type_id,:activity_date,:notes,:created_by_user_id)');
            $stmt->execute([
                'program_id' => $programId,
                'group_id' => $groupId,
                'activity_type_id' => $activityTypeId,
                'activity_date' => $activityDate,
                'notes' => $notes ?: null,
                'created_by_user_id' => user()['id'],
            ]);
            $activityId = (int)$db->lastInsertId();
            logAction('יצירת פעילות', 'activity', $activityId, ['group_id' => $groupId, 'type_id' => $activityTypeId]);
        }
        foreach ($validIds as $studentId) {
            $db->prepare('INSERT INTO activity_students (activity_id, student_id, participation_status) VALUES (:aid,:sid,:status)')->execute([
                'aid' => $activityId,
                'sid' => $studentId,
                'status' => in_array($studentId, $selected, true) ? 'present' : 'absent',
            ]);
        }
        flash('success', $editing ? 'הפעילות עודכנה בהצלחה.' : 'הפעילות נשמרה בהצלחה.');
        redirect('activity_view.php?id=' . $activityId);
    }
    $activity = ['group_id' => $groupId, 'activity_type_id' => $activityTypeId, 'activity_date' => $activityDate, 'notes' => $notes];
    $selectedIds = $selected;
    $stmt = $db->prepare('SELECT s.id, s.full_name, s.status, g.program_id FROM students s JOIN group_student gs ON gs.student_id = s.id AND gs.is_active = 1 JOIN groups g ON g.id = gs.group_id WHERE g.id = :gid AND s.status = "active" AND s.deleted_at IS NULL ORDER BY s.full_name');
    $stmt->execute(['gid' => $groupId]);
    $students = $stmt->fetchAll();
}
include __DIR__ . '/../includes/header.php';
?>
<h2><?= $editing ? 'עריכת פעילות' : 'פעילות חדשה' ?></h2>
<?php if ($errors): ?><div class="alert error"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="get" class="card form-grid compact-form">
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= (int)$id ?>"><?php endif; ?>
    <label><span>קבוצה</span><select name="group_id" onchange="this.form.submit()"><option value="">בחר קבוצה</option><?php foreach ($groups as $group): ?><option value="<?= (int)$group['id'] ?>" <?= $groupId===(int)$group['id']?'selected':'' ?>><?= e($group['name']) ?> - <?= e($group['program_name']) ?></option><?php endforeach; ?></select></label>
</form>
<form method="post" class="card form-grid" id="activity-form">
    <?= csrfField() ?>
    <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
    <label><span>סוג פעילות</span><select name="activity_type_id" required><option value="">בחר סוג פעילות</option><?php foreach ($types as $type): ?><option value="<?= (int)$type['id'] ?>" <?= (int)$activity['activity_type_id']===(int)$type['id']?'selected':'' ?>><?= e($type['name']) ?></option><?php endforeach; ?></select></label>
    <label><span>תאריך פעילות</span><input type="date" name="activity_date" value="<?= e((string)$activity['activity_date']) ?>" required></label>
    <label><span>הערות</span><textarea name="notes" rows="3"><?= e((string)$activity['notes']) ?></textarea></label>
    <div class="card student-picker">
        <div class="actions-between actions-tight"><strong>תלמידים פעילים בקבוצה</strong><div class="button-row"><button type="button" class="btn" id="select-all-students">סמן את כל התלמידים</button><button type="button" class="btn" id="clear-all-students">נקה סימון</button></div></div>
        <?php foreach ($students as $student): ?>
            <label class="checkbox-row student-row"><input type="checkbox" name="student_ids[]" value="<?= (int)$student['id'] ?>" <?= in_array((int)$student['id'], $selectedIds, true) ? 'checked' : '' ?>><span><?= e($student['full_name']) ?></span></label>
        <?php endforeach; ?>
        <?php if (!$students): ?><p class="muted">אין תלמידים פעילים בקבוצה זו.</p><?php endif; ?>
    </div>
    <button class="btn primary" type="submit"><?= $editing ? 'עדכון פעילות' : 'שמירת פעילות' ?></button>
</form>
<script src="../assets/js/activity-form.js"></script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
