# Docs Index

Bu klasör, projenin kurulumundan senkronizasyon mimarisine kadar tüm teknik dokümantasyonu içerir.

> Standart: Her doküman aynı başlık düzenini kullanır:
> **Amaç → Konum → Bağımlılıklar (ENV/Config) → Akış → DB/İlişkiler → Komutlar → Hata senaryoları → İyileştirme önerileri**

---

## 📁 Önerilen `docs/` Yapısı (Uygulanmış)

```
docs/
├── README.md
├── setup/
│   ├── local-setup.md
│   ├── env-configuration.md
│   ├── database-connections.md
│   └── deployment.md
│
├── database/
│   ├── migrations.md
│   ├── models.md
│   ├── relations.md
│   └── indexes.md
│
├── queue/
│   ├── queue-configuration.md
│   ├── worker-usage.md
│   └── supervisor.md
│
├── sync/
│   ├── overview.md
│   ├── command-sync-run.md
│   ├── job-avshocrecat-report.md
│   ├── job-credits-incremental.md
│   └── troubleshooting.md
│
├── business/
│   ├── rules.md
│   ├── payments.md
│   └── audit-and-snapshots.md
│
└── security/
    ├── secrets-and-env.md
    └── roles-and-access.md
```

---

## ✅ Hazır Olan Dokümanlar

Aşağıdaki dokümanlar hazır (bu sohbet içinde üretildi). Bunları belirtilen hedef dosya yollarına taşı:

### setup/
- ✅ `setup/env-configuration.md`  
  Kaynak: `environment-configuration.md`

### database/
- ✅ `database/migrations.md`  
  Kaynak: `database-migrations.md`
- ✅ `database/models.md`  
  Kaynak: `models-documentation.md`

### queue/
- ✅ `queue/queue-configuration.md`  
  Kaynak: `queue-configuration.md`

### sync/
- ✅ `sync/command-sync-run.md`  
  Kaynak: `sync-run-command.md`
- ✅ `sync/job-avshocrecat-report.md`  
  Kaynak: `sync-avshocrecat-report-job.md`

### seeders/
- (Opsiyonel klasör) ✅ `database/seeders/admin-user-seeder.md`  
  Kaynak: `admin-user-seeder.md`

---

## 🔜 Sıradaki Dokümanlar (Eksik Olanlar)

Aşağıdakiler, mevcut kod ve .env bilgilerine göre sırayla hazırlanmalı. Her madde için “Gerekli input” alanını sağladığında dosyayı tamamlayacağız.

### 1) `setup/local-setup.md`
**Amaç:** Local ortamda projeyi ayağa kaldırma (PHP/Composer, migration, queue, env).  
**Gerekli input:** Yok (mevcut bilgilere göre yazılabilir).

### 2) `setup/database-connections.md`
**Amaç:** `config/database.php` içinde `pgsql` ve `sqlsrv` bağlantılarının anlatımı.  
**Gerekli input:** `config/database.php` içeriği.

### 3) `setup/deployment.md`
**Amaç:** Sunucuda deploy adımları (composer, migrate, cache, queue restart).  
**Gerekli input:** Deploy yöntemin (git pull mı, zip mi), supervisor var mı?

### 4) `database/relations.md`
**Amaç:** FK + Eloquent ilişkiler haritası ve örnek eager loading.  
**Gerekli input:** Ek modeller varsa (ör. SyncState modeli, AvshocrecatReport modeli) ve kullanılan controller/service ilişkileri.

### 5) `database/indexes.md`
**Amaç:** Sorgu bazlı index stratejisi (credits_test, credit_payments, avshocrecat_report).  
**Gerekli input:** En sık kullanılan ekranlar/sorgular (listeleme filtreleri, arama alanları).

### 6) `queue/worker-usage.md`
**Amaç:** Worker komutları, parametreler, timeout/retry önerileri.  
**Gerekli input:** Yok (genel + projeye özel yazılabilir).

### 7) `queue/supervisor.md`
**Amaç:** Production’da queue worker’ı Supervisor ile yönetim.  
**Gerekli input:** Sunucuda Supervisor kullanıyor musun? (Varsa mevcut conf).

### 8) `sync/overview.md`
**Amaç:** Incremental (rowversion/rv_bigint) + snapshot rapor (truncate+insert) mimarisi.  
**Gerekli input:** `SyncCreditsJob` kodu (incremental mantık kesinleşsin).

### 9) `sync/job-credits-incremental.md`
**Amaç:** `SyncCreditsJob` detayları (last rv, chunk, upsert, hata senaryosu).  
**Gerekli input:** `SyncCreditsJob` kodu.

### 10) `sync/troubleshooting.md`
**Amaç:** En sık senkron/queue hataları ve çözüm checklist’i.  
**Gerekli input:** Şu ana kadar karşılaştığın tipik hatalar (varsa log örnekleri).

### 11) `business/rules.md`
**Amaç:** `local_remaining`, `local_closed`, local override mantığı, toleranslar.  
**Gerekli input:** İş kuralı kararları (local null iken “closed=true” kesin mi?).

### 12) `business/payments.md`
**Amaç:** Ödeme ekleme akışı, `pay_amount`, `change_amount`, `applied_amount`, method kuralları.  
**Gerekli input:** Ödeme ekranının validasyon kuralları (min/max, method dağılımı).

### 13) `business/audit-and-snapshots.md`
**Amaç:** `credit_payments` snapshot alanları (müşteri + created_by snapshot) neden var, nasıl dolduruluyor.  
**Gerekli input:** Payment kayıt edilirken snapshot’ı dolduran kod (controller/service).

### 14) `security/secrets-and-env.md`
**Amaç:** .env güvenliği, `.env.example`, erişim politikaları, secret yönetimi.  
**Gerekli input:** Yok (mevcut env bilgisine göre yazılabilir).

### 15) `security/roles-and-access.md`
**Amaç:** `UserRoleEnum`, admin/user yetkileri, middleware/guard yaklaşımı.  
**Gerekli input:** `UserRoleEnum` dosyası + varsa middleware/policy kodları.

---

## 🧾 Dosya Şablonu (Her doküman için)

Aşağıdaki şablonu yeni doküman açarken kullan:

```md
# Başlık

## Amaç

## Konum

## Bağımlılıklar (ENV/Config)

## Akış

## DB / İlişkiler

## Komutlar

## Hata Senaryoları ve Çözümleri

## İyileştirme Önerileri
```

---

## ✅ Devam Planı (Adım Adım)

Aşağıdaki sırayla ilerleyelim:

1) `setup/local-setup.md` (input gerektirmez)
2) `queue/worker-usage.md` (input gerektirmez)
3) `security/secrets-and-env.md` (input gerektirmez)
4) `setup/database-connections.md` (**config/database.php lazım**)
5) `sync/job-credits-incremental.md` (**SyncCreditsJob lazım**)

Bu sırayla hem hızlı ilerleriz hem de eksik input bekleyenler net olur.

