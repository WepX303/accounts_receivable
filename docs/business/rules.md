# 📐 Business Rules

Bu doküman, kredi ve ödeme tarafındaki **iş kurallarını** net ve tartışmasız şekilde tanımlar. Amaç; backend, frontend ve raporların **aynı mantıkla** çalışmasını sağlamaktır.

> Kapsam:
> - `credits` (Credit modeli)
> - `credit_payments` (CreditPayment modeli)
> - Local override alanları
> - Kapanma (closed) ve kalan (remaining) hesapları

---

## Amaç

- “Kalan borç nedir?” sorusuna tek bir cevap vermek
- Local alanlar varken MSSQL alanlarının rolünü netleştirmek
- Ödeme uygulama ve kapanma kurallarını standartlaştırmak

---

## Konum

`docs/business/rules.md`

---

## Tanımlar (Terminoloji)

### Source (MSSQL) Alanlar

MSSQL’den gelen, **orijinal** alanlar:

- `amount`
- `paid`

> Bu alanlar **read-only** kabul edilir. Uygulama içinde doğrudan update edilmez.

---

### Local Override Alanlar

Uygulama tarafından yönetilen alanlar:

- `amount_local`
- `paid_local`
- `amount_note`, `paid_note`
- `amount_updated_by`, `paid_updated_by`
- `amount_updated_at`, `paid_updated_at`

> Local alanlar, MSSQL verisinin **üzerine yazmak için değil**, uygulama içi düzeltme/uyarlama içindir.

---

## Temel Kurallar

## 1) Hangi değerler esas alınır?

| Senaryo | Kullanılan Alanlar |
|------|-------------------|
| Local alanlar **dolu** | `amount_local` + `paid_local` |
| Local alanlardan biri **null** | Kredi **kapalı kabul edilir** |

> ⚠️ Bu proje özelinde local alanlar null ise kredi kapalı sayılır. (Bilinçli iş kararı)

---

## 2) Local Remaining (Kalan Borç)

`Credit::getLocalRemainingAttribute()`

### Formül

```text
remaining = amount_local - paid_local
```

### Kurallar

- `amount_local == null` veya `paid_local == null` → `null`
- Sonuç < 0 ise → `0`
- Sonuç `round(2)` ile yuvarlanır

### Örnekler

| amount_local | paid_local | remaining |
|-------------|------------|-----------|
| 1000.00 | 200.00 | 800.00 |
| 1000.00 | 1000.00 | 0.00 |
| 1000.00 | 1200.00 | 0.00 |
| null | 200.00 | null |

---

## 3) Local Closed (Kapanma Kuralı)

`Credit::getLocalClosedAttribute()`

### Kurallar

- `amount_local == null` veya `paid_local == null` → **true (kapalı)**
- Aksi halde:

```text
paid_local >= amount_local - 0.01
```

> `0.01` toleransı decimal/rounding farklarını absorbe etmek içindir.

### Örnekler

| amount_local | paid_local | closed |
|-------------|------------|--------|
| 1000.00 | 999.99 | true |
| 1000.00 | 999.00 | false |
| null | null | true |

---

## 4) Ödeme Uygulama Mantığı (Applied Amount)

`CreditPayment::getAppliedAmountAttribute()`

### Tanım

- `pay_amount`: müşteriden alınan **toplam para**
- `change_amount`: müşteriye verilen **para üstü**

### Formül

```text
applied_amount = pay_amount - change_amount
```

### Kurallar

- Sonuç < 0 ise → `0`
- `round(2)` uygulanır

---

## 5) Payment → Credit Etkisi

Bir ödeme kaydedildiğinde:

1. `applied_amount` hesaplanır
2. `paid_local` artırılır
3. `amount_local` **değişmez**
4. Kalan borç yeniden hesaplanır

> Ödeme amount’u **kalan borcu** aşsa bile:
> - fazla kısım `change_amount` olarak ayrılır

---

## 6) Snapshot Kuralları (Payment Kayıtları)

`credit_payments` tablosu **snapshot** mantığıyla çalışır.

Kaydedilen bilgiler:

- Ödeme anındaki müşteri bilgileri
- Ödeme anındaki kullanıcı bilgileri
- Ödeme öncesi / sonrası local değerler

> Amaç: geçmiş kayıtlar **asla değişmesin**.

---

## UI / API İçin Zorunlu Kurallar

- Kalan borç **sadece** `local_remaining` üzerinden gösterilmeli
- Kapanma durumu **sadece** `local_closed` üzerinden kontrol edilmeli
- MSSQL `amount/paid` UI’da referans amaçlı gösterilebilir ama iş mantığında kullanılmamalı

---

## Hata Senaryoları ve Önlemler

### Yanlış kalan borç gösterimi

Sebep:
- Frontend kendi hesaplıyor

Çözüm:
- Backend accessor (`local_remaining`) kullanılmalı

---

### Kredi kapalı görünüyor ama ödeme alınıyor

Sebep:
- Local alanlar null iken ödeme ekranı açık

Çözüm:
- UI tarafında `local_closed === true` ise ödeme engellenmeli

---

## İyileştirme Önerileri

- Local alanlar için “initialized” flag düşünülebilir
- Business rule’lar unit test ile korunmalı
- `PaymentMethodEnum` gibi enum’lar eklenebilir

---

## Özet

- MSSQL alanlar **source**, local alanlar **uygulama gerçeği**
- Kalan borç ve kapanma tek noktadan hesaplanır
- Ödeme kayıtları immutable (snapshot)
- Bu doküman UI/API için **bağlayıcıdır**

