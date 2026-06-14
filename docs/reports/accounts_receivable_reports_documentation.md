# Accounts Receivable Reporting Module — Teknik Dokümantasyon

**Proje:** Accounts Receivable / Tahsilat ve Alacak Takip Sistemi  
**Doküman dili:** Türkçe  
**Rapor isimleri:** İngilizce  
**Oluşturulma tarihi:** 2026-06-15

---

## 1. Genel Özet

Bu doküman, Laravel projesinde oluşturulan raporlama modülünü baştan sona açıklamak için hazırlanmıştır.

Bu süreçte raporlama tarafında aşağıdaki raporlar oluşturuldu / düzenlendi:

1. **Payment Calendar Report**
2. **Overdue Payments Report**
3. **Collection Performance Report**
4. **Daily Cash Closing Report**
5. **Collection Trend Report**
6. **Promise To Pay Report**
7. **Recovery Effectiveness Report**
8. **Customer Statement Report**

Raporların ortak amacı:

- Borçlu müşterileri takip etmek
- Ödeme beklenen müşterileri görmek
- Yapılan tahsilatları analiz etmek
- Kasiyer / şube performansını ölçmek
- Kasa kapanışını kontrol etmek
- Ödeme sözü takibi yapmak
- Tahsilat başarısını ölçmek
- Tek müşteri hesap ekstresi çıkarmak

Genel yaklaşım:

- Tüm raporlarda `voided` ödemeler gerçek tahsilat hesabına dahil edilmez.
- Gerçek tahsilat için temel formül kullanılır:

```text
Net Tahsilat = pay_amount - change_amount
```

- Borç hesaplarında lokal alanlar önceliklidir:

```text
Toplam Borç = amount_local varsa amount_local, yoksa amount
Ödenen = paid_local varsa paid_local, yoksa paid
Kalan = Toplam Borç - Ödenen
```

- Büyük listelerde pagination kullanıldı.
- Excel export gereken raporlarda filtreler export'a da taşındı.
- PostgreSQL uyumlu `COALESCE`, `ILIKE`, `DATE_TRUNC`, `TO_CHAR` gibi yapılar kullanıldı.

---

## 2. Ortak Veri Yapısı

### 2.1 Ana Tablolar

Raporlarda en çok kullanılan tablolar:

```text
credits
credit_payments
users
activity_logs
login_histories
security_alerts
avshocrecat_report
```

Bu dokümandaki raporların ana ağırlığı şu iki tablo üzerindedir:

```text
credits
credit_payments
```

---

### 2.2 credits Tablosunda Kullanılan Önemli Alanlar

```text
source_id
logicalref
branch
name
phone
passport
contract
clientref
date_
amount
amount_local
paid
paid_local
willpaiddate
willpaidamount
status
active
is_blocked
note
manager
assurance
custstatus
cardno
fishno
confirmedby
gstatus
```

Temel borç hesaplaması:

```php
$total = (float) ($credit->amount_local ?? $credit->amount ?? 0);
$paid = (float) ($credit->paid_local ?? $credit->paid ?? 0);
$remaining = max($total - $paid, 0);
```

---

### 2.3 credit_payments Tablosunda Kullanılan Önemli Alanlar

```text
id
credit_source_id
customer_name
customer_contract
customer_phone
branch
method
pay_amount
change_amount
cash_amount
card_amount
phone_amount
receiver_phone_number
created_by_name
created_at
voided_at
corrected_at
note
```

Net ödeme hesabı:

```php
$net = (float) $payment->pay_amount - (float) ($payment->change_amount ?? 0);
```

Void ödeme kontrolü:

```php
->notVoided()
```

veya:

```php
whereNull('voided_at')
```

---

## 3. Payment Calendar Report

### 3.1 Raporun Amacı

**Payment Calendar Report**, taksit ödeme günlerini takvim mantığıyla gösteren rapordur.

Bu rapor şu soruya cevap verir:

```text
Hangi gün hangi müşterilerin ödeme yapması gerekiyor?
Hangi gün hangi ödemeler alınmış?
```

---

### 3.2 Ana Sayfa Mantığı

Rapor ay bazlı çalışır.

Örnek:

```text
2026-06
```

Seçilen ay içerisindeki günler için:

- Expected Payments
- Received Payments

takip edilir.

---

