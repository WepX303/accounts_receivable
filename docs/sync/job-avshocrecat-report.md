# 📊 SyncAvshocrecatReportJob Dokümantasyonu

Bu doküman `App\Jobs\SyncAvshocrecatReportJob` job’unun ne yaptığını, hangi konfigürasyonları kullandığını, veri akışını ve dikkat edilmesi gereken noktaları detaylı şekilde açıklar.

---

## 🎯 Amaç

`SyncAvshocrecatReportJob` şu işi yapar:

1. MSSQL tarafında belirli bir Stored Procedure’ü (`AVSHOCRECAT_TEST` / `AVSHOCRECAT`) çalıştırır.
2. Dönen rapor verisini PostgreSQL tarafındaki hedef rapor tablosuna yazar.
3. Yazma işlemi **snapshot mantığında** çalışır:
   - Önce hedef tablo **truncate** edilir.
   - Sonra tüm rapor verisi yeniden **insert** edilir.

Bu yaklaşım rapor tabloları için idealdir çünkü:
- “anlık durum” (snapshot) istenir,
- incremental takip etmek yerine her seferinde rapor yenilenir.

---

## 🧩 Job Konumu

**Dosya:** `app/Jobs/SyncAvshocrecatReportJob.php`

---

## 🧵 Queue Ayarları

Job kuyrukta çalışır (`ShouldQueue`).

```php
public int $tries = 3;
public int $timeout = 300;
```

### `tries`
- Job hata alırsa **en fazla 3 kez** tekrar denenir.

### `timeout`
- Job maksimum **300 saniye** (5 dakika) çalışabilir.
- Daha uzun sürebilecek senaryolarda artırılabilir.

> ⚠️ Not: Queue worker tarafında ayrıca `--timeout` parametresi de job timeout’unu etkiler.

---

## ⚙️ Kullanılan Config Değerleri

Job, tablo/proc adlarını ve chunk ayarını **config** üzerinden alır:

```php
$proc      = config('sync.mssql.avshocrecat_proc');
$pasport   = (string) config('sync.mssql.avshocrecat_pasport', '');
$pgTable   = config('sync.pgsql.avshocrecat_table');
$chunkSize = (int) config('sync.chunk_size', 1000);
```

### Bu değerler neyi ifade eder?

| Config | Açıklama | Örnek |
|------|----------|------|
| `sync.mssql.avshocrecat_proc` | MSSQL’de çalıştırılacak Stored Procedure | `BPA.dbo.AVSHOCRECAT_TEST` |
| `sync.mssql.avshocrecat_pasport` | Opsiyonel PASPORT filtresi | boşsa tümü |
| `sync.pgsql.avshocrecat_table` | PG hedef tablo adı | `avshocrecat_report` |
| `sync.chunk_size` | Insert için batch/chunk boyutu | `1000` |

> Bu tasarım sayesinde test/canlı geçişinde yalnızca `.env/config` değişir, kod değişmez.

---

## 🔄 Veri Akışı

### 1) MSSQL Stored Procedure çağrısı

```php
$rows = DB::connection('sqlsrv')->select(
    "EXEC {$proc} @PASPORT = ?",
    [$pasport]
);
```

- `sqlsrv` connection üzerinden SP çalıştırılır.
- Parametreli çağrı kullanıldığı için injection riski azaltılır.

> ⚠️ Not: `$proc` string interpolation ile yazıldığı için **proc adı config’den gelmeli ve güvenilir olmalı**.

---

### 2) PostgreSQL hedef tabloyu sıfırlama (snapshot)

```php
DB::connection('pgsql')->table($pgTable)->truncate();
```

- Job her çalıştığında rapor tablosu komple sıfırlanır.
- Bu nedenle tablo rapor amaçlıdır (transactional veri değil).

> ⚠️ Önemli: Truncate sırasında tablo kilitlenebilir. Rapor tablosunu okuyan ekranlar varsa “okuma anında boş” görme ihtimali vardır.

**İyileştirme fikri:**
- Transaction içinde delete/insert,
- veya temp tablo + swap,
- veya “report_version” mantığı.

---

### 3) Row mapping ve insert (chunk)

SP çıktısı kolon adları (Türkmence/Türkmence boşluklu) geldiği için job içinde **alan eşlemesi** yapılır:

Örnek:

