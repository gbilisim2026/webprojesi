CREATE DATABASE IF NOT EXISTS lise_otomasyon CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE lise_otomasyon;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    grade VARCHAR(20) DEFAULT NULL,
    advisor_teacher_id INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS subjects (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    role_id INT NOT NULL,
    class_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id),
    FOREIGN KEY (class_id) REFERENCES classes(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS teacher_class_subject (
    id INT AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT NOT NULL,
    class_id INT NOT NULL,
    subject_id INT NOT NULL,
    UNIQUE KEY uniq_teacher_class_subject (teacher_id, class_id, subject_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id),
    FOREIGN KEY (class_id) REFERENCES classes(id),
    FOREIGN KEY (subject_id) REFERENCES subjects(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO roles (name, label)
VALUES
    ('admin', 'Müdür'),
    ('assistant_principal', 'Müdür Yardımcısı'),
    ('teacher', 'Öğretmen'),
    ('student', 'Öğrenci')
ON DUPLICATE KEY UPDATE label = VALUES(label);

INSERT INTO classes (name, grade, advisor_teacher_id)
VALUES
    ('9-A', '9. Sınıf', NULL),
    ('10-B', '10. Sınıf', NULL),
    ('11-C', '11. Sınıf', NULL)
ON DUPLICATE KEY UPDATE grade = VALUES(grade);

INSERT INTO subjects (name)
VALUES
    ('Matematik'),
    ('Fizik'),
    ('Kimya'),
    ('Edebiyat'),
    ('Biyoloji'),
    ('Tarih')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (username, password, full_name, role_id, class_id)
VALUES
    ('mudur', '$2y$10$LzDuVQ2IcuTejjdlh2yUeu1jIAC/c4Q.hywFd4sBXQMdLBKFJhpVC', 'Ahmet Yılmaz', (SELECT id FROM roles WHERE name = 'admin'), NULL),
    ('mudur_yardimci', '$2y$10$LzDuVQ2IcuTejjdlh2yUeu1jIAC/c4Q.hywFd4sBXQMdLBKFJhpVC', 'Elif Demir', (SELECT id FROM roles WHERE name = 'assistant_principal'), NULL),
    ('zeynep_ogretmen', '$2y$10$LzDuVQ2IcuTejjdlh2yUeu1jIAC/c4Q.hywFd4sBXQMdLBKFJhpVC', 'Zeynep Arslan', (SELECT id FROM roles WHERE name = 'teacher'), NULL),
    ('taha_ogretmen', '$2y$10$LzDuVQ2IcuTejjdlh2yUeu1jIAC/c4Q.hywFd4sBXQMdLBKFJhpVC', 'Taha Korkmaz', (SELECT id FROM roles WHERE name = 'teacher'), NULL),
    ('ayse_ogrenci', '$2y$10$LzDuVQ2IcuTejjdlh2yUeu1jIAC/c4Q.hywFd4sBXQMdLBKFJhpVC', 'Ayşe Yıldız', (SELECT id FROM roles WHERE name = 'student'), (SELECT id FROM classes WHERE name = '9-A')),
    ('mehmet_ogrenci', '$2y$10$LzDuVQ2IcuTejjdlh2yUeu1jIAC/c4Q.hywFd4sBXQMdLBKFJhpVC', 'Mehmet Koç', (SELECT id FROM roles WHERE name = 'student'), (SELECT id FROM classes WHERE name = '9-A'))
ON DUPLICATE KEY UPDATE password = VALUES(password), full_name = VALUES(full_name), class_id = VALUES(class_id);

INSERT INTO teacher_class_subject (teacher_id, class_id, subject_id)
VALUES
    ((SELECT id FROM users WHERE username = 'zeynep_ogretmen'), (SELECT id FROM classes WHERE name = '9-A'), (SELECT id FROM subjects WHERE name = 'Matematik')),
    ((SELECT id FROM users WHERE username = 'taha_ogretmen'), (SELECT id FROM classes WHERE name = '10-B'), (SELECT id FROM subjects WHERE name = 'Fizik')),
    ((SELECT id FROM users WHERE username = 'zeynep_ogretmen'), (SELECT id FROM classes WHERE name = '9-A'), (SELECT id FROM subjects WHERE name = 'Kimya'))
ON DUPLICATE KEY UPDATE subject_id = VALUES(subject_id);