### 3.3 Expected Payments Hesabı

Her kredi 6 aylık ödeme planı üzerinden hesaplandı.

Temel mantık:

```php
$monthlyPayment = round($amount / 6, 2);
```

Her kredi için 1. aydan 6. aya kadar ödeme günü hesaplanır:

```php
for ($i = 1; $i <= 6; $i++) {
    $dueDate = $creditDate->copy()->addMonthsNoOverflow($i);
}
```

Eğer `dueDate` seçili güne eşitse müşteri o günün beklenen ödeme listesine girer.

---

### 3.4 Expected Detail Sayfası

Beklenen ödeme detaylarında gösterilen bilgiler:

```text
Credit ID
Customer
Contract
Phone
Branch
Credit Date
Due Date
Monthly Payment
Total Amount
Paid
Remaining
```

Daha sonra bu liste ekranda sadeleştirildi:

```text
Customer
Contract
Phone
Branch
Total Debt
Paid
Remaining
Expected Installment
Credit Date
```

---

### 3.5 Received Detail Sayfası

Ödeme yapılan günlerde ödeme hareketleri gösterilir.

Alanlar:

```text
Payment ID
Customer
Contract
Phone
Branch
Payment Date
Method
Received
Change
Net
Cashier
```

Net hesabı:

```php
$net = $pay_amount - $change_amount;
```

---

### 3.6 Excel Export

Payment Calendar export iki tip çalışır:

```text
expected
received
```

Controller:

```php
PaymentCalendarDetailsExportController
```

Export class:

```php
PaymentCalendarDetailsExport
```

Route parametreleri:

```text
type=expected|received
date=YYYY-MM-DD
```

Expected export detaylı müşteri / kredi bilgilerini verir.

Received export ödeme bilgilerini verir.

---

### 3.7 Yapılan İyileştirmeler

- Blade detail sayfası daha okunabilir yapıldı.
- Export linki `request('type')` yerine doğrudan `$type` ve `$date->toDateString()` ile güvenli hale getirildi.
- Expected ve Received export alanları zenginleştirildi.
- Tablolar horizontal scroll destekli hale getirildi.
- Static ID gösterimi yerine anlamlı kolonlar tercih edildi.
- UI sade ve kullanışlı hale getirildi.

---

## 4. Overdue Payments Report

### 4.1 Raporun Amacı

**Overdue Payments Report**, taksit planına göre ödeme yapması gerektiği halde geri kalan müşterileri gösterir.

Cevapladığı soru:

```text
Kim ödeme planına göre geride kaldı?
Ne kadar gecikti?
Ne kadar ödemesi gerekiyordu?
Ne kadar eksik ödedi?
```

---

### 4.2 Ana Hesap Mantığı

Her kredi için:

```php
$total = amount_local ?? amount
$paid = paid_local ?? paid
$monthly = total / 6
```

Bugüne kadar kaç taksit gelmiş hesaplanır.

```php
for ($i = 1; $i <= 6; $i++) {
    $dueDate = $creditDate->copy()->addMonthsNoOverflow($i);

    if ($today->gte($dueDate)) {
        $dueInstallmentCount = $i;
        $lastDueDate = $dueDate;
    }
}
```

Beklenen ödenmiş miktar:

```php
$expectedPaid = min($monthly * $dueInstallmentCount, $total);
```

Geciken tutar:

```php
$overdueAmount = max($expectedPaid - $paid, 0);
```

Gecikme günü:

```php
$overdueDays = $lastDueDate->diffInDays($today);
```

---

### 4.3 Min Overdue Kuralı

10 TMT altındaki küçük gecikmeler listeye alınmadı:

```php
if ($overdueAmount < 10) {
    continue;
}
```

Bunun amacı:

- Önemsiz küçük farkları rapordan temizlemek
- Tahsilat ekibine gerçek aksiyon listesi vermek
- Listeyi daha kullanışlı hale getirmek

---

### 4.4 Filtreler

Rapor filtreleri:

```text
Search
Branch
Min Overdue
Min Days
Sort
```

Search alanları:

```text
name
phone
passport
contract
clientref
```

---

### 4.5 Summary Kartları

```text
Customers
Total Debt
Paid
Remaining
Expected Paid
Overdue Amount
```

---

### 4.6 Excel Export

Export class:

```php
OverduePaymentsReportExport
```

