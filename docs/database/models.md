# 🧩 Models Dokümantasyonu

Bu doküman projedeki temel modelleri (Eloquent) detaylı şekilde açıklar:

- `User`
- `Credit` (credits)
- `CreditPayment` (credit_payments)

Her model için:

- Tablo ve primary key bilgisi
- `$fillable`, `$casts`, `$hidden`
- İlişkiler (relationships)
- Accessor / computed alanlar
- Kullanım notları (best practices)

---

## 1) `User` Modeli

**Dosya:** `app/Models/User.php`

### Amaç

Sistem kullanıcılarını temsil eder. Auth işlemleri için `Authenticatable` tabanlıdır ve Sanctum token desteği içerir.

### Trait’ler

- `HasApiTokens` (Sanctum)
- `HasFactory`
- `Notifiable`

### Mass Assignment (`$fillable`)

Aşağıdaki alanlar `User::create()` / `update()` ile güvenli şekilde doldurulabilir:

- `firstname`
- `lastname`
- `email`
- `phonenumber`
- `position`
- `role`
- `status`
- `password`
- `token`
- `token_expires_at`

### Gizlenen Alanlar (`$hidden`)

- `password`
- `remember_token`

> Bu alanlar JSON çıktısında görünmez.

### Casts (`$casts`)

- `role` → `UserRoleEnum::class`
- `token_expires_at` → `datetime`

> `role` enum cast sayesinde `UserRoleEnum::ADMIN` gibi nesne olarak kullanılabilir.

### Accessor’lar

#### `full_name`

```php
$user->full_name
```

- `firstname` + `lastname` alanlarını birleştirir.
- Trim kullanıldığı için gereksiz boşlukları temizler.

### Kullanım Notları

- Şifre her zaman `Hash::make()` ile yazılmalı.
- `token` / `token_expires_at` alanları manuel auth için kullanılabilir.

---

## 2) `Credit` Modeli

**Dosya:** `app/Models/Credit.php`

### Amaç

MSSQL’den senkronize edilen kredi kayıtlarını (test ortam tablosu) temsil eder.

### Tablo

- `protected $table = 'credits';`

### Primary Key

- `protected $primaryKey = 'logicalref';`
- `public $incrementing = false;`
- `protected $keyType = 'int';`

> `logicalref` bigInteger primary key olduğu için incrementing kapatılmış.

### Mass Assignment (`$fillable`)

Bu modelde `$fillable` sadece **local override** alanlarını kapsar:

**Paid Local alanları**

- `paid_local`
- `paid_note`
- `paid_updated_by`
- `paid_updated_at`

**Amount Local alanları**

- `amount_local`
- `amount_note`
- `amount_updated_by`
- `amount_updated_at`

> Bu yaklaşım doğru: MSSQL’den gelen alanlar uygulama içinde kazara update edilmesin.

### Casts (`$casts`)

**Finans alanları**

- `amount`, `paid`, `amount_local`, `paid_local` → `decimal:2`

**Tarih alanları**

- `paid_updated_at`, `amount_updated_at` → `datetime`
- `date_`, `willpaiddate`, `lastnoteddate` → `datetime`

**Boolean**

- `active` → `boolean`

### İlişkiler (Relationships)

#### 2.1 Paid Update User

```php
$credit->paidUpdatedByUser
```

- `belongsTo(User::class, 'paid_updated_by')`

#### 2.2 Amount Update User

```php
$credit->amountUpdatedByUser
```

- `belongsTo(User::class, 'amount_updated_by')`

#### 2.3 Payments

```php
$credit->payments
```

- `hasMany(CreditPayment::class, 'credit_logicalref', 'logicalref')`
- Varsayılan sıralama: `orderByDesc('id')`

> Bu sayede kredi detay sayfasında ödemeler en yeni en üstte gelir.

### Accessor / Computed Alanlar

#### 2.4 `local_remaining`

Sadece **local** değerlerden kalan borcu hesaplar.

```php
$credit->local_remaining
```

Kurallar:

- `amount_local` veya `paid_local` null ise `null` döner
- `remaining = amount_local - paid_local`
- Negatif çıkarsa 0’a sabitlenir
- 2 decimal’e round edilir

> Bu alan “local borç kalan” hesaplamasıdır; MSSQL orijinal değerleriyle karışmaz.

