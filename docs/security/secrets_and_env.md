# 🔐 Secrets ve .env Güvenliği

Bu doküman, projede kullanılan **.env** dosyasının güvenli yönetimini, ortam ayrımını (local / staging / production) ve senkronizasyon odaklı gizli bilgilerin nasıl ele alınması gerektiğini açıklar.

---

## Amaç

- Gizli bilgilerin (şifreler, anahtarlar) sızmasını engellemek
- `.env` / `.env.example` standardını oturtmak
- Test → canlı geçişlerinde güvenli ve hatasız ilerlemek

---

## Konum

`docs/security/secrets-and-env.md`

---

## Kritik Gizli Bilgiler

Aşağıdaki değişkenler **kesinlikle gizli** kabul edilir:

- `APP_KEY`
- `DB_PASSWORD`
- `MSSQL_PASSWORD`
- Mail server kullanıcı adı / şifresi
- Harici servis API key / secret değerleri

> ⚠️ Bu bilgiler **asla** git reposuna commit edilmemelidir.

---

## Git ve Versiyon Kontrol Kuralları

### 1) `.gitignore` Ayarı

Proje kökünde `.gitignore` içinde şunlar bulunmalıdır:

```gitignore
.env
.env.*
```

Bu sayede hiçbir ortam dosyası yanlışlıkla repoya girmez.

---

### 2) `.env.example` Kullanımı

`.env.example` dosyası:

- Gerçek şifre içermez
- Değişken isimlerini ve yapıyı gösterir
- Yeni geliştiriciler için referanstır

Örnek:

```env
APP_NAME=accounts_receivable
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=accounts_receivable
DB_USERNAME=postgres
DB_PASSWORD=

MSSQL_HOST=127.0.0.1
MSSQL_PORT=1433
MSSQL_DATABASE=BPA
MSSQL_USERNAME=sa
MSSQL_PASSWORD=
```

---

## Ortam Stratejisi

### Local Ortam

Önerilen ayarlar:

```env
APP_ENV=local
APP_DEBUG=true
LOG_LEVEL=debug
```

- Hata ekranları açık
- Loglar detaylı

---

### Production Ortam

Önerilen ayarlar:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=info
```

- Hata detayları kullanıcıya gösterilmez
- Loglar daha kontrollü

> ⚠️ `APP_DEBUG=true` production’da **kritik güvenlik açığıdır**.

---

## Senkronizasyon İçin Özel Güvenlik Notları

Bu projede senkronizasyon büyük ölçüde `.env` üzerinden kontrol edilir:

- `MSSQL_CREDITS_TABLE`
- `MSSQL_AVSHOCRECAT_PROC`
- `PG_CREDITS_TABLE`
- `PG_AVSHOCRECAT_TABLE`

### Neden Önemli?

- Proc ve tablo isimleri kod içinde hardcode edilmemiştir
- Yanlış `.env` ile yanlış tabloya yazma riski vardır

### Öneri

- Production `.env` dosyası **manuel ve kontrollü** hazırlanmalı
- Test değerleri production sunucuda kesinlikle kalmamalı (`*_TEST`)

---

## Erişim ve Dosya İzinleri

Production sunucuda:

```bash
chmod 600 .env
chown www-data:www-data .env
```

- Sadece uygulama kullanıcısı okuyabilmeli
- Diğer kullanıcılar erişememeli

---

## Hata Senaryoları ve Çözümleri

### Yanlış DB’ye bağlanma

Belirti:
- Migration yanlış DB’de çalışır
- Veri kaybı riski oluşur

Kontrol:
- `DB_CONNECTION`
- `DB_HOST / DB_DATABASE`

---

### MSSQL Senkron Çalışmıyor

Belirti:
- Job hata alır
- Timeout veya connection error

Kontrol:
- `MSSQL_HOST / PORT`
- `MSSQL_USERNAME / PASSWORD`
- Firewall / network erişimi

---

## İyileştirme Önerileri

- Production’da secrets için Vault / Secret Manager kullan
- DB kullanıcılarını minimum yetki ile tanımla
- `.env` değişikliklerini dokümante et (kim, ne zaman, neden)

---

## Özet

- `.env` dosyası projenin **en kritik güvenlik noktasıdır**
- Git’e asla girmemelidir
- Ortam bazlı ayarlar net ayrılmalıdır
- Senkronizasyon projelerinde `.env` hataları **doğrudan veri kaybına** yol açabilir

