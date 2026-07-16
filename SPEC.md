# EduQR Rebuild — Proje Spec Dosyası

> Bu dosya, projeyi hiç görmemiş bir yapay zeka modeline tüm bağlamı tek seferde aktarmak için hazırlanmıştır.

---

## 1. Proje Genel Bakış

**EduQR**, öğretmenlerin QR kod aracılığıyla sınıf içi anket/quiz oturumu yönettiği, öğrencilerin cihazlarıyla katıldığı bir PHP web uygulamasıdır.

- **Dil:** PHP 8.x (strict_types)
- **Veritabanı:** MySQL/MariaDB (PDO)
- **Sunucu:** Laragon (yerel geliştirme), Apache
- **Public URL tabanı:** `/eduqr-rebuild/public`
- **Şablonlama:** Saf PHP (`include` ile)
- **Çerçeve yok** — sıfırdan yazılmış basit MVC

---

## 2. Dizin Yapısı

```
c:\laragon\www\eduqr-rebuild\
├── src/
│   ├── Bootstrap.php          ← TÜM ROUTE'LAR BURADA tanımlanır
│   ├── Config.php             ← .env okuyucu
│   ├── Router.php             ← Basit URL yönlendirici
│   ├── helpers.php            ← Global yardımcı fonksiyonlar (t(), eduqr_path())
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── CourseController.php
│   │   ├── SessionController.php
│   │   ├── QuestionController.php
│   │   ├── QuestionBankController.php
│   │   ├── ReportController.php
│   │   ├── JoinController.php
│   │   └── Api/
│   │       └── PublicQuestionController.php
│   ├── Repositories/
│   │   ├── SessionRepository.php
│   │   ├── CourseRepository.php
│   │   ├── QuestionRepository.php
│   │   ├── AnswerRepository.php
│   │   └── ParticipantRepository.php
│   ├── Services/
│   │   └── AuthService.php    ← AuthService::user() ile oturum kontrolü
│   ├── Middleware/
│   │   └── AuthMiddleware.php ← AuthMiddleware::handle() admin koruması
│   ├── Support/
│   │   └── Database.php       ← Database::connect() → PDO singleton
│   └── I18n/
│       └── I18nService.php    ← t('key') çevirisi
├── templates/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── archive.php
│   │   ├── settings.php
│   │   ├── question_bank.php
│   │   └── sessions/
│   │       ├── detail.php     ← Oturum kontrol paneli (canlı)
│   │       └── report.php     ← Oturum raporu
├── database/
│   └── migrations/            ← Sıralı SQL dosyaları (elle çalıştırılır)
├── public/
│   └── index.php              ← Tek giriş noktası
└── vendor/                    ← Composer bağımlılıkları
```

---

## 3. Veritabanı Şeması

### `users`
| Kolon | Tip | Not |
|-------|-----|-----|
| id | INT PK AUTO_INCREMENT | |
| name | VARCHAR(255) | |
| email | VARCHAR(255) UNIQUE | |
| password_hash | VARCHAR(255) | |
| role | VARCHAR(50) | default: `instructor` |
| created_at / updated_at | TIMESTAMP | |

### `courses`
| Kolon | Tip | Not |
|-------|-----|-----|
| id | INT PK | |
| user_id | INT FK → users | |
| title | VARCHAR(255) | |
| code | VARCHAR(50) | |
| status | VARCHAR(50) | `active` / `archived` |

### `sessions`
| Kolon | Tip | Not |
|-------|-----|-----|
| id | INT PK | |
| course_id | INT FK → courses | |
| title | VARCHAR(255) | |
| short_code | VARCHAR(10) UNIQUE | QR koduna gömülür |
| status | VARCHAR(50) | `active` / `paused` / `closed` |
| show_results_to_students | TINYINT(1) | default: 1 |
| moderation_mode | TINYINT(1) | default: 0 |
| is_anonymized | TINYINT(1) | default: 0 — **YENİ, migration 0010** |
| created_at / updated_at | TIMESTAMP | |

