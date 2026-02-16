# ⚙️ Environment (.env) Konfigürasyon Dokümantasyonu

Bu doküman, projenin `.env` dosyasında bulunan ayarların ne işe yaradığını ve senkronizasyon (MSSQL → PostgreSQL) akışında nasıl kullanıldığını açıklar.

> ⚠️ Güvenlik Notu
>
> - `.env` dosyası **git’e commit edilmemelidir**.
> - İçindeki `APP_KEY`, DB kullanıcı/şifreleri ve MSSQL şifreleri gizli bilgidir.
> - Versiyon kontrolüne yalnızca `.env.example` koyulmalıdır.

---

## 1) Uygulama Ayarları

```env
APP_NAME=accounts_receivable
APP_ENV=local
APP_KEY=...
APP_DEBUG=true
APP_URL=http://localhost
```

- **APP_NAME**: Uygulama adı.
- **APP_ENV**: Ortam (local, staging, production).
- **APP_KEY**: Şifreleme anahtarı (Laravel için kritik).
- **APP_DEBUG**: Hata ekranlarını açar/kapatır (production’da `false` olmalı).
- **APP_URL**: Uygulama ana URL.

---

## 2) Log Ayarları

```env
LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug
```

- **LOG_CHANNEL**: Logların hangi kanala yazılacağı.
- **LOG_LEVEL**: Log seviyeleri (debug, info, warning, error).

> Production’da genelde `LOG_LEVEL=info` veya `warning` önerilir.

---

## 3) Ana Veritabanı (PostgreSQL)

```env
DB_CONNECTION=pgsql
DB_HOST=10.10.8.37
DB_PORT=5433
DB_DATABASE=accounts_receivable
DB_USERNAME=admin
DB_PASSWORD=admin123
```

Bu ayarlar Laravel’in **varsayılan veritabanı bağlantısını** belirler.

- **DB_CONNECTION=pgsql** → Laravel migrations ve Eloquent default olarak PostgreSQL kullanır.
- **DB_HOST / DB_PORT** → PostgreSQL sunucusu.
- **DB_DATABASE / DB_USERNAME / DB_PASSWORD** → bağlantı kimlik bilgileri.

### Not: Alternatif host/port satırları

Dosyada bazı seçenekler yorum satırına alınmış:

```env
# DB_HOST=217.174.231.218
# DB_PORT=3392

# DB_HOST=127.0.0.1
# DB_PORT=5432
```

Bu satırlar farklı ortamlara hızlı geçiş için tutuluyor olabilir.

---

## 4) MSSQL Kaynak Bağlantısı

```env
MSSQL_HOST=10.10.11.143
MSSQL_PORT=1433
MSSQL_DATABASE=BPA
MSSQL_USERNAME=sa
MSSQL_PASSWORD=***
```

Bu ayarlar senkronizasyon job’larının MSSQL’e bağlanması için kullanılır.

- MSSQL bağlantısı genelde `config/database.php` içinde ayrı bir connection olarak tanımlanır.
- `MSSQL_DATABASE=BPA` olduğundan tablolar ve SP çağrıları BPA üzerinden yapılır.

> ⚠️ Öneri: MSSQL şifresini düz metin yerine secret manager ile yönetmek (production).

---

## 5) Senkronizasyon Kaynakları (MSSQL)

```env
MSSQL_CREDITS_TABLE=BPA.dbo.CREDITS_TEST
MSSQL_AVSHOCRECAT_PROC=BPA.dbo.AVSHOCRECAT_TEST
MSSQL_AVSHOCRECAT_PASPORT=
```

### 5.1 `MSSQL_CREDITS_TABLE`

- Incremental senkron yapılacak MSSQL tablo adıdır.
- Test ortamında `CREDITS_TEST` kullanılmış.

### 5.2 `MSSQL_AVSHOCRECAT_PROC`

- Rapor için çağrılacak Stored Procedure adıdır.
- Test ortamında `AVSHOCRECAT_TEST` kullanılmış.

### 5.3 `MSSQL_AVSHOCRECAT_PASPORT`

- SP çağrısı için opsiyonel pasaport filtresi.
- Boşsa tüm kayıtlar alınır.

> Not: Komut tarafında `--passport=` opsiyonu ile de filtre verilebiliyor. Eğer ikisi bir arada kullanılacaksa, hangi kaynağın öncelikli olduğu kod tarafında standardize edilmelidir.

---

## 6) PostgreSQL Hedef Tablo Adları

```env
PG_CREDITS_TABLE=credits
PG_AVSHOCRECAT_TABLE=avshocrecat_report
```

Senkronizasyon job’ları PostgreSQL tarafında hangi tabloya yazacağını buradan okur.

- **PG_CREDITS_TABLE**: Credits verisinin yazıldığı tablo.
- **PG_AVSHOCRECAT_TABLE**: Avshocrecat raporunun yazıldığı tablo.

> Bu yaklaşım sayesinde test → canlı geçişinde yalnızca `.env` değişir, kod değişmez.

---

## 7) Cache / Session / Queue

```env
CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=pgsql
```

### Queue

- **QUEUE_CONNECTION=database**
    - Queue driver olarak database kullanılır.
    - Bekleyen job’lar `jobs` tablosunda tutulur.

- **DB_QUEUE_CONNECTION=pgsql**
    - Queue tablolarının hangi DB bağlantısında olduğu.
    - PostgreSQL üzerinde `jobs` tablosu kullanılacağı anlamına gelir.

> ⚠️ Job’ların çalışması için worker gerekli:
>
> ```bash
> php artisan queue:work
> ```

### Cache & Session

- Dosya tabanlı cache ve session (local için uygundur).

---

## 8) Redis (Opsiyonel - Şu an Kapalı)

```env
# REDIS_CLIENT=predis
# REDIS_HOST=10.10.8.37
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

Redis şimdilik devre dışı.

> İleride queue/cache için Redis’e geçilecekse `QUEUE_CONNECTION=redis` + redis ayarları aktif edilebilir.

---

## 9) Mail (Local - Mailpit)

```env
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Local geliştirme ortamında mail test etmek için Mailpit kullanılıyor.

---

## 10) Pusher / Vite Ayarları

```env
PUSHER_...
VITE_...
```

Bu alanlar broadcasting / real-time özellikler için ayrılmış. Şu an boş.

---

## ✅ Önerilen `.env.example` Şablonu

> Aşağıdaki şablon güvenli bir örnek dosyadır. Şifreler ve gerçek IP’ler bulunmaz.

```env
APP_NAME=accounts_receivable
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

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

MSSQL_CREDITS_TABLE=BPA.dbo.CREDITS_TEST
MSSQL_AVSHOCRECAT_PROC=BPA.dbo.AVSHOCRECAT_TEST
MSSQL_AVSHOCRECAT_PASPORT=

PG_CREDITS_TABLE=credits
PG_AVSHOCRECAT_TABLE=avshocrecat_report

QUEUE_CONNECTION=database
DB_QUEUE_CONNECTION=pgsql
SESSION_DRIVER=file
CACHE_DRIVER=file
```

---
