# ⚡ Database Indexes (Performans Rehberi)

Bu doküman, projedeki index’leri (migration’lardan gelen) ve **hangi sorgulara hizmet ettiklerini** açıklar. Ayrıca ihtiyaç halinde eklenebilecek index önerilerini içerir.

> Kapsam:
> - `users`
> - `credits`
> - `credit_payments`
> - `avshocrecat_report`
> - Queue tabloları (`jobs`, `failed_jobs`)

---

## Amaç

- Listeleme/arama ekranlarının hızlı çalışmasını sağlamak
- “Hangi index var, neden var?” sorusuna net cevap vermek
- İleriye dönük index stratejisini standardize etmek

---

## Konum

`docs/database/indexes.md`

---

## Genel Notlar

- Index, okuma hızını artırır ama yazma (insert/update) maliyeti getirir.
- Bu projede senkron job’ları yoğun insert/update yaptığı için **gereksiz index** eklemekten kaçın.
- “En çok kullanılan filtre/sıralama alanları” index için önceliklidir.

---

## 1) `users` Indexleri

Migration: `2014_10_12_000000_create_users_table.php`

### Var olan index/constraint’ler

- `email` → **unique**
- `phonenumber` → **unique**
- `id` → PK (index)

### Neye yarar?

- Login / kullanıcı bulma işlemleri
- Unique constraint ile tekrar kayıt engelleme

---

## 2) `credits` Indexleri

Migration: `2026_01_19_123226_create_credits_table.php`

### Var olan index/constraint’ler

- `logicalref` → PK (primary)
- `rv_bigint` → **index**

Ek migration’lar:

- `paid_updated_at` → **index** (`2026_01_20_122800_add_paid_local_fields...`)
- `amount_updated_at` → **index** (`2026_01_20_123743_add_amount_local_fields...`)

### Neye yarar?

- `rv_bigint` index:
  - Incremental senkron için kritik
  - “son değişen kayıtları bulma” senaryolarında yardımcı

- `*_updated_at` index:
  - “Local değer en son ne zaman değişti?” gibi admin/audit ekranları
  - tarih bazlı filtreleme

### Öneri (opsiyonel)

Eğer UI’da sık kullanılıyorsa, aşağıdaki alanlar için index düşünülebilir:

- `passport`
- `phone`
- `contract`
- `branch`

> ⚠️ Ancak `credits` senkron sırasında yoğun update aldığı için ek index’ler senkron performansını düşürebilir. Önce sorgu ihtiyacını netleştir.

---

## 3) `credit_payments` Indexleri

### Temel index/constraint’ler

Migration: `2026_01_22_170329_create_credit_payments_table.php`

- `id` → PK
- `credit_logicalref` → **index**
- `created_at` → **index**

Ek migration’lar:

- `cp_credit_id_idx` → **index(credit_logicalref, id)** (`2026_01_23_113120_add_credit_payments_lookup_index.php`)
- `change_amount` → **index** (`2026_01_23_152556_add_change_amount...`)

Customer snapshot index’leri (`2026_01_24_141813_add_customer_snapshot...`):
- `customer_contract` → index
- `customer_phone` → index
- `customer_passport` → index
- `branch` → index

Created-by snapshot index’leri (`2026_01_24_143254_add_created_by_snapshot...`):
- `cp_created_by_name_idx` → index
- `cp_created_by_email_idx` → index
- `cp_created_by_phone_idx` → index

### Neye yarar?

- `credit_logicalref`:
  - Bir kredinin ödeme geçmişini hızlı listelemek

- `(credit_logicalref, id)`:
  - “krediye ait en son ödeme” gibi sorgularda çok faydalı
  - özellikle `orderByDesc('id')` ile birlikte

- `created_at`:
  - Tarih aralığında ödeme raporları

- `customer_*` ve `branch`:
  - Ödeme arama/filtreleme ekranları
  - Snapshot alanlar üzerinden geçmiş kayıtları stabil filtrelemek

- `created_by_* snapshot`:
  - “hangi kullanıcı hangi ödemeleri almış?” gibi aramalar

### Öneri (opsiyonel)

- Eğer sık kullanılıyorsa:
  - `method`
  - `created_by`

için index düşünülebilir.

---

## 4) `avshocrecat_report` Indexleri

Migration: `2026_01_19_174944_create_avshocrecat_report_table.php`

### Var olan index’ler

- `pasport_belgisi` → index
- `tiger_kody` → index
- `magazyn` → index

### Neye yarar?

- Rapor ekranında pasaport ile filtreleme
- Tiger kodu ile arama
- Mağaza bazlı raporlar

> Bu tablo snapshot (truncate + insert) güncellendiği için index’ler insert maliyeti getirir ama rapor ekranı için kritiktir.

---

## 5) Queue Tabloları Indexleri

### `jobs`

Migration: `2026_01_19_123027_create_jobs_table.php`

- `queue` → index

Neye yarar?
- Worker belirli bir queue’yı dinlerken hızlı filtreleme

### `failed_jobs`

Migration: `2019_08_19_000000_create_failed_jobs_table.php`

- `uuid` → unique

Neye yarar?
- Failed job kayıtlarının benzersiz olması

---

## 6) Index Seçimi için Pratik Kriterler

Bir alan için index eklemeden önce:

- UI/API’da sık filtreleniyor mu?
- Sık sıralanıyor mu?
- Sorgu “% kaç satırı” döndürüyor? (çok geniş filtrelerde index faydası azalır)
- Yazma yükü (senkron + upsert) çok mu?

> İdeal: Önce gerçek sorguları belirle → sonra index.

---

## 7) Örnek Sorgu – Index’ten Faydalanma

### A) Kredinin ödemelerini hızlı listeleme

- index: `credit_logicalref`, `cp_credit_id_idx`

```sql
select *
from credit_payments
where credit_logicalref = 123
order by id desc
limit 50;
```

### B) Raporu pasaportla filtreleme

- index: `pasport_belgisi`

```sql
select *
from avshocrecat_report
where pasport_belgisi = 'A1234567'
order by id desc;
```

---

## 8) İyileştirme Önerileri

- Sorgu performans sorunlarında:
  - `EXPLAIN (ANALYZE, BUFFERS)` ile planı incele
  - gereksiz index’leri kaldırmayı düşün

- Senkron performansı düşerse:
  - `credits` üzerinde eklenen index’leri tekrar değerlendir
  - chunk size ve worker concurrency (numprocs) ayarla

---

## Özet

- `credits.rv_bigint` incremental sync için kritik
- `credit_payments` index’leri raporlama ve hızlı lookup için zengin
- `avshocrecat_report` index’leri rapor filtreleri için gerekli
- Yeni index eklemeden önce gerçek sorgu ihtiyacını doğrula

