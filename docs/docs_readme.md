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
│   └── deployment.md
│
├── database/
│   ├── migrations.md
│   ├── models.md
│   └── indexes.md
│
├── queue/
│   ├── queue-configuration.md
│   ├── worker-usage.md
│   └── supervisor.md
│   
├── commands/
│   └── sync_run_command.md
│
├── seeders/
│   └── admin_user_seeder.md
│
├── tinker/
│   └── credit_payment_tinker.md
│
├── sync/
│   ├── overview.md
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

# 📚 Project Documentation Index

Bu klasör, **Accounts Receivable** projesinin tüm teknik ve işsel dokümantasyonunu içerir.

Amaç:
- Yeni geliştiricilerin projeye hızlı adapte olması
- Sistem mimarisinin, iş kurallarının ve kritik kararların kalıcı olarak belgelenmesi
- Test / canlı ortamlar arasında hatasız geçiş sağlanması

---

## 📌 Önerilen Okuma Sırası

Projeyi ilk kez inceleyenler için tavsiye edilen sıra:

1. **setup/** – Ortam kurulumu ve deploy
2. **security/** – Auth, roller ve gizli bilgiler
3. **database/** – DB yapısı, modeller ve index’ler
4. **sync/** – MSSQL → PostgreSQL senkron mimarisi
5. **queue/** – Queue & worker altyapısı
6. **business/** – İş kuralları ve finansal mantık
7. **commands/** – Artisan komutları
8. **seeders/** – İlk veriler
9. **tinker/** – Test & debug örnekleri

---

## 📁 Klasörler ve İçerikleri

### 🔧 setup/
Ortam kurulumu ve production hazırlıkları
- `local_setup.md` – Local geliştirme ortamı
- `env_configuration.md` – `.env` ayarları
- `deployment.md` – Production deploy rehberi

---

### 🔐 security/
Güvenlik ve erişim kontrolü
- `secrets_and_env.md` – Gizli bilgiler ve env kuralları
- `roles-and-access.md` – Roller, auth ve middleware

---

### 🗄️ database/
Veritabanı yapısı
- `migrations.md` – Migration açıklamaları
- `models.md` – Eloquent modeller
- `indexes.md` – Performans index’leri

---

### 🔄 sync/
Veri senkronizasyonu (MSSQL → PostgreSQL)
- `overview.md` – Genel mimari
- `job-credits-incremental.md` – Incremental credits sync
- `job-avshocrecat-report.md` – Snapshot rapor sync
- `troubleshooting.md` – Sync sorunları

---

### ⏳ queue/
Queue & worker altyapısı
- `queue_configuration.md` – Queue ayarları
- `worker_usage.md` – Worker kullanımı
- `supervisor.md` – Supervisor konfigürasyonu

---

### 💼 business/
İş kuralları ve finansal mantık
- `rules.md` – Genel iş kuralları
- `payments.md` – Ödeme mantığı
- `audit_and_snapshots.md` – Audit & snapshot stratejileri

---

### 🧾 commands/
Artisan komutları
- `sync_run_command.md` – `sync:run` komutu

---

### 🌱 seeders/
Seed verileri
- `admin_user_seeder.md` – İlk admin kullanıcı

---

### 🧪 tinker/
Test & debug
- `credit_payment_tinker.md` – Tinker örnekleri

---

## 🚀 Hızlı Komutlar

```bash
# Senkron başlat
php artisan sync:run

# Queue worker çalıştır
php artisan queue:work

# Cache temizle
php artisan optimize:clear
```

---

## 📝 Dokümantasyon Kuralları

- Her yeni özellik için ilgili klasöre `.md` eklenmeli
- İş kuralı değişirse **business/** güncellenmeli
- DB değişikliklerinde **database/** dokümanları revize edilmeli
- Sync/job değişiklikleri **sync/** altında belgelenmeli

---

## ✅ Durum

Bu dokümantasyon seti:
- Production-ready
- Uzun vadeli bakım için uygun
- Yeni geliştirici onboarding için yeterlidir