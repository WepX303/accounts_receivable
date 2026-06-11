# 👷 Queue Worker Kullanımı

Bu doküman, projede kullanılan **database queue** yapısında worker’ın nasıl çalıştırılacağını ve pratik kullanım senaryolarını açıklar.

> Proje özelinde:
> - `QUEUE_CONNECTION=database`
> - `DB_QUEUE_CONNECTION=pgsql`
> - Job’lar PostgreSQL’deki `jobs` tablosuna yazılır, worker onları işler.

---

## Amaç

- Job’ların nasıl işlendiğini anlatmak
- Local ve production worker komutlarını standartlaştırmak
- Timeout / retry gibi önemli parametreleri netleştirmek

---

## Konum

`docs/queue/worker-usage.md`

---

## Bağımlılıklar (ENV/Config)

`.env` içinde:

```env
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=pgsql
```

`config/queue.php` içinde:

- `connections.database.connection` → `DB_QUEUE_CONNECTION`
- `table` → `jobs`
- `retry_after` → `90`

---

## Akış

1. Komut/Controller bir job dispatch eder
2. Job `jobs` tablosuna yazılır
3. Worker `jobs` tablosunu dinler
4. Job çalışır, hata alırsa `tries` kadar tekrar dener
5. Tamamen başarısızsa `failed_jobs` tablosuna düşer

---

## Worker Komutları

### 1) Tek sefer çalıştır (test için)
Queue boşalınca durur:

```bash
php artisan queue:work --stop-when-empty
```

### 2) Sürekli çalıştır (local geliştirme)

```bash
php artisan queue:work
```

### 3) Daha güvenli parametrelerle çalıştırma

```bash
php artisan queue:work --tries=3 --timeout=300
```

> `SyncAvshocrecatReportJob` zaten `tries=3` ve `timeout=300` tanımlıyor; yine de worker tarafında da uyumlu değerler kullanmak faydalıdır.

### 4) Sadece belirli queue ismini dinlemek

```bash
php artisan queue:work --queue=default
```

> Bu projede varsayılan queue adı `default`.

---

## Failed Jobs Yönetimi

### Listele

```bash
php artisan queue:failed
```

### Tekrar dene

```bash
php artisan queue:retry all
```

### Belirli job’u sil

```bash
php artisan queue:forget <id>
```

### Tüm failed kayıtları temizle

```bash
php artisan queue:flush
```

---

## Sık Kullanılan Debug Komutları

### Queue tabloları var mı?

```bash
php artisan migrate:status
```

### Jobs tablosunda bekleyen var mı?

- PostgreSQL:

```sql
select * from jobs order by id desc;
```

### Failed job’larda hata ne?

- PostgreSQL:

```sql
select * from failed_jobs order by failed_at desc;
```

---

## Hata Senaryoları ve Çözümleri

### Job’lar çalışmıyor

Kontrol listesi:

- Worker çalışıyor mu?
- `jobs` tablosu var mı?
- `.env` `QUEUE_CONNECTION=database` mı?
- `DB_QUEUE_CONNECTION=pgsql` doğru mu?

### Job sürekli retry oluyor

- `timeout` düşük kalmış olabilir
- MSSQL bağlantısı kesiliyor olabilir
- Stored procedure çok uzun sürüyor olabilir

### `failed_jobs` doluyor

- `storage/logs/laravel.log` içindeki exception’a bak
- MSSQL SP output kolonları değişmiş olabilir (mapping bozulur)
- PostgreSQL insert hatası olabilir (type mismatch / null constraint)

---

## İyileştirme Önerileri

- Production: Supervisor ile worker yönet (bkz: `docs/queue/supervisor.md`)
- Büyük veri: `retry_after` ve `timeout` artır
- Senkron job’larda satır sayısı, süre gibi metrikleri logla
- `failed_jobs` için alarm/monitoring ekle

