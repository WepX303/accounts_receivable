# 📥 SyncCreditsJob (Incremental Credits Sync)

Bu doküman, `App\Jobs\SyncCreditsJob` job’unun **gerçek koduna birebir uygun** şekilde nasıl çalıştığını açıklar.

> Senkron tipi: **Incremental**
>
> - Kaynak: MSSQL (`sqlsrv`) – `CREDITS_TEST / CREDITS`
> - Hedef: PostgreSQL (`pgsql`) – `credits`
> - Incremental anahtar: MSSQL `RV` (rowversion) → PG `rv_bigint`
> - State tablosu: `sync_state`

---

## Amaç

- MSSQL’deki Credits tablosundaki yeni/değişen satırları **kaldığı yerden** çekmek
- PostgreSQL’de `credits` tablosuna **upsert** ile yazmak
- Local alanları korumak ve gerektiğinde “ilk init” yapmak

---

## Konum

- Job: `app/Jobs/SyncCreditsJob.php`
- Doküman: `docs/sync/job-credits-incremental.md`

---

## Queue Ayarları

Job kuyrukta çalışır (`ShouldQueue`).

```php
public int $tries = 5;
public int $timeout = 120;
```

- `tries=5`: Hata alırsa en fazla 5 kez dener.
- `timeout=120`: Maksimum 120 saniye çalışabilir.

> Worker tarafında da uyumlu çalıştırmak için:
>
> ```bash
> php artisan queue:work --tries=5 --timeout=120
> ```

---

## Bağımlılıklar (ENV/Config)

Job, tablo adlarını ve chunk ayarını config üzerinden alır:

```php
$mssqlTable = config('sync.mssql.credits_table');
$pgTable    = config('sync.pgsql.credits_table');
$chunkSize  = config('sync.chunk_size', 1000);
```

`.env` tarafında genelde şunlara karşılık gelir:

- `MSSQL_CREDITS_TABLE=BPA.dbo.CREDITS_TEST`
- `PG_CREDITS_TABLE=credits`

---

## MSSQL DB Context Garantisi

Job, MSSQL tarafında doğru DB’de olduğundan emin olmak için `USE [db]` çalıştırır:

```php
$dbName = config('database.connections.sqlsrv.database');
$sqlsrv->statement("USE [$dbName]");
```

Bu yaklaşım özellikle:

- bağlantı farklı DB’ye düşerse
- stored proc / tablo isimleri farklı DB’de olursa

gibi senaryolarda karışıklığı azaltır.

---

## Incremental State Key (Ortam Bazlı)

State anahtarı ortam bazlı oluşturulur:

```php
$stateKey = app()->environment() . '_credits_last_rv';
```

Örnek:

- local: `local_credits_last_rv`
- production: `production_credits_last_rv`

### Neden?

- Test/Prod aynı PostgreSQL’i paylaşıyorsa “state karışmasın” diye.

---

## State Okuma

```php
$stateRow = $pgsql->table('sync_state')->where('key', $stateKey)->first();
$lastRv   = $stateRow?->value ? (int) $stateRow->value : 0;
```

- State yoksa `lastRv=0` kabul edilir.

---

## MSSQL Sorgusu

Job, MSSQL’de `RV` alanını bigint’e çevirerek `rv_bigint` üretir:

```php
$query = $sqlsrv
  ->table($mssqlTable)
  ->selectRaw('*, CONVERT(bigint, RV) as rv_bigint')
  ->whereRaw('CONVERT(bigint, RV) > ?', [$lastRv])
  ->orderByRaw('RV');
```

### Önemli Notlar

- Filter: `RV > lastRv` → sadece yeni/değişen kayıtlar
- Order: `ORDER BY RV` → state güncellemesi güvenli olsun

---

## Chunk Akışı

Veri chunk’lar halinde işlenir:

```php
$query->chunk($chunkSize, function ($rows) { ... });
```

Chunk içinde iş akışı:

1. Chunk’taki `LOGICALREF` listesi çıkarılır
2. PG’den mevcut kayıtlar çekilir (local alanları korumak için)
3. Payload hazırlanır
4. PG’ye `upsert` yapılır

---

## Mevcut Kayıtları Çekme

Job, mevcut satırlar için sadece gerekli alanları çekiyor:

```php
$existingRows = $pgsql->table($pgTable)
  ->select(['logicalref','amount_local','paid_local','amount_updated_at','paid_updated_at','created_at'])
  ->whereIn('logicalref', $logicalRefs)
  ->get();
```

Bu map, **local alanları korumak** ve **created_at** değerini bozmamak için kullanılır.

---

## Local Alan Kuralları (Kodun Asıl Kritik Kısmı)

### Local alanlar neden var?

