# CREDIT & PAYMENT – TINKER DOKÜMANTASYONU

Bu doküman, **Credit (müşteri)** ve **CreditPayment (ödeme)** yapısı için Tinker üzerinden yapılabilecek **tüm kontrol, doğrulama ve raporlama işlemlerini** tek sayfada toplar.  
Amaç: **Bir ödemenin hangi müşteriye ve hangi kullanıcı tarafından alındığını açık, güvenilir ve tekrar kullanılabilir şekilde görmek**.

> ⚠️ Not: Tinker çok satırlı `->` zincirlerini sevmez. Bu yüzden **tüm komutlar tek satırdır**.

---

## 0️⃣ Tinker Açma
```bash
php artisan tinker
```

---

## 1️⃣ Credit (Müşteri) Bulma

### 1.1 logicalref ile
```php
$id=53145; \App\Models\Credit::findOrFail($id);
```

### 1.2 contract ile (birden fazla olabilir)
```php
$contract='0654'; \App\Models\Credit::where('contract',$contract)->orderByDesc('rv_bigint')->get();
```

### 1.3 telefon ile
```php
$phone='864300000'; \App\Models\Credit::where('phone','ilike',"%$phone%")->get();
```

---

## 2️⃣ Credit – Tüm Bilgiler (FULL)

```php
$id=53145; \App\Models\Credit::with(['paidUpdatedByUser','amountUpdatedByUser','payments.createdByUser'])->findOrFail($id)->toArray();
```

---

## 3️⃣ Bir Credit’e Ait Tüm Ödemeler

```php
$id=53145; \App\Models\CreditPayment::where('credit_logicalref',$id)->orderByDesc('id')->get();
```

---

## 4️⃣ Credit + Ödeme Geçmişi (Okunabilir Özet)

```php
$id=53145; $c=\App\Models\Credit::with('payments.createdByUser')->findOrFail($id); ['logicalref'=>$c->logicalref,'name'=>$c->name,'contract'=>$c->contract,'branch'=>$c->branch,'amount_local'=>$c->amount_local,'paid_local'=>$c->paid_local,'remaining'=>number_format((float)$c->amount_local-(float)$c->paid_local,2,'.',''),'payments'=>$c->payments->map(fn($p)=>['id'=>$p->id,'at'=>$p->created_at?->format('Y-m-d H:i:s'),'method'=>$p->method,'received'=>$p->pay_amount,'change'=>$p->change_amount,'applied'=>number_format((float)$p->pay_amount-(float)($p->change_amount??0),2,'.',''),'cash'=>$p->cash_amount,'card'=>$p->card_amount,'customer'=>$p->customer_name,'contract'=>$p->customer_contract,'by'=>$p->createdByUser?->full_name])];
```

---

## 5️⃣ Ödemeler Doğru Credit’e Bağlı mı? (KRİTİK KONTROL)

```php
\App\Models\CreditPayment::with('credit')->get()->every(fn($p)=>$p->credit!==null);
```

✅ `true` → hiçbir ödeme boşa düşmemiştir.

---

## 6️⃣ Payment Snapshot – Müşteri Bilgileri

```php
\App\Models\CreditPayment::orderByDesc('id')->limit(10)->get(['id','customer_name','customer_phone','customer_contract','branch']);
```

---

## 7️⃣ Payment Snapshot – Ödemeyi Alan Kullanıcı

```php
\App\Models\CreditPayment::with('createdByUser')->orderByDesc('id')->limit(10)->get()->map(fn($p)=>['id'=>$p->id,'user'=>$p->createdByUser?->full_name,'email'=>$p->createdByUser?->email,'phone'=>$p->createdByUser?->phonenumber]);
```

---

## 8️⃣ Snapshot User Alanları Dolu mu?

```php
\App\Models\CreditPayment::whereNull('created_by_name')->orWhere('created_by_name','')->count();
```

0 olmalı ✅

---

## 9️⃣ Kullanıcı Bazlı Ödeme Özeti (Audit / Kasa)

```php
\App\Models\CreditPayment::where('created_by',1)->selectRaw("COUNT(*) cnt, SUM(pay_amount) total_received, SUM(pay_amount-COALESCE(change_amount,0)) total_applied, SUM(cash_amount) total_cash, SUM(card_amount) total_card, SUM(COALESCE(change_amount,0)) total_change")->first();
```

---

## 🔟 Sistem Genel Toplamları

```php
\App\Models\CreditPayment::selectRaw("COUNT(*) cnt, SUM(pay_amount) total_received, SUM(pay_amount-COALESCE(change_amount,0)) total_applied, SUM(cash_amount) total_cash, SUM(card_amount) total_card, SUM(COALESCE(change_amount,0)) total_change")->first();
```

---

## 1️⃣1️⃣ Contract Bazlı Ödeme Özeti

```php
\App\Models\CreditPayment::selectRaw("customer_contract, customer_name, COUNT(*) cnt, SUM(pay_amount) total")->groupBy('customer_contract','customer_name')->orderByDesc('total')->get();
```

---

## 1️⃣2️⃣ Son Ödeme – Her Şeyiyle

```php
\App\Models\CreditPayment::with(['credit','createdByUser'])->orderByDesc('id')->first()->toArray();
```

---

## ✅ Nihai Durum

- ✔ Ödeme → doğru credit
- ✔ Credit → doğru müşteri
- ✔ Payment snapshot → müşteri bilgisi sabit
- ✔ Payment snapshot → kullanıcı bilgisi sabit
- ✔ Raporlama, audit ve kasa için **güvenli yapı**

Bu doküman **canlı sistemde denetim, hata ayıklama ve rapor üretimi** için yeterlidir.

