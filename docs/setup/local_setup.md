# 🛠️ Local Setup

Bu doküman, projeyi **local ortamda** çalıştırmak için gerekli adımları içerir.

> Bu projede:
>
> - Ana DB: **PostgreSQL (pgsql)**
> - Kaynak DB: **MSSQL (sqlsrv)**
> - Queue driver: **database** (jobs tablosu PostgreSQL’de)
> - Senkron: MSSQL → PostgreSQL Job’ları ile yapılır

---

## Amaç

- Projeyi localde çalıştırmak
- PostgreSQL + MSSQL bağlantılarını hazırlamak
- Migration + Seeder çalıştırmak
- Queue worker başlatmak
- Senkron komutunu test etmek

---

## Konum

- Proje kök dizini
- Doküman yolu: `docs/setup/local-setup.md`

---

## Gereksinimler

### Yazılımlar

- PHP (Laravel 12 ile uyumlu)
- Composer
- PostgreSQL
- MSSQL erişimi (SQL Server)
- ODBC / sqlsrv driver (PHP için)

> Not: Node/NPM bu projede zorunlu değil (Vite kullanımı opsiyonel).

### PHP Eklentileri

Genelde gerekli olanlar:

- `pdo_pgsql`
- `pdo_sqlsrv` / `sqlsrv` (SQL Server için)
- `mbstring`, `openssl`, `json`, `tokenizer`, `ctype`, `xml`

---

## 1) Projeyi Klonla ve Bağımlılıkları Kur

```bash
composer install
```

---

## 2) .env Oluştur

`.env` dosyanı oluştur:

```bash
cp .env.example .env
```

Ardından `.env` içindeki DB bilgilerini ayarla.

Bu proje için kritik alanlar:

- PostgreSQL:
    - `DB_CONNECTION=pgsql`
    - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`

- MSSQL:
    - `MSSQL_HOST`, `MSSQL_PORT`, `MSSQL_DATABASE`, `MSSQL_USERNAME`, `MSSQL_PASSWORD`
    - `MSSQL_CREDITS_TABLE`, `MSSQL_AVSHOCRECAT_PROC`

- Queue:
    - `QUEUE_CONNECTION=database`
    - `DB_QUEUE_CONNECTION=pgsql`

---

## 3) APP_KEY Üret

```bash
php artisan key:generate
```

> Eğer `.env` içinde zaten APP_KEY varsa tekrar üretmen gerekmez.

---

## 4) PostgreSQL Bağlantısını Test Et

Laravel’in DB bağlantısını test etmek için:

```bash
php artisan tinker
```

Tinker içinde:

```php
DB::connection()->getPdo();
```

Hata yoksa PostgreSQL bağlantın hazır.

---

## 5) MSSQL Bağlantısını Test Et

Tinker içinde:

```php
DB::connection('sqlsrv')->getPdo();
```

> Bağlantı hatası alırsan: SQL Server driver / firewall / port / kullanıcı şifresi kontrol et.

---

## 6) Migration’ları Çalıştır

> Dikkat: `DB_CONNECTION=pgsql` olduğundan migration’lar PostgreSQL’e uygulanır.

```bash
php artisan migrate
```

Bu işlem şu tabloları oluşturur:

- `users`, `credits`, `credit_payments`, `sync_state`, `jobs`, `failed_jobs` vb.
- Ayrıca `avshocrecat_report` tablosu migration içinde `Schema::connection('pgsql')` ile PG’de oluşur.

---

## 7) Admin Kullanıcısı (Seeder)

Varsayılan admin hesabı oluşturmak için:

```bash
php artisan db:seed --class=AdminUserSeeder
```

> Seeder çalıştıktan sonra admin hesabı oluşur (email/telefon unique kontrolü var).

---

## 8) Queue Worker Başlat

Queue driver `database` olduğu için job’ların çalışması için worker gerekir.

### Tek sefer (test)

```bash
php artisan queue:work --stop-when-empty
```

### Sürekli (local geliştirme)

```bash
php artisan queue:work
```

---

## 9) Senkronu Çalıştır

Komut iki job dispatch eder:

- `SyncCreditsJob` (incremental)
- `SyncAvshocrecatReportJob` (truncate + insert)

### Tüm pasaportlar

```bash
php artisan sync:run
```

### Belirli pasaport

```bash
php artisan sync:run --passport=123456
```

> Not: Bu projede `SyncAvshocrecatReportJob` varsayılan pasaport filtresini `config('sync.mssql.avshocrecat_pasport')` içinden de okuyabilir.

---

## 10) Sık Karşılaşılan Sorunlar

### “could not find driver” (pgsql/sqlsrv)

- PHP extension eksik
- Doğru `pdo_pgsql` ve `sqlsrv/pdo_sqlsrv` kurulduğundan emin ol

### MSSQL connection refused / timeout

- SQL Server port: `1433` açık mı?
- Firewall / network erişimi var mı?
- Kullanıcı/şifre doğru mu?

### Queue job çalışmıyor

- Worker çalışıyor mu?
- `jobs` tablosu var mı?
- `QUEUE_CONNECTION=database` doğru mu?

---

## İyileştirme Önerileri

- Production’da queue için Supervisor kullan
- Senkron job’ları için log/sync-state kayıtlarını standartlaştır
- Büyük veri varsa `retry_after`/`timeout` değerlerini yükselt
