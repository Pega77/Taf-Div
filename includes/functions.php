<?php
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function post(string $key, $default = null)
{
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null)
{
    return $_GET[$key] ?? $default;
}

function today(): string
{
    return date('Y-m-d');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function flash(string $key, ?string $value = null): ?string
{
    if ($value !== null) {
        $_SESSION['flash'][$key] = $value;
        return null;
    }

    $message = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $message;
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    $token = (string)post('csrf_token', '');
    if (!$token || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        flash('error', 'פג תוקף הטופס. יש לנסות שוב.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'dashboard.php');
    }
}

function isPost(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function validateNationalId(string $nationalId): bool
{
    return (bool)preg_match('/^[0-9]{5,9}$/', $nationalId);
}

function selectedValue(array $valueRow, string $fieldType): string
{
    return match ($fieldType) {
        'number' => (string)($valueRow['value_number'] ?? ''),
        'date' => (string)($valueRow['value_date'] ?? ''),
        'boolean' => !empty($valueRow['value_boolean']) ? '1' : '0',
        'select' => (string)(json_decode($valueRow['value_json'] ?? 'null', true) ?? ''),
        default => (string)($valueRow['value_text'] ?? ''),
    };
}

function roleLabel(string $role): string
{
    return match ($role) {
        'admin' => 'מנהל',
        'coordinator' => 'רכז',
        'instructor' => 'מדריך',
        default => $role,
    };
}

function statusLabel(string $status): string
{
    return match ($status) {
        'active' => 'פעיל',
        'frozen' => 'מוקפא',
        'inactive' => 'לא פעיל',
        'present' => 'השתתף',
        'absent' => 'לא השתתף',
        default => $status,
    };
}

function old(string $key, $default = '')
{
    return $_POST[$key] ?? $default;
}

function currentPath(): string
{
    return basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
}

function isCurrentPage(string $fileName): bool
{
    return currentPath() === $fileName;
}

function logAction(string $action, string $entityType, int $entityId = 0, array $details = []): void
{
    if (empty($_SESSION['user'])) {
        return;
    }

    $db = getDB();
    $stmt = $db->prepare('INSERT INTO audit_logs (user_id, action, entity_type, entity_id, details_json, ip_address, created_at) VALUES (:user_id, :action, :entity_type, :entity_id, :details_json, :ip_address, NOW())');
    $stmt->execute([
        'user_id' => $_SESSION['user']['id'],
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'details_json' => $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    ]);
}

function requireAdminOrCoordinator(): void
{
    if (!user() || !in_array(user()['role'], ['admin', 'coordinator'], true)) {
        redirect('unauthorized.php');
    }
}

function pageParam(): int
{
    return max(1, (int)get('page', 1));
}

function perPageParam(int $default = 10): int
{
    $value = (int)get('per_page', $default);
    $allowed = [10, 20, 50];
    return in_array($value, $allowed, true) ? $value : $default;
}

function buildQueryString(array $overrides = [], array $exclude = []): string
{
    $query = $_GET;
    foreach ($exclude as $key) {
        unset($query[$key]);
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($query[$key]);
        } else {
            $query[$key] = $value;
        }
    }
    $qs = http_build_query($query);
    return $qs ? ('?' . $qs) : '';
}

function renderPagination(int $page, int $totalPages): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav class="pagination">';
    $html .= '<a class="page-link' . ($page <= 1 ? ' disabled' : '') . '" href="' . e(buildQueryString(['page' => max(1, $page - 1)])) . '">הקודם</a>';
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i === 1 || $i === $totalPages || abs($i - $page) <= 2) {
            $html .= '<a class="page-link' . ($i === $page ? ' active' : '') . '" href="' . e(buildQueryString(['page' => $i])) . '">' . $i . '</a>';
        } elseif (abs($i - $page) === 3) {
            $html .= '<span class="page-dots">…</span>';
        }
    }
    $html .= '<a class="page-link' . ($page >= $totalPages ? ' disabled' : '') . '" href="' . e(buildQueryString(['page' => min($totalPages, $page + 1)])) . '">הבא</a>';
    $html .= '</nav>';
    return $html;
}

function isAjaxRequest(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}
