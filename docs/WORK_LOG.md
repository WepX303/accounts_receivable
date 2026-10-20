# Çalışma Günlüğü

> Her çalışma gününün kısa kaydı: ne yapıldı, neden, sonuç ne oldu, sırada ne var.
> En yeni gün en üstte. Projenin genel rehberi: `CLAUDE.md`.

---

## 2026-10-21

### Ana iş: Elektronik şertnama (sözleşme) modülü

Matbaada bastırılan kâğıt şertnamaların yerine panelden şertnama oluşturup basmak.
Plan: `/Users/wepx/.claude/plans/smooth-zooming-squirrel.md`.

**Nasıl çalışıyor**
1. Şertnamalar → Şertnama döret → müşteri aranır → "Saýla" (bilgiler otomatik gelir)
   ya da "Müşderi ýok — el bilen doldur" (müşteri kaydı oluşturulmaz).
2. Ayrı bir sayfada şertnama **kâğıt görünümünde** açılır; bilgiler metindeki sarı boşluklara yazılı gelir.
3. "Döret" → şertnama kaydedilir (durum: Çap edilmedik). Basılana kadar düzeltilebilir, her değişiklik loglanır.
4. "Çap et" → PDF yeni sekmede açılır; **ilk baskıda kilitlenir** (durum: Çap edildi). Sonra yalnızca tekrar basılabilir.
5. Hata varsa sebep yazılarak iptal edilir (Ýatyrylan); kayıt ve numara silinmez.

**Kurallar**
| Konu | Karar |
|---|---|
| Sayfa 1 | Banka onaylı; metni **hiç değişmez**. Orijinal PDF olduğu gibi alınır, yalnızca boşluklara yazılır (piksel karşılaştırmasıyla doğrulandı) |
| Sayfa 2 | Teslim tutanağı + ürün tablosu (isteğe bağlı, genelde IMEI) + **alıcının eski kredileri** (tüm şubelerden, Türkmence) |
| Numara | Otomatik: `UT-2026-0000001`; tek sıra, her yıl 1'den başlar |
| Elle doldurulan | "Satyş hünärmeni" ve ALYJY (alıcı) sütunu boş basılır, kalemle doldurulur |
| 3.1 / 3.2 tutarları | Kasiyer yazar, veritabanından hesaplanmaz |
| Adres / pasaport | Adres `credits.assurance`'tan gelir; `I-AŞ 350924` → seri `I-AŞ`, numara `350924` |
| Ayrılık | Modül `credits` ve `credit_payments`'ı yalnızca okur, asla yazmaz (testle kanıtlı) |
| Erişim | **Şimdilik yalnızca SuperAdmin ve Admin** (oluşturma, basma, iptal). Kasiyer/operatör sonra açılacak: `Access.php`, `routes/web.php`, `sidebar.blade.php` |
| Türkçe | Arayüzde "Sözleşme"; Türkmence ve PDF'te "Şertnama" |

**Gün içinde denenip vazgeçilenler** (geri getirilmemeli)
- Onay adımı (Operator/Admin onaylar) → kasiyeri bekletiyordu; yerine "ilk baskıda kilit".
- Numaralı boş şablon → "elle doldur" ile aynı işi yapıyordu ve bilgiyi yalnızca kâğıtta bırakıyordu.
- 2. sayfada karekod (doğrulama) → eklendi, sonra kaldırıldı (`29bd6c8` → `740c554`).
- Menüyü sığdırmak için CSS ile sıkıştırma → beğenilmedi; yerine etiket kısaltıldı.