### `questions`
| Kolon | Tip | Not |
|-------|-----|-----|
| id | INT PK | |
| session_id | INT FK → sessions | |
| question_text | TEXT | |
| type | VARCHAR(50) | `multiple_choice` / `open_ended` / `yes_no` / `likert` |
| options | JSON | Çoktan seçmeli şıklar |
| correct_answer | VARCHAR(255) | |
| status | VARCHAR(50) | `draft` / `active` / `closed` |
| sort_order | INT | |

### `participants`
| Kolon | Tip | Not |
|-------|-----|-----|
| id | INT PK | |
| session_id | INT FK → sessions | |
| nickname | VARCHAR(50) | Öğrencinin takma adı |
| device_cookie | VARCHAR(255) | Cihaz tanımlama |
| UNIQUE | (session_id, nickname) | |

### `answers`
| Kolon | Tip | Not |
|-------|-----|-----|
| id | INT PK | |
| question_id | INT FK → questions | |
| participant_id | INT FK → participants | |
| answer_value | TEXT | |
| is_hidden | TINYINT(1) | moderasyon için |
| UNIQUE | (question_id, participant_id) | |

---

## 4. Routing Kuralları (Bootstrap.php)

Tüm route'lar `src/Bootstrap.php` içinde tanımlanır. Route formatı:

```php
$router->get('/eduqr-rebuild/public/YOLU', function (array $params): void {
    \EduQR\Middleware\AuthMiddleware::handle(); // admin koruması gerekiyorsa
    (new \EduQR\Controllers\XyzController())->metodAdı($params);
});

$router->post('/eduqr-rebuild/public/YOLU', function (array $params): void {
    \EduQR\Middleware\AuthMiddleware::handle();
    (new \EduQR\Controllers\XyzController())->metodAdı($params);
});
```

URL parametreleri `{id}`, `{course_id}`, `{session_id}` vb. ile tanımlanır ve `$params` array'i içinde gelir.

### Mevcut Session Route'ları
```
GET  /admin/sessions/{id}                    → SessionController::show()
GET  /admin/sessions/{id}/report             → ReportController::showReport()
GET  /admin/sessions/{id}/report/csv         → ReportController::exportCsv()
POST /admin/sessions/{id}/anonymize          → ReportController::anonymize()
POST /admin/sessions/{id}/delete             → ReportController::delete()
GET  /admin/sessions/{id}/results            → SessionController::results()
GET  /admin/sessions/{id}/participants/count → SessionController::participantsCount()
```

---

## 5. Controller Kalıbı

Tüm controller metodları aynı yapıyı izler:

```php
public function metodAdı(array $params): void
{
    // 1. Auth kontrolü
    $user = AuthService::user();
    if ($user === null) {
        header('Location: ' . eduqr_path('/login'));
        exit;
    }

    // 2. Kayıt bulma
    $sessionId = (int) $params['id'];
    $session = $this->sessionRepo->findById($sessionId);
    if ($session === null) {
        http_response_code(404); exit;
    }

    // 3. Yetki kontrolü (sahiplik)
    $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
    if ($course === null) {
        http_response_code(403); exit;
    }

    // 4. İş mantığı...

    // 5. Yönlendirme veya şablon render
    header('Location: ' . eduqr_path('/admin/dashboard'));
    exit;
    // VEYA:
    include __DIR__ . '/../../templates/admin/sessions/detail.php';
}
```

---

## 6. Repository Kalıbı

```php
final class SomeRepository
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect(); // PDO singleton
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM table WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
```

`SessionRepository` mevcut metodları:
- `findById(int $id): ?array`
- `findByCourseId(int $courseId): array`
- `findByShortCode(string $shortCode): ?array`
- `create(int $courseId, string $title, string $shortCode): int`
- `updateStatus(int $id, string $status): void` ← status'ü günceller

---

## 7. Global Yardımcı Fonksiyonlar

```php
// Çeviri anahtarı → string
t('admin.session.close_session')

// URL oluşturma (base path dahil)
eduqr_path('/admin/sessions/5/report')
// → "/eduqr-rebuild/public/admin/sessions/5/report"
```