Export edilen alanlar:

```text
Credit ID
LogicalRef
Customer
Contract
Phone
Passport
Branch
ClientRef
Credit Date
Last Due Date
Monthly Payment
Due Installments
Total Debt
Paid
Expected Paid
Overdue Amount
Remaining
Overdue Days
Credit Status
Payment ID
Payment Date
Payment Method
Received Amount
Change Amount
Net Applied
Cash Amount
Card Amount
Phone Amount
Cashier
Receiver Phone
Payment Note
```

Bu exportta her gecikmiş müşteri için ödeme hareketleri de gösterilir.

Eğer müşterinin ödemesi yoksa ödeme kolonları boş kalır.

---

### 4.7 Performans Notu

İlk versiyonda tüm kayıtlar alınarak PHP tarafında hesaplanıyordu.

Daha sonra PostgreSQL tarafında ön filtre eklendi:

```sql
COALESCE(amount_local, amount, 0) > COALESCE(paid_local, paid, 0)
```

ve yaklaşık gecikme filtresi DB tarafına taşındı.

Bu, DB yükünü azaltır ve PHP tarafında gereksiz kayıt işlemeyi önler.

---

## 5. Collection Performance Report

### 5.1 Raporun Amacı

**Collection Performance Report**, kasiyer ve şube bazlı tahsilat performansını gösterir.

Cevapladığı sorular:

```text
Hangi kasiyer ne kadar tahsilat yaptı?
Hangi şube ne kadar tahsilat yaptı?
Kaç işlem yapıldı?
Nakit, kart, telefon dağılımı nasıl?
```

---

### 5.2 Payment Method Mantığı

Sistemde `mixed` ayrı bir ödeme tipi olarak kabul edilmedi.

Senin belirttiğin mantığa göre:

```text
mixed sadece ödeme dağılımıdır.
Bir ödeme yarısı cash, yarısı card olabilir.
```

Bu yüzden method filtreleri şu şekilde yorumlandı:

```php
cash  => cash_amount > 0
card  => card_amount > 0
phone => phone_amount > 0
```

Yani `method = mixed` gibi ayrı sınıflandırma yapılmadı.

---

### 5.3 Ana Summary

```text
Transactions
Gross Collection
Change Returned
Net Collection
Cash
Card
Phone
Cashiers
Branches
```

Net hesap:

```php
SUM(pay_amount - COALESCE(change_amount, 0))
```

---

### 5.4 Cashier Performance

Kasiyer bazlı tablo:

```text
Cashier
Transactions
Gross
Change
Net
Cash
Card
Phone
```

Transactions sayısına tıklanınca detay sayfasına gidiyor.

---

### 5.5 Branch Performance

Şube bazlı tablo:

```text
Branch
Transactions
Gross
Change
Net
Cash
Card
Phone
```

Transactions sayısına tıklanınca detay sayfasına gidiyor.

---

### 5.6 Details Sayfası

Cashier veya Branch için ödeme hareketleri listelenir.

Alanlar:

```text
Payment ID
Date
Customer
Contract
Phone
Branch
Cashier
Method
Received
Change
Net
Cash
Card
Phone Amount
```

PostgreSQL grouping hatası yaşandı:

```text
column "created_at" must appear in the GROUP BY clause...
```

Sebep:

```php
$query üzerinde orderBy kaldıktan sonra aggregate first() çalıştırılması
```

Çözüm:

Summary query için ayrı clone kullanılıp order temiz mantıkla yapıldı.

---

### 5.7 Excel Export

Ana performans exportta:

- Önce Cashiers bölümü
- 2 boş satır
- Sonra Branches bölümü

şeklinde çıktı verildi.

Bu yapı Excel içinde daha okunabilir oldu.

---

### 5.8 UX İyileştirmesi

Transactions linklerine ok eklendi:

```text
123 <-
```

veya ikonla görünür hale getirildi.

Ama gereksiz tıklanabilirlik istenmeyen raporlarda kaldırıldı.

---

## 6. Daily Cash Closing Report

### 6.1 Raporun Amacı

**Daily Cash Closing Report**, sadece günlük kasa kapanışına odaklanır.

Cevapladığı soru:

```text
Bugün veya seçilen tarihlerde kasa kaç kapandı?
Nakit, kart, telefon ve para üstü toplamları nedir?
```

