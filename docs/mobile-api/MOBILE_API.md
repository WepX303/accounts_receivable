# Mobil API ve Mobil Uygulama — Yapılan İşlerin Dokümanı

> Bu doküman, 2026-10-12 / 2026-10-13 tarihlerinde yapılan çalışmanın tamamını anlatır:
> web panelinin mobil sürümü için yazılan API, panelde yapılan değişiklikler,
> canlıya alma ve canlı ortamda yapılan ayarlar.
> Mobil uygulamanın kendi dokümanı ayrı depodadır: `accounts_receivable_mobile/docs/PROJECT.md`.

---

## 1) Özet

| Konu | Durum |
|---|---|
| API | `/api/v1` altında 49 uç; panelin tüm ekranlarının karşılığı var |
| Dal | `mobile-api` (`wepx` dalından açıldı); canlıdaki `crdt-serv` dalı bu dala ileri sarıldı |
| Canlı sürüm | `9a92b3c` (2026-10-13) |
| Testler | 175 test, hepsi geçiyor (`php artisan test`) |
| Mobil uygulama | Android yayın APK'sı hazır (1.0.0); iOS simülatörde ve gerçek cihazda çalıştırıldı |

Temel ilke: **mobil, panelin kurallarını kopyalamaz, aynı kodu çalıştırır.** Para, giriş,
profil, dashboard ve müşteri listesi mantığı ortak servislere taşındı; raporlar, SMS,
kullanıcılar, loglar ve komut merkezi ise panelin kendi controller'ları üzerinden çalışır.

---

## 2) Mimari

### 2.1 Ortak servisler (`src/App/Services/`)

Panel ve API aynı servisi çağırır. Taşımadan önce panelin mevcut davranışı testlerle
sabitlendi (`tests/Feature/Web/`), taşımadan sonra aynı testler değişmeden geçti.

| Servis | Ne yapar |
|---|---|
| `Payments/PaymentService` | Ödeme alma, iptal, düzeltme (audit log ve alarmlarla birlikte) |
| `Payments/Money` | Para biçimlendirme ve yuvarlama |
| `Auth/LoginService` | Giriş kuralları, giriş geçmişi, audit log, alarmlar |
| `Profile/ProfileService` | Profil ve şifre güncelleme |
| `Dashboard/DashboardService` | Kasiyer ve yönetici dashboard verileri |
| `Customers/CustomerListService` | Müşteri listesi, filtreler, sayaçlar; **Excel dışa aktarma da bu sorguyu kullanır** |

### 2.2 Panel köprüsü (`src/App/Support/`)

Raporlar, SMS, kullanıcılar, loglar ve komut merkezi için ikinci bir sorgu kopyası
yazılmadı. `Panel::call()` panelin controller'ını çalıştırır, `ViewData::toArray()`
view'a gönderilen veriyi JSON'a çevirir. Bu yüzden bu uçların JSON alan adları panelin
değişken adlarını izler ve **panelde bir rapor değişince API kendiliğinden değişir.**

| Sınıf | Görev |
|---|---|
| `Panel` | Panel controller'ını çağırır; yönlendirme/flash mesajlarını API cevabına çevirir |
| `ViewData` | View verisini (model, paginator, koleksiyon) düz JSON'a çevirir |
| `ApiResponse` | Tek cevap zarfı: `{success, message, data}` veya `{success, message, error_code, errors}` |
| `Access` | Yetki haritası; `routes/web.php` ile aynı roller. `auth/me` bu haritayı döner |
| `ClientInfo` | İsteğin kaynağı (web / mobile / system), gerçek IP, cihaz bilgisi |

### 2.3 Kimlik doğrulama

- **Sanctum kişisel erişim token'ları.** Panelin `users.token` + çerez akışına dokunulmadı;
  mobil giriş panel oturumunu düşürmez.
- Token süresi 7 gün (`SANCTUM_TOKEN_EXPIRATION`, dakika). Kullanıcı başına en fazla 5 cihaz.
- Şifre değişince, hesap pasifleşince veya silinince kullanıcının tüm token'ları iptal edilir.
- API yalnızca token ile açılır (`config/sanctum.php` → `guard: []`); tarayıcıdaki panel
  oturumu API'yi açmaz.