- MSSQL’den gelen `amount/paid` “source” kabul edilir.
- Uygulama içinde düzeltme/uyarlama için `amount_local/paid_local` kullanılır.

### KURAL-1: Yeni kayıt → local init

Eğer kayıt PG’de yoksa:

- `amount_local = amount`
- `paid_local = paid`

Bu sayede local sistem ilk kez “başlangıç değerlerini” alır.

### KURAL-2: Eski kayıt ama local “bakir” ise doldur

Mevcut kayıtta:

- `amount_updated_at` **null** → amount_local hiç elle dokunulmamış
- `paid_updated_at` **null** → paid_local hiç elle dokunulmamış

Ve eğer local değer **hala null** ise, remote doluysa local doldurulur:

- `amount_local == null` ve `amount_updated_at == null` ve `amount != null` → `amount_local = amount`
- `paid_local == null` ve `paid_updated_at == null` ve `paid != null` → `paid_local = paid`

> Bu, “local alanlar sonradan eklendi / geçmiş veri eksik kaldı” gibi senaryolarda otomatik toparlama sağlar.

---

## Payload Alanları

Job, MSSQL kolonlarını hedef PG kolonlarına map eder.

Örnek eşlemeler:

- `LOGICALREF` → `logicalref` (PK)
- `NAME_` → `name`
- `PASSPORT_` → `passport`
- `CONTRACT_` → `contract`
- `DATE_` → `date_`
- `RV` → `rv_bigint`

Ayrıca:

- `created_at`: mevcut kayıtta eski `created_at` korunur
- `updated_at`: her senkron run’ında `now()` set edilir

---

## Upsert Stratejisi

Upsert anahtarı:

- `logicalref`

```php
$pgsql->table($pgTable)->upsert(
  $payload,
  ['logicalref'],
  [
    'branch','name','passport','phone','contract','date_',
    'amount','paid','willpaiddate','willpaidamount','note','lastnoteddate',
    'status','active','initiator_i','clientref','custstatus','assurance','ctype',
    'cardno','fishno','manager','confirmedby','gstatus',
    'rv_bigint',
    'amount_local','paid_local',
    'updated_at',
  ]
);
```

### created_at neden update listesinde yok?

- Kodda `created_at` payload’da var ama update listesinde yok.
- Böylece var olan kayıtların `created_at`’ı değişmez.

---

## maxRvSeen ve State Güncelleme

Job, süreç boyunca en büyük rv değerini takip eder:

```php
$maxRvSeen = $lastRv;
...
if ($rv > $maxRvSeen) $maxRvSeen = $rv;
```

İş bitince `sync_state` güncellenir:

- varsa update (created_at korunur)
- yoksa insert

```php
if ($existingState) {
  update(['value' => (string) $maxRvSeen, 'updated_at' => now()]);
} else {
  insert(['key'=>..., 'value'=>..., 'created_at'=>now(), 'updated_at'=>now()]);
}
```

### Önemli

- State **en sonda** yazılır → veri kaçırma riskini azaltır.
- Chunk içinde state güncellenmez.

---

## Hata Senaryoları ve Çözümleri

### 1) MSSQL RV dönüşüm hatası

- `RV` beklenenden farklı tipteyse `CONVERT(bigint, RV)` patlayabilir.
- Çözüm: MSSQL tarafında `RV`’nin rowversion/timestamp olduğundan emin ol.

### 2) Worker timeout

- Job timeout 120, worker daha düşükse job kesilebilir.

Öneri:

```bash
php artisan queue:work --timeout=120 --tries=5
```

### 3) State yanlış ilerledi

Belirti:

- bazı kayıtlar gelmiyor

Çözüm:

- `sync_state` içinde ilgili key’in value’sunu kontrollü şekilde geri çek
- job’u tekrar çalıştır

---

## Performans Notları

- PG tarafında `rv_bigint` indexli (migration’da var) → filtering/sorting yardımcı olur
- MSSQL tarafında `RV` rowversion genelde index desteklidir
- `chunk_size` büyükse RAM artar, küçükse DB round-trip artar

Pratik aralık:

- 500 – 2000

---

## İyileştirme Önerileri

- Senkron logları için `sync_logs` tablosu:
    - start/end time
    - rows processed
    - max rv
    - status + error

- MSSQL tarafında sadece gerekli kolonları select etmek (şu an `*` çekiliyor)

- Upsert payload içinde `date_` / datetime alanlarında format standardı testleri

---

## Özet

- Incremental filtre `RV > lastRv`
- Ortam bazlı state key ile test/prod karışmaz
- Local alanlar:
    - yeni kayıt → init
    - eski kayıt ama bakir local → remote’dan doldur
- Upsert ile insert/update aynı anda
- State, job sonunda tek sefer güncellenir
