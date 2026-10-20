# Accounts Receivable — Çalışma Rehberi

> Projeyi tek dosyada anlatan rehber. Durum: 2026-10-20. Canlı kod `production` dalında, çalışma dalı `mobile-api` (bkz. §12).
> Ayrıntılı dokümanlar: `docs/PROJECT_OVERVIEW.md` (panel), `docs/mobile-api/MOBILE_API.md` (API + canlı ortam).
> `docs/` altındaki eski dosyaların bir kısmı güncel değil (MSSQL, 3 rol vb. anlatır) — kod esas alınır.

---

## 0) Önce bunu oku: altın kurallar

**Bu proje canlıda.** Gerçek para (tahsilat) işleniyor, her gece yönetime rapor gidiyor.

1. Para, yetki, şube veya senkron koduna dokunmadan önce etki analizi yap ve **kullanıcıdan onay al**.
2. **Deploy, sunucu veya nginx değişikliği her seferinde ayrı onay ister.** Commit/push da yalnızca istenince.
3. `credit_payments` satırlarının tutarları **asla güncellenmez**. Düzeltme = eskiyi void + yeni kayıt.
4. Ödeme / iptal / düzeltme akışlarında `DB::transaction` + `lockForUpdate` korunur.
5. Para mantığı **tek yerde**: `src/App/Services/Payments/PaymentService.php`. Web ve API ikisi de bunu çağırır; kopyalama.
6. `BranchScope`'u atlama. Gerekirse açıkça `withoutGlobalScope(BranchScope::class)` yaz ve nedenini belirt.
7. Migration'lar yalnızca ekleme yapsın; geri alma yerine "forward fix".
8. Değişiklikten sonra `php artisan test` çalıştır (175 test, hepsi geçmeli).
9. `.DS_Store` dosyalarını commit'e katma.
10. Mobil uygulama **SMS göndermez** (yalnızca önizleme + Excel). Gönderimi API'ye geri ekleme.

---

## 1) Proje ne yapar?

Türkmenistan'da taksitli kredi alacaklarının **takip ve tahsilat paneli** (para birimi TMT).
Arayüz adı (tk): *Algylary Dolandyrmak*.

| Kim | Ne yapar |
|---|---|
| Kasiyer (Cashier) | Müşteri arar, ödeme alır (nakit / kart / karışık / telefon) |
| Admin | Ödemeyi iptal eder (void) veya düzeltir (correct), kullanıcı yönetir |
| Analist (Analyst) | Raporları görür, Excel'e aktarır |
| Operatör (Operator) | Müşterilere toplu SMS hazırlar/gönderir, ödeme de alabilir |
| Manager | Müşteri listesi ve ekstre |
| SuperAdmin | Her şey + komut merkezi, loglar |

- Her gece 23:00'te **günlük tahsilat raporu** e-postası gider.
- Kredi verisi başka bir sistemden (kaynak PostgreSQL) **15 dakikada bir senkronlanır**.
- Aynı kurallarla çalışan bir **mobil uygulama** (Flutter) var; `/api/v1` üzerinden konuşur.
- Diller: `tk` (varsayılan), `ru`, `en`, `tr` (`resources/lang/<dil>/`).

---

## 2) Teknoloji

