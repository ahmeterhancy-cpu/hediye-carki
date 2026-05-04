# Deploy Kılavuzu

## Hetzner / Ubuntu Server Kurulumu

### 1. Sunucu hazırlığı

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-zip \
  php8.2-curl php8.2-gd mysql-server certbot python3-certbot-nginx git unzip

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. Veritabanı

```bash
sudo mysql_secure_installation

sudo mysql -u root -p <<EOF
CREATE DATABASE hediye_carki CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'hcarki'@'localhost' IDENTIFIED BY 'GUVENLI_SIFRE_BURAYA';
GRANT ALL PRIVILEGES ON hediye_carki.* TO 'hcarki'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### 3. Uygulama deploy

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone <repo-url> hediye-carki
cd hediye-carki

sudo chown -R www-data:www-data .
sudo -u www-data composer install --no-dev --optimize-autoloader

# .env yapılandır
sudo -u www-data cp .env.example .env
sudo -u www-data nano .env
# DB_USER=hcarki, DB_PASS=GUVENLI_SIFRE_BURAYA, APP_DEBUG=false, APP_URL=https://carkim.example.com

# Migration + seed
sudo -u www-data php database/migrate.php
sudo -u www-data mysql -u hcarki -p hediye_carki < database/seed.sql

# Klasör izinleri
sudo chmod -R 775 storage
```

### 4. Nginx + SSL

```bash
sudo cp deploy/nginx.conf.example /etc/nginx/sites-available/hediye-carki
sudo nano /etc/nginx/sites-available/hediye-carki
# server_name kısmını kendi domain'inle değiştir

sudo ln -s /etc/nginx/sites-available/hediye-carki /etc/nginx/sites-enabled/
sudo nginx -t

# Let's Encrypt SSL
sudo certbot --nginx -d carkim.example.com

sudo systemctl reload nginx
```

### 5. Cron

```bash
sudo crontab -u www-data -e
# deploy/crontab.example içeriğini ekle
```

### 6. Default admin parolasını değiştir

İlk girişte `/admin/login` → `admin@local` / `admin123`

Yeni hash üret:
```bash
php -r "echo password_hash('YENI_GUVENLI_SIFRE', PASSWORD_BCRYPT);"
```

DB'de güncelle:
```bash
mysql -u hcarki -p hediye_carki -e \
  "UPDATE users SET password_hash='\$2y\$10\$....' WHERE email='admin@local'"
```

## Kiosk modu (Linux + Chrome)

`/etc/systemd/system/kiosk.service`:

```ini
[Unit]
Description=Kiosk Mode Chrome
After=graphical.target

[Service]
Type=simple
User=kiosk
Environment=DISPLAY=:0
ExecStart=/usr/bin/chromium-browser \
  --kiosk \
  --noerrdialogs \
  --disable-translate \
  --no-first-run \
  --fast \
  --fast-start \
  --disable-features=TranslateUI \
  --disable-pinch \
  --overscroll-history-navigation=0 \
  --disk-cache-dir=/tmp/chrome-cache \
  https://carkim.example.com

Restart=always

[Install]
WantedBy=graphical.target
```

```bash
sudo systemctl enable kiosk.service
```

## Bakım

### Backup geri yükleme
```bash
gunzip < /var/backups/hediye_carki_YYYYMMDD.sql.gz | mysql -u hcarki -p hediye_carki
```

### Etkinlik bittikten sonra
- `/admin/settings` → "Etkinlik Aktif" kapatılır
- `/admin/participants/export` → Excel indir
- DB backup arşivlenir

### Sorun giderme
- Log'lar: `storage/logs/app-YYYY-MM-DD.log`
- Cron log: `storage/logs/cron.log`
- Nginx: `/var/log/nginx/hediye-carki.error.log`
- PHP: `/var/log/php8.2-fpm.log`

## Performance Notes

- ~600 spin/saat (10/saniye) rahatlıkla destekleniyor
- Stress test: 366 spin/saniye throughput (tek sunucu)
- Bottleneck: MySQL `FOR UPDATE` lock — yüksek trafik için connection pool önerilir