---

## 8. Şablon Kalıbı

Şablonlar `include` ile render edilir. Controller'dan değişkenler doğrudan `$session`, `$course`, `$questions` vb. olarak gelir:

```php
// Controller içinde:
include __DIR__ . '/../../templates/admin/sessions/report.php';

// Şablonda erişim:
$session['id'], $session['status'], $session['is_anonymized']
```

Dil:
```php
$locale = \EduQR\I18n\I18nService::getLocale(); // 'tr' veya 'en'
$locale === 'en' ? 'Close Session' : 'Oturumu Kapat'
// veya:
t('admin.session.close_session')
```

---

## 9. Şablonlarda Status Badge Kalıbı (detail.php)

```php
$statusClass = 'badge-active';
$statusText = t('admin.session.status_active');
if ($session['status'] === 'paused') {
    $statusClass = 'badge-paused';
    $statusText = t('admin.session.status_paused');
} elseif ($session['status'] === 'closed') {
    $statusClass = 'badge-closed';
    $statusText = t('admin.session.status_closed');
}
```

---

## 10. Aktif Görev — Uygulanacak Değişiklikler

### Sorun 1: Anonimleştir butonu tekrar görünüyor
- `sessions` tablosuna `is_anonymized TINYINT(1) DEFAULT 0` eklendi (migration `0010_add_is_anonymized_to_sessions.sql` hazır, phpMyAdmin'den çalıştırılacak)
- `anonymize()` metodu katılımcıları güncelledikten sonra `is_anonymized = 1` yapmalı
- `report.php` şablonunda buton `<?php if (!$session['is_anonymized']): ?>` koşuluyla sarılmalı

### Sorun 2: Oturumu Kapat status güncellenmiyor
- `confirmCloseSession()` JS fonksiyonu şu an sadece `window.location.href = /admin/dashboard` yapıyor — DB güncellemiyor
- `POST /admin/sessions/{id}/close` route'u YOK → eklenecek
- `SessionController::close()` metodu eklenecek (DB'de `status = 'closed'` yapacak)
- `detail.php`'deki buton hidden form + POST yapısına çevrilecek
- Oturum `closed` ise buton gizlenecek

---

### Yapılacaklar (Sırayla)

#### Adım 1 — Migration (phpMyAdmin'den manuel çalıştır)
```sql
-- Dosya: database/migrations/0010_add_is_anonymized_to_sessions.sql
ALTER TABLE sessions ADD COLUMN IF NOT EXISTS is_anonymized TINYINT(1) DEFAULT 0;
```

#### Adım 2 — SessionRepository.php'ye metod ekle
Dosya: `src/Repositories/SessionRepository.php`

Mevcut `updateStatus()` metodunun altına ekle:
```php
public function setAnonymized(int $id): void
{
    $stmt = $this->db->prepare("UPDATE sessions SET is_anonymized = 1 WHERE id = :id");
    $stmt->execute(['id' => $id]);
}
```

#### Adım 3 — ReportController.php → anonymize() metodunu güncelle
Dosya: `src/Controllers/ReportController.php`

`foreach ($participants ...)` döngüsünden SONRA, `header('Location:...')` satırından ÖNCE şunu ekle:
```php
$this->sessionRepo->setAnonymized($sessionId);
```

Redirect'i güncelle (query parametresi kaldır):
```php
// ESKİ:
header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId . '/report?anonymized=1'));
// YENİ:
header('Location: ' . eduqr_path('/admin/sessions/' . $sessionId . '/report'));
```

#### Adım 4 — SessionController.php'ye close() metodu ekle
Dosya: `src/Controllers/SessionController.php`

`results()` metodunun ALTINA ekle:
```php
public function close(array $params): void
{
    $user = AuthService::user();
    if ($user === null) {
        http_response_code(403);
        exit;
    }

    $sessionId = (int) $params['id'];
    $session = $this->sessionRepo->findById($sessionId);
    if ($session === null) {
        http_response_code(404);
        exit;
    }

    $course = $this->courseRepo->findByIdAndUserId((int)$session['course_id'], $user['id']);
    if ($course === null) {
        http_response_code(403);
        exit;
    }

    $this->sessionRepo->updateStatus($sessionId, 'closed');

    header('Location: ' . eduqr_path('/admin/dashboard'));
    exit;
}
```

#### Adım 5 — Bootstrap.php'ye route ekle
Dosya: `src/Bootstrap.php`

`POST /admin/sessions/{id}/anonymize` route'unun (satır ~142) ALTINA ekle:
```php
$router->post('/eduqr-rebuild/public/admin/sessions/{id}/close', function (array $p): void {
    \EduQR\Middleware\AuthMiddleware::handle();
    (new \EduQR\Controllers\SessionController())->close($p);
});
```

#### Adım 6 — detail.php → Oturumu Kapat butonunu düzelt
Dosya: `templates/admin/sessions/detail.php`

**Mevcut kod (satır ~467):**
```html
<button class="btn btn-logout ms-md-auto" onclick="confirmCloseSession(event)"><?= htmlspecialchars(t('admin.session.close_session')) ?></button>
```

**Değiştir:**
```php
<?php if ($session['status'] !== 'closed'): ?>
    <form id="close-session-form"
          action="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/close') ?>"
          method="POST"
          style="display:none;">
    </form>
    <button class="btn btn-logout ms-md-auto" onclick="confirmCloseSession(event)">
        <?= htmlspecialchars(t('admin.session.close_session')) ?>
    </button>
<?php endif; ?>
```

**JS fonksiyonunu güncelle (satır ~809):**
```javascript
// ESKİ:
function confirmCloseSession(e) {
    if (!confirm(translationCloseWarning)) {
        e.preventDefault();
        return false;
    }
    window.location.href = <?= json_encode(eduqr_path('/admin/dashboard')) ?>;
}

// YENİ:
function confirmCloseSession(e) {
    e.preventDefault();
    if (!confirm(translationCloseWarning)) {
        return false;
    }
    document.getElementById('close-session-form').submit();
}
```

#### Adım 7 — report.php → Anonimleştir butonunu koşula al
Dosya: `templates/admin/sessions/report.php`

**Mevcut kod (satır ~484-489):**
```html
<!-- Anonymize Session Form -->
<form action="..." method="POST" onsubmit="...">
    <button type="submit" class="btn-anonymize-session">
        Oturumu Anonimleştir
    </button>
</form>
```

**Tüm `<form>...</form>` bloğunu (satır 485-489) şuna sar:**
```php
<?php if (!$session['is_anonymized']): ?>
    <form action="<?= eduqr_path('/admin/sessions/' . (int)$session['id'] . '/anonymize') ?>" method="POST" onsubmit="return confirm('<?= $locale === 'en' ? 'Are you sure you want to permanently anonymize this session\'s participants?' : 'Bu oturumdaki tüm katılımcı isimlerini kalıcı olarak anonimleştirmek istediğinize emin misiniz?' ?>');">
        <button type="submit" class="btn-anonymize-session">
            <?= $locale === 'en' ? 'Anonymize Session' : 'Oturumu Anonimleştir' ?>
        </button>
    </form>
<?php endif; ?>
```

---

## 11. Önemli Notlar & Kurallar

- **`exit` zorunlu:** Her controller metodu sonunda `exit` çağrılmalı
- **`eduqr_path()` kullan:** URL oluştururken doğrudan string birleştirme yapma
- **`(int)` cast:** Kullanıcıdan gelen id'leri daima `(int)` ile cast et
- **`htmlspecialchars()`:** Şablonda tüm kullanıcı verisini escape et
- **Yetki akışı:** Her metod önce `AuthService::user()`, sonra `courseRepo->findByIdAndUserId()` ile sahiplik doğrular
- **Migration'lar elle çalıştırılır** — Otomatik migration sistemi yoktur, phpMyAdmin kullanılır
- **Namespace:** `EduQR\Controllers`, `EduQR\Repositories`, `EduQR\Services`
