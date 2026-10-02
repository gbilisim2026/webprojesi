<?php

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';
$dbName = getenv('DB_NAME') ?: 'lise_otomasyon';

try {
    $pdo = new PDO("mysql:host={$dbHost};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `{$dbName}`;");

    $sql = file_get_contents(__DIR__ . '/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('schema.sql bulunamadı.');
    }

    $pdo->exec($sql);

    echo "Veritabanı ve örnek veriler başarıyla oluşturuldu.\n";
    echo "Kullanıcılar: mudur, mudur_yardimci, zeynep_ogretmen, ayse_ogrenci\n";
    echo "Şifre: 123456\n";
} catch (Throwable $exception) {
    echo 'Hata: ' . $exception->getMessage() . PHP_EOL;
    exit(1);
}
