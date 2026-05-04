# 🎡 Hediye Çarkı

AVM içi dokunmatik kiosk + bilgisayar üzerinden çalışan hediye çarkı sistemi.
PHP 8.2+ vanilla, MySQL 8, Vanilla JS + Canvas, Tailwind (CDN).

## 5 Katmanlı Mimari

| Katman | Aktör | Cihaz | Amaç |
|--------|-------|-------|------|
| 1 | Admin | Laptop/Masaüstü | Çark, şart, stok, görevli, rapor |
| 2 | Görevli | Tablet/Telefon | Müşteri onayı → 6 haneli kod |
| 3 | Müşteri | Dokunmatik kiosk | Kod gir → kayıt → çark → kazan |
| 4 | Sistem | Server | Çark motoru, animasyon |
| 5 | Veri | MySQL | Kalıcı kayıt + raporlama |

## Akış

```
Müşteri kasada fiş gösterir
  → Görevli /staff'ta ONAYLA basar (PIN ile giriş)
    → 6 haneli kod üretilir, görevli müşteriye verir
      → Müşteri kioska gider, kodu girer
        → Ad/soyad/telefon + KVKK onayı
          → Çark sunucu tarafında çevrilir, stok düşer
            → Kazanma ekranı (flash + konfeti + logo)
              → Müşteri standa gider, hediyesini alır
```

**Kritik prensipler:**
- Çark sonucu **istemcide değil sunucuda** belirlenir (anti-cheat).
- Stok düşürme `FOR UPDATE` transaction içinde (race-condition güvenli).
- 6 haneli kod tek-kullanımlık + TTL (default 5 dakika).
- Görevli PIN'leri admin tarafından atanır, bcrypt hash'lenir.

## Kurulum

```bash
# 1. Klon
git clone <repo> hediye-carki && cd hediye-carki

# 2. Composer
composer install

# 3. .env
cp .env.example .env
# DB credentials, APP_URL düzenle

# 4. Veritabanı
mysql -u root -p -e "CREATE DATABASE hediye_carki CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
php database/migrate.php
mysql -u root -p hediye_carki < database/seed.sql

# 5. Klasör izinleri (Linux)
chmod -R 775 storage
chown -R www-data:www-data storage

# 6. Cron — kullanılmayan kodları temizle (her 5 dk)
# crontab -e
# */5 * * * * cd /var/www/hediye-carki && php bin/expire-codes.php
```

### Windows (Laragon)

```bash
# 1. Composer
composer install

# 2. DB oluştur
"C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe" -u root -e "CREATE DATABASE hediye_carki CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"

# 3. Migration + seed
"C:/laragon/bin/php/php-8.3.30-Win32-vs16-x64/php.exe" database/migrate.php
"C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe" -u root hediye_carki < database/seed.sql

# 4. Geliştirme sunucusu
cd public
php -S 127.0.0.1:8080
```

### Default Admin

- E-posta: `admin@local`
- Parola: `admin123`

⚠️ **Production'a çıkmadan parolayı mutlaka değiştirin.** `/admin/settings` üzerinden değil, doğrudan DB'den (admin parola değiştirme UI ileride eklenecek).

## URL Yapısı

| URL | Açıklama |
|-----|----------|
| `/` | Müşteri kiosk ana ekran (kod girişi) |
| `/spin/register` | Müşteri kayıt formu |
| `/spin/wheel` | Çark sayfası |
| `/spin/win/{id}` | Kazanma ekranı |
| `/staff/login` | Görevli PIN girişi |
| `/staff` | Görevli onay ekranı |
| `/admin/login` | Admin girişi |
| `/admin` | Dashboard |
| `/admin/prizes` | Çark dilim yönetimi |
| `/admin/stock` | Stok yönetimi |
| `/admin/settings` | Etkinlik şartları |
| `/admin/staff-users` | Görevli + PIN yönetimi |
| `/admin/participants` | Katılımcı listesi + Excel export |
| `/admin/reports` | Grafikler ve KPI'lar |

## Kullanım Senaryoları

