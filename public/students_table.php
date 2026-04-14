<?php foreach ($students as $student): ?>
<tr class="<?= !empty($student['deleted_at']) ? 'deleted-row' : '' ?>">
    <td><?= e($student['full_name']) ?></td>
    <td><?= e($student['national_id']) ?></td>
    <td><?= e($student['group_name']) ?></td>
    <td>
        <?php if (!empty($student['deleted_at'])): ?>
            <span class="status-pill deleted">נמחק רכות</span>
        <?php else: ?>
            <span class="status-pill <?= e($student['status']) ?>"><?= e(statusLabel($student['status'])) ?></span>
        <?php endif; ?>
    </td>
    <td class="table-actions">
        <a href="student_view.php?id=<?= (int)$student['id'] ?>">צפייה</a>
        <?php if (empty($student['deleted_at'])): ?>
            | <a href="student_form.php?id=<?= (int)$student['id'] ?>">עריכה</a>
            <?php if ($student['status'] === 'active'): ?>
                |
                <form method="post" action="freeze_student.php" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">
                    <button class="link-button confirm-action" type="submit">הקפאה</button>
                </form>
            <?php else: ?>
                |
                <form method="post" action="unfreeze_student.php" class="inline-form">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">
                    <button class="link-button confirm-action" type="submit">הפשרה</button>
                </form>
            <?php endif; ?>
            |
            <form method="post" action="delete_student.php" class="inline-form">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">
                <button class="link-button confirm-action" type="submit">מחיקה</button>
            </form>
        <?php else: ?>
            |
            <form method="post" action="restore_student.php" class="inline-form">
                <?= csrfField() ?>
                <input type="hidden" name="id" value="<?= (int)$student['id'] ?>">
                <button class="link-button" type="submit">שחזור</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$students): ?><tr><td colspan="5" class="centered muted">לא נמצאו תלמידים.</td></tr><?php endif; ?>