---

### 6.2 Neden Cashier / Branch Tabloları Kaldırıldı?

İlk versiyonda raporda şunlar vardı:

```text
Daily Closing Totals
Cashier Closing Totals
Branch Closing Totals
```

Sonra analiz edildi:

- Cashier performansı zaten **Collection Performance Report** içinde var.
- Branch performansı zaten **Collection Performance Report** içinde var.
- Daily Cash Closing tekrar aynı bilgiyi vermemeli.

Bu yüzden 4. rapor sadeleştirildi.

Kalan ana yapı:

```text
Summary Cards
Filters
Daily Cash Closing Totals
Excel Export
```

---

### 6.3 Filtreler

```text
Date From
Date To
Branch
Cashier
```

---

### 6.4 Summary Kartları

```text
Transactions
Gross Collection
Change Returned
Net Collection
Cash
Card
Phone
```

---

### 6.5 Daily Closing Totals

Günlük satırlar:

```text
Date
Transactions
Gross
Change
Net
Cash
Card
Phone
```

SQL mantığı:

```php
DATE(created_at) as report_date
GROUP BY DATE(created_at)
```

---

### 6.6 Excel Export

Export class:

```php
DailyCashClosingExport
```

Export kolonları:

```text
Date
Transactions
Gross Collection
Change Returned
Net Collection
Cash Amount
Card Amount
Phone Amount
```

Filtreler export'a taşınır:

```text
date_from
date_to
branch
cashier
```

---

## 7. Collection Trend Report

### 7.1 Raporun Amacı

**Collection Trend Report**, tahsilatın zaman içindeki yönünü gösterir.

Cevapladığı sorular:

```text
Tahsilat yükseliyor mu?
Düşüyor mu?
Hangi gün/hafta/ay daha güçlü?
Nakit, kart, telefon trendi nasıl?
```

---

### 7.2 Group By Mantığı

3 tip gruplama var:

```text
Daily
Weekly
Monthly
```

SQL:

```php
day   => TO_CHAR(created_at::date, 'YYYY-MM-DD')
week  => TO_CHAR(DATE_TRUNC('week', created_at), 'YYYY-MM-DD')
month => TO_CHAR(DATE_TRUNC('month', created_at), 'YYYY-MM')
```

---

### 7.3 Summary Kartları

```text
Net Collection
Transactions
Average Net
Change Returned
Cash
Card
Phone
```

Average Net:

```php
$trendRows->avg('net_total')
```

---

### 7.4 Best / Lowest Period

En iyi dönem:

```php
$bestRow = $trendRows->sortByDesc('net_total')->first();
```

En düşük dönem:

```php
$lowestRow = $trendRows->where('net_total', '>', 0)->sortBy('net_total')->first();
```

---

### 7.5 Grafik

ApexCharts kullanıldı.

Seriler:

```text
Net
Gross
Cash
Card
Phone
```

Controller’dan JavaScript’e aktarılan yapı:

```php
$chart = [
    'labels' => ...,
    'net' => ...,
    'gross' => ...,
    'cash' => ...,
    'card' => ...,
    'phone' => ...,
];
```

Blade:

```js
window.COLLECTION_TREND = ...
```

---

### 7.6 Tooltip Sorunu

Grafikte Phone serisi görünmüyordu.

Custom tooltip denendi ama ApexCharts default görünümü daha iyi olduğu için default tooltip korundu.

Çözüm:

```js
tooltip: {
    shared: true,
    intersect: false,
    y: {
        formatter: ...
    }
}
```

ve chart yüksekliği artırıldı.

---

### 7.7 Export Kararı

Bu rapor grafik / trend odaklı olduğu için export zorunlu görülmedi.

Rapor şu haliyle tamamlandı:

```text
Filter
Summary
Best/Lowest
Chart
Trend table
Footer totals
```

---

## 8. Promise To Pay Report

### 8.1 Raporun Amacı

**Promise To Pay Report**, müşterilerin ödeme sözü takibini yapar.

Cevapladığı sorular:

```text
Kim ödeme sözü verdi?
Ne kadar ödeme sözü verdi?
Söz verdiği gün geldi mi?
Sözünü tuttu mu?
Sözünü bozdu mu?
```

---

### 8.2 Kullanılan Alanlar