### 1. Etkinlik öncesi (Admin)
1. `/admin/login` → giriş yap
2. **Şartlar** → etkinlik saatlerini ayarla, telefon limitini belirle
3. **Dilimler** → dilimleri ekle (ad, marka, renk, ağırlık, başlangıç stoğu)
4. **Stok** → günlük limitleri belirle
5. **Görevliler** → her vardiya için bir görevli kaydı, PIN ata, görevliye ilet

### 2. Etkinlik sırasında (Görevli)
1. `/staff/login` → PIN ile giriş
2. Müşteri fişini gösterir
3. Opsiyonel: fiş no ve tutarı gir
4. **✓ ONAYLA** → büyük 6 haneli kod ekrana gelir
5. Kodu müşteriye söyle/yaz
6. **+ Yeni Onay** → bir sonrakiye geç

### 3. Etkinlik sırasında (Müşteri / Kiosk)
1. `/` → 6 haneli kodu numerik tuş takımıyla gir
2. Ad/soyad/telefon + KVKK
3. **ÇEVİR!** butonuna bas
4. 5 sn animasyon → konfeti + kazanma ekranı
5. Hediyesini standdan alır

### 4. Etkinlik sonrası (Admin)
1. **Katılımcılar** → tarih filtresiyle Excel export
2. **Raporlar** → saatlik dağılım, hediye dağılımı

## Güvenlik Özellikleri

- ✅ Tüm SQL prepared statement
- ✅ Output `htmlspecialchars(ENT_QUOTES, 'UTF-8')`
- ✅ CSRF token her POST formda
- ✅ Çark sonucu sadece sunucuda
- ✅ Stok düşürme `FOR UPDATE` transaction
- ✅ 6 haneli kod tek-kullanımlık + TTL
- ✅ PIN/parola bcrypt
- ✅ Görevli PIN'i admin tarafından, kullanıcı belirleyemez
- ✅ PIN bir kez gösterilir, log'a yazılmaz
- ✅ Rate limit: spin/login/code endpoint'lerinde
- ✅ Session: HttpOnly + SameSite=Lax
- ✅ Audit log tüm önemli aksiyonlarda
- ✅ Logo upload: MIME + uzantı + 2MB limit

## Faz 0 ve sonrası

Tüm fazlar tamamlandı:

- [x] Faz 0: İskelet (Core, Router, DB, autoload)
- [x] Faz 1: Admin login + Dilim CRUD
- [x] Faz 2: Stok + Şartlar
- [x] Faz 3: Görevli + PIN yönetimi
- [x] Faz 4: Görevli onay akışı + 6 haneli kod
- [x] Faz 5: Müşteri kod giriş + kayıt
- [x] Faz 6: Çark motoru + Canvas animasyon
- [x] Faz 7: Kazanma ekranı (flash + konfeti)
- [x] Faz 8: Raporlama + Excel export
- [x] Faz 9: Polish + Deploy notları

## Test

```bash
# 1000-spin stress test (stok-aware ağırlık dağılım doğrulaması)
php bin/stress-test.php

# Cron simulasyonu
php bin/expire-codes.php
```

## Klasör Yapısı

```
hediye-carki/
├── public/                # Web kökü
│   ├── index.php          # Router giriş noktası
│   ├── .htaccess
│   └── assets/
│       ├── css/app.css
│       ├── js/wheel.js     # Canvas çark
│       └── js/confetti.js
│
├── app/
│   ├── Core/              # Database, Router, Auth, Csrf, RateLimit, Logger, Response
│   ├── Services/          # WheelEngine, EligibilityChecker, CodeService, ReportService
│   ├── Controllers/       # Admin, Staff, Customer, Api
│   ├── Models/            # User, Prize, Stock, Settings, Participant, SpinCode, AuditLog
│   └── Views/
│       ├── layouts/        # admin, staff, customer
│       ├── admin/          # 8 sayfa
│       ├── staff/          # login, approve
│       └── customer/       # enter_code, register, wheel, win
│
├── config/                # app.php, database.php
├── database/migrations/   # 001-008.sql
├── database/seed.sql      # default admin + 8 demo dilim
├── storage/               # logs, exports, uploads
├── bin/                   # expire-codes.php, stress-test.php
└── composer.json
```

## Lisans

Internal proje. Telif: yazar.