| Konu | Değer |
|---|---|
| Framework | Laravel 10, PHP ^8.4 |
| Ana DB | PostgreSQL, bağlantı `pgsql` |
| Kaynak DB | İkinci PostgreSQL, bağlantı `second_pgsql` (**MSSQL değil**; `config/sync.php`'deki `mssql` anahtarları eski kalıntı) |
| Mobil kimlik | Laravel Sanctum (kişisel erişim token'ı) |
| Web kimlik | Kendi sistemi: `users.token` + `auth_token` çerezi |
| Excel | `maatwebsite/excel`, `openspout/openspout` |
| Canlıda cache / session / queue | Redis |
| Tema | Velzon admin teması (Blade); `resources/js/pages/*` çoğunlukla kullanılmayan tema dosyaları |
| **Autoload** | `App\` → **`src/App/`** (standart `app/` klasörü yok!) |

---

## 3) Klasör haritası

```
src/App/
├── Console/Commands/        sync:run, sync:credits, sync:avshocrecat, report:daily-payments,
│                            credits:init-local, credits:resync-amount-local, clear:all, logs:clear
├── Console/Kernel.php       zamanlama (canlıda schedule:run YOK, cron doğrudan çağırır — bkz. §11)
├── Enums/UserRoleEnum.php   7 rol + çok dilli etiket
├── Exports/                 her rapor için bir Excel sınıfı
├── Http/Controllers/        panel controller'ları (Auth, Customers, Payments, Reports, Sms, Users, Logs, Admin/Settings)
├── Http/Controllers/Api/V1/ mobil API controller'ları
├── Http/Middleware/         AuthToken, RoleMiddleware, SetLocale, SetApiLocale, ThrottleApiByAddress,
│                            ApiSecurityHeaders, LimitApiBodySize, OneExportAtATime, EnsureUserIsActive, TrustProxies
├── Http/Resources/          API JSON kaynakları (Credit, Payment, CustomerRow, ScheduleRow, User, ManagedUser)
├── Jobs/                    SyncCreditsJob, SyncAvshocrecatReportJob  (*copy.php dosyaları kullanılmıyor)
├── Mail/                    DailyPaymentReportMail
├── Models/                  Credit, CreditPayment, User, AvshocrecatReport, ActivityLog, LoginHistory, SecurityAlert
├── Models/Scopes/           BranchScope
├── Services/                Payments/, Auth/, Profile/, Dashboard/, Customers/, Reports/, Sms/, Credits/, AuditLogger
└── Support/                 Access, Panel, ViewData, ApiResponse, ClientInfo
routes/web.php               panel rotaları + rol grupları
routes/api.php               /api/v1 rotaları
resources/views/pages/       panel sayfaları (blade)
resources/views/emails/      günlük rapor e-postası
tests/Feature/Web/           panel davranışını sabitleyen testler
tests/Feature/Api/V1/        API testleri
docs/                        dokümanlar
```

---

## 4) Veri modeli (en önemli tablolar)

### `credits` — müşteri kredisi (senkronla gelir)
- Birincil anahtar: `source_id` (kaynak tablodaki `id`). `logicalref` ikinci kimlik.
- Kimlik alanları: `name`, `phone`, `passport`, `contract`, `clientref`, `branch`, `date_` (kredi tarihi).
- **Kaynak para alanları:** `amount`, `paid` → yalnızca referans, iş mantığında kullanılmaz.
- **Uygulamanın gerçeği:** `amount_local`, `paid_local` (+ `*_note`, `*_updated_by`, `*_updated_at`).
- Diğer: `active`, `is_blocked`, `willpaiddate`, `willpaidamount`, `status`, `manager`, `rv_bigint`...
- Hesaplanan alanlar (`Credit` modeli):
  - `local_remaining = max(amount_local - paid_local, 0)`, 2 hane.
  - `local_closed = paid_local >= amount_local - 0.01`; local alanlardan biri `null` ise **kapalı sayılır**.
  - `sms_*` accessor'ları: 6 eşit aylık taksit varsayımıyla gecikme hesabı.

### `credit_payments` — her ödeme (değişmez kayıt)
- `credit_source_id` → `credits.source_id`.
- Müşteri **snapshot**'ı: `customer_name/phone/passport/contract`, `branch`.
- Kasiyer snapshot'ı: `created_by`, `created_by_name/email/phone`.
- Tutarlar: `pay_amount` (alınan), `change_amount` (para üstü), `cash_amount`, `card_amount`, `phone_amount`, `method`, `receiver_phone_number`.
- `old_amount_local` / `new_amount_local` → **isimlerine rağmen kalan borcu** (önce/sonra) tutar.
- `old_paid_local` / `new_paid_local`.
- İptal: `voided_at`, `voided_by`, `void_reason`.
- Düzeltme: `corrected_by`, `corrected_at`, `correct_reason`, `corrected_from_payment_id`.
- `client_request_id` (unique) → mobil çift ödeme koruması (Idempotency-Key).
- Bir ödemenin krediye **uygulanan** kısmı = `pay_amount - change_amount`.

### Diğerleri
| Tablo | İçerik |
|---|---|
| `users` | `firstname`, `lastname`, `email`, `phonenumber`, `role`, `branches` (JSON dizi), `daily_report`, `status`, `token`, `token_expires_at` |
| `avshocrecat_report` | Kaynak fonksiyondan gelen aylık taksit planı snapshot'ı (`sertnama_nomeri = credits.contract`) |
| `sync_state` | Senkronun son `rv` değeri (`{APP_ENV}_credits_last_rv_hex`) |
| `activity_logs`, `login_histories`, `security_alerts` | Denetim kayıtları; `client` sütunu: `web` / `mobile` / `system` |
| `personal_access_tokens` | Sanctum (mobil) token'ları |
| `jobs`, `failed_jobs` | Kuyruk |

---

## 5) Para mantığı (en kritik kısım) — `PaymentService`

### Ödeme alma — `create()`
1. `method`: `cash | card | mixed | phone`. `phone` için alıcı numarası zorunlu, yalnızca rakam.
2. `received` (`pay_amount`) > 0 olmalı. `mixed` ise `cash + card = received` (±0.01), ikisi de negatif olamaz.
3. `payment_at` opsiyonel; **ileri tarih yasak**; geçmiş tarih → **backdated** işaretlenir.
4. Transaction içinde kredi `active=true` ve `lockForUpdate` ile kilitlenir.
5. `Idempotency-Key` daha önce kullanıldıysa yeni ödeme açılmaz, ilk kayıt döner (başka müşteri/kullanıcı ise hata).
6. Local alanlar boş → red. Kredi kapalı (`paid >= amount - 0.01`) → red.
7. **Fazla ödeme sınırı:** `received <= remaining + 100` (`MAX_EXTRA`).
8. `apply = min(received, remaining)`, `change = received - apply`.
9. `credits.paid_local += apply`; `paid_note`'a ayrıntılı audit satırı (`backdated=… | method=… | …`).
10. `credit_payments` kaydı (snapshot'larla) oluşturulur.
11. Audit log; backdated → `high` alarm; `received >= 5000` (`HIGH_AMOUNT`) → `medium` alarm.

### İptal — `void()` (yalnızca SuperAdmin, Admin)
- Sebep zorunlu. Ödeme + kredi kilitlenir. Zaten iptal edilmişse red.
- `paid_local -= (pay_amount - change_amount)` (negatife düşmez).
- Ödeme silinmez: `voided_at`, `voided_by`, `void_reason` yazılır. `critical` log + alarm.

### Düzeltme — `correct()` (yalnızca SuperAdmin, Admin)
- Tek transaction: eski ödeme void → aynı kurallarla yeni ödeme.
- Yeni kayıtta `created_by` **orijinal kasiyer** kalır; düzelten `corrected_by`, kaynak `corrected_from_payment_id`.
- Void sonrası borç kapalıysa düzeltme reddedilir ("sadece void kullan").

### Bilinmesi gerekenler
- Hata durumları `PaymentException(code, message)` ile döner (`code` API'de `error_code` olur).
- Tutar okuma/biçimlendirme: `Money::parse()` ("1 234,50" gibi girişleri okur), `Money::format()`.
- `CreditPayment` değişince dashboard cache versiyonu (`admin_dashboard:v`) artar.
- Pasif (`active=false`) kredideki ödeme iptal/düzeltilemez (bilinen kısıt).

---

## 6) Senkronizasyon (tek yön: `second_pgsql` → `pgsql`)

`sync:run` → 14 dk'lık cache lock alır, iki job'ı kuyruğa atar. **Queue worker çalışmıyorsa senkron olmaz.**

### `SyncCreditsJob` (artımlı)
- Kaynak `credits` tablosundan `rv > son_rv` satırları `rv` sırasıyla chunk'larla okur, `source_id` ile upsert eder.
- **Local alan kuralı:** yeni kayıtta `amount_local = amount`, `paid_local = paid`. Mevcut kayıtta local alanlar
  **korunur** (sadece `null` ve hiç elle dokunulmamışsa doldurulur). Yani uygulamada alınan ödemeler senkronla ezilmez.
- Kaynak sütun adları farklı: `name_`, `passport_`, `contract_`, `MANAGER`.

### `SyncAvshocrecatReportJob` (snapshot)
- `SELECT * FROM public.avshonewcrecat(?)` → hedef tabloyu **truncate** edip yeniden doldurur.
- Truncate–insert arası tablo kısa süre boş görünür (atomik değil).

---

## 7) Kimlik doğrulama, roller, şube

### Web paneli
- E-posta **veya** telefon + şifre (`LoginService::attempt`).
- Başarılı girişte `users.token` (64 hex, düz metin) üretilir, 1 gün geçerli, `auth_token` çerezine yazılır (httpOnly, `secure=false`).
- `AuthToken` middleware çerezi doğrular; kullanıcı pasifse çıkış yaptırır.

### Mobil API
- Sanctum token, 7 gün (`SANCTUM_TOKEN_EXPIRATION`), kullanıcı başına en fazla 5 cihaz.
- Mobil giriş web oturumunu **düşürmez** (ayrı sistem).
- Şifre değişince / hesap pasifleşince / silinince token'lar iptal (`User::booted`).

### Roller ve yetkiler
Yetki haritası iki yerde **aynı tutulmalı**: `routes/web.php` (rol grupları) ve `src/App/Support/Access.php` (API).

| Yetki (`Access`) | Roller |
|---|---|
| Dashboard, profil | Giriş yapmış herkes |
| `customers.info`, `payments.take` | SuperAdmin, Admin, Cashier, Operator |
| `reports.avshocrecat` | SuperAdmin, Admin, Cashier, Analyst, Operator |
| `customers.list` (+ export, ekstre) | SuperAdmin, Admin, Manager, Analyst, Operator |
| `payments.void`, `payments.correct` | SuperAdmin, Admin |
| `reports` (`/reports/*`) | SuperAdmin, Admin, Analyst |
| `sms` | SuperAdmin, Admin, Operator |
| `users` | SuperAdmin, Admin (Admin SuperAdmin'i göremez/oluşturamaz) |
| `logs`, `commands` | SuperAdmin |

`User` rolü route'larda hiçbir gruba dahil değil (yalnızca dashboard/profil).

### Şube kısıtlaması — `BranchScope`
- `Credit` ve `CreditPayment` üzerinde global scope; `users.branches` doluysa `branch IN (...)`.
- Boş liste = tüm şubeler. **SuperAdmin her zaman kısıtsız.**
- Giriş yapmış kullanıcı yoksa (CLI, job, gece raporu) filtre uygulanmaz.
- Şubeye bağlı cache anahtarları `branchCacheKey()` ile ayrılır.
- `Http/Kernel::$middlewarePriority`: kullanıcı belli olmadan URL'deki model yüklenmez (şube sızıntısını önler).

---

## 8) Ekranlar / modüller

| Modül | Web rotası | Ana dosyalar |
|---|---|---|
| Dashboard (kasiyer / yönetici) | `/` | `DashboardService` |
| Müşteri arama + ödeme | `/customers/info`, `/payments` | `CustomersInfoController`, `CustomersPaymentController` |
| Müşteri listesi + Excel | `/customers`, `/customers/export` | `CustomerListService` (liste ve export **aynı sorgu**) |
| Ödeme dökümü (ekstre) | `/payments/customer/{credit}/statement` | `CustomerPaymentStatementController` |
| Avshocrecat raporu | `/report` | `AvshocrecatReportController` |
| Raporlar | `/reports/*` | `Controllers/Reports/*`, `Exports/*`, `Services/Reports/*` |
| SMS | `/sms` | `CustomerSmsController`, `Services/Sms/*` |
| Kullanıcılar | `/users` | `Users/UserController` |
| Loglar | `/logs` | `LogsController` |
| Komut merkezi | `/commands` | `CommandCenterController` |

**Müşteri listesi hızlı filtreleri:** `all, paid_today, paid_yesterday, paid_7d … paid_12m, due_today, due_3d, due_7d,
has_debt, no_debt, no_payment, blocked, deleted, active, bermejek, paid_mismatch`.
Vade tanımı: kredi tarihinden itibaren 6 eşit aylık taksit; ödenenin kapatmadığı ilk taksitin tarihi "vade"dir.

**Raporlar (11):** ödeme takvimi (+detay), geciken ödemeler, tahsilat performansı (+detay), günlük kasa kapanışı,
tahsilat trendi, ödeme sözü, geri kazanım etkinliği, müşteri ekstresi, iptal/düzeltme denetimi,
bakiye tutarlılığı, avshocrecat. Çoğunun Excel export'u var.

**SMS:** filtrele → önizle → (yalnızca panelde) gönder. Listede yalnızca `SMS_CUSTOMERS_FROM` (2024-08-01) ve
sonrası krediler. Gönderim `SMS_API_BASE_URL` / `SMS_API_TOKEN`.

**Günlük e-posta** (`report:daily-payments`, `DailyPaymentReportService`):
- Alıcılar: `DAILY_REPORT_EMAILS` (tüm şubeler) + `daily_report=true` aktif kullanıcılar (kendi şubeleri,
  `dailyReportBranches()` — burada SuperAdmin de şubesine göre filtrelenir).
- Seçenekler: `--date=Y-m-d`, `--to=adres`, `--locale=`, `--dry-run`.

---

## 9) Mobil API (`/api/v1`)

**İlke: mobil kuralları kopyalamaz, aynı kodu çalıştırır.**

- Para, giriş, profil, dashboard, müşteri listesi → ortak servisler (`src/App/Services/*`).
- Raporlar, SMS, kullanıcılar, loglar, komutlar → panelin kendi controller'ı çağrılır:
  `Panel::call()` + `ViewData::toArray()`. Bu yüzden bu uçlarda **JSON alan adları panelin view değişken adlarıdır**
  ve panelde rapor değişince API de değişir.
- Cevap zarfı (`ApiResponse`): `{success, message, data}` veya `{success, message, error_code, errors}`.
- `GET auth/me` kullanıcının yetki haritasını (`Access::for`) döner; mobil menü buna göre kurulur.
- Dil: `Accept-Language` (`tk`, `ru`, `en`, `tr`).
- Hız sınırları `RouteServiceProvider`'da (giriş 5/dk hesap, 20/dk IP; export 6/dk + aynı anda tek; yazma 40/dk ...).
- Gövde 512 KB üstü → 413. Hata mesajlarında SQL/yol sızmaz.

Uçların tam listesi: `docs/mobile-api/MOBILE_API.md` §3. Mobil uygulama ayrı depoda:
`/Users/wepx/Projects/accounts_receivable_mobile` (Flutter, Clean Architecture, Riverpod; dokümanı `docs/PROJECT.md`).

**Yeni bir panel özelliği eklerken:** mobil ile tam eşlik (parity) beklenir — aynı alanlar, aynı kurallar.
API kaynağını ilgili Blade view ile karşılaştır; yetkiyi hem `routes/web.php` hem `Access` içine ekle.

---

## 10) Denetim (audit)

Tümü `AuditLogger` (`log()` ve `alert()`) üzerinden yazılır; kaynak/IP/cihaz `ClientInfo`'dan gelir.

| Tablo | İçerik |
|---|---|
| `activity_logs` | Kritik işlemler: kullanıcı snapshot'ı, IP, cihaz, URL, eski/yeni değerler |
| `login_histories` | Giriş / başarısız deneme / çıkış |
| `security_alerts` | Yanlış şifre, pasif hesap girişi, backdated ödeme, yüksek tutar, iptal, düzeltme, `login_throttled` |

Gerçek ziyaretçi IP'si nginx'in PROXY protocol ayarıyla gelir (bkz. MOBILE_API.md §7.3).
SMS ve komut işlemleri audit log'a yazılmaz.

---

## 11) Ortamlar

### Yerel geliştirme
```sh
composer install
php artisan migrate          # ana DB: .env içindeki pgsql
php artisan serve
php artisan queue:work       # senkron job'ları için
php artisan sync:run         # elle senkron
```

### Testler
```sh
php artisan test
```
- Test DB: yerel PostgreSQL `accounts_receivable_test` (`phpunit.xml`, `force="true"`).
- `tests/TestCase.php` adı `_test` ile bitmeyen DB'de çalışmayı **reddeder** (gerçek veriyi korur).
- `admin` DB rolü veritabanı oluşturamaz; OS kullanıcısı oluşturabilir.
- Factory'ler: `UserFactory::new()`, `CreditFactory::new()` (`User::factory()` yanlış çözülür, namespace `src/App`).
- Ödeme testleri için temel sınıf: `tests/Feature/Web/Payments/PaymentTestCase.php`; API için `tests/Feature/Api/V1/ApiTestCase.php`.

### Canlı (production)
- Adres: `https://creditpos.xcess.it.com` (API `/api/v1`).
- Sunucu: `ssh -p 225 credit@93.171.223.137`, proje `/var/www/accounts_receivable`, dal **`crdt-serv`**.
- systemd: `accounts-receivable-http` (`php artisan serve` :8001, tek iş parçacıklı) ve `accounts-receivable-queue`.
- Redis (cache/session/queue); PostgreSQL ayrı makinede (10.10.8.20). Sunucuda `pg_dump` ve sudo yok → oradan yedek alınamaz.
- Config/route cache **kullanılmıyor**.
- Cron: `sync:run` 15 dk'da bir (09–23), `report:daily-payments` 23:00. **`schedule:run` yok** →
  `Console/Kernel.php`'ye eklenen yeni zamanlama canlıda çalışmaz (ör. `sanctum:prune-expired` tetiklenmiyor).
- Önde ayrı sunucuda Docker içinde nginx (`ssh -p 221 xcess@93.171.223.137`); başka siteler ve VPN de orada — dokunmadan önce onay.

### Deploy (yalnızca onayla)
Canlıya giden kod `production` dalındadır. Sunucu kendi `crdt-serv` dalında kalır ve yalnızca
`production`'dan **ileri sarılır** (`--ff-only`; merge commit'i oluşmaz).

**Sunucu GitHub'a erişemez** (`github.com` DNS'te çözülmüyor; 2026-10-20'de denendi), bu yüzden
`git pull` çalışmaz. Kod yerelden doğrudan sunucu deposuna yan dal olarak itilir:
```sh
# Yerelde
php artisan test
git checkout production && git merge --ff-only mobile-api && git checkout mobile-api
git push origin mobile-api production
GIT_SSH_COMMAND="ssh -p 225" git push credit@93.171.223.137:/var/www/accounts_receivable production:refs/heads/deploy-production

# Sunucuda
cd /var/www/accounts_receivable
git status --porcelain              # boş olmalı
git rev-parse --short HEAD > storage/app/last-deploy-prev.txt
php artisan down --retry=10         # yalnızca migration varsa gerekli
git merge --ff-only deploy-production
php artisan migrate --force         # yalnızca çalışmamış migration'ları çalıştırır, veri silmez
php artisan view:clear
php artisan queue:restart
php artisan up
```
- Canlıda **asla**: `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:wipe`.
- Migration içeren deploy'dan önce DB sunucusunda (10.10.8.20) yedek alınmalı; uygulama sunucusunda `pg_dump` yok.
Geri dönüş için önceki commit: `storage/app/last-deploy-prev.txt`.
Kontrol: giriş sayfası 200, tokensiz `GET /api/v1/auth/me` → 401 JSON.

---

## 12) Git dalları

| Dal | Durum |
|---|---|
| `production` | **Canlıda çalışan kod.** Yalnızca ileri sarılır (`--ff-only`); doğrudan commit atılmaz |
| `mobile-api` | **Aktif çalışma dalı.** Test edilen iş buradan `production`'a ileri sarılır |
| `wepx` | Mobil öncesi ana çalışma dalı (tamamı `mobile-api` içinde) |
| `crdt-serv` (sunucu) | Sunucudaki yerel dal; itilen `deploy-production`'dan `merge --ff-only` ile güncellenir. GitHub'daki `origin/crdt-serv` eski (Haziran), kullanma |
| `main` | Yalnızca ilk commit — PR hedefi olarak kullanma |
| `wepx-api`, `wepx-local`, `wepx-reports`, `server`, `prod-server` | Eski; referans |

Commit mesajları İngilizce, kısa, "ne değişti" anlatan cümle (ör. `Customer list: who is due, and an export that holds what the list shows`).

---

## 13) Ortam değişkenleri (özel olanlar)

| Değişken | Not |
|---|---|
| `DB_*` | Ana PostgreSQL |
| `SECOND_PGSQL_HOST/PORT/DATABASE/USERNAME/PASSWORD` | Kaynak PostgreSQL (senkron) |
| `SYNC_CHUNK_SIZE` | Varsayılan 1000 |
| `PG_CREDITS_TABLE`, `PG_AVSHOCRECAT_TABLE` | Hedef tablo adları |
| `DAILY_REPORT_EMAILS` | Virgülle ayrılmış, tüm şubeleri alan adresler |
| `DAILY_REPORT_LOCALE` | Rapor dili (varsayılan `tk`) |
| `SMS_API_BASE_URL`, `SMS_API_TOKEN` | SMS servisi |
| `SMS_CUSTOMERS_FROM` | SMS listesindeki en eski kredi tarihi (2024-08-01) |
| `SANCTUM_TOKEN_EXPIRATION` | Dakika, varsayılan 10080 (7 gün) |
| `CORS_ALLOWED_ORIGINS` | Boş = CORS kapalı |

`.env` dosyasını asla commit'leme veya içeriğini yazdırma.

---

## 14) Bilinen sorunlar ve açık işler

| Konu | Not |
|---|---|
| Tek iş parçacıklı canlı sunucu | `php artisan serve`; kalıcı çözüm nginx + php-fpm |
| Web girişinde rate limit yok | API'de var, panelde yok |
| Web token düz metin, çerez `secure=false` | `users.token` hash'lenmiyor |
| Pasif krediye iptal/düzeltme yapılamıyor | `active=true` şartı |
| Avshocrecat snapshot atomik değil | truncate–insert arası boş |
| Avshocrecat raporunda şube kısıtı yok | `magazyn` ↔ şube eşleşmesi netleşmeli |
| SMS "bugün ödemeli" filtresi | 30 günlük dönem ile takvim ayını karıştırıyor |
| Tahsilat performansı / trendi kasiyer filtresi | Hâlâ ödemelerde adı geçen herkesi listeliyor |
| `config('sync.pgsql.avshocrecat_pasport')` | Anahtar `mssql` altında; job her zaman boş pasaport gönderir |
| Kopya dosyalar | `*copy.php` (2 job, 1 controller), `daily_payment_report.blade copy.php` kullanılmıyor |
| Sabit eşikler | Fazla ödeme 100, yüksek tutar 5000 kodda sabit |
| Günlük e-posta | 2026-10-12'de SMTP adresi DNS'te çözülemedi |
| Mobil | Bundle id `com.wepx.accounts_receivable_mobile` geçici; iOS dağıtımı yapılmadı |
| Eski dokümanlar | `docs/` içindeki sync/security/business dosyaları kısmen güncel değil |
