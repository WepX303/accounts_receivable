# 🧾 Audit & Snapshot Mantığı

Bu doküman, sistemdeki kritik tabloların neden **snapshot (immutable)** mantığıyla çalıştığını ve audit (izlenebilirlik) gereksinimlerinin nasıl karşılandığını açıklar.

> Bu proje özelinde audit yaklaşımı özellikle:
> - `credit_payments`
> - `credits` local alanları
> üzerinden uygulanır.

---

## Amaç

- Geçmiş kayıtların **asla değişmemesini** garanti altına almak
- Kim, ne zaman, hangi değerle işlem yaptı sorularına net cevap vermek
- Finansal kayıtlar için denetlenebilir (audit-friendly) yapı sağlamak

---

## Konum

`docs/business/audit-and-snapshots.md`

---

## Snapshot Nedir?

Snapshot yaklaşımı:

- Bir kaydın **oluştuğu andaki** tüm kritik bilgilerin saklanmasıdır
- İlgili ana kayıt (müşteri, kullanıcı, kredi) sonradan değişse bile
  geçmiş kayıt **etkilenmez**

> Finansal sistemlerde snapshot **zorunluluktur**.

---

## 1) `credit_payments` Snapshot Yapısı

`credit_payments` tablosu **tamamen immutable** kabul edilir.

### Değiştirilemez Alanlar

Bir ödeme kaydı oluşturulduktan sonra **güncellenmemelidir**:

- `pay_amount`
- `change_amount`
- `method`
- `cash_amount`
- `card_amount`
- `old_amount_local`
- `new_amount_local`
- `old_paid_local`
- `new_paid_local`
- `created_at`

> Bu alanlar **audit kaydıdır**.

---

### Müşteri Snapshot Alanları

Ödeme anındaki müşteri bilgileri:

- `customer_name`
- `customer_phone`
- `customer_passport`
- `customer_contract`
- `branch`

Amaç:
- Müşteri bilgileri sonradan güncellense bile
- Ödeme kaydı **orijinal haliyle** korunur

---

### Kullanıcı Snapshot Alanları

Ödemeyi alan kullanıcı bilgileri:

- `created_by`
- `created_by_name`
- `created_by_email`
- `created_by_phone`

Amaç:
- Ödemeyi **hangi kullanıcı** aldı?
- Kullanıcı sonradan silinse bile kayıt anlamlı kalsın

---

## 2) `credits` Local Audit Alanları

`credits` tablosunda local override alanları için audit tutulur.

### Amount Local Audit

- `amount_local`
- `amount_note`
- `amount_updated_by`
- `amount_updated_at`

### Paid Local Audit

- `paid_local`
- `paid_note`
- `paid_updated_by`
- `paid_updated_at`

Amaç:
- Local değerler **neden ve kim tarafından** değiştirildi?
- Değişiklik zamanı nedir?

---

## 3) Audit Kuralları

### Kural 1: Payment Update Yasak

- `credit_payments` kayıtları **update edilmez**
- Yanlış kayıt varsa:
  - Yeni bir ödeme / düzeltme kaydı eklenir

---

### Kural 2: Local Alanlar Açıklamasız Değiştirilemez

- `amount_local` veya `paid_local` değiştiriliyorsa:
  - `*_note` **zorunlu**
  - `*_updated_by` ve `*_updated_at` set edilmeli

---

### Kural 3: Audit UI’dan Gizlenmez

- Audit bilgileri (kim, ne zaman) admin kullanıcılar için görünür olmalı

---

## 4) Audit vs Log Farkı

| Audit | Log |
|-----|----|
| İş verisinin parçası | Teknik/debug amaçlı |
| DB’de saklanır | Log dosyasında |
| Değişmez | Dönebilir / silinebilir |
| Finansal zorunluluk | Operasyonel |

---

## 5) Hata Senaryoları ve Önlemler

### Yanlış payment güncelleme denemesi

Önlem:
- Model seviyesinde `updating` event ile engelle
- Policy veya Service Layer kullan

---

### Local değer sessizce değiştirildi

Önlem:
- Backend validasyon
- Note alanını zorunlu kıl

---

## 6) Geliştirme Önerileri

- Audit amaçlı ayrı bir `credit_audit_logs` tablosu düşünülebilir
- Local değişiklikler için history (before/after) tutulabilir
- Kritik işlemler için “soft approval” (2. göz) mekanizması eklenebilir

---

## Özet

- `credit_payments` = **snapshot + immutable**
- `credits` local alanları = **audit’li override**
- Geçmiş kayıtlar **asla değişmez**
- Düzeltme yeni kayıtla yapılır

Bu yapı finansal sistemler için güvenli ve denetlenebilirdir.

