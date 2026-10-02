<?php

require_once __DIR__ . '/../config/db.php';

function fetchOne(PDO $pdo, string $sql, array $params = []): ?array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);
    $result = $statement->fetch();

    return $result ?: null;
}

function fetchAll(PDO $pdo, string $sql, array $params = []): array
{
    $statement = $pdo->prepare($sql);
    $statement->execute($params);

    return $statement->fetchAll();
}

function getClassName(PDO $pdo, ?int $classId): string
{
    if (!$classId) {
        return 'Atanmamış';
    }

    $row = fetchOne($pdo, 'SELECT name FROM classes WHERE id = :id', ['id' => $classId]);

    return $row['name'] ?? 'Bilinmeyen sınıf';
}

function getSubjectName(PDO $pdo, ?int $subjectId): string
{
    if (!$subjectId) {
        return 'Atanmamış';
    }

    $row = fetchOne($pdo, 'SELECT name FROM subjects WHERE id = :id', ['id' => $subjectId]);

    return $row['name'] ?? 'Bilinmeyen ders';
}

function getRoleName(PDO $pdo, ?int $roleId): string
{
    if (!$roleId) {
        return 'Belirtilmemiş';
    }

    $row = fetchOne($pdo, 'SELECT name FROM roles WHERE id = :id', ['id' => $roleId]);

    return $row['name'] ?? 'Bilinmeyen rol';
}
