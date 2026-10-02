<?php

$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'lise_otomasyon';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

function getDbConnection(): ?PDO
{
    global $host, $dbName, $dbUser, $dbPass;

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
            $dbUser,
            $dbPass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        return $pdo;
    } catch (PDOException $exception) {
        return null;
    }
}

$db = getDbConnection();
