<?php
require_once __DIR__ . '/../includes/auth.php';
if (user()) {
    redirect('dashboard.php');
}
redirect('login.php');