- MSSQL: `Karz alyjy` → PG: `karz_alyjy`
- MSSQL: `Telefon belgisi` → PG: `telefon_belgisi`
- MSSQL: `Tiger Kody` / `Tiger Kodu` → PG: `tiger_kody`

#### Numeric dönüşümleri
Aylık ve bakiye alanları gibi numeric değerler `toNumeric()` ile normalize edilir:

- `,` → `.` dönüşümü (ör. `10,25` → `10.25`)
- numeric değilse null

#### Date dönüşümü
`toDate()` sadece `YYYY-MM-DD` formatını kabul eder.

---

## 🧠 Chunk Mantığı

Job önce buffer’a doldurur, buffer `$chunkSize` kadar olunca insert eder:

```php
if (count($buffer) >= $chunkSize) {
    DB::connection('pgsql')->table($pgTable)->insert($buffer);
    $buffer = [];
}
```

En sonda kalan buffer varsa tekrar insert edilir.

### Neden gerekli?
- Tek seferde binlerce satırı insert etmek RAM ve SQL paket limitlerini zorlayabilir.
- Chunk, daha stabil ve hızlı çalışır.

---

## 🧰 Helper Fonksiyonlar

### `toNumeric($value)`

Amaç: MSSQL’den dönen numeric alanları string/decimal uyumlu hale getirmek.

Kurallar:
- null/boş → null
- numeric ise string döner
- `,` içeriyorsa `.` ile değiştirir
- yine numeric değilse null

> Not: Bu fonksiyon string döndürüyor; Laravel insert sırasında decimal’a cast edilebilir.

### `toDate($value)`

Amaç: `date` kolonu için güvenli format.

Kurallar:
- null/boş → null
- sadece `YYYY-MM-DD` formatı kabul edilir
- diğer formatlar → null

---

## ⚠️ Dikkat Edilmesi Gerekenler

### 1) Proc adı interpolasyonu

```php
"EXEC {$proc} @PASPORT = ?"
```

- Parametre sadece `PASPORT` için uygulanıyor.
- Proc adı config’den geldiği için **güvenilir kaynak** olmalı.

### 2) Truncate + Insert sırasında boş tablo
- UI rapor ekranları varsa job çalışırken kısa süre boş görünme ihtimali vardır.

### 3) Büyük veri setleri
- `$rows` tüm sonuçları belleğe alır.
- Eğer rapor çok büyürse “streaming” yaklaşımı gerekebilir.

### 4) Dil / kolon isimleri
- SP çıktısındaki kolon adları boşluklu geldiği için mapping kritik.
- SP değişirse job mapping de güncellenmeli.

---

## ✅ Önerilen İyileştirmeler

### 1) “Atomic refresh” (temp tablo)
- `avshocrecat_report_tmp` oluştur
- Tüm insert tmp’ye
- sonra rename/swap ile ana tabloyu değiştir

### 2) Job Log / Sync State
- Başlangıç-bitiş zamanı
- Kaç satır insert edildi
- Son durum (success/failed)

Bu log, `sync_state` veya ayrı bir `sync_logs` tablosuna yazılabilir.

### 3) `PASPORT` override
Şu an pasaport config’den geliyor.
Eğer `sync:run --passport=` ile de geliyorsa:
- CLI öncelik mi, env öncelik mi netleştirilmeli.

---

## 🧪 Test Senaryoları

- PASPORT boş → tüm rapor doldurulmalı
- PASPORT dolu → sadece filtreli kayıtlar gelmeli
- Numeric alanlar `10,25` formatında → doğru normalize edilmeli
- `Tolejek senesi` farklı formatta → null olmalı
- SP boş sonuç döndürürse → truncate sonrası tablo boş kalmalı (beklenen davranış)

---

## 🔗 İlişkili Dosyalar

- `config/sync.php` (varsayılan ayarlar)
- `.env` (MSSQL/PG tablo & proc isimleri)
- `database/migrations/...create_avshocrecat_report_table.php`
- `App\Console\Commands\SyncRunCommand`

---

## 📝 Özet

`SyncAvshocrecatReportJob` rapor verisini **snapshot** mantığında yenileyen bir job’dur:

- MSSQL SP çağırır
- PG rapor tablosunu truncate eder
- Sonuçları chunk’lar halinde insert eder
- Numeric/date alanları normalize eder

Bu job, rapor sayfasının her zaman “en güncel snapshot” veriyi göstermesini sağlar.

