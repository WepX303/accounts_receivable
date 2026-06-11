# 💰 Payments (Ödeme Akışı)

Bu doküman, kredi ödemelerinin sistemde **nasıl alındığını**, **nasıl hesaplandığını** ve **nasıl kayıt altına alındığını** tanımlar. Amaç; ödeme sürecinde **tutarlılık**, **izlenebilirlik** ve **geri dönülemezlik (audit)** sağlamaktır.

> Kapsam:
> - `credit_payments` tablosu
> - `CreditPayment` modeli
> - Ödeme method’ları
> - Para üstü (change) ve uygulanan tutar (applied)

---

## Amaç

- Ödeme alma sürecini standartlaştırmak
- Hatalı ödeme / yanlış hesaplama riskini ortadan kaldırmak
- Geçmiş ödeme kayıtlarının değişmemesini garanti altına almak

---

## Konum

`docs/business/payments.md`

---

## Temel Kavramlar

### 1) pay_amount

- Müşteriden **fiilen alınan toplam para**
- Kasa girişini temsil eder

Örnek:
- Müşteri 1000₺ borç için 1100₺ verirse → `pay_amount = 1100`

---

### 2) change_amount (Para Üstü)

- Müşteriye geri verilen fazla tutar
- Borca uygulanmaz

Örnek:
- `pay_amount = 1100`
- Borç = 1000
- `change_amount = 100`

---

### 3) applied_amount (Borca Uygulanan)

Hesaplanan (computed) alandır:

```text
applied_amount = pay_amount - change_amount
```

Kurallar:
- Negatif olamaz
- `round(2)` uygulanır

---

## Ödeme Method’ları

`credit_payments.method`

Desteklenen değerler:

| Method | Açıklama |
|------|---------|
| `cash` | Tamamı nakit |
| `card` | Tamamı kart |
| `mixed` | Nakit + kart |

> ⚠️ Şu an string olarak tutuluyor. Enum’a çevrilmesi önerilir.

---

## Method Bazlı Kurallar

### 1) cash

```text
pay_amount = cash_amount
card_amount = 0
change_amount <= cash_amount
```

---

### 2) card

```text
pay_amount = card_amount
cash_amount = 0
change_amount = 0
```

> Kart ile para üstü verilmez.

---

### 3) mixed

```text
pay_amount = cash_amount + card_amount
change_amount <= cash_amount
```

> Para üstü **sadece nakitten** verilir.

---

## Ödeme Akışı (Step by Step)

1. Kullanıcı ödeme ekranını açar
2. Sistem `local_closed` kontrolü yapar
   - `true` ise ödeme engellenir
3. Kullanıcı method seçer (`cash/card/mixed`)
4. `pay_amount`, `cash_amount`, `card_amount` girilir
5. Sistem `change_amount` hesaplar veya doğrular
6. `applied_amount` hesaplanır
7. `paid_local` artırılır
8. Ödeme kaydı `credit_payments` tablosuna yazılır

---

## Kredi Güncelleme Kuralları

Ödeme kaydedildiğinde:

- `credits.paid_local`:
  - `old_paid_local` → `new_paid_local`
- `credits.amount_local`:
  - **değişmez**

> Kredi güncellemesi ve ödeme kaydı **aynı transaction** içinde yapılmalıdır.

---

## Snapshot Mantığı

`credit_payments` tablosu **immutable (değişmez)** kabul edilir.

Kaydedilen snapshot alanları:

### Müşteri Snapshot
- `customer_name`
- `customer_phone`
- `customer_passport`
- `customer_contract`
- `branch`

### Kullanıcı Snapshot
- `created_by_name`
- `created_by_email`
- `created_by_phone`

> Amaç: müşteri veya kullanıcı bilgileri sonradan değişse bile geçmiş ödeme kayıtları korunur.

---

## Validasyon Kuralları (Önerilen)

### Genel

- `pay_amount > 0`
- `change_amount >= 0`
- `applied_amount >= 0`

---

### Method Bazlı

#### cash
- `cash_amount > 0`
- `card_amount = 0`

#### card
- `card_amount > 0`
- `cash_amount = 0`
- `change_amount = 0`

#### mixed
- `cash_amount > 0`
- `card_amount > 0`
- `change_amount <= cash_amount`

---

## Edge Case’ler

### Fazla ödeme

- Borçtan fazla ödeme alınırsa:
  - fazla kısım `change_amount`
  - borç **0**’a sabitlenir

---

### Yuvarlama farkları

- `0.01` tolerans
- `local_closed` buna göre hesaplanır

---

## Hata Senaryoları ve Önlemler

### Aynı krediye eşzamanlı ödeme

Çözüm:
- Transaction
- Gerekirse row-level lock (`select ... for update`)

---

### Yanlış method kombinasyonu

Çözüm:
- Backend validasyon (frontend’e güvenme)

---

## İyileştirme Önerileri

- `PaymentMethodEnum` ekle
- Payment Service Layer oluştur
- Unit test ile business rule’ları koru
- Ödeme iptal (reversal) için ayrı akış tasarla

---

## Özet

- `pay_amount`: kasaya giren toplam para
- `change_amount`: müşteriye iade
- `applied_amount`: borca uygulanan gerçek tutar
- Ödeme kayıtları **değişmez (snapshot)**
- Kurallar backend tarafından enforce edilmelidir

