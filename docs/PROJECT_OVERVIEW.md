# Accounts Receivable — Proje Özeti

> Bu doküman projenin mevcut durumunu (2026-10-12, `wepx` dalı, commit `9867e34`) özetler.
> Proje **canlıda çalışıyor**; gerçek tahsilat ve ödeme kayıtları işleniyor. Her değişiklik dikkatle ve onayla yapılmalıdır.

---

## 1) Ne yapıyor?

Taksitli kredi alacaklarının takip ve tahsilat paneli:

- **Kasiyer** müşteri arar, ödeme alır (nakit / kart / karışık / telefon).
- **Admin** ödemeyi iptal (void) eder veya düzeltir (correct).
- **Analist** raporları görüntüler ve Excel'e aktarır.
- **Operatör** müşterilere toplu SMS gönderir.
- **SuperAdmin** her şeye ek olarak senkron komutlarını, logları ve kullanıcıları yönetir.
- Yönetime ve seçili kullanıcılara her gece 23:00'te **günlük tahsilat raporu** e-postası gider.

Arayüz dilleri: `tk` (varsayılan), `ru`, `en` (`resources/lang/`). Rol etiketlerinde `tr` de var.

---

## 2) Teknik yapı

| Konu | Değer |
|---|---|
| Framework | Laravel 10 (`laravel/framework ^10.10`) |
| PHP | ^8.4 (composer platform 8.4.0) |
| Ana DB | PostgreSQL (`pgsql`) |
| Kaynak DB | İkinci PostgreSQL (`second_pgsql`) — **MSSQL değil** |
| Queue | `database` driver (`jobs` tablosu) |
| Excel | `maatwebsite/excel`, `openspout/openspout` |
| Tema | Velzon admin teması (`resources/js/pages/*` çoğunlukla kullanılmayan tema dosyaları) |
| Autoload | `App\` → `src/App/` (standart `app/` değil) |

### Önemli klasörler

```
src/App/
├── Console/Commands/      sync:run, sync:credits, sync:avshocrecat, report:daily-payments, clear:all ...
├── Console/Kernel.php     zamanlanmış görevler
├── Enums/UserRoleEnum.php 7 rol
├── Exports/               Excel export sınıfları (her rapor için bir tane)
├── Http/Controllers/      Auth, Customers, Payments, Reports, Sms, Users, Logs, Admin/Settings
├── Http/Middleware/       AuthToken, RoleMiddleware, SetLocale, TrustProxies
├── Jobs/                  SyncCreditsJob, SyncAvshocrecatReportJob
├── Mail/                  DailyPaymentReportMail
├── Models/                Credit, CreditPayment, User, AvshocrecatReport, ActivityLog, LoginHistory, SecurityAlert
├── Models/Scopes/         BranchScope (şube kısıtlaması)
└── Services/              AuditLogger, Reports/*, Sms/*, Credits/*
resources/views/pages/     sayfa blade'leri
resources/views/emails/    günlük rapor e-postası
docs/                      işsel ve teknik dokümanlar (bir kısmı güncel değil, bkz. §10)
```

---

## 3) Zamanlanmış görevler (`src/App/Console/Kernel.php`)

| Komut | Zaman | Ne yapar |
|---|---|---|
| `sync:run` | 09:00–22:59 arası her 15 dk, `withoutOverlapping` | `SyncCreditsJob` + `SyncAvshocrecatReportJob` kuyruğa atar |
| `report:daily-payments` | Her gün 23:00 | Günlük tahsilat raporunu e-postayla gönderir |

`sync:run` ayrıca 14 dk'lık bir cache lock (`sync:run-lock`) kullanır. Job'ların çalışması için bir **queue worker** (Supervisor) gerekir.

---

## 4) Senkronizasyon

Tek yönlü: `second_pgsql` (kaynak) → `pgsql` (uygulama).

### `SyncCreditsJob` — artımlı (incremental)
- Kaynak `credits` tablosundan `rv > son_rv` olan satırları `rv` sırasıyla, chunk'lar halinde okur.
- Son görülen `rv` değeri `sync_state` tablosunda `{APP_ENV}_credits_last_rv_hex` anahtarıyla tutulur.
- Hedef `credits` tablosuna `source_id` üzerinden **upsert** yapar.
- **Local alan kuralları:**
  - Yeni kayıt → `amount_local = amount`, `paid_local = paid` (başlangıç değeri).
  - Mevcut kayıt → local alanlar **korunur**. Sadece local `null` ise ve hiç elle dokunulmamışsa (`*_updated_at` null) kaynaktan doldurulur.
  - Yani uygulamada alınan ödemeler (`paid_local`) senkronla ezilmez.
- `tries=5`, `timeout=120`.

### `SyncAvshocrecatReportJob` — snapshot
- `SELECT * FROM public.avshonewcrecat(?)` fonksiyonunu çağırır.
- Hedef `avshocrecat_report` tablosunu **truncate** eder, sonra chunk'lar halinde insert eder.
- Truncate ile insert arasında tablo kısa süre boş görünür (atomik değil).
- Ödeme ekranındaki aylık taksit planı bu tablodan gelir (`sertnama_nomeri = credits.contract`).

---

## 5) Para mantığı (en kritik kısım)

### Temel alanlar (`credits`)
- `amount`, `paid` → kaynaktan gelir, **iş mantığında kullanılmaz** (referans).
- `amount_local`, `paid_local` → **uygulamanın gerçeği**.
- Kalan borç: `local_remaining = max(amount_local - paid_local, 0)`, 2 haneye yuvarlanır.
- Kapalı: `local_closed = paid_local >= amount_local - 0.01`; local alanlardan biri `null` ise **kapalı sayılır**.

### Ödeme alma — `CustomersPaymentController::store`
1. Method: `cash | card | mixed | phone`. `phone` için alıcı telefon numarası zorunlu (sadece rakam).
2. `pay_amount` = müşteriden alınan para (> 0). `mixed` ise `cash + card = pay_amount` (±0.01).
3. `payment_at` opsiyonel; ileri tarih yasak. Geçmiş tarih → **backdated** olarak işaretlenir.
4. `DB::transaction` + `lockForUpdate` ile kredi satırı kilitlenir (eşzamanlı ödemeye karşı).
5. Kredi kapalıysa veya local alanlar boşsa reddedilir.
6. Fazla ödeme sınırı: `received <= remaining + 100`.
7. `apply = min(received, remaining)`, `change = received - apply`.
8. `credits.paid_local += apply`; `paid_note` içine detaylı audit satırı yazılır.
9. `credit_payments` kaydı oluşturulur (müşteri ve kasiyer **snapshot** alanlarıyla).
10. Audit log yazılır; backdated → `high` alarm, `>= 5000` → `medium` alarm.

> Not: `credit_payments.old_amount_local / new_amount_local` alanları isimlerine rağmen **kalan borcu** (önce/sonra) tutar.

### İptal — `PaymentVoidController` (SuperAdmin, Admin)
- Sebep zorunlu. Ödeme ve kredi kilitlenir.
- `paid_local -= applied` (negatife düşmez).
- Ödeme silinmez; `voided_at`, `voided_by`, `void_reason` set edilir.
- `critical` audit + alarm.

### Düzeltme — `PaymentCorrectController` (SuperAdmin, Admin)
- Tek transaction içinde: eski ödeme void edilir → yeni ödeme oluşturulur.
- Yeni kayıtta `created_by` **orijinal kasiyer** kalır; düzelten kişi `corrected_by`, kaynak `corrected_from_payment_id`.
- Aynı kurallar: ileri tarih yasak, fazla ödeme sınırı 100.

### Değişmezlik (immutability)
- `credit_payments` kayıtlarının tutar alanları güncellenmez; düzeltme her zaman yeni kayıtla yapılır.
- `CreditPayment` created/updated/deleted olduğunda `admin_dashboard:v` cache versiyonu artırılır (dashboard cache'i geçersiz olur).

---

## 6) Kimlik doğrulama ve yetki

### Giriş — `AuthController`
- E-posta **veya** telefon + şifre.
- Başarılı girişte `users.token` (64 hex) üretilir, 1 gün geçerli; `auth_token` cookie'sine yazılır (httpOnly, **secure=false**).
- Her başarısız deneme `login_histories`, `activity_logs` ve `security_alerts` tablolarına yazılır.
- Çıkışta token DB'den silinir.

### `AuthToken` middleware
- Cookie'deki token'ı `token_expires_at > now()` ile doğrular.
- Kullanıcı pasifse (`status=false`) token'ı siler ve çıkış yaptırır.
- `Auth::setUser($user)` ile kullanıcıyı request'e bağlar.

### Roller (`UserRoleEnum`)
`SuperAdmin`, `Admin`, `User`, `Manager`, `Analyst`, `Cashier`, `Operator`

| Sayfa / işlem | Roller |
|---|---|
| Dashboard, profil | Giriş yapmış herkes |
| Müşteri bilgisi, ödeme alma | SuperAdmin, Admin, Cashier, Operator |
| Avshocrecat raporu | SuperAdmin, Admin, Cashier, Analyst, Operator |
| Müşteri listesi + export, ekstre | SuperAdmin, Admin, Manager, Analyst, Operator |
| Ödeme iptal / düzeltme | SuperAdmin, Admin |
| Raporlar (`/reports/*`) | SuperAdmin, Admin, Analyst |
| SMS | SuperAdmin, Admin, Operator |
| Komut merkezi, loglar | SuperAdmin |
| Kullanıcı yönetimi | SuperAdmin, Admin (Admin SuperAdmin göremez/oluşturamaz) |

### Şube kısıtlaması — `BranchScope`
- `Credit` ve `CreditPayment` modellerine **global scope** olarak eklenir.
- `users.branches` (JSON dizi) doluysa sorgular `branch IN (...)` ile filtrelenir.
- Boş liste = tüm şubeler. **SuperAdmin her zaman kısıtsız** (yanlış atamada kilitlenmeyi önlemek için).
- Giriş yapmış kullanıcı yoksa (CLI, job, gece raporu) filtre uygulanmaz.
- Dashboard cache anahtarları `branchCacheKey()` ile şubeye göre ayrılır.

### Proxy
- `TrustProxies::$proxies = '*'` — uygulama reverse proxy arkasında; gerçek istemci IP'si ve https bu sayede algılanır.
- Production'da `AppServiceProvider` https'i zorlar.

---

## 7) Raporlar

`src/App/Http/Controllers/Reports/` + `src/App/Exports/` + `src/App/Services/Reports/`

| Rapor | Route |
|---|---|
| Ödeme takvimi (+detay, çoklu şube filtresi) | `/reports/payment-calendar` |
| Geciken ödemeler | `/reports/overdue-payments` |
| Tahsilat performansı (+detay) | `/reports/collection-performance` |
| Günlük kasa kapanışı | `/reports/daily-cash-closing` |
| Tahsilat trendi | `/reports/collection-trend` |
| Ödeme sözü (promise to pay) | `/reports/promise-to-pay` |
| Geri kazanım etkinliği | `/reports/recovery-effectiveness` |
| Müşteri ekstresi | `/reports/customer-statement` |
| İptal / düzeltme denetimi | `/reports/void-correction` |
| Bakiye tutarlılığı (reconciliation) | `/reports/balance-integrity` |
| Avshocrecat | `/report` |

Çoğunun Excel export'u var.

### Günlük e-posta raporu — `report:daily-payments`
- Servis: `DailyPaymentReportService` (özet, önceki dönemle karşılaştırma, trend, yöntem/şube/kasiyer kırılımı, en büyük ödemeler, vadesi gelenler ile tahsilat, iptal/düzeltme/backdated denetimi, işlem yapmayan şubeler, portföy durumu).
- Alıcılar:
  - `DAILY_REPORT_EMAILS` (env) → tüm şubeler.
  - `daily_report=true` ve aktif kullanıcılar → kendi şubeleri (`dailyReportBranches()`; burada SuperAdmin de şubesine göre filtrelenir).
  - Aynı adres iki listede de varsa kullanıcı kaydı geçerli olur.
- Aynı şube setine sahip alıcılar için rapor bir kez hesaplanır.
- Seçenekler: `--date=Y-m-d`, `--to=adres`, `--locale=`, `--dry-run`.
- `daily_report` bayrağını sadece SuperAdmin açabilir.

---

## 8) SMS

- `CustomerSmsController` + `Services/Sms/*` (`SmsApiClientService`, `SmsPhoneNormalizerService`, `CustomerSmsTemplateService`).
- Akış: filtrele → önizle (export edilebilir) → gönder.
- Gecikme hesabı `Credit` modelindeki `sms_*` accessor'larından gelir (6 taksit varsayımı: aylık = tutar / 6).
- Config: `SMS_API_BASE_URL`, `SMS_API_TOKEN`.

---

## 9) Denetim ve loglama

| Tablo | İçerik |
|---|---|
| `activity_logs` | Her kritik işlem: kullanıcı snapshot'ı, IP, cihaz, URL, eski/yeni değerler |
| `login_histories` | Her giriş / başarısız deneme / çıkış |
| `security_alerts` | Riskli olaylar: yanlış şifre, pasif hesap girişi, backdated ödeme, yüksek tutar, iptal, düzeltme |

Tümü `AuditLogger` servisi üzerinden yazılır ve `/logs` sayfasında (SuperAdmin) görülür.

---

## 10) Bilinen sorunlar ve riskler

| # | Konu | Not |
|---|---|---|
| 1 | **Test yok** | Sadece Laravel örnek testleri var. Ödeme mantığına dokunmadan önce test yazılmalı. |
| 2 | Cookie `secure=false` | Güvenli sürüm yorum satırında duruyor. |
| 3 | Login'de rate limit yok | Brute-force'a açık; sadece loglanıyor. |
| 4 | Token düz metin | `users.token` hash'lenmeden saklanıyor. |
| 5 | Pasif krediye iptal yapılamıyor | `PaymentVoidController` / `PaymentCorrectController` `active=true` şartı arıyor. |
| 6 | Snapshot sync atomik değil | Truncate–insert arasında rapor kısa süre boş. |
| 7 | Kopya dosyalar | `*copy.php` (2 job, 1 controller), `daily_payment_report.blade copy.php` kullanılmıyor. |
| 8 | Dokümanlar güncel değil | `docs/` MSSQL'den, 3 rolden, "role middleware yok" diye bahsediyor; kod artık farklı. |
| 9 | `.DS_Store` repoda takip ediliyor | `.gitignore`'a eklenebilir. |
| 10 | Sabit değerler | Fazla ödeme sınırı (100) ve yüksek tutar eşiği (5000) kodda sabit. |

---

## 11) Git durumu

- Aktif dal: **`wepx`** — diğer tüm dallardan ileride.
- `main` hâlâ ilk commit'te (`3f13d85`).
- Diğer dallar (`prod-server`, `server`, `crdt-serv`, `wepx-api`, `wepx-local`, `wepx-reports`) eski; `wepx`'in gerisinde.
- Son commit: `9867e34 Trust the reverse proxy's forwarded headers`.

---

## 12) Çalışma kuralları (canlı proje)

1. Her değişiklikten önce etki analizi yap ve onay al.
2. `credit_payments` kayıtlarının tutarlarını asla güncelleme; düzeltme yeni kayıtla yapılır.
3. Ödeme / iptal / düzeltme akışlarında transaction + `lockForUpdate` korunmalı.
4. Yeni sorgularda `BranchScope`'u bypass etme; gerekiyorsa açıkça `withoutGlobalScope(BranchScope::class)` yaz ve gerekçelendir.
5. Migration'larda rollback yerine "forward fix" tercih et.
6. Deploy sonrası: `config:cache`, `route:cache`, `view:cache`, `queue:restart`.
