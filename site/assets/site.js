const demoAccounts = {
    mudur: {
        password: '123456',
        name: 'Ahmet Yılmaz',
        role: 'Müdür',
        initials: 'AY',
        metrics: [['03', 'Sınıf'], ['02', 'Öğretmen'], ['02', 'Öğrenci'], ['06', 'Ders']],
        heading: 'Okul genel görünümü',
        rows: [
            ['9-A', '9. Sınıf', 'Zeynep Arslan', 'Matematik · Kimya'],
            ['10-B', '10. Sınıf', 'Taha Korkmaz', 'Fizik'],
            ['11-C', '11. Sınıf', 'Atama bekliyor', 'Henüz ders yok']
        ]
    },
    mudur_yardimci: {
        password: '123456',
        name: 'Elif Demir',
        role: 'Müdür Yardımcısı',
        initials: 'ED',
        metrics: [['03', 'Sınıf'], ['02', 'Öğretmen'], ['06', 'Ders'], ['03', 'Atama']],
        heading: 'Öğretmen ders atamaları',
        rows: [
            ['Zeynep Arslan', '9-A', 'Matematik', 'Aktif'],
            ['Zeynep Arslan', '9-A', 'Kimya', 'Aktif'],
            ['Taha Korkmaz', '10-B', 'Fizik', 'Aktif']
        ]
    },
    zeynep_ogretmen: {
        password: '123456',
        name: 'Zeynep Arslan',
        role: 'Öğretmen',
        initials: 'ZA',
        metrics: [['02', 'Ders'], ['01', 'Sınıf'], ['02', 'Branş'], ['9-A', 'Danışmanlık']],
        heading: 'Ders programım',
        rows: [
            ['9-A', 'Matematik', '09:00 – 09:40', 'Pazartesi'],
            ['9-A', 'Kimya', '10:00 – 10:40', 'Salı']
        ]
    },
    ayse_ogrenci: {
        password: '123456',
        name: 'Ayşe Yıldız',
        role: 'Öğrenci',
        initials: 'AY',
        metrics: [['9-A', 'Sınıfım'], ['02', 'Ders'], ['02', 'Öğretmen'], ['2026', 'Eğitim yılı']],
        heading: '9-A ders programı',
        rows: [
            ['Matematik', 'Zeynep Arslan', '09:00 – 09:40', 'Pazartesi'],
            ['Kimya', 'Zeynep Arslan', '10:00 – 10:40', 'Salı']
        ]
    }
};

const loginView = document.querySelector('#login-view');
const dashboardView = document.querySelector('#dashboard-view');
const loginForm = document.querySelector('#login-form');
const usernameInput = document.querySelector('#username');
const passwordInput = document.querySelector('#password');
const formError = document.querySelector('#form-error');

function renderDashboard(account) {
    const rows = account.rows.map((row) => `<tr>${row.map((cell) => `<td>${cell}</td>`).join('')}</tr>`).join('');
    const metrics = account.metrics.map(([value, label], index) => `
        <article class="metric metric-${index + 1}"><span>${label}</span><strong>${value}</strong></article>
    `).join('');

    dashboardView.innerHTML = `
        <header class="dashboard-topbar">
            <a class="brand" href="#top"><span class="brand-mark">L</span><span>Lise Otomasyon</span></a>
            <div class="profile"><span class="profile-avatar">${account.initials}</span><span><strong>${account.name}</strong><small>${account.role}</small></span><button class="logout-button" type="button" id="logout-button">Çıkış</button></div>
        </header>
        <div class="dashboard-content" id="top">
            <div class="welcome-line"><div><p class="eyebrow">OKUL YÖNETİMİ / GENEL BAKIŞ</p><h1>Merhaba, ${account.name.split(' ')[0]}.</h1></div><span class="today-label">2026 · DEMO ORTAMI</span></div>
            <div class="metrics-grid">${metrics}</div>
            <section class="data-panel"><div class="data-heading"><div><p class="panel-index">ROL: ${account.role.toLocaleUpperCase('tr-TR')}</p><h2>${account.heading}</h2></div><span class="demo-tag">STATİK VERİ</span></div>
                <div class="table-scroll"><table><tbody>${rows}</tbody></table></div>
            </section>
            <p class="dashboard-notice">Bu panel yalnızca arayüz demosudur. Değişiklikler kalıcı değildir ve gerçek öğrenci verisi kullanılmaz.</p>
        </div>
        <footer class="page-footer dashboard-footer"><span>© 2026 Lise Otomasyon</span><button class="text-button" type="button" id="back-button">Demo hesaplarına dön</button></footer>
    `;

    loginView.hidden = true;
    dashboardView.hidden = false;
    document.querySelector('#logout-button').addEventListener('click', showLogin);
    document.querySelector('#back-button').addEventListener('click', showLogin);
}

function showLogin() {
    dashboardView.hidden = true;
    loginView.hidden = false;
    passwordInput.value = '';
    formError.hidden = true;
}

function login() {
    const account = demoAccounts[usernameInput.value.trim()];

    if (!account || account.password !== passwordInput.value) {
        formError.hidden = false;
        passwordInput.focus();
        return;
    }

    formError.hidden = true;
    renderDashboard(account);
}

loginForm.addEventListener('submit', (event) => {
    event.preventDefault();
    login();
});

document.querySelectorAll('[data-username]').forEach((button) => {
    button.addEventListener('click', () => {
        usernameInput.value = button.dataset.username;
        passwordInput.value = '123456';
        login();
    });
});