#### 2.5 `local_closed`

Kredinin local olarak kapalı sayılıp sayılmayacağını verir.

```php
$credit->local_closed
```

Kurallar:

- `amount_local` veya `paid_local` null ise `true` döner (kapalı gibi kabul)
- `paid >= total - 0.01` toleransı ile kapalı sayar

> 0.01 toleransı float/decimal rounding farklarını absorbe eder.

### Kullanım Notları

- Local alanlar boşsa “kapalı” kabul edilmesi iş kuralıdır; raporlamada dikkat edilmelidir.
- Eğer local alanlar null iken “kapalı değil” kabul edilecekse `getLocalClosedAttribute` kuralı değiştirilmelidir.

---

## 3) `CreditPayment` Modeli

**Dosya:** `app/Models/CreditPayment.php`

### Amaç

Kredi ödeme hareketlerini (tahsilat kayıtlarını) tutar.

### Tablo

- `protected $table = 'credit_payments';`

### Timestamps

- `public $timestamps = false;`

> Bu modelde sadece `created_at` alanı manuel set ediliyor. `updated_at` yok.

### Mass Assignment (`$fillable`)

Bu model; hem ödeme detaylarını hem de snapshot alanlarını birlikte tutar.

**Bağlantı**

- `credit_logicalref`

**Customer snapshot**

- `customer_name`
- `customer_phone`
- `customer_passport`
- `customer_contract`
- `branch`

**Created by snapshot**

- `created_by_name`
- `created_by_email`
- `created_by_phone`

**Ödeme alanları**

- `pay_amount` (müşterinin verdiği para)
- `change_amount` (para üstü)
- `method` (cash|card|mixed)
- `cash_amount`
- `card_amount`

**Before/After local snapshot**

- `old_amount_local`
- `new_amount_local`
- `old_paid_local`
- `new_paid_local`

**Diğer**

- `note`
- `created_by`
- `created_at`

### Casts (`$casts`)

- `credit_logicalref` → `integer`
- `created_by` → `integer`
- `created_at` → `datetime`

**Finans alanları**

- `pay_amount`, `change_amount` → `decimal:2`
- `cash_amount`, `card_amount` → `decimal:2`
- `old_amount_local`, `new_amount_local` → `decimal:2`
- `old_paid_local`, `new_paid_local` → `decimal:2`

### İlişkiler (Relationships)

#### 3.1 Credit

```php
$payment->credit
```

- `belongsTo(Credit::class, 'credit_logicalref', 'logicalref')`

#### 3.2 Created By User

```php
$payment->createdByUser
```

- `belongsTo(User::class, 'created_by')`

### Accessor / Computed Alanlar

#### 3.3 `applied_amount`

Borçtan düşen gerçek miktarı hesaplar:

```php
$payment->applied_amount
```

Formül:

- `applied = pay_amount - change_amount`
- Negatif ise 0’a sabitlenir
- 2 decimal’e round edilir

> Bu yaklaşım `pay_amount` alanının “müşteriden alınan para” olduğu senaryolarda doğrudur.

### Kullanım Notları

- `method` alanı enum’a çevrilebilir (örn: `PaymentMethodEnum`).
- Snapshot alanları sayesinde geçmiş kayıtlar müşteri/kullanıcı değişse bile korunur.

---

## ✅ İlişki Özeti

- `Credit` → `User` (paid_updated_by) : `belongsTo`
- `Credit` → `User` (amount_updated_by) : `belongsTo`
- `Credit` → `CreditPayment` : `hasMany`
- `CreditPayment` → `Credit` : `belongsTo`
- `CreditPayment` → `User` (created_by) : `belongsTo`

---

## 🔮 Geliştirme Önerileri

### 1) Enum ile standardizasyon

- `CreditPayment.method` için enum (`cash|card|mixed`) çok uygun.

### 2) Accessor’ları görünür yapmak

UI/API tarafında kolay olsun diye `$appends` kullanılabilir:

- `Credit` için: `local_remaining`, `local_closed`
- `CreditPayment` için: `applied_amount`

### 3) Strict types ve return tipleri

Relationship methodlarına return type eklemek (IDE + static analysis için iyi):

- `BelongsTo`, `HasMany`

---
