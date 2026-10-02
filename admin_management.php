<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

if (!$db) {
    die('MySQL bağlantısı kurulamadı. Lütfen veritabanını oluşturup bağlantı bilgilerini kontrol edin.');
}

$current = currentUser();
$currentUser = fetchOne(
    $db,
    'SELECT u.*, r.name AS role_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE u.id = :id
     LIMIT 1',
    ['id' => (int) $current['id']]
);

if (!$currentUser || !in_array($currentUser['role_name'], ['admin', 'assistant_principal'], true)) {
    header('Location: dashboard.php');
    exit;
}

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_class') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $grade = trim((string) ($_POST['grade'] ?? ''));

        if ($name !== '') {
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE classes SET name = :name, grade = :grade WHERE id = :id');
                $stmt->execute(['name' => $name, 'grade' => $grade, 'id' => $id]);
            } else {
                $stmt = $db->prepare('INSERT INTO classes (name, grade) VALUES (:name, :grade)');
                $stmt->execute(['name' => $name, 'grade' => $grade]);
            }
            $successMessage = 'Sınıf kaydı başarıyla güncellendi.';
        }
    }

    if ($action === 'delete_class') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM teacher_class_subject WHERE class_id = :id')->execute(['id' => $id]);
            $db->prepare('UPDATE users SET class_id = NULL WHERE class_id = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM classes WHERE id = :id')->execute(['id' => $id]);
            $successMessage = 'Sınıf silindi.';
        }
    }

    if ($action === 'save_subject') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));

        if ($name !== '') {
            if ($id > 0) {
                $stmt = $db->prepare('UPDATE subjects SET name = :name WHERE id = :id');
                $stmt->execute(['name' => $name, 'id' => $id]);
            } else {
                $stmt = $db->prepare('INSERT INTO subjects (name) VALUES (:name)');
                $stmt->execute(['name' => $name]);
            }
            $successMessage = 'Ders kaydı başarıyla kaydedildi.';
        }
    }

    if ($action === 'delete_subject') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM teacher_class_subject WHERE subject_id = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM subjects WHERE id = :id')->execute(['id' => $id]);
            $successMessage = 'Ders silindi.';
        }
    }

    if ($action === 'save_teacher') {
        $id = (int) ($_POST['id'] ?? 0);
        $username = trim((string) ($_POST['username'] ?? ''));
        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $password = trim((string) ($_POST['password'] ?? ''));
        $teacherRoleId = (int) fetchOne($db, 'SELECT id FROM roles WHERE name = :name', ['name' => 'teacher'])['id'];

        if ($username !== '' && $fullName !== '') {
            $duplicate = fetchOne(
                $db,
                'SELECT id FROM users WHERE username = :username AND role_id = :role_id AND id != :id LIMIT 1',
                ['username' => $username, 'role_id' => $teacherRoleId, 'id' => $id]
            );

            if ($duplicate) {
                $errorMessage = 'Bu kullanıcı adı kullanılıyor. Lütfen farklı bir kullanıcı adı girin.';
            } else {
                if ($id > 0) {
                    if ($password !== '') {
                        $stmt = $db->prepare('UPDATE users SET username = :username, full_name = :full_name, password = :password WHERE id = :id AND role_id = :role_id');
                        $stmt->execute(['username' => $username, 'full_name' => $fullName, 'password' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id, 'role_id' => $teacherRoleId]);
                    } else {
                        $stmt = $db->prepare('UPDATE users SET username = :username, full_name = :full_name WHERE id = :id AND role_id = :role_id');
                        $stmt->execute(['username' => $username, 'full_name' => $fullName, 'id' => $id, 'role_id' => $teacherRoleId]);
                    }
                } else {
                    $stmt = $db->prepare('INSERT INTO users (username, password, full_name, role_id) VALUES (:username, :password, :full_name, :role_id)');
                    $stmt->execute(['username' => $username, 'password' => password_hash($password !== '' ? $password : '123456', PASSWORD_DEFAULT), 'full_name' => $fullName, 'role_id' => $teacherRoleId]);
                }
                $successMessage = 'Öğretmen kaydı başarıyla kaydedildi.';
            }
        }
    }

    if ($action === 'delete_teacher') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->prepare('DELETE FROM teacher_class_subject WHERE teacher_id = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM users WHERE id = :id AND role_id = (SELECT id FROM roles WHERE name = :role)')->execute(['id' => $id, 'role' => 'teacher']);
            $successMessage = 'Öğretmen silindi.';
        }
    }

    if ($action === 'assign_teacher') {
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);

        if ($teacherId > 0 && $classId > 0 && $subjectId > 0) {
            $stmt = $db->prepare('INSERT INTO teacher_class_subject (teacher_id, class_id, subject_id) VALUES (:teacher_id, :class_id, :subject_id) ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id), class_id = VALUES(class_id), subject_id = VALUES(subject_id)');
            $stmt->execute(['teacher_id' => $teacherId, 'class_id' => $classId, 'subject_id' => $subjectId]);
            $successMessage = 'Öğretmen sınıf ve derse atandı.';
        }
    }
}