### 2.4 Çift ödeme koruması

`POST /payments` bir `Idempotency-Key` başlığı alır. Aynı anahtar ikinci kez gelirse yeni
ödeme açılmaz, ilk kayıt döner (`credit_payments.client_request_id`, benzersiz).

---

## 3) API uçları (`/api/v1`)

| Modül | Uçlar |
|---|---|
| Giriş | `POST auth/login`, `GET auth/me`, `POST auth/logout` |
| Profil | `PUT profile` |
| Dashboard | `GET dashboard` (role göre kasiyer / yönetici görünümü) |
| Müşteri (kasiyer) | `GET customers/search`, `GET customers/{credit}`, `GET customers/{credit}/payments` |
| Ödeme | `POST payments`, `POST payments/{payment}/void`, `POST payments/{payment}/correct` |
| Müşteri listesi | `GET customers`, `GET customers/export`, `GET customers/{credit}/statement` |
| Raporlar | `GET reports/{rapor}` (13 adres) ve `GET reports/{rapor}/export` (12 adres) |
| SMS | `GET sms/customers`, `POST sms/preview`, `POST sms/preview/export` |
| Kullanıcılar | `GET/POST users`, `PUT/DELETE users/{user}` |
| Loglar | `GET logs` |
| Komut merkezi | `GET commands`, `POST commands/{command}` |

Raporlar: `avshocrecat`, `payment-calendar` (+`/details`), `overdue-payments`,
`collection-performance` (+`/details`), `daily-cash-closing`, `collection-trend`,
`promise-to-pay`, `recovery-effectiveness`, `customer-statement`, `void-correction`,
`balance-integrity`.

**SMS gönderimi API'de yoktur.** Mobil yalnızca mesajları hazırlar ve Excel'e aktarır;
gönderim panelde kalır (`POST /api/v1/sms/send` bilerek kaldırıldı, 404 döner).

Dil: `Accept-Language` başlığı (`tk`, `ru`, `en`, `tr`).

---

## 4) Güvenlik ve yük koruması

