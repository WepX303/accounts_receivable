# 🧯 Sync Troubleshooting

Bu doküman, MSSQL → PostgreSQL senkronizasyonunda (Jobs + Queue) karşılaşılabilecek sorunlar için **hızlı teşhis** ve **çözüm checklist**’i sunar.

> Kapsam:
> - `SyncCreditsJob` (incremental)
> - `SyncAvshocrecatReportJob` (truncate + insert)
> - Queue (database driver)
> - MSSQL bağlantısı (sqlsrv)
> - PostgreSQL (pgsql)

---

## Amaç

- Senkron neden çalışmıyor? sorusuna hızlı cevap vermek
- Hataları sistematik şekilde teşhis etmek
- Sık görülen problemler için çözüm adımlarını standartlaştırmak

---

## Konum

`docs/sync/troubleshooting.md`

---

## Ön Kontrol (5 Dakikalık Checklist)

### 1) Queue Worker çalışıyor mu?

```bash
php artisan queue:work --stop-when-empty
```

Worker yoksa job’lar `jobs` tablosunda bekler ve hiç çalışmaz.

---

### 2) Job queue’ya yazılıyor mu?

PostgreSQL’de kontrol:

```sql
select * from jobs order by id desc;
```

- Kayıt geliyor ama çalışmıyorsa → worker yok / yanlış queue bağlantısı
- Kayıt hiç gelmiyorsa → dispatch edilmiyor / command çalışmıyor

---

### 3) Failed jobs var mı?

```bash
php artisan queue:failed
```

PostgreSQL’de kontrol:

```sql
select * from failed_jobs order by failed_at desc;
```

---

### 4) MSSQL bağlantısı çalışıyor mu?

```bash
php artisan tinker
```

```php
DB::connection('sqlsrv')->getPdo();
```

---

### 5) PostgreSQL bağlantısı çalışıyor mu?

```bash
php artisan tinker
```

```php
DB::connection('pgsql')->getPdo();
```

---

## Sık Hatalar ve Çözümleri

## 1) Job’lar hiç çalışmıyor

### Belirti
- `php artisan sync:run` çalışıyor ama veri gelmiyor
- `jobs` tablosu doluyor, ancak işlenmiyor

### Çözüm
- Worker başlat:

```bash
php artisan queue:work
```

- `.env` kontrol:

```env
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=pgsql
```

- `config/queue.php` içinde database connection doğru mu?

---

## 2) `could not find driver` (pgsql/sqlsrv)

### Belirti
- Migration veya job çalışırken “driver not found” hatası

### Çözüm
- PostgreSQL için: `pdo_pgsql`
- MSSQL için: `sqlsrv` + `pdo_sqlsrv`

Sunucuya göre paket isimleri değişir.

---

## 3) MSSQL bağlantı hatası (timeout / login failed)

### Belirti
- `SQLSTATE[HYT00]` timeout
- “Login failed for user”
- “Connection refused”

### Çözüm
- `.env` MSSQL bilgilerini kontrol et:
  - `MSSQL_HOST`
  - `MSSQL_PORT` (genelde 1433)
  - `MSSQL_USERNAME / MSSQL_PASSWORD`
- Firewall / network erişimini kontrol et
- SQL Server’da uzak bağlantı açık mı kontrol et

---

## 4) `SyncAvshocrecatReportJob` truncate sonrası rapor boş görünüyor

### Belirti
- Job çalışırken rapor sayfası kısa süre boş geliyor

### Sebep
- Job önce `truncate` ediyor, sonra insert ediyor (snapshot refresh)

### Çözüm (kısa vadeli)
- Bu davranış “beklenen” ise UI’da loading/refresh göstergesi ekle

### Çözüm (uzun vadeli)
- Atomic refresh yaklaşımı:
  - `avshocrecat_report_tmp` tabloya yaz
  - sonra rename/swap ile ana tabloyu değiştir

---

## 5) SP kolon isimleri değişti, mapping patladı

### Belirti
- Job exception alır
- Bazı alanlar null gelir
- Insert sırasında type mismatch hatası

### Çözüm
- `SyncAvshocrecatReportJob` içinde mapping kontrol et:
  - `Tiger Kody` vs `Tiger Kodu`
  - Boşluklu kolon adları (`Karz alyjy` gibi)

> SP output değişirse job mapping güncellenmelidir.

---

## 6) Numeric alanlar yanlış geliyor (10,25 gibi)

### Belirti
- Decimal alanlarda hatalı değer
- Insert sırasında numeric conversion problemi

### Çözüm
- Job içindeki `toNumeric()` fonksiyonu `,` → `.` normalize ediyor.
- Eğer farklı format geliyorsa (örn `10 000,25`) fonksiyon güncellenmeli.

---

## 7) Timeout / retry loop

### Belirti
- Job yarıda kesiliyor
- Sürekli retry oluyor
- `failed_jobs` doluyor

### Çözüm
- Job timeout’unu yükselt:
  - `SyncAvshocrecatReportJob` için `public int $timeout = 300;`

- Worker parametrelerini uyumlu çalıştır:

```bash
php artisan queue:work --timeout=300 --tries=3
```

- `config/queue.php` `retry_after` artırılabilir:
  - büyük raporlar için 180–300 önerilir

---

## 8) PostgreSQL insert hatası (constraint / type mismatch)

### Belirti
- `SQLSTATE` ile başlayan insert hataları

### Çözüm
- Hedef tablonun kolon tiplerini kontrol et:
  - decimal alanlar
  - nullable alanlar
  - string uzunlukları

- Örnek kontrol:

```sql
\d+ avshocrecat_report
```

---

## 9) Senkron verisi gelmiyor (ama job success)

### Belirti
- Job hata vermiyor
- Tablo güncellenmiyor / boş kalıyor

### Çözüm
- MSSQL kaynak sorgusunu/tabloları kontrol et:
  - `MSSQL_CREDITS_TABLE`
  - `MSSQL_AVSHOCRECAT_PROC`

- `PASPORT` filtresi boş mu?
  - config/env’de pasaport doluysa sadece filtreli sonuç gelir

---

## Komutlar (Hızlı Referans)

### Senkron tetikle

```bash
php artisan sync:run
php artisan sync:run --passport=123456
```

### Worker

```bash
php artisan queue:work
php artisan queue:work --stop-when-empty
```

### Failed job yönetimi

```bash
php artisan queue:failed
php artisan queue:retry all
php artisan queue:flush
```

---

## İyileştirme Önerileri

- `sync_state` veya `sync_logs` tablosu ile:
  - başlama/bitiş zamanı
  - satır sayısı
  - status
  - hata mesajı
  kayıt altına alınabilir.

- Rapor refresh için atomic yaklaşım düşün.

- Büyük veri için chunk size ve worker timeout standartlaştır.