```text
credits.willpaiddate
credits.willpaidamount
credit_payments.created_at
credit_payments.pay_amount
credit_payments.change_amount
```

Sistemde `willpaiddate` dolu kayıt sayısı kontrol edildi:

```text
5795 kayıt
```

Bu, raporun gerçek veriyle kullanılabilir olduğunu gösterdi.

---

### 8.3 Status Mantığı

```text
Broken
Today
Upcoming
Kept
```

Broken:

```text
Promise date geçmiş
ve
Promise date sonrası ödeme yok
```

Today:

```text
Promise date bugün
```

Upcoming:

```text
Promise date gelecekte
```

Kept:

```text
Promise date geçmiş
ve
Promise date sonrası ödeme var
```

Kod mantığı:

```php
if ($promiseDate->isToday()) {
    $promiseStatus = 'today';
} elseif ($promiseDate->isPast()) {
    $promiseStatus = $paidOnOrAfterPromise > 0 ? 'kept' : 'broken';
} else {
    $promiseStatus = 'upcoming';
}
```

---

### 8.4 Paid After Promise

Söz tarihinden sonra yapılan ödeme:

```php
CreditPayment::query()
    ->notVoided()
    ->where('credit_source_id', $credit->source_id)
    ->whereDate('created_at', '>=', $promiseDate)
    ->sum(DB::raw('pay_amount - COALESCE(change_amount, 0)'));
```

---

### 8.5 Sıralama

Liste önceliği:

```text
1. Broken
2. Today
3. Upcoming
4. Kept
```

Bunun amacı tahsilat ekibinin önce problemli müşterileri görmesidir.

---

### 8.6 Summary Kartları

```text
Promise Customers
Promise Amount
Paid After Promise
Remaining
Broken
Today
Upcoming
Kept
```

---

### 8.7 Excel Export

Export class:

```php
PromiseToPayExport
```

Export detaylıdır.

Alanlar:

```text
Credit ID
LogicalRef
Customer
Phone
Passport
Contract
Branch
ClientRef
Customer Status
Assurance
Credit Type
Card No
Fish No
Manager
Confirmed By
G Status
Credit Date
Total Debt
Paid
Remaining
Remote Amount
Remote Paid
Local Amount
Local Paid
Promise Date
Promise Amount
Paid After Promise
Promise Status
Promise Days
Credit Status
Active
Is Blocked
Will Pay Date Raw
Will Pay Amount Raw
Note
Last Noted Date
Paid Updated At
Paid Updated By
Paid Note
Created At
Updated At
```

---

### 8.8 Değerlendirme

Bu rapor tahsilat ekibi için çok değerlidir.

Günlük kullanım:

```text
Bugün kim ödeme sözü verdi?
Kim sözünü bozdu?
Kim tekrar aranmalı?
```

---

## 9. Recovery Effectiveness Report

### 9.1 Raporun Amacı

**Recovery Effectiveness Report**, borçlu müşterilerden seçilen tarihler arasında ne kadar geri tahsilat yapıldığını ölçer.

Cevapladığı temel soru:

```text
Tahsilat ekibi gerçekten başarılı mı?
```

---

### 9.2 Ana Mantık

Önce borcu kalan aktif müşteriler alınır:

```php
active = true
is_blocked = 0
total > paid
```

Sonra seçilen tarih aralığındaki ödemeler `credit_source_id` bazında gruplanır.

---

### 9.3 Payment Aggregation

```php
COUNT(*) as payment_count
SUM(pay_amount) as gross_recovered
SUM(change_amount) as change_total
SUM(pay_amount - COALESCE(change_amount, 0)) as net_recovered
MAX(created_at) as last_payment_at
```

---

### 9.4 Recovery Rate

Müşteri bazlı recovery rate:

```php
$recoveryRate = ($netRecovered / $total) * 100;
```

Genel müşteri recovery rate:

```php
recovered_customers / overdue_customers * 100
```

Genel tutar recovery rate:

```php
net_recovered / total_debt * 100
```

---

### 9.5 Summary Kartları

```text
Overdue Customers
Recovered Customers
Not Recovered
Customer Recovery Rate
Total Debt
Net Recovered
Gross Recovered
Change Returned
```

---

### 9.6 Branch Recovery Performance

Şube bazlı tablo:

```text
Branch
Customers
Recovered
Customer Rate
Total Debt
Net Recovered
Amount Rate
```