| Önlem | Değer |
|---|---|
| Adres bazlı ön sınır (token'a bakılmadan) | IP başına 600 istek/dakika |
| Genel sınır | Kullanıcı başına 180/dk; girişsiz isteklerde IP başına 40/dk |
| Giriş denemesi | Hesap başına 5/dk, IP başına 20/dk; aşılınca `login_throttled` alarmı |
| Raporlar | Kullanıcı başına 40/dk |
| Excel dışa aktarma | Kullanıcı başına 6/dk ve **aynı anda tek dışa aktarma** |
| Değişiklik yapan uçlar (ödeme, kullanıcı) | Kullanıcı başına 40/dk |
| Profil güncelleme | 10/dk (mevcut şifre soruluyor) |
| Komut çalıştırma | 6/dk |
| İstek gövdesi | 512 KB üstü okunmadan reddedilir (413) |
| Log sayfası | `page` en fazla 500 |

Diğer önlemler:

- Giriş hatasında tek mesaj: "kullanıcı adı veya şifre hatalı" (hesabın var olup olmadığı anlaşılmaz).
- Sunucu hatasında SQL, dosya yolu veya istisna metni dışarı çıkmaz (`Handler`).
- Cevap başlıkları: `Cache-Control: no-store`, `X-Content-Type-Options`, `X-Frame-Options`,
  `Content-Security-Policy: default-src 'none'`, `Referrer-Policy`, https'te `Strict-Transport-Security`.
- CORS varsayılan olarak kapalı (`CORS_ALLOWED_ORIGINS` boş).
- `BranchScope` hiçbir API sorgusunda atlanmaz; kullanıcı kimliği belli olmadan URL'deki kayıt yüklenmez
  (`Kernel::$middlewarePriority`).

Hız sınırları önbelleği kullanır; canlıda Redis olduğu için birden fazla işçide de doğru çalışır.

---

## 5) Panelde (web) değişen davranışlar

Bu çalışma sırasında panelin kendisinde de değişiklikler yapıldı:

| Değişiklik | Açıklama |
|---|---|
| Şube sızıntısı kapatıldı | Ödeme dökümü sayfasında URL'deki kayıt, kullanıcının şubeleri kontrol edilmeden yükleniyordu |
| Müşteri listesi: vade filtreleri | "Bugün ödemeli", "3 gün içinde ödemeli", "1 hafta içinde ödemeli" |
| Müşteri Excel'i | Artık listenin kendi sorgusunu kullanır: tarih aralığına uyar, iptal edilmiş ödemeleri saymaz, silinmiş müşterileri indirmez, "bloklu" filtresi doğru çalışır |
| Loglar | Her kayıt kaynağını tutar (`web` / `mobile` / `system`); log sayfası çevrildi, "Kaynak" sütunu eklendi; sayfa artık tüm tabloları belleğe almıyor |
| Gerçek IP | Kayıtlarda proxy'nin değil ziyaretçinin adresi (bkz. 7.3) |
| Günlük kasa kapanış raporu | Kasiyer filtresi yalnızca aktif kasiyerleri gösterir (önceden: ödemelerde adı geçen herkes) |
| SMS listesi | Yalnızca 2024-08-01 ve sonrasında verilen krediler (`SMS_CUSTOMERS_FROM`) |
| Çeviriler | Metinlerden teknik alan adları çıkarıldı (`amount_local` vb.); ödeme notundaki anahtarlar çevrildi; Türkmencede "Töleg geçmişi", "Algylary Dolandyrmak" |

### Vade filtrelerinin tanımı

Kredi tarihinden itibaren 6 eşit aylık taksit sayılır. Ödenen tutarın kapatmadığı ilk
taksitin tarihi "vade"dir. "3 gün" ve "1 hafta" *içinde* demektir (bugünü de kapsar).
Vadesi geçmiş müşteriler bu filtrelerde çıkmaz.

---

## 6) Veritabanı değişiklikleri

Hepsi yalnızca ekleme yapar; mevcut satırlara dokunmaz.

| Migration | Ne ekler |
|---|---|
| `2026_10_12_150000_add_client_request_id_to_credit_payments_table` | `credit_payments.client_request_id` (nullable, unique) |
| `2026_10_12_180000_add_client_to_log_tables` | `activity_logs`, `login_histories`, `security_alerts` → `client` (nullable) |
| `2026_10_13_140000_mark_earlier_log_entries_as_web` | Kaynağı boş eski log kayıtlarını `web` yapar (geri alınamaz) |

---

## 7) Canlı ortam

### 7.1 Yapı

- Adres: `https://creditpos.xcess.it.com` (API: `/api/v1`).
- Uygulama sunucusu: proje `/var/www/accounts_receivable`, dal `crdt-serv`.
- systemd servisleri: `accounts-receivable-http` (`php artisan serve`, port 8001) ve
  `accounts-receivable-queue` (`queue:work`, `Restart=always`).
- Önbellek, oturum ve kuyruk Redis'te; veritabanı (PostgreSQL) ayrı bir makinede.
- Zamanlanmış işler cron ile doğrudan çağrılır: `sync:run` (15 dakikada bir, 09–23),
  `report:daily-payments` (23:00). `schedule:run` kullanılmıyor.
- Önde ayrı bir sunucuda, Docker içinde nginx çalışır (bkz. 7.3).

### 7.2 Yayına alma adımları

Uygulama sunucusu etkileşimsiz SSH oturumunda GitHub'dan çekemiyor; bu yüzden yeni sürüm
yerelden doğrudan sunucu deposuna ayrı bir dal olarak gönderilir, orada ileri sarılır:

```sh
# Yerelde
php artisan test
git push origin mobile-api
git push <sunucu>:/var/www/accounts_receivable mobile-api:refs/heads/deploy-mobile-api

# Sunucuda
cd /var/www/accounts_receivable
git status --porcelain            # boş olmalı
php artisan down --retry=10       # yalnızca migration varsa gerekli
git merge --ff-only deploy-mobile-api
php artisan migrate --force
php artisan view:clear
php artisan queue:restart
php artisan up
```

Geri dönüş: bir önceki commit `storage/app/last-deploy-prev.txt` içinde tutulur.

Yayın sonrası kontrol: panel giriş sayfası 200, `GET /api/v1/auth/me` tokensiz 401 (JSON).

### 7.3 Gerçek IP (nginx)

nginx 443'ü önce `stream` katmanında (SNI'ye göre) karşılayıp siteye kendi içinden
aktarıyordu; site her ziyaretçiyi `127.0.0.1` görüyordu. Yalnızca creditpos için:

- `nginx.conf` → `stream` bloğuna `server_name creditpos.xcess.it.com` ile ikinci bir
  `server`: bağlantıyı `proxy_protocol on` ile `127.0.0.1:9454` adresine aktarır.
- `https.d/creditpos.xcess.it.com.conf` → `listen 9454 ssl proxy_protocol;` eklendi;
  `X-Real-IP` ve `X-Forwarded-For` başlıkları `$proxy_protocol_addr` değerinden yazılır
  (istekten alınmaz; ziyaretçi sahte IP gönderemez).

Diğer siteler ve VPN eski kuraldan geçmeye devam eder. Orijinal dosyalar nginx sunucusunda
`~/service/nginx/backup-20261013-realip/` klasöründedir.

Dikkat: `nginx.conf` kapsayıcıya tek dosya olarak bağlıdır; yerinde düzenlenmelidir
(`sed -i` gibi dosyayı yeniden oluşturan araçlar kapsayıcının eski dosyayı görmesine yol açar).
Sınama: `docker exec nginx /usr/local/nginx/sbin/nginx -t`, uygulama: `... -s reload`.

Şirket ağından veya VPN'den bağlananlar loglarda iç adresle görünür; dışarıdan gelenler genel IP ile.

---

## 8) Ortam değişkenleri

| Değişken | Varsayılan | Açıklama |
|---|---|---|
| `SANCTUM_TOKEN_EXPIRATION` | `10080` | Token süresi, dakika (7 gün) |
| `CORS_ALLOWED_ORIGINS` | boş | Virgülle ayrılmış kökenler; boşsa CORS kapalı |
| `SMS_CUSTOMERS_FROM` | `2024-08-01` | SMS listesine girecek en eski kredi tarihi |

---

## 9) Testler

```sh
php artisan test
```

- Test veritabanı: `accounts_receivable_test` (`phpunit.xml`). `tests/TestCase.php`, adı
  `_test` ile bitmeyen veritabanında çalışmayı reddeder.
- Factory'ler `UserFactory::new()` / `CreditFactory::new()` şeklinde çağrılır
  (`User::factory()` yanlış çözülür; uygulama ad alanı `src/App` altındadır).
- `tests/Feature/Web/` panelin davranışını sabitler; `tests/Feature/Api/V1/` API'yi.

Mobil uygulamanın cihaz testleri için ayrı bir demo veritabanı kullanılır
(`accounts_receivable_mobile_demo`); ayrıntı mobil deponun README dosyasında.

---

## 10) Bilinen sorunlar ve açık konular

| Konu | Not |
|---|---|
| Tek iş parçacıklı sunucu | Canlı site `php artisan serve` ile çalışıyor; aynı anda tek istek işler. Kalıcı çözüm nginx + php-fpm |
| Veritabanı yedeği | Uygulama sunucusunda `pg_dump` yok; yayın öncesi yedek oradan alınamıyor |
| Günlük rapor e-postası | 2026-10-12 23:00'te SMTP adresi çözülemedi (DNS); bu çalışmadan bağımsız |
| SMS ekranındaki "bugün ödemeli" filtresi | 30 günlük dönemlerle takvim aylarını karıştırıyor; neredeyse hiç sonuç vermiyor. Müşteri listesindeki yeni filtreler doğru tanımla yazıldı, SMS'teki değiştirilmedi |
| Avshocrecat raporu | Şube kısıtlaması yok (`magazyn` değerlerinin şube adlarıyla eşleşmesi netleşmeli) |
| Diğer raporlardaki kasiyer filtresi | "Tahsilat performansı" ve "Tahsilat eğilimi" hâlâ ödemelerde adı geçen herkesi listeliyor |
| SMS ve komut işlemleri | Audit log'a yazılmıyor (panelde de böyle) |
| Token temizliği | `sanctum:prune-expired` zamanlanmış ama `schedule:run` çalışmadığı için tetiklenmiyor; süresi dolan token'lar yine de reddedilir |
| nginx erişim kaydı | `access.log` hâlâ `127.0.0.1` yazıyor (genel log ayarı, dokunulmadı) |