$classes = fetchAll($db, 'SELECT * FROM classes ORDER BY name ASC');
$subjects = fetchAll($db, 'SELECT * FROM subjects ORDER BY name ASC');
$teachers = fetchAll($db, 'SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE name = :role) ORDER BY full_name ASC', ['role' => 'teacher']);
$teacherAssignments = fetchAll($db, 'SELECT tcs.id, t.full_name AS teacher_name, c.name AS class_name, s.name AS subject_name FROM teacher_class_subject tcs INNER JOIN users t ON t.id = tcs.teacher_id INNER JOIN classes c ON c.id = tcs.class_id INNER JOIN subjects s ON s.id = tcs.subject_id ORDER BY c.name ASC, s.name ASC');

$panelTitle = ($currentUser['role_name'] === 'admin') ? 'Müdür Yönetim Paneli' : 'Müdür Yardımcısı Yönetim Paneli';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($panelTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/app.js"></script>
</head>
<body>
    <div class="dashboard">
        <div class="topbar">
            <div class="brand">
                <span class="brand-mark">L</span>
                <span>Lise Otomasyon</span>
            </div>
            <div class="user-box">
                <div class="avatar"><?= htmlspecialchars(substr($currentUser['full_name'], 0, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="user-meta">
                    <strong><?= htmlspecialchars($currentUser['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small><?= htmlspecialchars(roleLabel($currentUser['role_name']), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <a href="dashboard.php" class="logout-btn" style="margin-left: 8px;">Ana Panel</a>
                <a href="logout.php" class="logout-btn" style="margin-left: 8px;">Çıkış</a>
            </div>
        </div>

        <div class="content">
            <div class="panel" style="margin-bottom: 24px;">
                <div class="panel-header-block">
                    <h3><?= htmlspecialchars($panelTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                </div>
                <div class="panel-body">
                    <?php if ($successMessage): ?>
                        <div class="flash-alert success" role="alert">
                            <span><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                            <button type="button" class="flash-close" data-dismiss-alert aria-label="Kapat">×</button>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMessage): ?>
                        <div class="flash-alert error" role="alert">
                            <span><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></span>
                            <button type="button" class="flash-close" data-dismiss-alert aria-label="Kapat">×</button>
                        </div>
                    <?php endif; ?>
                    <p class="muted">Bu panelde ders, sınıf ve öğretmen kayıtlarını ekleyebilir, güncelleyebilir, silebilir ve listede görüntüleyebilirsiniz.</p>
                </div>
            </div>

            <div class="panel-grid">
                <div class="panel">
                    <div class="panel-header-block">
                        <h3>Sınıf Yönetimi</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="admin_management.php">
                            <input type="hidden" name="action" value="save_class">
                            <div class="form-grid">
                                <div class="field">
                                    <label for="class_name">Sınıf Adı</label>
                                    <input id="class_name" type="text" name="name" placeholder="Örn: 9-A" required>
                                </div>
                                <div class="field">
                                    <label for="class_grade">Şube / Seviye</label>
                                    <input id="class_grade" type="text" name="grade" placeholder="Örn: 9. Sınıf">
                                </div>
                            </div>
                            <div class="inline-action">
                                <button type="submit" class="primary-btn" style="width: auto; min-width: 200px;">Sınıf Ekle</button>
                            </div>
                        </form>
                        <div class="table-wrap" style="margin-top: 18px;">
                            <table>
                                <thead>
                                    <tr><th>Sınıf</th><th>Seviye</th><th>İşlem</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($classes as $classItem): ?>
                                        <tr>
                                            <td>
                                                <form method="post" action="admin_management.php" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                    <input type="hidden" name="action" value="save_class">
                                                    <input type="hidden" name="id" value="<?= (int) $classItem['id']; ?>">
                                                    <input type="text" name="name" value="<?= htmlspecialchars($classItem['name'], ENT_QUOTES, 'UTF-8'); ?>" required style="min-width:120px;">
                                                </form>
                                            </td>
                                            <td>
                                                <form method="post" action="admin_management.php" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                    <input type="hidden" name="action" value="save_class">
                                                    <input type="hidden" name="id" value="<?= (int) $classItem['id']; ?>">
                                                    <input type="text" name="grade" value="<?= htmlspecialchars($classItem['grade'] ?: '', ENT_QUOTES, 'UTF-8'); ?>" style="min-width:120px;">
                                                    <button type="submit" class="logout-btn">Kaydet</button>
                                                </form>
                                            </td>
                                            <td>
                                                <form method="post" action="admin_management.php" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_class">
                                                    <input type="hidden" name="id" value="<?= (int) $classItem['id']; ?>">
                                                    <button type="submit" class="logout-btn" data-confirm="Sınıfı silmek istediğinize emin misiniz?">Sil</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-header-block">
                        <h3>Ders Yönetimi</h3>
                    </div>
                    <div class="panel-body">
                        <form method="post" action="admin_management.php">
                            <input type="hidden" name="action" value="save_subject">
                            <div class="form-grid">
                                <div class="field full">
                                    <label for="subject_name">Ders Adı</label>
                                    <input id="subject_name" type="text" name="name" placeholder="Örn: Matematik" required>
                                </div>
                            </div>
                            <div class="inline-action">
                                <button type="submit" class="primary-btn" style="width: auto; min-width: 200px;">Ders Ekle</button>
                            </div>
                        </form>
                        <div class="table-wrap" style="margin-top: 18px;">
                            <table>
                                <thead>
                                    <tr><th>Ders</th><th>İşlem</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td>
                                                <form method="post" action="admin_management.php" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                    <input type="hidden" name="action" value="save_subject">
                                                    <input type="hidden" name="id" value="<?= (int) $subject['id']; ?>">
                                                    <input type="text" name="name" value="<?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8'); ?>" required style="min-width:180px;">
                                                    <button type="submit" class="logout-btn">Kaydet</button>
                                                </form>
                                            </td>
                                            <td>
                                                <form method="post" action="admin_management.php" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete_subject">
                                                    <input type="hidden" name="id" value="<?= (int) $subject['id']; ?>">
                                                    <button type="submit" class="logout-btn" data-confirm="Dersi silmek istediğinize emin misiniz?">Sil</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top: 24px;">
                <div class="panel-header-block">
                    <h3>Öğretmen Yönetimi</h3>
                </div>
                <div class="panel-body">
                    <form method="post" action="admin_management.php">
                        <input type="hidden" name="action" value="save_teacher">
                        <div class="form-grid">
                            <div class="field">
                                <label for="teacher_username">Kullanıcı Adı</label>
                                <input id="teacher_username" type="text" name="username" placeholder="Örn: zeynep_ogretmen" required>
                            </div>
                            <div class="field">
                                <label for="teacher_name">Ad Soyad</label>
                                <input id="teacher_name" type="text" name="full_name" placeholder="Örn: Zeynep Arslan" required>
                            </div>
                            <div class="field full">
                                <label for="teacher_password">Şifre</label>
                                <input id="teacher_password" type="password" name="password" placeholder="Şifre giriniz (boş bırakılırsa mevcut şifre korunur)">
                            </div>
                        </div>
                        <div class="inline-action">
                            <button type="submit" class="primary-btn" style="width: auto; min-width: 220px;">Öğretmen Ekle</button>
                        </div>
                    </form>

                    <div class="table-wrap" style="margin-top: 18px;">
                        <table>
                            <thead>
                                <tr><th>Öğretmen</th><th>Kullanıcı Adı</th><th>Şifre</th><th>İşlem</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teachers as $teacher): ?>
                                    <tr>
                                        <td>
                                            <form method="post" action="admin_management.php" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                                <input type="hidden" name="action" value="save_teacher">
                                                <input type="hidden" name="id" value="<?= (int) $teacher['id']; ?>">
                                                <input type="text" name="full_name" value="<?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8'); ?>" required style="min-width:160px;">
                                        </td>
                                        <td>
                                                <input type="text" name="username" value="<?= htmlspecialchars($teacher['username'], ENT_QUOTES, 'UTF-8'); ?>" required style="min-width:150px;">
                                        </td>
                                        <td>
                                                <input type="password" name="password" placeholder="Yeni şifre" style="min-width:120px;">
                                        </td>
                                        <td>
                                                <button type="submit" class="logout-btn">Kaydet</button>
                                            </form>
                                            <form method="post" action="admin_management.php" style="display:inline; margin-left: 8px;">
                                                <input type="hidden" name="action" value="delete_teacher">
                                                <input type="hidden" name="id" value="<?= (int) $teacher['id']; ?>">
                                                <button type="submit" class="logout-btn" data-confirm="Öğretmeni silmek istediğinize emin misiniz?">Sil</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="panel" style="margin-top: 24px;">
                <div class="panel-header-block">
                    <h3>Öğretmen - Sınıf - Ders Atama</h3>
                </div>
                <div class="panel-body">
                    <form method="post" action="admin_management.php" data-confirm="Öğretmen atamasını kaydetmek istediğinize emin misiniz?">
                        <input type="hidden" name="action" value="assign_teacher">
                        <div class="form-grid">
                            <div class="field">
                                <label for="assignment_teacher">Öğretmen</label>
                                <select id="assignment_teacher" name="teacher_id" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($teachers as $teacher): ?>
                                        <option value="<?= (int) $teacher['id']; ?>"><?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field">
                                <label for="assignment_class">Sınıf</label>
                                <select id="assignment_class" name="class_id" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($classes as $classItem): ?>
                                        <option value="<?= (int) $classItem['id']; ?>"><?= htmlspecialchars($classItem['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field full">
                                <label for="assignment_subject">Ders</label>
                                <select id="assignment_subject" name="subject_id" required>
                                    <option value="">Seçiniz</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?= (int) $subject['id']; ?>"><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="inline-action">
                            <button type="submit" class="primary-btn" style="width: auto; min-width: 220px;">Atama Kaydet</button>
                        </div>
                    </form>

                    <div class="table-wrap" style="margin-top: 18px;">
                        <table>
                            <thead>
                                <tr><th>Öğretmen</th><th>Sınıf</th><th>Ders</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($teacherAssignments as $assignment): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($assignment['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($assignment['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        <td><?= htmlspecialchars($assignment['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
