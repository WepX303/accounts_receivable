# 🧩 Supervisor ile Queue Worker Yönetimi

Bu doküman, production (veya sürekli çalışan sunucu) ortamında Laravel queue worker’ını **Supervisor** ile nasıl yöneteceğini anlatır.

> Proje özelinde:
> - Queue driver: `database`
> - Queue DB: `pgsql`
> - Job’lar `jobs` tablosundan alınır

---

## Amaç

- Queue worker’ın sunucuda sürekli çalışmasını sağlamak
- Worker çökerse otomatik yeniden başlatmak
- Log ve restart yönetimini standartlaştırmak

---

## Konum

`docs/queue/supervisor.md`

---

## Bağımlılıklar (ENV/Config)

`.env`:

```env
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=pgsql
```

Worker parametre önerisi (job’ların timeout değerine uyumlu):
- `--tries=3`
- `--timeout=300`

---

## Supervisor Kurulumu

Ubuntu/Debian için:

```bash
sudo apt update
sudo apt install supervisor
```

Servis durum kontrol:

```bash
sudo systemctl status supervisor
```

---

## Supervisor Program Konfigürasyonu

Aşağıdaki dosyayı oluştur:

```bash
sudo nano /etc/supervisor/conf.d/laravel-queue.conf
```

Örnek config:

```ini
[program:laravel-queue]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/accounts_receivable/artisan queue:work database --sleep=3 --tries=3 --timeout=300

autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/accounts_receivable/storage/logs/queue-worker.log
stopwaitsecs=360
```

### Alan Açıklamaları

- `command`: Worker komutu
- `database`: queue connection adı (QUEUE_CONNECTION=database)
- `--sleep=3`: queue boşsa 3 sn bekler
- `--tries=3`: hata alırsa 3 kez dener
- `--timeout=300`: job için max süre
- `stdout_logfile`: worker log dosyası
- `stopwaitsecs=360`: durdururken bekleme süresi

> Not: Proje yolu ve PHP binary yolu sunucuya göre değişebilir.

---

## Supervisor Reload / Start

Config dosyası eklendikten sonra:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue:*
```

Durum kontrol:

```bash
sudo supervisorctl status
```

---

## Restart (Deploy Sonrası)

Deploy sonrası worker restart önerisi:

```bash
sudo supervisorctl restart laravel-queue:*
```

Ayrıca Laravel’in önerdiği şekilde:

```bash
php artisan queue:restart
```

> `queue:restart` worker’ların “graceful” şekilde kendini yenilemesini sağlar.

---

## Log Takibi

Worker loglarını izlemek için:

```bash
tail -f /var/www/accounts_receivable/storage/logs/queue-worker.log
```

Laravel uygulama logu:

```bash
tail -f /var/www/accounts_receivable/storage/logs/laravel.log
```

---

## Hata Senaryoları ve Çözümleri

### Worker çalışıyor görünüyor ama job işlemiyor

- `QUEUE_CONNECTION` yanlış olabilir
- `jobs` tablosu başka DB’de olabilir
- `.env` cache’lenmiş olabilir

Çözüm:

```bash
php artisan config:clear
php artisan cache:clear
sudo supervisorctl restart laravel-queue:*
```

### Timeout

- Job `timeout` arttırılmalı
- Supervisor komutunda `--timeout` uyumlu olmalı

### Permission hatası

- `storage/` ve `bootstrap/cache/` izinleri

```bash
sudo chown -R www-data:www-data /var/www/accounts_receivable
sudo chmod -R 775 /var/www/accounts_receivable/storage /var/www/accounts_receivable/bootstrap/cache
```

---

## İyileştirme Önerileri

- Yoğun trafikte `numprocs` artırılabilir (2-4 worker)
- Uzun süren senkron job’ları için ayrı queue ismi (`sync`) kullanılabilir:
  - `dispatch()->onQueue('sync')`
  - supervisor config `--queue=sync`
- `failed_jobs` için monitoring/alarm eklenebilir

