# Çalışma Günlüğü

> Her çalışma gününün kısa kaydı: ne yapıldı, neden, sonuç ne oldu, sırada ne var.
> En yeni gün en üstte. Projenin genel rehberi: `CLAUDE.md`.

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
