#!/bin/sh
# SmartBengkel entrypoint - menunggu DB siap, inisialisasi skema + sample data, lalu jalankan Apache
set -e

# 1. Tentukan target DB (Railway: environment variables; lokal: default MariaDB)
DB_HOST="${DB_HOST:-db}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-db_bengkel}"

echo "== SmartBengkel: menunggu database ${DB_HOST} ..."

# 2. Tunggu sampai MySQL/MariaDB siap (maks 60 detik)
attempt=0
until php -r '
    $h = getenv("DB_HOST") ?: "db";
    $p = getenv("DB_PORT") ?: 3306;
    @fsockopen($h, (int)$p, $errno, $errstr, 2) or exit(1);
' 2>/dev/null; do
    attempt=$((attempt+1))
    if [ "$attempt" -ge 30 ]; then
        echo "ERROR: Database tidak siap setelah 60 detik." >&2
        exit 1
    fi
    sleep 2
done
echo "== Database siap."

# 3. Inisialisasi skema + sample data (idempotent)
echo "== Menjalankan inisialisasi database ..."
php /var/www/html/deploy/init_db.php

# 4. Jalankan Apache
echo "== Memulai Apache ..."
exec apache2-foreground
