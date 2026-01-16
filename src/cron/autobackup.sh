#!/usr/bin/env bash
set -e

APP_ROOT="/var/www/paserexpress"
BACKUP_DIR="${APP_ROOT}/src/storage/backups"
ENV_FILE="${APP_ROOT}/.env"

mkdir -p "$BACKUP_DIR"

STATUS_FILE="${APP_ROOT}/src/storage/backup_status.txt"
if [[ -f "$STATUS_FILE" ]]; then
    STATUS=$(cat "$STATUS_FILE")
    if [[ "$STATUS" != "on" ]]; then
        echo "[INFO] Auto-backup is OFF. Exit."
        exit 0
    fi
fi

DB_NAME=$(grep "^DB_NAME=" "$ENV_FILE" | cut -d '=' -f2)
DB_USER=$(grep "^DB_USER=" "$ENV_FILE" | cut -d '=' -f2)
DB_PASS=$(grep "^DB_PASS=" "$ENV_FILE" | cut -d '=' -f2)

NOW=$(date +"%Y-%m-%d_%H-%M-%S")
SQL_FILE="${BACKUP_DIR}/db_${NOW}.sql"
TARGET="${BACKUP_DIR}/backup_${NOW}.tar.gz"

echo "[INFO] Starting backup at $NOW"

mysqldump -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$SQL_FILE"

tar -czf "$TARGET" \
    -C "$APP_ROOT" src \
    -C "$APP_ROOT" config \
    "$ENV_FILE" \
    "$SQL_FILE"

rm -f "$SQL_FILE"

echo "[INFO] Backup completed → $TARGET"
