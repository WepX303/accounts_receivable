# 🗄️ Database Migrations Dokümantasyonu

Bu doküman, projedeki tüm migration dosyalarını **tek bir yerde**, **detaylı** ve **okunabilir** şekilde açıklar.

> Notlar
> - Bu projede hem **MySQL/Default connection** hem de **PostgreSQL (pgsql)** kullanılıyor.
> - `avshocrecat_report` tablosu migration içinde açıkça `Schema::connection('pgsql')` ile oluşturuluyor.
> - Diğer tablolar varsayılan DB bağlantısında oluşturuluyor (genelde `.env` `DB_CONNECTION`).

---

## 📌 İçindekiler

- [users](#1-users-tablosu)
- [password_reset_tokens](#2-password_reset_tokens-tablosu)
- [failed_jobs](#3-failed_jobs-tablosu)
- [personal_access_tokens](#4-personal_access_tokens-tablosu)
- [jobs](#5-jobs-tablosu-queue)
- [credits_test](#6-credits_test-tablosu)
- [sync_state](#7-sync_state-tablosu)
- [avshocrecat_report (pgsql)](#8-avshocrecat_report-tablosu-pgsql)
- [credits_test ek alanlar](#9-credits_test-ek-alanlar-local-paid--amount)
- [credit_payments](#10-credit_payments-tablosu)
- [credits_test amount tipi değişimi](#11-credits_test-amount-tip-değişimi)
- [credit_payments index ve ek alanlar](#12-credit_payments-index--ek-alanlar)

---

## 1) `users` Tablosu

**Migration:** `2014_10_12_000000_create_users_table.php`

### Amaç
Sistem kullanıcılarını, rollerini ve login ile ilgili temel alanları tutar.

### Şema
- `id` (PK)
- `firstname` (string, zorunlu)
- `lastname` (string, zorunlu)
- `email` (string, **unique**, zorunlu)
- `phonenumber` (string, **unique**, zorunlu)
- `position` (string, nullable)
- `role` (string, default: `UserRoleEnum::USER->value`)
- `status` (boolean, default: `true`) — yorum: `1=Active, 0=Deactive`
- `password` (string, zorunlu)
- `token` (string, nullable)
- `token_expires_at` (timestamp, nullable)
- `remember_token` (string, nullable)
- `created_at`, `updated_at`

### Index / Unique
- Unique: `email`
- Unique: `phonenumber`

### Kullanım Notları
- `role` alanı `UserRoleEnum` ile uyumlu çalışır.
- `token` + `token_expires_at` alanları (manual auth veya özel token sistemi) için ayrılmıştır.

---

## 2) `password_reset_tokens` Tablosu

**Migration:** `2014_10_12_100000_create_password_reset_tokens_table.php`

### Amaç
Şifre sıfırlama token’larını tutar.

### Şema
- `email` (string, **primary key**)
- `token` (string)
- `created_at` (timestamp, nullable)

### Index / PK
- PK: `email`

### Kullanım Notları
- Laravel’in klasik password reset tablosu yapısına benzer.
- Email PK olduğu için her email için **tek aktif token** tutma yaklaşımına uygundur.

---

## 3) `failed_jobs` Tablosu

**Migration:** `2019_08_19_000000_create_failed_jobs_table.php`

### Amaç
Queue üzerinde çalışan job’lar başarısız olursa burada loglanır.

### Şema
- `id` (PK)
- `uuid` (string, **unique**)
- `connection` (text)
- `queue` (text)
- `payload` (longText)
- `exception` (longText)
- `failed_at` (timestamp, default: current)

### Index / Unique
- Unique: `uuid`

---

## 4) `personal_access_tokens` Tablosu

**Migration:** `2019_12_14_000001_create_personal_access_tokens_table.php`

### Amaç
Laravel Sanctum/Token tabanlı auth yapıları için kişisel erişim token’larını tutar.

### Şema
- `id` (PK)
- `tokenable_type` (string)
- `tokenable_id` (unsignedBigInt)
- `name` (string)
- `token` (string(64), **unique**)
- `abilities` (text, nullable)
- `last_used_at` (timestamp, nullable)
- `expires_at` (timestamp, nullable)
- `created_at`, `updated_at`

### Index / Unique
- `morphs('tokenable')` otomatik index üretir (`tokenable_type`, `tokenable_id`).
- Unique: `token`

---

## 5) `jobs` Tablosu (Queue)

**Migration:** `2026_01_19_123027_create_jobs_table.php`

### Amaç
Queue driver olarak **database** kullanıldığında bekleyen job kayıtlarını tutar.

### Şema
- `id` (bigIncrements, PK)
- `queue` (string, index)
- `payload` (longText)
- `attempts` (unsignedTinyInteger)
- `reserved_at` (unsignedInteger, nullable)
- `available_at` (unsignedInteger)
- `created_at` (unsignedInteger)

### Index
- Index: `queue`

### Kullanım Notları
- `reserved_at / available_at / created_at` unix timestamp olarak saklanır (int).

---

## 6) `credits_test` Tablosu

**Migration:** `2026_01_19_123226_create_credits_test_table.php`

### Amaç
MSSQL kaynak tablodan senkronize edilen **credits** verisini (test ortamı) uygulama tarafında tutar.

### Şema
**Primary Key**
- `logicalref` (bigInteger, **primary key**)

**Müşteri ve sözleşme bilgileri**
- `branch` (string, nullable)
- `name` (text, nullable)
- `passport` (string, nullable)
- `phone` (string, nullable)
- `contract` (string, nullable)

**Kredi/tahsilat alanları**
- `date_` (timestamp, nullable)
- `amount` (double -> sonradan decimal’a çevrildi) (nullable)
- `paid` (decimal(18,2), nullable)
- `willpaiddate` (timestamp, nullable)
- `willpaidamount` (integer, nullable)

**Not ve durum**
- `note` (text, nullable)
- `lastnoteddate` (timestamp, nullable)
- `status` (string, nullable)
- `active` (boolean, default true)

**Ek alanlar (operasyonel)**
- `initiator_i` (integer, nullable)
- `clientref` (string, nullable)
- `custstatus` (string, nullable)
- `assurance` (string, nullable)
- `ctype` (integer, nullable)
- `cardno` (string, nullable)
- `fishno` (string, nullable)
- `manager` (string, nullable)
- `confirmedby` (string, nullable)
- `gstatus` (string, nullable)

**Incremental senkron takip alanı**
- `rv_bigint` (bigInteger, default 0, **index**)

**Timestamps**
- `created_at`, `updated_at`

### Index
- Index: `rv_bigint`

### Kullanım Notları
- `rv_bigint`, MSSQL `rowversion` alanının bigint’e çevrilmiş halini temsil eder.
- Incremental job’lar bu alan üzerinden “son görülen” değerden devam eder.

---

## 7) `sync_state` Tablosu

**Migration:** `2026_01_19_123226_create_sync_state_table.php`

### Amaç
Senkronizasyon için küçük ve esnek bir “key-value state” tablosudur.

### Şema
- `key` (string, **primary key**)
- `value` (text, nullable)
- `created_at`, `updated_at`

### Kullanım Örneği
- `key`: `credits_last_rv`
- `value`: `123456789`

> Bu tabloda value text olduğu için istenen formatta veri saklanabilir (json/string/int).

---

## 8) `avshocrecat_report` Tablosu (PGSQL)

**Migration:** `2026_01_19_174944_create_avshocrecat_report_table.php`

### Amaç
MSSQL Stored Procedure `AVSHOCRECAT(_TEST)` çıktısını PostgreSQL tarafında rapor tablosu olarak saklar.

### Bağlantı
- Bu tablo **PostgreSQL** üzerinde oluşturulur:

```php
Schema::connection('pgsql')->create('avshocrecat_report', ...)
```

### Şema
- `id` (bigIncrements, PK)

**Kimlik / müşteri alanları**
- `magazyn` (string(255), nullable)
- `karz_alyjy` (text, nullable)
- `telefon_belgisi` (string(255), nullable)
- `pasport_belgisi` (string(255), nullable)
- `sertnama_nomeri` (string(255), nullable)
- `tiger_kody` (string(255), nullable)

**Finansal alanlar**
- `kt_cykdajy` (decimal(18,2), nullable)
- `dt_girdeji` (decimal(18,2), nullable)

**Aylık alanlar**
- `m1..m6` (decimal(18,2), nullable)

**Özet alanlar**
- `galyndy` (decimal(18,2), nullable)
- `aylyk_tolegi` (decimal(18,2), nullable)

**Tarih alanları (string olarak)**
- `karz_alan_senesi` (string(30), nullable)
- `gutaryan_senesi` (string(30), nullable)

**Kategori ve açıklamalar**
- `kategoriyasy` (string(255), nullable)
- `maglumat` (string(255), nullable)
- `bellik` (string(255), nullable)

**Ek tarih ve durum**
- `tolejek_senesi` (date, nullable)
- `statusy` (string(255), nullable)

**Timestamps**
- `created_at`, `updated_at`

### Index
- Index: `pasport_belgisi`
- Index: `tiger_kody`
- Index: `magazyn`

### Kullanım Notları
- Bu tablo genelde `SyncAvshocrecatReportJob` ile **truncate + insert** mantığında güncellenir.
- `karz_alan_senesi` ve `gutaryan_senesi` string tutulmuş (format kaynağa göre değişiyor olabilir). Eğer standardize edilecekse datetime/date dönüşümü yapılabilir.

---

## 9) `credits_test` Ek Alanlar (Local Paid / Amount)

Bu bölüm, `credits_test` tablosuna sonradan eklenen “local override” alanlarını açıklar.

### 9.1 Paid Local Alanları

**Migration:** `2026_01_20_122800_add_paid_local_fields_to_credits_test_table.php`

#### Amaç
MSSQL’den gelen `paid` değerine ek olarak, uygulama içinde manuel düzenlenebilen lokal tahsilat alanlarını tutar.

#### Eklenen Alanlar
- `paid_local` (decimal(18,2), nullable)
- `paid_updated_by` (unsignedBigInteger, nullable) → FK `users.id`
- `paid_updated_at` (timestamp, nullable)
- `paid_note` (text, nullable)

#### İlişki
- `paid_updated_by` → `users.id` (nullOnDelete)

#### Index
- Index: `paid_updated_at`

---

### 9.2 Amount Local Alanları

**Migration:** `2026_01_20_123743_add_amount_local_fields_to_credits_test_table.php`

#### Amaç
MSSQL’den gelen `amount` değerine ek olarak, uygulama içinde manuel düzenlenebilen lokal tutar alanlarını tutar.

#### Eklenen Alanlar
- `amount_local` (decimal(18,2), nullable)
- `amount_updated_by` (unsignedBigInteger, nullable) → FK `users.id`
- `amount_updated_at` (timestamp, nullable)
- `amount_note` (text, nullable)

#### İlişki
- `amount_updated_by` → `users.id` (nullOnDelete)

#### Index
- Index: `amount_updated_at`

---

## 10) `credit_payments` Tablosu

**Migration:** `2026_01_22_170329_create_credit_payments_table.php`

### Amaç
Kredi tahsilat hareketlerini (ödeme kayıtlarını) tutar.

### Şema
- `id` (PK)

**Bağlantı**
- `credit_logicalref` (unsignedBigInteger, index) → FK `credits_test.logicalref`

**Ödeme detayları**
- `pay_amount` (decimal(18,2))
- `method` (string(10)) — `cash|card|mixed`
- `cash_amount` (decimal(18,2), default 0)
- `card_amount` (decimal(18,2), default 0)

**Önce/Sonra snapshot alanları**
- `old_amount_local` (decimal(18,2), nullable)
- `new_amount_local` (decimal(18,2), nullable)
- `old_paid_local` (decimal(18,2), nullable)
- `new_paid_local` (decimal(18,2), nullable)

**Not**
- `note` (text, nullable)

**Audit**
- `created_by` (unsignedBigInteger, nullable) → FK `users.id`
- `created_at` (timestamp, default current)

### Foreign Keys
- `credit_logicalref` → `credits_test.logicalref` (cascadeOnDelete)
- `created_by` → `users.id` (nullOnDelete)

### Index
- Index: `credit_logicalref`
- Index: `created_at`

### Kullanım Notları
- Bu tablo, ödeme geçmişini sakladığı için **append-only** mantığında kullanılmalıdır (gerekmedikçe update değil insert).
- `created_at` manuel current ile set edilmiş, `updated_at` yok.

---

## 11) `credits_test` Amount Tip Değişimi

**Migration:** `2026_01_23_112947_change_amount_type_in_credits_test_table.php`

### Amaç
`credits_test.amount` alanını daha güvenli finans tipi olan `decimal(18,2)` yapmaya dönüştürür.

### Değişim
- Up:
  - `amount`: `double` → `decimal(18,2)`
- Down:
  - `amount`: `decimal(18,2)` → `double`

### Kullanım Notları
- Finansal veriler için `double` yerine `decimal` tercih edilmesi doğru yaklaşımdır.

---

## 12) `credit_payments` Index & Ek Alanlar

Bu bölüm, `credit_payments` tablosuna eklenen index/alanları kapsar.

### 12.1 Lookup Index

**Migration:** `2026_01_23_113120_add_credit_payments_lookup_index.php`

- Eklenen composite index:
  - `cp_credit_id_idx` → (`credit_logicalref`, `id`)

**Amaç:**
- Bir krediye ait ödemeleri hızlı listelemek (özellikle `WHERE credit_logicalref=? ORDER BY id DESC/ASC`).

---

### 12.2 Change Amount Alanı

**Migration:** `2026_01_23_152556_add_change_amount_to_credit_payments_table.php`

- Eklenen alan:
  - `change_amount` (decimal(18,2), default 0)
- Eklenen index:
  - Index: `change_amount`

**Amaç:**
- Para üstü / düzeltme farkı gibi değerleri ayrı saklamak.

---

### 12.3 Customer Snapshot Alanları

**Migration:** `2026_01_24_141813_add_customer_snapshot_to_credit_payments_table.php`

#### Eklenen Alanlar
- `customer_name` (string(255), nullable)
- `customer_phone` (string(50), nullable)
- `customer_passport` (string(50), nullable)
- `customer_contract` (string(50), nullable)
- `branch` (string(50), nullable)

#### Index
- Index: `customer_contract`
- Index: `customer_phone`
- Index: `customer_passport`
- Index: `branch`

#### Amaç
- Ödeme kaydı oluşturulduğu andaki müşteri bilgilerini “snapshot” olarak saklamak.
- Müşteri bilgileri `credits_test` tablosunda değişse bile geçmiş ödeme kayıtları etkilenmez.

---

### 12.4 Created By Snapshot Alanları

**Migration:** `2026_01_24_143254_add_created_by_snapshot_to_credit_payments_table.php`

#### Eklenen Alanlar
- `created_by_name` (string(255), nullable)
- `created_by_email` (string(255), nullable)
- `created_by_phone` (string(50), nullable)

#### Index
- `cp_created_by_name_idx` → `created_by_name`
- `cp_created_by_email_idx` → `created_by_email`
- `cp_created_by_phone_idx` → `created_by_phone`

#### Amaç
- Ödemeyi yapan kullanıcı bilgilerini snapshot olarak saklamak.
- Kullanıcı kaydı silinse/degisse bile ödeme kaydındaki görünüm korunur.

---

## ✅ Genel Öneriler (Best Practices)

- **Connection standardı:** Eğer projede iki DB aktifse (default + pgsql), tabloların hangi DB’de olduğunu dokümantasyonda net tutmak çok önemlidir.
- **Naming:** `sync_state` key/value yapısı ok ama ileride daha gelişmiş state gerekiyorsa `sync_states` gibi daha açıklayıcı bir tabloya geçilebilir.
- **Snapshot yaklaşımı:** `credit_payments` için snapshot alanları doğru karar; raporlamayı kolaylaştırır.
- **Decimal standardı:** Finans alanlarının tamamında `decimal(18,2)` kullanımı standardize edilebilir (`willpaidamount` gibi int alanlar gözden geçirilebilir).

---