---

### 9.7 Customer Recovery Details

Müşteri bazlı detay:

```text
Credit ID
Customer
Contract
Phone
Branch
Total Debt
Paid
Remaining
Payments
Gross Recovered
Change
Net Recovered
Recovery %
Last Payment
```

---

### 9.8 Excel Export

Export class:

```php
RecoveryEffectivenessExport
```

Export kolonları:

```text
Credit ID
LogicalRef
Customer
Contract
Phone
Passport
Branch
ClientRef
Credit Date
Credit Status
Total Debt
Paid
Remaining
Payment Count
Gross Recovered
Change Returned
Net Recovered
Recovery %
Last Payment
Remote Amount
Remote Paid
Local Amount
Local Paid
Manager
Assurance
Customer Status
Card No
Fish No
Confirmed By
G Status
Note
Created At
Updated At
```

---

## 10. Customer Statement Report

### 10.1 Raporun Amacı

**Customer Statement Report**, tek müşteri / tek kontrat için hesap ekstresi verir.

Cevapladığı sorular:

```text
Müşterinin borcu neydi?
Ne kadar ödeme yaptı?
Hangi tarihlerde ödeme yaptı?
Void ödeme var mı?
Corrected ödeme var mı?
Kalan borcu ne?
```

---

### 10.2 Arama Mantığı

Arama alanları:

```text
Contract
Phone
Customer
Branch
```

İlk yaklaşım:

```php
first()
```

ile ilk müşteri açılıyordu.

Ama isim aramalarında çok müşteri çıkabileceği için bu riskli bulundu.

Örnek:

```text
Leyla
```

çok sayıda müşteri getirebilir.

Bu yüzden seçim listesi eklendi.

---

### 10.3 Yeni Arama Akışı

```text
Contract girildiyse
    Direkt ekstre açılır

Phone veya Customer girildiyse
    Çoklu sonuç varsa seçim listesi gösterilir

Tek sonuç varsa
    Direkt ekstre açılır
```

---

### 10.4 Pagination

Çoklu müşteri seçim listesine pagination eklendi.

Controller:

```php
->paginate(
    perPage: 50,
    columns: [...],
    pageName: 'page'
)
->appends($request->query());
```

Blade:

```blade
{{ $matches->links('vendor.pagination.custom') }}
```

Bu sayede DB ve UI şişmez.

---

### 10.5 Güvenli Seçim

View Statement linki sadece contract değil branch de taşır:

```blade
route('reports.customer-statement', [
    'contract' => $m->contract,
    'branch' => $m->branch,
])
```

Bu aynı kontratın farklı branch ihtimalinde daha güvenlidir.

---

### 10.6 Statement Hesabı

İlk satır:

```text
Credit Created
```

Bu borcu oluşturur.

```php
$balance = $amount;
```

Sonra ödemeler sırayla işlenir:

```php
gross = pay_amount
change = change_amount
net = gross - change
```

Eğer ödeme void değilse:

```php
$balance = max($balance - $net, 0);
```

Eğer ödeme void ise:

```text
Bakiye düşmez
Credit = 0
Net = 0
```

---

### 10.7 Statement Tablosu

Kolonlar:

```text
Date
Type
Description
Debit
Credit
Change
Net
Balance
Method
Cashier
Note
```

Type değerleri:

```text
Credit Created
Payment
Voided Payment
Corrected Payment
```

---

### 10.8 Summary

```text
Total Debt
Paid
Remaining
Payment Count
Voided Count
Last Payment
```

---

### 10.9 Excel Export

Export class:

```php
CustomerStatementExport
```

Export sadece seçili contract olduğunda çalışır.

Controller güvenliği:

```php
if ($contract === '') {
    return back()->with('error', 'Please select a customer statement before exporting.');
}
```

Export kolonları:

```text
Date
Type
Description
Debit
Credit
Change
Net
Balance
Method
Cashier
Note
Payment ID
Customer
Contract
Phone
Passport
Branch
Credit ID
LogicalRef
ClientRef
Credit Date
Total Debt
Paid
Remaining
Credit Status
```

---

## 11. Raporların Genel Değerlendirmesi

### 11.1 Tamamlanan Raporlar

