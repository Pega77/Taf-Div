<?php
require_once __DIR__ . '/auth.php';

function isAdmin(): bool
{
    return user() && user()['role'] === 'admin';
}

function isCoordinator(): bool
{
    return user() && user()['role'] === 'coordinator';
}

function isInstructor(): bool
{
    return user() && user()['role'] === 'instructor';
}

function denyIfUnauthorized(bool $allowed): void
{
    if (!$allowed) {
        redirect('unauthorized.php');
    }
}

function canAccessGroup(int $groupId): bool
{
    if ($groupId <= 0) {
        return false;
    }

    if (isAdmin() || isCoordinator()) {
        return true;
    }

    $stmt = getDB()->prepare('SELECT COUNT(*) FROM groups WHERE id = :id AND instructor_user_id = :user_id AND deleted_at IS NULL');
    $stmt->execute([
        'id' => $groupId,
        'user_id' => user()['id'],
    ]);

    return (int)$stmt->fetchColumn() > 0;
}

function canAccessStudent(int $studentId): bool
{
    if (isAdmin() || isCoordinator()) {
        return true;
    }

    $stmt = getDB()->prepare('SELECT COUNT(*) FROM group_student gs JOIN groups g ON g.id = gs.group_id JOIN students s ON s.id = gs.student_id WHERE gs.student_id = :sid AND gs.is_active = 1 AND g.instructor_user_id = :uid AND g.deleted_at IS NULL');
    $stmt->execute(['sid' => $studentId, 'uid' => user()['id']]);
    return (int)$stmt->fetchColumn() > 0;
}

function canAccessActivity(int $activityId): bool
{
    if (isAdmin() || isCoordinator()) {
        return true;
    }

    $stmt = getDB()->prepare('SELECT COUNT(*) FROM activities a JOIN groups g ON g.id = a.group_id WHERE a.id = :id AND g.instructor_user_id = :uid AND a.deleted_at IS NULL AND g.deleted_at IS NULL');
    $stmt->execute(['id' => $activityId, 'uid' => user()['id']]);
    return (int)$stmt->fetchColumn() > 0;
}
