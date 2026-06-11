# 🧵 Queue (config/queue.php) Dokümantasyonu

Bu doküman, projede kullanılan **Laravel Queue yapılandırmasını** (`config/queue.php`) detaylı şekilde açıklar.

Bu proje özelinde:
- Queue driver olarak **database** kullanılmaktadır
- Queue tabloları **PostgreSQL (pgsql)** üzerinde tutulur
- Senkronizasyon Job’ları (Credits, Avshocrecat) bu yapı üzerinden çalışır

---

## 🎯 Genel Amaç

Queue sistemi sayesinde:

- Uzun süren işlemler (MSSQL → PG senkronizasyonu gibi) request süresinden ayrılır
- Job’lar asenkron çalışır
- Sistem daha stabil ve ölçeklenebilir hale gelir

---

## 1️⃣ Varsayılan Queue Connection

```php
'default' => env('QUEUE_CONNECTION', 'sync'),
```

### Açıklama

- Varsayılan queue bağlantısı `.env` içindeki `QUEUE_CONNECTION` değişkeninden okunur
- Bu projede `.env`:

```env
QUEUE_CONNECTION=database
```

➡️ Sonuç olarak **database queue aktif** durumdadır.

---

## 2️⃣ Queue Connections

Laravel birden fazla queue driver’ı destekler:

- `sync`
- `database` ✅ (aktif)
- `redis`
- `sqs`
- `beanstalkd`

Bu projede **database driver** kullanılmaktadır.

---

## 3️⃣ `sync` Driver

```php
'sync' => [
    'driver' => 'sync',
],
```

### Ne İşe Yarar?

- Job’ları **anında**, senkron olarak çalıştırır
- Queue worker gerekmez

### Ne Zaman Kullanılır?

- Local test
- Basit projeler

> ⚠️ Bu projede **önerilmez**, çünkü MSSQL senkronizasyonu uzun sürebilir.

---

## 4️⃣ `database` Driver (AKTİF)

```php
'database' => [
    'driver' => 'database',
    'connection' => env('DB_QUEUE_CONNECTION', env('DB_CONNECTION', 'pgsql')),
    'table' => 'jobs',
    'queue' => 'default',
    'retry_after' => 90,
    'after_commit' => false,
],
```

### Alan Açıklamaları

| Alan | Açıklama |
|----|--------|
| `driver` | Database queue driver |
| `connection` | Queue tablolarının hangi DB’de olduğu |
| `table` | Job’ların tutulduğu tablo (`jobs`) |
| `queue` | Queue adı (`default`) |
| `retry_after` | Job kilit süresi (sn) |
| `after_commit` | DB transaction sonrası çalışsın mı |

### DB Connection Detayı

```env
DB_QUEUE_CONNECTION=pgsql
```

➡️ Job’lar **PostgreSQL** üzerindeki `jobs` tablosunda tutulur.

---

## 5️⃣ retry_after Nedir?

```php
'retry_after' => 90,
```

- Job 90 saniye içinde tamamlanmazsa:
  - Serbest bırakılır
  - Tekrar denenebilir

### Senkronizasyon Job’ları için Öneri

- Büyük veri varsa bu değer **120–300** yapılabilir

```php
'retry_after' => 300,
```

---

## 6️⃣ after_commit Ayarı

```php
'after_commit' => false,
```

### Anlamı

- `false`: Job, transaction bitmeden dispatch edilebilir
- `true`: Job, DB transaction **commit edildikten sonra** dispatch edilir

### Bu Proje İçin

- Senkronizasyon job’ları genelde transaction dışında dispatch edildiği için `false` uygundur.

---

## 7️⃣ Diğer Queue Driver’ları (Pasif)

### Beanstalkd

```php
'beanstalkd' => [...]
```

- Şu an kullanılmıyor

---

### SQS (AWS)

```php
'sqs' => [...]
```

- Cloud queue senaryoları için
- Şu an kullanılmıyor

---

### Redis

```php
'redis' => [...]
```

- High-performance queue için uygun
- Şu an pasif (`QUEUE_CONNECTION=database`)

---

## 8️⃣ Job Batching

```php
'batching' => [
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'job_batches',
],
```

### Amaç

- Birden fazla job’ı **batch** halinde yönetmek
- Toplu ilerleme ve iptal imkanı

### Bu Proje İçin

- Şu an aktif kullanılmıyor
- İleride `SyncCreditsJob` chunk bazlı batch yapılabilir

---

## 9️⃣ Failed Jobs

```php
'failed' => [
    'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
    'database' => env('DB_CONNECTION', 'mysql'),
    'table' => 'failed_jobs',
],
```

### Amaç

- Hata alan job’ları loglamak

### Bu Proje İçin

- `failed_jobs` tablosu PostgreSQL’de bulunur
- Senkronizasyon hataları burada incelenebilir

### Faydalı Komutlar

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:forget {id}
```

---

## 🔧 Queue Worker Çalıştırma

### Local / Test

```bash
php artisan queue:work --stop-when-empty
```

### Sürekli (Production)

```bash
php artisan queue:work
```

> Production’da **Supervisor** kullanılması önerilir.

---

## 🚀 Best Practices

- Uzun süren job’lar için:
  - `timeout` ve `retry_after` artırılmalı
- Büyük MSSQL senkronizasyonlarında:
  - Chunk + batch job yapısı düşünülmeli
- Queue mutlaka monitor edilmeli (`failed_jobs`)

---