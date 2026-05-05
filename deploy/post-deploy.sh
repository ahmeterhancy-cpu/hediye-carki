#!/bin/bash
# cPanel Git deployment hook
# Çağrı: .cpanel.yml içinden

set -e
DEPLOYPATH="/home/erst6559/hediye-carki"
LOG="$DEPLOYPATH/storage/deploy.log"

mkdir -p "$DEPLOYPATH/storage/logs" \
         "$DEPLOYPATH/storage/exports" \
         "$DEPLOYPATH/storage/uploads" \
         "$DEPLOYPATH/public/uploads"

{
  echo "═══════════════════════════════════════"
  echo "Deploy başladı: $(date)"
  echo "Path: $DEPLOYPATH"
  echo "═══════════════════════════════════════"

  cd "$DEPLOYPATH"

  # Composer'ı bul: önce sistem, yoksa indir
  if command -v composer >/dev/null 2>&1; then
    COMPOSER_CMD="composer"
    echo "✓ Sistem composer bulundu: $(which composer)"
  elif [ -f "/usr/local/bin/composer" ]; then
    COMPOSER_CMD="/usr/local/bin/composer"
    echo "✓ /usr/local/bin/composer bulundu"
  elif [ -f "$DEPLOYPATH/composer.phar" ]; then
    COMPOSER_CMD="php $DEPLOYPATH/composer.phar"
    echo "✓ Yerel composer.phar bulundu"
  else
    echo "→ Composer bulunamadı, indiriliyor..."
    cd "$DEPLOYPATH"
    curl -sS https://getcomposer.org/installer | php
    COMPOSER_CMD="php $DEPLOYPATH/composer.phar"
    echo "✓ composer.phar indirildi"
  fi

  echo ""
  echo "→ composer install çalıştırılıyor..."
  cd "$DEPLOYPATH"
  $COMPOSER_CMD install --no-dev --optimize-autoloader 2>&1

  echo ""
  echo "✓ Deploy tamamlandı: $(date)"
} > "$LOG" 2>&1
