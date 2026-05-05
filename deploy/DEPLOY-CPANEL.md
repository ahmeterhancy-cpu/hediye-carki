# cPanel Deploy Kılavuzu

PHP 8.2+ / Apache / MySQL — SSH gerektirmez.

---

## Adım 1 — Yerel Hazırlık (Bilgisayarınızda)

```bash
cd F:\Yazılımlar\cark

# vendor/ klasörünü üret (bu klasör git'e gitmez, yerel üretmeniz gerekir)
composer install --no-dev --optimize-autoloader

# Her şeyi ZIP'le (vendor/ dahil)
# Windows: sağ tık → Sıkıştır  VEYA PowerShell:
Compress-Archive -Path * -DestinationPath hediye-carki.zip
```

> **Not:** ZIP'e şunlar dahil olmalı: `app/`, `bin/`, `config/`, `database/`,
> `deploy/`, `public/`, `storage/`, `vendor/`, `.env.example`, `composer.json`,
> `composer.lock`
> `.env` dosyası **dahil edilmez** (gitignore'da).

---

## Adım 2 — cPanel'de Klasör Yükle

1. **cPanel → Dosya Yöneticisi** açın
2. Ana dizine gidin: `/home/KULLANICI/`
3. `hediye-carki` adında **yeni klasör** oluşturun
4. Klasörün içine girin → **Yükle** → `hediye-carki.zip`
5. ZIP'i seçin → **Çıkart** → hepsini `hediye-carki/` içine çıkartın

Sonuç yapısı:
```
/home/KULLANICI/
  hediye-carki/
    app/
    config/
    database/
    public/          ← DOMAIN'İN KÖKÜ BURASI OLACAK
    vendor/
    ...
```

---

## Adım 3 — Addon Domain / Subdomain Kur

Bu adım kritik: domain'i `hediye-carki/public` klasörüne bağlayın.

### A) Mevcut domaine alt klasör olarak (Subdomain önerilir)

1. **cPanel → Subdomains**
2. Subdomain: `cark` → Ana domain: `siteniz.com`
3. **Document Root:** `/home/KULLANICI/hediye-carki/public` (elle yazın)
4. Kaydet

URL: `https://cark.siteniz.com`

### B) Ayrı domain (Addon Domain)

1. **cPanel → Addon Domains**
2. Domain adı girin
3. **Document Root:** `/home/KULLANICI/hediye-carki/public`
4. Kaydet

---

## Adım 4 — Veritabanı Oluştur

1. **cPanel → MySQL Databases**
2. **Yeni veritabanı:** `hediye_carki` → Oluştur
3. **Yeni kullanıcı:** `hcarki_user` + güçlü şifre → Oluştur
4. **Kullanıcıyı veritabanına ekle:** `hcarki_user` + `hediye_carki` + **Tüm yetkiler**

Notları kaydedin:
```
DB Host:  localhost   (cPanel'de genellikle localhost)
DB Adı:   CPANEL_PREFIX_hediye_carki
DB Kullanıcı: CPANEL_PREFIX_hcarki_user
DB Şifre: <girdiğiniz şifre>
```

> cPanel'de veritabanı ve kullanıcı adları otomatik olarak
> `KULLANICI_` öneki alır. Örnek: `ahmet_hediye_carki`

---

## Adım 5 — .env Dosyası Oluştur

1. **Dosya Yöneticisi → `/home/KULLANICI/hediye-carki/`**
2. `.env.example` dosyasını seçin → **Kopyala** → `.env` olarak kaydet
3. `.env` dosyasına çift tıklayın → **Düzenle**

```env
APP_URL=https://cark.siteniz.com
APP_ENV=production
APP_DEBUG=false
APP_KEY=<32 karakter random>

DB_HOST=localhost
DB_PORT=3306
DB_NAME=CPANEL_PREFIX_hediye_carki
DB_USER=CPANEL_PREFIX_hcarki_user
DB_PASS=<4. adımdaki şifre>

SESSION_NAME=hcarki_sess
SESSION_LIFETIME=7200

INSTALL_SECRET=<en az 12 karakter, tahmin edilmez bir şey>
```

**APP_KEY üretmek için:** cPanel'de PHP sürümü 8.2 seçiliyken **Terminal** açın:
```bash
php -r "echo bin2hex(random_bytes(16));"
```
Terminal yoksa online üreticilerden 32 karakterlik random string alın.

---

## Adım 6 — PHP Versiyonunu Kontrol Et

1. **cPanel → MultiPHP Manager** (veya PHP Selector)
2. `hediye-carki` veya subdomain için **PHP 8.2** veya üstü seçin
3. Kaydet

---

## Adım 7 — Veritabanını Kur (Browser'dan)

Tarayıcıda açın:
```
https://cark.siteniz.com/install.php?secret=<INSTALL_SECRET değeri>
```

Yeşil tik'ler görünüyorsa kurulum tamamdır.

**Hata alırsanız:**
- `DB bağlantı hatası` → .env'deki DB bilgilerini kontrol edin
- `vendor/autoload.php bulunamadı` → vendor/ klasörü ZIP'e dahil edilmemiş, Adım 1'i tekrarlayın

---

## Adım 8 — İlk Giriş ve Şifre Değiştir

1. `/admin/login` → `admin@local` / `admin123`
2. Hemen şifre değiştirin:

   cPanel → phpMyAdmin → `hediye_carki` → `users` tablosu → `admin@local` satırı

   ```sql
   UPDATE users
   SET password_hash = '$2y$10$...'  -- aşağıda üretin
   WHERE email = 'admin@local';
   ```

   Hash üretmek için cPanel Terminal:
   ```bash
   php -r "echo password_hash('YeniSifreniz123!', PASSWORD_BCRYPT);"
   ```

3. `/admin/settings` → Etkinlik adı, saatleri, telefon limitini ayarlayın
4. `/admin/prizes` → Dilimlerinizi ekleyin
5. `/admin/stock` → Stokları girin
6. `/admin/staff-users` → Görevlileri ve PIN'leri oluşturun

---

## Adım 9 — Temizlik

```
# install.php'yi silin (Dosya Yöneticisinden):
public/install.php

# .env'den INSTALL_SECRET satırını silin
```

---

## Adım 10 — Cron (Opsiyonel)

**cPanel → Cron Jobs:**

```
*/5 * * * *   /usr/bin/php /home/KULLANICI/hediye-carki/bin/expire-codes.php
```

> Artık kodlar kullanılmadığı için (tek-cihaz akışı) bu cron kritik değil.

---

## Sorun Giderme

| Belirti | Neden | Çözüm |
|---------|-------|-------|
| 500 Internal Server Error | PHP sürümü < 8.2 | MultiPHP Manager'dan 8.2 seçin |
| Sayfa boş / 404 | .htaccess çalışmıyor | cPanel → Apache Handlers veya mod_rewrite aktif mi? |
| DB bağlanamıyor | DB adı/kullanıcı öneki | cPanel'deki tam adı (ön ek ile) kullanın |
| upload hatası | Klasör izni | `public/uploads/` chmod 775 |
| Oturum düşüyor | SESSION_LIFETIME | .env'de SESSION_LIFETIME artırın |

---

## Kiosk Modu (Dokunmatik Ekran)

Çark sayfası Chrome'da tam ekran kiosk modunda açılabilir:

**Windows:**
```
chrome.exe --kiosk --app=https://cark.siteniz.com/staff/login
```

**Otomatik başlatma için:** Başlangıç uygulamalarına ekleyin.
