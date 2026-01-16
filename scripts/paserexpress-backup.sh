#!/usr/bin/env bash

DATE=$(date +"%Y%m%d-%H%M")
BACKUP_DIR="/var/backups/paserexpress"
APP_ROOT="/var/www/paserexpress"

DB_NAME=$(grep '^DB_NAME=' "$APP_ROOT/.env" | cut -d '=' -f2)
DB_USER=$(grep '^DB_USER=' "$APP_ROOT/.env" | cut -d '=' -f2)
DB_PASS=$(grep '^DB_PASS=' "$APP_ROOT/.env" | cut -d '=' -f2)

mkdir -p "$BACKUP_DIR/files" "$BACKUP_DIR/db"

# Backup database
mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/db/db-$DATE.sql"

# Backup source code
tar -czf "$BACKUP_DIR/files/source-$DATE.tar.gz" "$APP_ROOT"

echo "[OK] Backup completed at $DATE"