**Teknik**
- Yeni paketler: `setasign/fpdi` + `tecnickcom/tcpdf` **6.x** (7.x FPDI ile uyumsuz).
- Şablon: `resources/contract-templates/v1/` (`storage/app` gitignore'da olduğu için orada değil).
- Yazılar şablon satırının taban çizgisine basılır (10pt Times, satır üstü + 3,14 mm); sola yaslı alanlar 2 mm içeriden.
- Yeni tablolar: `customer_contracts`, `contract_sequences` (yalnızca ekleme).
- Dosyalar: `Services/Contracts/*`, `Models/CustomerContract.php`, `Controllers/Contracts/ContractController.php`,
  `views/pages/contracts/*`, `lang/*/pages/contracts.php`, `tests/Feature/Web/Contracts/*`.

### Diğer değişiklikler
- Menü: Türkmence "Dolandyryş Paneli" → **"Baş sahypa"**, Rusça "Панель управления" → **"Главная"** (menü sığmıyordu).
- Şertnamalar listesine **Arassala** (filtreyi temizle) düğmesi.

### Commit'ler (`mobile-api`)
| Commit | Açıklama |
|---|---|
| `a00a537` | Çalışma günlüğü |
| `8bb14c3` | Şertnama modülü |
| `02b5869` | Boş şablon numarası + Türkçede "Sözleşme" (boş şablon sonra kaldırıldı) |
| `e0a15ac` | Menüde "Baş sahypa" / "Главная" |
| `eb03b16` | Onaysız akış, kâğıt görünümü, hizalama, boş şablonun kaldırılması |
| `29bd6c8` / `740c554` | Karekod eklendi / kaldırıldı |
| (bu kayıtla birlikte) | Erişim yalnızca SuperAdmin + Admin; bu günlük |

### Durum (gün sonu)
- **Canlıya alındı:** `production` ve sunucu (`crdt-serv`) `5cd0f95`. Önceki sürüm `015b31a` (`storage/app/last-deploy-prev.txt`).
  - Kullanıcının kararıyla DB yedeği alınmadan yapıldı: iki migration yalnızca yeni tablo oluşturuyor.
  - Sunucu internete çıkamadığı için paketler yerel Composer önbelleğinden kopyalanıp kuruldu (`CLAUDE.md` §11).
  - Kontrol: servisler çalışıyor, giriş 200, API 401, bekleyen migration 0, sunucuda PDF üretimi çalışıyor.
- Testler: 211, hepsi geçiyor.

### Sırada
- Canlıda Admin ile gerçek bir şertnama oluşturup **kâğıda basarak** son hizalama kontrolü.
- Sonra: kasiyer/operatöre açmak; mobil uygulamaya şertnama ekranları.
- `public/build/docs/` altındaki iki fotoğraf gerçek müşteri bilgisi içeriyor → silinmeli (commit'e girmedi).
- Kararsız test: `DashboardTest::test_admin_sees_todays_totals_by_method_cashier_and_branch` bir kez düştü,
  tekrarda geçti (muhtemelen eşit tutarlı kasiyerlerin sırası). Şertnamadan bağımsız; incelenmeli.

---

## 2026-10-20

### Yapılanlar

| # | İş | Sonuç |
|---|---|---|
| 1 | Proje baştan sona tarandı, `CLAUDE.md` rehberi yazıldı | Kök dizinde; Claude Code her oturumda otomatik okur |
| 2 | `.DS_Store` dosyaları (6 adet) git takibinden çıkarıldı, `.gitignore`'a eklendi | Diskteki dosyalar yerinde; artık commit'e girmez |
| 3 | Yerelde giriş hatası (`column "client" of relation "login_histories" does not exist`) | Yerel DB'de 3 migration çalıştırılmamıştı; `php artisan migrate` ile çözüldü. Canlıyı etkilemedi |
| 4 | `production` dalı açıldı | Canlıda çalışan kod artık bu dalda |
| 5 | Canlıya alındı: `crdt-serv` `9a92b3c` → `015b31a` | Yalnızca doküman ve `.DS_Store` değişikliği; migration yok. Servisler çalışıyor, giriş sayfası 200 |
| 6 | GitHub güncellendi | `mobile-api` ve `production` → `31d9c41` |

### Commit'ler

| Commit | Açıklama |
|---|---|
| `a9c1aa6` | Stop tracking macOS folder files, and add a project guide |
| `015b31a` | Describe the production branch and how the server follows it |
| `31d9c41` | Deploy by pushing to the server: it cannot reach GitHub |

### Alınan kararlar

- **Dal düzeni:** İş `mobile-api`'de yapılır → test edilir → `production` ileri sarılır (`--ff-only`) → sunucuya gönderilir.
  Sunucu kendi `crdt-serv` dalında kalır ve yalnızca `production`'dan ileri sarılır.
- **Deploy yolu:** Sunucu GitHub'a erişemiyor (`github.com` DNS'te çözülmüyor), bu yüzden `git pull` çalışmaz.
  Kod yerelden doğrudan sunucuya `deploy-production` yan dalı olarak itilir, orada `git merge --ff-only deploy-production`.
  Adımlar: `CLAUDE.md` §11.
- **Geri dönüş:** Her deploy'dan önce eski commit `storage/app/last-deploy-prev.txt` dosyasına yazılır (şu an `9a92b3c`).

### Öğrenilenler

- `php artisan migrate --force` veri silmez; yalnızca çalışmamış migration'ları çalıştırır.
  Canlıda asla: `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `db:wipe`.
- Başka bir daldan kod geldikten sonra yerelde "column does not exist" hatası → önce `php artisan migrate`.
- GitHub'daki `origin/crdt-serv` Haziran'da kalmış eski bir kopya; kullanılmamalı.

### Taramada bulunan yeni sorunlar (`CLAUDE.md` §14'e eklendi)

- Panel (web) girişinde deneme sınırı yok; yalnızca mobil API'de var.
- `SyncAvshocrecatReportJob` pasaport ayarını yanlış anahtardan (`sync.pgsql.avshocrecat_pasport`) okuyor; her zaman boş gönderiyor.

### Durum (gün sonu)

- Yerel: `mobile-api`, temiz; bekleyen migration yok; 175 test geçiyor.
- Canlı: `015b31a`, çalışıyor.
- Sunucudaki `CLAUDE.md` bir önceki hâliyle duruyor (yalnızca doküman farkı); bir sonraki deploy'da güncellenir.

### Sırada

Sonraki çalışmada seçilecek. Adaylar (`CLAUDE.md` §14):
- Panel girişine deneme sınırı (rate limit)
- SMS "bugün ödemeli" filtresinin düzeltilmesi
- Tahsilat performansı / trendi raporlarında kasiyer filtresi
- `avshocrecat_pasport` ayar anahtarı
- Kullanılmayan `*copy.php` dosyalarının temizlenmesi
