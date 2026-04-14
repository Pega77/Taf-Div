<?php
require_once __DIR__ . '/../includes/auth.php';
$error = null;
if (isPost()) {
    verifyCsrf();
    if (loginUser(trim((string)post('username')), (string)post('password'))) {
        redirect('dashboard.php');
    }
    $error = 'שם המשתמש או הסיסמה שגויים.';
}
include __DIR__ . '/../includes/header.php';
?>
<section class="auth-box">
    <h2>כניסה למערכת</h2>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="card form-grid">
        <?= csrfField() ?>
        <label>
            <span>שם משתמש</span>
            <input type="text" name="username" required autocomplete="username">
        </label>
        <label>
            <span>סיסמה</span>
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn primary">כניסה</button>
    </form>
    <p class="muted">משתמש בדיקה: admin / 123456</p>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
