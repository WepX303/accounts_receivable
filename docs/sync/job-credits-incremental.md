# SyncCreditsJob (MSSQL → PostgreSQL) Detaylı Dokümantasyon

Bu job’un amacı: **MSSQL’deki CREDITS tablosunu** (RV/rowversion ile incremental) **PostgreSQL’deki credits_test** tablosuna **parça parça (chunk)** senkronize etmek ve **local alanları (amount_local/paid_local)** kullanıcı müdahalesi varsa bozmadan korumaktır.

## 1) Ön Koşullar

### Bağlantılar
- `config/database.php` içinde `sqlsrv` bağlantısı (MSSQL)
- `config/database.php` içinde `pgsql` bağlantısı (PostgreSQL)

### Tablolar
- MSSQL: `dbo.CREDITS_TEST` (prod’da `dbo.CREDITS`)
- PGSQL: `credits_test`
- PGSQL: `sync_state` (son RV değerini tutar)

### Konfigürasyon (örnek)
`config/sync.php`:
- `sync.mssql.credits_table` => `dbo.CREDITS_TEST`
- `sync.pgsql.credits_table` => `credits_test`
- `sync.chunk_size` => `1000`

---

## 2) Job Akışı

1. **Bağlantıları alır**
   - `DB::connection('sqlsrv')` ve `DB::connection('pgsql')`
2. **MSSQL DB context’i garantiye alır**
   - `USE [dbName]`
3. **sync_state** tablosundan `lastRv` okur
4. MSSQL’de **RV > lastRv** olan kayıtları **RV sırasıyla** çeker
5. `chunk($chunkSize)` ile satırları parça parça işler
6. Her chunk’ta:
   - `logicalref` listesi çıkarır
   - PG’de mevcut kayıtları (local alanlar + timestamps) tek sorgu ile çeker
   - **local alanlar için koruma kurallarını** uygular
   - payload hazırlar
   - PG’de `upsert` yapar
7. En sonunda `sync_state` içine **maxRvSeen** yazar

---

## 3) Local Alanları Koruma Mantığı

### Kural-1: Yeni kayıt (PG’de yok)
- `amount_local = amount`
- `paid_local = paid`

### Kural-2: Eski kayıt ama local “bakir” ise
- `amount_local` NULL **ve** `amount_updated_at` NULL **ve** MSSQL `amount` doluysa → local’i doldur
- `paid_local` NULL **ve** `paid_updated_at` NULL **ve** MSSQL `paid` doluysa → local’i doldur

> Böylece: kullanıcı local’e dokunduysa (`*_updated_at` dolu) job local’i bozmaz.

---

## 4) Kritik Noktalar

- `ORDER BY RV` şart: yoksa `maxRvSeen` yanlış ilerleyebilir.
- MSSQL kolon isimleri (ör: `NAME_`, `CONTRACT_`) gerçekten böyle mi kontrol et.
- Ödeme tarafında `paid_updated_at` dolduruyorsun ✅
  - Amount local değişecekse `amount_updated_at` da dolmalı.

---

## 5) Tinker Komutları (SyncCreditsJob kontrol)

> Tinker’da satır satır `->` ile yazınca parse error alıyorsun. O yüzden hepsi **tek satır**.

### A) sync_state son değer
```php
$k=app()->environment().'_credits_last_rv'; DB::connection('pgsql')->table('sync_state')->where('key',$k)->first();
```

### B) PG max rv_bigint
```php
DB::connection('pgsql')->table('credits_test')->max('rv_bigint');
```

### C) MSSQL max RV
```php
DB::connection('sqlsrv')->table(config('sync.mssql.credits_table'))->selectRaw('MAX(CONVERT(bigint,RV)) as maxrv')->first();
```

### D) Job’u manuel çalıştır
```php
(new App\Jobs\SyncCreditsJob())->handle();
```

### E) Bir logicalref karşılaştır (MSSQL vs PG)
```php
$id=53145; ['mssql'=>DB::connection('sqlsrv')->table(config('sync.mssql.credits_table'))->selectRaw('LOGICALREF, AMOUNT, PAID, CONVERT(bigint,RV) as rv')->where('LOGICALREF',$id)->first(),'pgsql'=>DB::connection('pgsql')->table('credits_test')->where('logicalref',$id)->first()];
```

### F) Local alanlar ve updated_at durumları
```php
$id=53145; DB::connection('pgsql')->table('credits_test')->where('logicalref',$id)->first(['logicalref','amount','paid','amount_local','paid_local','amount_updated_at','paid_updated_at','rv_bigint']);
```

---