```text
Payment Calendar Report              ✅
Overdue Payments Report              ✅
Collection Performance Report        ✅
Daily Cash Closing Report            ✅
Collection Trend Report              ✅
Promise To Pay Report                ✅
Recovery Effectiveness Report        ✅
Customer Statement Report            ✅
```

---

### 11.2 Raporların Birbirini Tamamlaması

| Rapor | Cevapladığı Soru |
|---|---|
| Payment Calendar Report | Hangi gün kim ödeme yapmalı / yaptı? |
| Overdue Payments Report | Kim ödeme planına göre geride? |
| Collection Performance Report | Kasiyer ve şube ne kadar tahsilat yaptı? |
| Daily Cash Closing Report | Günlük kasa kaç kapandı? |
| Collection Trend Report | Tahsilat yükseliyor mu düşüyor mu? |
| Promise To Pay Report | Kim söz verdi, sözünü tuttu mu? |
| Recovery Effectiveness Report | Borçlulardan ne kadar geri tahsil edildi? |
| Customer Statement Report | Tek müşterinin hesap ekstresi nedir? |

---

### 11.3 Ortak Güçlü Noktalar

- Filtreler var
- Pagination kullanılan yerlerde eklendi
- Excel export gereken raporlara eklendi
- Void ödemeler doğru ele alındı
- Change amount net hesaptan düşüldü
- Local amount / local paid önceliklendirildi
- Branch ve cashier analizleri ayrıştırıldı
- Raporlar birbirini tekrar etmeyecek şekilde düzenlendi
- UI/UX sade tutuldu
- Tablolarda horizontal scroll kullanıldı
- PostgreSQL uyumlu sorgular kullanıldı

---

## 12. Performans ve Production Notları

### 12.1 DB'yi Zorlamamak İçin Yapılanlar

- Büyük raporlarda pagination kullanıldı.
- Search sonuçlarında pagination eklendi.
- Gereksiz tekrar tablolar kaldırıldı.
- Export filtreleri request ile taşındı.
- Aggregate işlemleri SQL tarafında yapıldı.
- Void ödemeler baştan filtrelendi.
- Tablolarda sadece gereken kolonlar `get([...])` ile çekildi.

---

### 12.2 Dikkat Edilmesi Gerekenler

İleri seviye production için ileride şu indexler faydalı olabilir:

```sql
credits(contract)
credits(branch)
credits(source_id)
credits(name)
credits(phone)
credits(willpaiddate)

credit_payments(credit_source_id)
credit_payments(created_at)
credit_payments(branch)
credit_payments(created_by_name)
credit_payments(voided_at)
```

PostgreSQL `ILIKE '%text%'` aramaları büyüyen veride yavaşlayabilir. Çok büyük veride trigram index düşünülebilir:

```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX credits_name_trgm_idx ON credits USING gin (name gin_trgm_ops);
CREATE INDEX credits_phone_trgm_idx ON credits USING gin (phone gin_trgm_ops);
```

---

## 13. Önerilen Sonraki Raporlar

Bu modül artık güçlü bir raporlama temel yapısına sahiptir.

Sonraki eklenebilecek raporlar:

1. **Executive Dashboard Report**
   - Tüm KPI'ların tek ekranda yönetim özeti

2. **Branch Comparison Report**
   - Şubelerin borç, tahsilat, recovery, promise performansı karşılaştırması

3. **Cashier Ranking Report**
   - Kasiyerlerin dönemsel sıralaması

4. **Risky Customers Report**
   - Uzun süredir ödeme yapmayan ve yüksek borçlu müşteriler

5. **Payment Method Analysis Report**
   - Cash / Card / Phone kullanım oranları

---

## 14. Sonuç

Bu raporlama modülü artık hem operasyonel hem yönetimsel ihtiyaçları karşılayacak seviyededir.

Tahsilat ekibi için:

```text
Overdue
Promise To Pay
Customer Statement
```

Muhasebe için:

```text
Daily Cash Closing
Customer Statement
Payment Calendar
```

Yönetim için:

```text
Collection Performance
Collection Trend
Recovery Effectiveness
```

en değerli raporlardır.

Genel değerlendirme:

```text
Durum: Production için uygun
Kod mantığı: Tutarlı
Hesaplamalar: Net ve doğru
UI/UX: Kullanılabilir
Performans: Kontrollü
Export: Gerekli raporlarda mevcut
```

