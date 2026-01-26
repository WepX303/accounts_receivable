# 🛡️ Roles & Access Control

Bu doküman, projede kullanıcı rolleri, erişim kontrol yaklaşımı ve `AuthToken` middleware davranışını açıklar.

> Kapsam:
> - `UserRoleEnum`
> - `users.role` alanı ve enum cast
> - `AuthToken` middleware (cookie tabanlı auth)
> - Status kontrolü (aktif/pasif)

---

## Amaç

- Rol isimlerini ve anlamlarını netleştirmek
- Auth akışında “token + cookie” yaklaşımını dokümante etmek
- Admin/Analyst/User gibi rollerin erişim politikasını standardize etmek

---

## Konum

- Enum: `app/Enums/UserRoleEnum.php`
- Middleware: `app/Http/Middleware/AuthToken.php`
- Doküman: `docs/security/roles-and-access.md`

---

## 1) Roller (UserRoleEnum)

```php
enum UserRoleEnum: string
{
    case USER  = 'User';
    case ADMIN = 'Admin';
    case ANA   = 'Analyst';
}
```

### Roller ve Anlamları

| Enum | String Value | Anlam |
|------|--------------|------|
| `USER` | `User` | Standart kullanıcı |
| `ADMIN` | `Admin` | Yönetici |
| `ANA` | `Analyst` | Analist / rapor kullanıcı rolü |

---

## 2) User Modelinde Enum Cast

`User` modelinde:

```php
protected $casts = [
    'role' => UserRoleEnum::class,
    'token_expires_at' => 'datetime',
];
```

### Etkisi

- DB’de `role` string saklanır
- PHP tarafında `UserRoleEnum` gibi kullanılır

Örnek:

```php
if ($user->role === UserRoleEnum::ADMIN) {
   // ...
}
```

> DB’ye yazarken `UserRoleEnum::ADMIN->value` (`Admin`) stringi gider.

---

## 3) Auth Yaklaşımı (Cookie + Token)

Bu proje, Laravel’in klasik session guard’ından ziyade **cookie’de token** yaklaşımı kullanır:

- Cookie adı: `auth_token`
- DB alanları:
  - `users.token`
  - `users.token_expires_at`

### Akış

1. Kullanıcı login olur
2. Sistem `users.token` üretir ve DB’ye kaydeder
3. Tarayıcıya `auth_token` cookie set edilir
4. Her request’te middleware cookie’yi kontrol eder

---

## 4) `AuthToken` Middleware Davranışı

### Konum

`app/Http/Middleware/AuthToken.php`

### Akış (Step by Step)

1) Cookie’den token al:

```php
$token = $request->cookie('auth_token');
```

2) Token yoksa:
- `login` route’una yönlendir

3) Token varsa DB’den user bul:

- `token` eşleşmeli
- `token_expires_at > now()` olmalı

4) User yoksa:
- login’e yönlendir
- cookie’yi sil (`forget`)

5) User pasifse (`status=false`):
- `Auth::logout()`
- cookie sil
- login’e yönlendir + hata mesajı

6) User aktifse:
- `Auth::login($user->fresh())`
- Blade için `view()->share('user', Auth::user())`
- request devam eder

---

## 5) Status Kuralı (Aktif/Pasif)

`users.status` alanı:

- `true` → aktif
- `false` → pasif

Middleware pasif kullanıcıyı sistemden çıkarır.

---

## 6) Role Based Access (Önerilen Standart)

Şu an middleware sadece “giriş var mı?” kontrol ediyor.

Rollere göre sayfa kısıtlamak için öneriler:

### A) Role Middleware

Örn:
- `role:Admin`
- `role:Analyst`

Route kullanımı:

```php
Route::middleware(['auth.token', 'role:Admin'])->group(function () {
    // admin routes
});
```

### B) Policy/Gate

Özellikle ödeme işlemleri için örnek kural:

- Admin + Analyst ödeme girebilir
- User sadece görüntüleyebilir

---

## 7) Sık Hatalar ve Çözümleri

### Kullanıcı login görünüyor ama auth düşüyor

- `token_expires_at` dolmuş olabilir
- Cookie tarayıcıda silinmiş olabilir

### Kullanıcı pasif edildi ama hala erişiyor

- Middleware ilgili route’larda uygulanmıyor olabilir
- `.env/config` cache temizlenmemiş olabilir

Komutlar:

```bash
php artisan config:clear
php artisan cache:clear
```

---

## İyileştirme Önerileri

- Token’ı plaintext yerine hash saklama düşünülebilir
- Role middleware eklenmeli
- Kritik aksiyonlar için audit/log eklenmeli

---

## Özet

- Roller `UserRoleEnum` ile tanımlı
- `User.role` enum cast ile okunuyor
- Auth cookie token ile yapılıyor (`auth_token`)
- Pasif kullanıcı middleware tarafından otomatik logout edilir
- Rollere göre erişim kontrolü için role middleware/policy önerilir

