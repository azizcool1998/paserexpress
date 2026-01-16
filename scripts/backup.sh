#!/usr/bin/env bash
set -e

DATE=$(date +%Y-%m-%d_%H-%M)
BACKUP_DIR="/var/backups/paserexpress/$DATE"
APP_DIR="/var/www/paserexpress"

mkdir -p "$BACKUP_DIR"

echo "[Backup] Dumping database..."
mysqldump paserexpress > "$BACKUP_DIR/db.sql"

echo "[Backup] Copying source..."
cp -r "$APP_DIR" "$BACKUP_DIR/app"

echo "[Backup] Compressing..."
tar -czf "/var/backups/paserexpress/backup_${DATE}.tar.gz" "$BACKUP_DIR"

echo "[Backup] Done."
