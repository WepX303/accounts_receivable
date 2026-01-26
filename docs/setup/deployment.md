# 🚀 Deployment (Production / VPS)

Bu doküman, projeyi production sunucuya (VPS/Dedicated) deploy etmek için **standart ve güvenli** bir akış sunar.

> Bu proje özelinde kritik noktalar:
> - Ana DB: PostgreSQL (`pgsql`)
> - Kaynak DB: MSSQL (`sqlsrv`)
> - Queue: `database` driver (jobs tablosu PG’de)
> - Worker: Production’da Supervisor önerilir

---

## Amaç

- Deploy adımlarını standartlaştırmak
- Cache/config, migration, queue restart gibi kritik adımları unutmamak
- Senkron ve ödeme akışının production’da stabil çalışmasını sağlamak

---

## Konum

`docs/setup/deployment.md`

---

## Ön Koşullar

### Sunucu Gereksinimleri

- PHP (Laravel 12 uyumlu)
- Composer
- Nginx/Apache
- PostgreSQL erişimi (sunucu içi veya remote)
- MSSQL erişimi (SQL Server port 1433)
- Supervisor (queue için)

### PHP Extensions

- `pdo_pgsql`
- `sqlsrv`, `pdo_sqlsrv` (MSSQL için)
- `mbstring`, `openssl`, `json`, `tokenizer`, `ctype`, `xml`

---

## 1) Kodun Sunucuya Alınması

### A) Git ile (önerilen)

```bash
cd /var/www/accounts_receivable
git pull origin main
```

### B) Zip / Upload ile

- Proje klasörünü sunucuya kopyala
- `.env` dosyasını **manuel** koy

---

## 2) .env (Production) Hazırlığı

### Zorunlu Ayarlar

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=info

DB_CONNECTION=pgsql

QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=pgsql
```

### MSSQL Ayarları

```env
MSSQL_HOST=...
MSSQL_PORT=1433
MSSQL_DATABASE=...
MSSQL_USERNAME=...
MSSQL_PASSWORD=...
```

### Test → Canlı Geçiş

Production’da test isimleri kalmamalı:

- `MSSQL_CREDITS_TABLE` → `...CREDITS`
- `MSSQL_AVSHOCRECAT_PROC` → `...AVSHOCRECAT`

> Kod değişmez, yalnızca `.env` değişir.

---

## 3) Composer Install

Production’da dev paketleri kurma:

```bash
composer install --no-dev --optimize-autoloader
```

---

## 4) Storage & Cache İzinleri

```bash
sudo chown -R www-data:www-data /var/www/accounts_receivable
sudo chmod -R 775 /var/www/accounts_receivable/storage /var/www/accounts_receivable/bootstrap/cache
```

> www-data yerine sunucundaki web user kimse onu kullan.

---

## 5) APP_KEY

Eğer ilk kurulumsa:

```bash
php artisan key:generate
```

> APP_KEY zaten varsa tekrar üretme.

---

## 6) Migration

```bash
php artisan migrate --force
```

- `--force` production’da zorunludur.
- Bu işlem PostgreSQL üzerinde tablo yapılarını kurar/günceller.

---

## 7) Cache / Config Optimize

```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> `.env` değiştiyse mutlaka `config:clear` / `config:cache` uygula.

---

## 8) Queue Worker (Supervisor)

Production’da worker’ı Supervisor ile ayakta tut:

- Doküman: `docs/queue/supervisor.md`

### Deploy Sonrası Worker Restart

```bash
php artisan queue:restart
sudo supervisorctl restart laravel-queue:*
```

---

## 9) Senkron Smoke Test

Deploy sonrası hızlı test:

```bash
php artisan sync:run
php artisan queue:work --stop-when-empty
```

Kontrol:
- `credits_test` güncellendi mi?
- `avshocrecat_report` doldu mu?
- `failed_jobs` oluştu mu?

---

## 10) Log İzleme

Laravel log:

```bash
tail -f storage/logs/laravel.log
```

Queue worker log (varsa):

```bash
tail -f storage/logs/queue-worker.log
```

---

## Rollback Stratejisi (Öneri)

### Kod Rollback
- Git ile bir önceki commit’e dön

### Migration Rollback (dikkat)

Finansal sistemlerde DB rollback risklidir.

- Eğer zorunluysa kontrollü:

```bash
php artisan migrate:rollback --step=1 --force
```

> Öneri: DB değişikliklerinde rollback yerine “forward fix” yaklaşımı.

---

## Sık Hatalar ve Çözümler

### 1) 500 Error (permissions)

- `storage/` ve `bootstrap/cache/` izinlerini kontrol et

### 2) MSSQL bağlantı yok

- Firewall / port 1433
- SQL Server remote erişim
- Doğru kullanıcı/şifre

### 3) Job’lar çalışmıyor

- Supervisor çalışıyor mu?
- `QUEUE_CONNECTION=database` doğru mu?
- `jobs` tablosu PostgreSQL’de var mı?

---

## İyileştirme Önerileri

- Healthcheck endpoint + cron
- `sync_logs` tablosu ile senkron izleme
- Monitoring (disk, cpu, queue depth, failed_jobs)

---

## Özet

- Production deploy: `.env` → composer → migrate → cache → queue restart
- Queue worker Supervisor ile ayakta tutulmalı
- Senkron smoke test deploy sonrası yapılmalı

