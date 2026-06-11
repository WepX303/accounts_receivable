# 👤 AdminUserSeeder Dokümantasyonu

Bu doküman, Laravel projesinde kullanılan **AdminUserSeeder** sınıfının amacını, çalışma mantığını ve kullanım şeklini detaylı olarak açıklar.

---

## 🎯 Amaç

`AdminUserSeeder`, sistemde **varsayılan bir admin kullanıcısı** oluşturmak için kullanılır.

Bu seeder sayesinde:

- Proje ilk kurulduğunda admin kullanıcı manuel oluşturulmaz
- Tekrarlı seed işlemlerinde **aynı admin kullanıcısı yeniden eklenmez**
- Rol ve yetkilendirme sistemiyle uyumlu bir admin hesabı garanti altına alınır

---

## 📁 Dosya Konumu

```text
database/seeders/AdminUserSeeder.php
```

---

## 🧱 Kullanılan Bileşenler

| Bileşen | Açıklama |
|-------|---------|
| `Seeder` | Laravel seed altyapısı |
| `User` Model | Kullanıcı tablosu işlemleri |
| `UserRoleEnum` | Kullanıcı rol enum yapısı |
| `Hash` | Şifreyi güvenli şekilde hashlemek için |

---

## 🧠 Çalışma Mantığı

Seeder çalıştığında aşağıdaki adımlar izlenir:

1. **Admin email ve telefon numarası** sabit olarak tanımlanır
2. Aynı email **veya** telefon numarasına sahip kullanıcı var mı kontrol edilir
3. Eğer varsa → **hiçbir işlem yapılmadan çıkılır**
4. Yoksa → yeni admin kullanıcı oluşturulur

Bu sayede seeder **idempotent** çalışır (kaç kere çalıştırılırsa çalıştırılsın sonuç değişmez).

---

## 👤 Oluşturulan Admin Kullanıcı Bilgileri

| Alan | Değer |
|----|------|
| Firstname | Admin |
| Lastname | User |
| Email | `admin@gmail.com` |
| Phone Number | `99312345678` |
| Position | Administrator |
| Role | `ADMIN` |
| Status | Aktif (`true`) |
| Password | `12341234` (hashlenmiş) |

> ⚠️ **Not:** Gerçek projelerde varsayılan şifrenin ilk girişte değiştirilmesi önerilir.

---

## 🔐 Güvenlik Detayları

- Şifre **plain text** olarak saklanmaz
- `Hash::make()` kullanılarak güvenli şekilde hashlenir
- Token alanları başlangıçta `null` olarak ayarlanır

```php
'password' => Hash::make('12341234'),
```

---

## 🧾 Kod Akışı

```php
$email = 'admin@gmail.com';
$phone = '99312345678';

if (User::where('email', $email)
    ->orWhere('phonenumber', $phone)
    ->exists()) {
    return;
}
```

Yukarıdaki kontrol sayesinde:

- Aynı email
- Aynı telefon numarası

dan biri bile varsa yeni kayıt eklenmez.

---

## ▶️ Seeder Çalıştırma

### Tek Başına Çalıştırma

```bash
php artisan db:seed --class=AdminUserSeeder
```

---

### DatabaseSeeder İçinden Çağırma

```php
$this->call(AdminUserSeeder::class);
```

Ardından:

```bash
php artisan db:seed
```

---

## 🚀 Avantajlar

- ✅ Tekrarlı çalıştırmalarda güvenli
- ✅ Otomatik admin kullanıcı oluşturma
- ✅ Enum tabanlı rol yönetimi
- ✅ Kurulum sürecini hızlandırır

---

## 🔮 Geliştirme Önerileri

- 🔐 Şifreyi `.env` üzerinden yönetmek
- 📧 Admin email bilgisini konfigürasyona almak
- 🔄 İlk girişte şifre değiştirme zorunluluğu
- 🧩 Çoklu admin desteği

---

## 📝 Özet

`AdminUserSeeder`, Laravel projelerinde **standart ve güvenli bir admin hesabı** oluşturmak için kullanılan, temiz ve sürdürülebilir bir seed yapısıdır.

Kurumsal projelerde ilk kurulum için **olmazsa olmaz** bir bileşendir.

---

> 📌 Not: Seeder production ortamında çalıştırılacaksa admin bilgileri mutlaka gözden geçirilmelidir.

