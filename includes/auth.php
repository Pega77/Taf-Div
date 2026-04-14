<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$config = getConfig();
session_name($config['session_name']);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function loginUser(string $username, string $password): bool
{
    $stmt = getDB()->prepare('SELECT * FROM users WHERE username = :username AND is_active = 1 AND deleted_at IS NULL LIMIT 1');
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'role' => $user['role'],
    ];
    getDB()->prepare('UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = :id')->execute(['id' => $user['id']]);
    csrfToken();
    logAction('כניסה למערכת', 'user', (int)$user['id']);

    return true;
}

function logoutUser(): void
{
    if (!empty($_SESSION['user']['id'])) {
        logAction('התנתקות', 'user', (int)$_SESSION['user']['id']);
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
    }
    session_destroy();
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!user()) {
        redirect('login.php');
    }
}
