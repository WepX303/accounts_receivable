# 📦 MSSQL → PostgreSQL Senkronizasyon Sistemi

Bu doküman, Laravel projesinde kullanılan **incremental tablo senkronizasyonu** ve **rapor yenileme** işlemlerini yöneten `sync:run` Artisan komutunu ve ilişkili Job yapılarını detaylı şekilde açıklar.

---

## 🎯 Amaç

Bu senkronizasyon sistemi ile:

- MSSQL üzerindeki **CREDITS** tablosu PostgreSQL'e **incremental (rowversion)** mantığıyla aktarılır.
- MSSQL Stored Procedure **AVSHOCRECAT** çıktısı PostgreSQL tarafında **truncate + insert** yöntemiyle güncellenir.
- Tüm işlemler **Queue (Job)** yapısı üzerinden asenkron çalışır.
- Tek bir Artisan komutu ile tüm süreç tetiklenir.

---

## 🧱 Mimari Genel Bakış

```
Artisan Command
   |
   v
sync:run
   |
   ├── SyncCreditsJob (incremental)
   |
   └── SyncAvshocrecatReportJob (truncate + insert)
```

---

## 🧩 Kullanılan Bileşenler

| Bileşen | Açıklama |
|-------|---------|
| Artisan Command | Job’ları tetikleyen CLI komutu |
| Queue / Jobs | Asenkron veri senkronizasyonu |
| MSSQL | Kaynak veritabanı |
| PostgreSQL | Hedef veritabanı |
| rowversion | Incremental veri takibi |

---

## ⚙️ Artisan Komutu

### Komut Adı

```bash
php artisan sync:run {--passport=}
```

### Komut Sınıfı

```php
App\Console\Commands\SyncRunCommand
```

---

## 🔧 Komut Parametreleri

| Parametre | Açıklama |
|----------|----------|
| `--passport` | AVSHOCRECAT raporu için PASPORT filtresi |
| Boş bırakılırsa | Tüm pasaportlar dahil edilir |

---

## ▶️ Kullanım Örnekleri

### 🔹 Tüm Kayıtlar İçin

```bash
php artisan sync:run
```

veya

```bash
php artisan sync:run --passport=
```

---

### 🔹 Belirli Bir PASPORT İçin

```bash
php artisan sync:run --passport=123456
```

---

## 🧠 Komutun Çalışma Akışı

Komut çalıştırıldığında aşağıdaki adımlar sırayla tetiklenir:

### 1️⃣ SyncCreditsJob

- MSSQL tablo: **CREDITS**
- PostgreSQL tablo: **credits**
- **Incremental senkronizasyon** yapılır
- `rowversion (rv_bigint)` alanı kullanılır
- Yeni veya değişmiş kayıtlar **upsert** edilir

```php
SyncCreditsJob::dispatch();
```

---

### 2️⃣ SyncAvshocrecatReportJob

- MSSQL Stored Procedure: **AVSHOCRECAT**
- PostgreSQL rapor tablosu
- Önce tablo **truncate** edilir
- Ardından SP çıktısı **insert** edilir
- Opsiyonel `PASPORT` filtresi uygulanır

```php
SyncAvshocrecatReportJob::dispatch($passport);
```

---

## 🧵 Queue (Job) Çalıştırma

Job’ların çalışabilmesi için queue worker aktif olmalıdır.

### Tek Seferlik Çalıştırma

```bash
php artisan queue:work --stop-when-empty
```

### Sürekli Çalışma (Production Önerilen)

```bash
php artisan queue:work
```

> Production ortamında **Supervisor** kullanılması önerilir.

---

## 📤 Komut Çıktısı

Komut çalıştırıldığında terminalde aşağıdaki bilgi gösterilir:

```
Dispatched: SyncCreditsJob + SyncAvshocrecatReportJob (passport=ALL)
```

veya

```
Dispatched: SyncCreditsJob + SyncAvshocrecatReportJob (passport=123456)
```

---

## 🔐 Ortam Değişkenleri (.env)

Canlı / test ortamı geçişi için tablo ve Stored Procedure isimleri `.env` üzerinden yönetilir.

### Canlı Ortam

```env
MSSQL_CREDITS_TABLE=CREDITS
MSSQL_AVSHOCRECAT_SP=AVSHOCRECAT
```

### Test Ortamı

```env
MSSQL_CREDITS_TABLE=CREDITS_TEST
MSSQL_AVSHOCRECAT_SP=AVSHOCRECAT_TEST
```

> Bu sayede **kod değişmeden** ortam geçişi yapılabilir.

---

## 🚀 Avantajlar

- ✅ Yüksek performanslı incremental senkronizasyon
- ✅ Büyük veri setlerine uygun
- ✅ Asenkron ve ölçeklenebilir yapı
- ✅ Test / canlı ortam uyumluluğu
- ✅ Tek komutla yönetim

---

## 🔮 Geliştirme Önerileri

- ⏱️ Scheduler (`Kernel.php`) ile otomatik çalıştırma
- 📊 Senkronizasyon durum tablosu
- 🔁 Job retry / timeout ayarları
- 🧾 Admin panel rapor ekranı

---

## 📝 Özet

Bu yapı, **kurumsal MSSQL → PostgreSQL veri senkronizasyonu** için:

- Güvenli
- Performanslı
- Bakımı kolay
- Production-ready

bir çözüm sunar.

---

> 📌 Not: Tüm senkronizasyon işlemleri queue üzerinden çalıştığı için, queue sisteminin aktif olması zorunludur.

