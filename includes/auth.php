<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        header('Location: index.php');
        exit;
    }
}

function userHasRole(array $roles): bool
{
    $user = currentUser();

    if (!$user) {
        return false;
    }

    return in_array($user['role_name'], $roles, true);
}

function roleLabel(string $role): string
{
    $labels = [
        'admin' => 'Müdür',
        'assistant_principal' => 'Müdür Yardımcısı',
        'teacher' => 'Öğretmen',
        'student' => 'Öğrenci',
    ];

    return $labels[$role] ?? ucfirst(str_replace('_', ' ', $role));
}
