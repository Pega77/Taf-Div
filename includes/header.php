<?php require_once __DIR__ . '/permissions.php'; ?>
<!doctype html>
<html lang="he" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e(getConfig()['app_name']) ?></title>
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/forms.css">
    <link rel="stylesheet" href="../assets/css/tables.css">
    <link rel="stylesheet" href="../assets/css/mobile.css">
    <link rel="stylesheet" href="../assets/css/app.css">
</head>
<body>
<header class="site-header">
    <div class="container header-inner">
        <h1 class="site-title"><?= e(getConfig()['app_name']) ?></h1>
        <?php if (user()): ?>
            <nav class="main-nav">
                <a class="<?= isCurrentPage('dashboard.php') ? 'active' : '' ?>" href="dashboard.php">דשבורד</a>
                <?php if (isAdmin() || isCoordinator()): ?>
                    <a class="<?= isCurrentPage('programs.php') ? 'active' : '' ?>" href="programs.php">תוכניות</a>
                <?php endif; ?>
                <a class="<?= isCurrentPage('groups.php') ? 'active' : '' ?>" href="groups.php">קבוצות</a>
                <a class="<?= isCurrentPage('students.php') ? 'active' : '' ?>" href="students.php">תלמידים</a>
                <a class="<?= isCurrentPage('activities.php') ? 'active' : '' ?>" href="activities.php">פעילויות</a>
                <?php if (isAdmin() || isCoordinator()): ?>
                    <a class="<?= isCurrentPage('activity_types.php') ? 'active' : '' ?>" href="activity_types.php">סוגי פעילות</a>
                    <a class="<?= isCurrentPage('users.php') ? 'active' : '' ?>" href="users.php">משתמשים</a>
                    <a class="<?= isCurrentPage('audit_logs.php') ? 'active' : '' ?>" href="audit_logs.php">יומן פעולות</a>
                <?php endif; ?>
                <a href="logout.php">התנתקות</a>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main class="container main-content">
<?php if ($message = flash('success')): ?>
    <div class="alert success"><?= e($message) ?></div>
<?php endif; ?>
<?php if ($message = flash('error')): ?>
    <div class="alert error"><?= e($message) ?></div>
<?php endif; ?>
