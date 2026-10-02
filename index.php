<?php

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (currentUser()) {
    header('Location: dashboard.php');
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($db) {
        $user = fetchOne(
            $db,
            'SELECT u.id, u.username, u.password, u.full_name, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.username = :username
             LIMIT 1',
            ['username' => $username]
        );

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'role_name' => $user['role_name'],
            ];

            header('Location: dashboard.php');
            exit;
        }

        $errorMessage = 'Kullanıcı adı veya şifre hatalı. Lütfen bilgileri kontrol edin.';
    } else {
        $errorMessage = 'MySQL bağlantısı kurulamadı. Lütfen config/db.php dosyasını ve veritabanını kontrol edin.';
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lise Otomasyon Sistemi</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-shell">
        <div class="login-card">
            <div class="login-hero">
                <div class="hero-inner">
                    <span class="kicker">Akademik Yönetim</span>
                    <h1 class="hero-title">Lise Otomasyon Sistemi</h1>
                    <p class="hero-text">
                        Müdür, müdür yardımcıları, öğretmenler ve öğrenciler için tek merkezli, güvenli ve modern bir akademik yönetim deneyimi.
                    </p>

                    <div class="demo-list">
                        <div class="demo-item">
                            <strong>Müdür</strong>
                            Kullanıcı adı: mudur / Şifre: 123456
                        </div>
                        <div class="demo-item">
                            <strong>Müdür Yardımcısı</strong>
                            Kullanıcı adı: mudur_yardimci / Şifre: 123456
                        </div>
                        <div class="demo-item">
                            <strong>Öğretmen</strong>
                            Kullanıcı adı: zeynep_ogretmen / Şifre: 123456
                        </div>
                        <div class="demo-item">
                            <strong>Öğrenci</strong>
                            Kullanıcı adı: ayse_ogrenci / Şifre: 123456
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-form-panel">
                <div class="panel-header">
                    <h2>Giriş Yap</h2>
                    <p>Hesabınıza ait kullanıcı adı ve şifrenizi giriniz.</p>
                </div>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>

                <form method="post" action="index.php">
                    <div class="form-group">
                        <label for="username">Kullanıcı Adı</label>
                        <input type="text" id="username" name="username" value="" placeholder="Kullanıcı adınızı yazın" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Şifre</label>
                        <input type="password" id="password" name="password" placeholder="Şifrenizi yazın" required>
                    </div>

                    <button type="submit" class="primary-btn">Giriş Yap</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
