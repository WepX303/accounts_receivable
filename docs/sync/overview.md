# 🔄 Sync Overview (MSSQL → PostgreSQL)

Bu doküman, projedeki **veri senkronizasyon mimarisini** yüksek seviyede açıklar. Amaç; hangi verinin **nasıl**, **ne zaman** ve **hangi stratejiyle** taşındığını netleştirmektir.

> Bu projede senkronizasyon **tek yönlüdür**:
> **MSSQL (source) → PostgreSQL (target)**

---

## Amaç

- Senkron mimarisini yeni geliştiricilere hızlıca anlatmak
- Incremental vs Snapshot yaklaşımlarını ayırmak
- Test → canlı geçişinde riskleri minimize etmek

---

## Konum

`docs/sync/overview.md`

---

## Genel Mimari

```
[MSSQL]
   │
   │  (sqlsrv)
   ▼
[Laravel Jobs]
   │
   │  (queue: database)
   ▼
[PostgreSQL]
```

- Kaynak sistem: **MSSQL (Logo / Tiger)**
- Uygulama katmanı: **Laravel (Jobs + Queue)**
- Hedef sistem: **PostgreSQL**

---

## Senkron Türleri

Bu projede **iki farklı senkron stratejisi** vardır:

### 1️⃣ Incremental Sync

- Sadece **değişen kayıtlar** alınır
- Büyük tablolar için performanslıdır
- Kaynak tabloda **artımlı bir alan** gerekir

Bu projede:

- Tablo: `CREDITS_TEST`
- Alan: `rv_bigint`

> Bu alan MSSQL `rowversion`’dan türetilmiştir.

---

### 2️⃣ Snapshot Sync (Refresh)

- Hedef tablo **tamamen temizlenir** (`truncate`)
- Kaynaktan **tüm veri yeniden çekilir**

Bu projede:

- Kaynak: `AVSHOCRECAT` (Stored Procedure)
- Hedef: `avshocrecat_report`

> Amaç: raporun **her zaman tutarlı** olması.

---

## Senkron Job’ları

| Job                        | Tür         | Açıklama                   |
| -------------------------- | ----------- | -------------------------- |
| `SyncCreditsJob`           | Incremental | MSSQL CREDITS → PG credits |
| `SyncAvshocrecatReportJob` | Snapshot    | SP output → PG report      |

Bu job’lar genellikle **birlikte** çalıştırılır.

---

## Tetikleme Mekanizması

### Artisan Command

```bash
php artisan sync:run
```

Bu komut:

1. `SyncCreditsJob`
2. `SyncAvshocrecatReportJob`

job’larını queue’ya dispatch eder.

---

## Queue Rolü

- Senkron işlemleri **request lifecycle’dan ayırır**
- Uzun süren işlemlerin uygulamayı kilitlemesini önler
- Retry / timeout kontrolü sağlar

Bu projede:

- Queue driver: `database`
- Worker: Supervisor veya manuel

---

## Konfigürasyon Kaynakları

Senkron davranışı **koddan bağımsız** olarak `.env` üzerinden kontrol edilir.

### MSSQL

- `MSSQL_CREDITS_TABLE`
- `MSSQL_AVSHOCRECAT_PROC`
- `MSSQL_AVSHOCRECAT_PASPORT`

### PostgreSQL

- `PG_CREDITS_TABLE`
- `PG_AVSHOCRECAT_TABLE`

---

## Test → Canlı Geçiş Stratejisi

### Test Ortamı

```env
MSSQL_CREDITS_TABLE=BPA.dbo.CREDITS_TEST
MSSQL_AVSHOCRECAT_PROC=BPA.dbo.AVSHOCRECAT_TEST
```

### Canlı Ortam

```env
MSSQL_CREDITS_TABLE=BPA.dbo.CREDITS
MSSQL_AVSHOCRECAT_PROC=BPA.dbo.AVSHOCRECAT
```

> Kod değişmez, sadece `.env` değişir.

---

## Riskler ve Önlemler

### 1) Snapshot sırasında rapor boş görünmesi

- Beklenen davranış (truncate → insert)
- UI tarafında loading gösterilmeli

---

### 2) Incremental state bozulması

- `rv_bigint` yanlış güncellenirse veri kaçabilir
- `sync_state` tablosu ile state yönetimi önerilir

---

### 3) SP output değişiklikleri

- Kolon isimleri değişirse mapping bozulur
- Job güncellenmelidir

---

## İyileştirme Önerileri

- Incremental sync için:
    - transaction + upsert
    - satır sayısı / süre loglama

- Snapshot sync için:
    - atomic refresh (tmp table + swap)

- Senkron sonuçlarını izlemek için:
    - `sync_logs` tablosu

---

## Özet

- Senkron **tek yönlüdür** (MSSQL → PG)
- İki strateji vardır: incremental + snapshot
- Queue, performans ve güvenlik için kritiktir
- `.env` ile test/canlı ayrımı yapılır
- Bu mimari uzun vadeli ve ölçeklenebilirdir
