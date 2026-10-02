# Lise Otomasyon Sistemi

Bu proje, PHP + MySQL tabanlı modern bir lise otomasyon sistemidir. Sistem içinde müdür, müdür yardımcıları, öğretmenler ve öğrenciler için ayrı paneller bulunmaktadır. Müdür tüm yetkilere sahiptir, müdür yardımcıları öğretmenleri sınıflara atayabilir ve öğrenciler sınıf bazlı ders programlarına erişebilir.

## Özellikler

- Kullanıcı girişi ve çoklu rol yönetimi
- Müdür paneli: tüm yetkiler, sınıf ve öğretmen görünümü
- Müdür yardımcıları: öğretmen atama ekranı
- Öğretmen paneli: sınıf ve ders listesi
- Öğrenci paneli: sınıf bilgisi ve ders programı
- MySQL veritabanı yapısı
- Modern ve mobil uyumlu UI/UX tasarım

## Kurulum

1. MySQL sunucusunu çalıştırın.
2. Proje klasöründe `database/init_db.php` dosyasını çalıştırın:

   `php database/init_db.php`

3. `config/db.php` dosyasındaki veritabanı bilgilerini kendi ortamınıza göre güncelleyin.
4. Projeyi çalıştırın:

   `php -S 127.0.0.1:8000`

5. Tarayıcıda `http://127.0.0.1:8000` adresine gidin.

## Varsayılan Hesaplar

- Müdür: `mudur` / `123456`
- Müdür Yardımcısı: `mudur_yardimci` / `123456`
- Öğretmen: `zeynep_ogretmen` / `123456`
- Öğrenci: `ayse_ogrenci` / `123456`

## Dosya Yapısı

- `index.php`: giriş ekranı
- `dashboard.php`: rol bazlı kullanıcı panelleri
- `logout.php`: çıkış işlemi
- `config/db.php`: veritabanı bağlantısı
- `includes/auth.php`: oturum ve yetki kontrolü
- `includes/functions.php`: yardımcı fonksiyonlar
- `database/schema.sql`: veritabanı şeması ve örnek veriler
- `database/init_db.php`: veritabanı kurulum betiği
- `assets/css/style.css`: modern arayüz stil dosyası
- `assets/js/app.js`: küçük arayüz davranışları

## Not

Bu örnek sistem, çalıştırılabilir bir eğitim amaçlı otomasyon arayüzüdür. Gerçek üretim ortamında kullanıcı izinleri, öğrenci notları, devamsızlık takibi ve daha fazla akademik modül eklenebilir.
