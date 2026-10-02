<?php

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

requireLogin();

if (!$db) {
    die('MySQL bağlantısı kurulamadı. Lütfen veritabanını oluşturup bağlantı bilgilerini kontrol edin.');
}

$current = currentUser();
$user = fetchOne(
    $db,
    'SELECT u.*, r.name AS role_name
     FROM users u
     INNER JOIN roles r ON r.id = u.role_id
     WHERE u.id = :id
     LIMIT 1',
    ['id' => (int) $current['id']]
);

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$role = $user['role_name'];

$classes = fetchAll($db, 'SELECT * FROM classes ORDER BY name ASC');
$teachers = fetchAll($db, 'SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE name = :role) ORDER BY full_name ASC', ['role' => 'teacher']);
$students = fetchAll($db, 'SELECT * FROM users WHERE role_id = (SELECT id FROM roles WHERE name = :role) ORDER BY full_name ASC', ['role' => 'student']);
$subjects = fetchAll($db, 'SELECT * FROM subjects ORDER BY name ASC');
$teacherAssignments = fetchAll(
    $db,
    'SELECT tcs.id, t.full_name AS teacher_name, c.name AS class_name, s.name AS subject_name
     FROM teacher_class_subject tcs
     INNER JOIN users t ON t.id = tcs.teacher_id
     INNER JOIN classes c ON c.id = tcs.class_id
     INNER JOIN subjects s ON s.id = tcs.subject_id
     ORDER BY c.name ASC, s.name ASC'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_teacher') {
    if ($role === 'assistant_principal' || $role === 'admin') {
        $teacherId = (int) ($_POST['teacher_id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);
        $subjectId = (int) ($_POST['subject_id'] ?? 0);

        if ($teacherId > 0 && $classId > 0 && $subjectId > 0) {
            $stmt = $db->prepare(
                'INSERT INTO teacher_class_subject (teacher_id, class_id, subject_id)
                 VALUES (:teacher_id, :class_id, :subject_id)
                 ON DUPLICATE KEY UPDATE teacher_id = VALUES(teacher_id), class_id = VALUES(class_id), subject_id = VALUES(subject_id)'
            );
            $stmt->execute([
                'teacher_id' => $teacherId,
                'class_id' => $classId,
                'subject_id' => $subjectId,
            ]);

            $successMessage = 'Öğretmen sınıfa ve derse başarıyla atandı.';
        }
    }
}

$teacherAssignments = fetchAll(
    $db,
    'SELECT tcs.id, t.full_name AS teacher_name, c.name AS class_name, s.name AS subject_name
     FROM teacher_class_subject tcs
     INNER JOIN users t ON t.id = tcs.teacher_id
     INNER JOIN classes c ON c.id = tcs.class_id
     INNER JOIN subjects s ON s.id = tcs.subject_id
     ORDER BY c.name ASC, s.name ASC'
);

$scheduleRows = [];
if ($role === 'student') {
    $studentClassId = (int) ($user['class_id'] ?? 0);
    $scheduleRows = fetchAll(
        $db,
        'SELECT s.name AS subject_name, u.full_name AS teacher_name, c.name AS class_name
         FROM teacher_class_subject tcs
         INNER JOIN subjects s ON s.id = tcs.subject_id
         INNER JOIN users u ON u.id = tcs.teacher_id
         INNER JOIN classes c ON c.id = tcs.class_id
         WHERE c.id = :class_id
         ORDER BY s.name ASC',
        ['class_id' => $studentClassId]
    );
}

if ($role === 'teacher') {
    $teacherSchedule = fetchAll(
        $db,
        'SELECT c.name AS class_name, s.name AS subject_name
         FROM teacher_class_subject tcs
         INNER JOIN classes c ON c.id = tcs.class_id
         INNER JOIN subjects s ON s.id = tcs.subject_id
         WHERE tcs.teacher_id = :teacher_id
         ORDER BY c.name ASC',
        ['teacher_id' => (int) $user['id']]
    );
}

$stats = [
    ['label' => 'Sınıflar', 'value' => count($classes)],
    ['label' => 'Öğretmenler', 'value' => count($teachers)],
    ['label' => 'Öğrenciler', 'value' => count($students)],
    ['label' => 'Dersler', 'value' => count($subjects)],
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli</title>
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
                <div class="avatar"><?= htmlspecialchars(substr($user['full_name'], 0, 1), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="user-meta">
                    <strong><?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    <small><?= htmlspecialchars(roleLabel($role), ENT_QUOTES, 'UTF-8'); ?></small>
                </div>
                <a href="logout.php" class="logout-btn">Çıkış</a>
            </div>
        </div>

        <div class="content">
            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="stat-card">
                        <div class="label"><?= htmlspecialchars($stat['label'], ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="value"><?= htmlspecialchars((string) $stat['value'], ENT_QUOTES, 'UTF-8'); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!empty($successMessage)): ?>
                <div class="alert alert-success" style="margin-bottom: 20px;"><?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($role === 'admin' || $role === 'assistant_principal'): ?>
                <div style="display:flex; gap:12px; flex-wrap:wrap; margin-bottom: 24px;">
                    <a href="admin_management.php" class="logout-btn">Müdür Yönetim Paneli</a>
                    <a href="assistant_management.php" class="logout-btn">Müdür Yardımcısı Yönetim Paneli</a>
                </div>
            <?php endif; ?>

            <?php if ($role === 'admin' || $role === 'assistant_principal'): ?>
                <div class="panel-grid">
                    <div class="panel">
                        <div class="panel-header-block">
                            <h3>Sınıf ve Ders Yönetimi</h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Sınıf</th>
                                            <th>Şube</th>
                                            <th>Öğrenci Sayısı</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($classes as $classRow): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($classRow['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($classRow['grade'] ?? 'Seviye', ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= count(array_filter($students, fn($student) => (int) $student['class_id'] === (int) $classRow['id'])); ?></td>
                                                <td><span class="badge green">Aktif</span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header-block">
                            <h3>Rol Dağılımı</h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Rol</th>
                                            <th>Toplam</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr><td>Müdür</td><td>1</td></tr>
                                        <tr><td>Müdür Yardımcısı</td><td>1</td></tr>
                                        <tr><td>Öğretmen</td><td><?= count($teachers); ?></td></tr>
                                        <tr><td>Öğrenci</td><td><?= count($students); ?></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'assistant_principal' || $role === 'admin'): ?>
                <div class="panel-grid">
                    <div class="panel">
                        <div class="panel-header-block">
                            <h3>Öğretmen Sınıf Atama</h3>
                        </div>
                        <div class="panel-body">
                            <form method="post" action="dashboard.php" data-confirm="Öğretmenin sınıf ve ders atamasını kaydetmek istediğinize emin misiniz?">
                                <input type="hidden" name="action" value="assign_teacher">
                                <div class="form-grid">
                                    <div class="field">
                                        <label for="teacher_id">Öğretmen</label>
                                        <select id="teacher_id" name="teacher_id" required>
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($teachers as $teacher): ?>
                                                <option value="<?= (int) $teacher['id']; ?>"><?= htmlspecialchars($teacher['full_name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field">
                                        <label for="class_id">Sınıf</label>
                                        <select id="class_id" name="class_id" required>
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($classes as $classRow): ?>
                                                <option value="<?= (int) $classRow['id']; ?>"><?= htmlspecialchars($classRow['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="field full">
                                        <label for="subject_id">Ders</label>
                                        <select id="subject_id" name="subject_id" required>
                                            <option value="">Seçiniz</option>
                                            <?php foreach ($subjects as $subject): ?>
                                                <option value="<?= (int) $subject['id']; ?>"><?= htmlspecialchars($subject['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="inline-action">
                                    <button type="submit" class="primary-btn" style="width: auto; min-width: 220px;">Atamayı Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header-block">
                            <h3>Öğretmen Atamaları</h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Öğretmen</th>
                                            <th>Sınıf</th>
                                            <th>Ders</th>
                                        </tr>
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
            <?php endif; ?>

            <?php if ($role === 'teacher'): ?>
                <div class="panel">
                    <div class="panel-header-block">
                        <h3>Öğretmen Paneli</h3>
                    </div>
                    <div class="panel-body">
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Sınıf</th>
                                        <th>Ders</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teacherSchedule as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars($item['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($role === 'student'): ?>
                <div class="panel-grid">
                    <div class="panel">
                        <div class="panel-header-block">
                            <h3>Öğrenci Bilgileri</h3>
                        </div>
                        <div class="panel-body">
                            <p><strong>Öğrenci:</strong> <?= htmlspecialchars($user['full_name'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Sınıf:</strong> <?= htmlspecialchars(getClassName($db, (int) $user['class_id']), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p class="muted">Sınıf rehberi ve akademik planlar burada görünür.</p>
                        </div>
                    </div>

                    <div class="panel">
                        <div class="panel-header-block">
                            <h3>Ders Programı</h3>
                        </div>
                        <div class="panel-body">
                            <div class="table-wrap">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Ders</th>
                                            <th>Öğretmen</th>
                                            <th>Sınıf</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($scheduleRows as $row): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['subject_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($row['teacher_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td><?= htmlspecialchars($row['class_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
