#!/usr/bin/env bash
set -euo pipefail

# ===========================================
# PaserExpress Uninstaller (Safe Removal)
# ===========================================

info(){ echo -e "\n[INFO] $*"; }
warn(){ echo -e "\n[WARN] $*"; }
err(){ echo -e "\n[ERR ] $*" >&2; exit 1; }

trim(){ echo -n "$1" | xargs; }

ask_yn() {
    local prompt="$1"
    while true; do
        read -rp "$prompt (y/n): " ans
        ans="$(trim "${ans,,}")"
        case "$ans" in
            y|yes) return 0 ;;
            n|no)  return 1 ;;
            *) echo "Jawab y atau n." ;;
        esac
    done
}

need_root(){
    if [[ ${EUID:-$(id -u)} -ne 0 ]]; then
        err "Harus berjalan sebagai root!"
    fi
}

need_root

echo ""
echo "==============================================="
echo "       PaserExpress SAFE UNINSTALLER           "
echo "==============================================="
echo ""

# ================================
# INPUT: APP ROOT DIRECTORY
# ================================
APP_ROOT=""
while [[ -z "${APP_ROOT}" ]]; do
    read -rp "Masukkan path instalasi PaserExpress (contoh: /var/www/paserexpress): " APP_ROOT
    APP_ROOT="$(trim "$APP_ROOT")"
    [[ -z "$APP_ROOT" ]] && echo "Path tidak boleh kosong!"
done

if [[ ! -d "$APP_ROOT" ]]; then
    err "Direktori $APP_ROOT tidak ditemukan!"
fi

# ================================
# Backup (optional)
# ================================
if ask_yn "Backup folder PaserExpress sebelum menghapus?"; then
    BACKUP_PATH="${APP_ROOT}-backup-$(date +%F_%H%M%S)"
    info "Backup ke: $BACKUP_PATH"
    cp -a "$APP_ROOT" "$BACKUP_PATH"
    info "Backup selesai."
fi

# ================================
# Remove Nginx Config
# ================================
SITE_NAME="$(basename "$APP_ROOT")"

NGINX_CONF1="/etc/nginx/sites-available/${SITE_NAME}.conf"
NGINX_CONF2="/etc/nginx/sites-available/${SITE_NAME}"
ENABLED1="/etc/nginx/sites-enabled/${SITE_NAME}.conf"
ENABLED2="/etc/nginx/sites-enabled/${SITE_NAME}"

info "Menghapus konfigurasi Nginx..."

rm -f "$NGINX_CONF1" "$NGINX_CONF2" "$ENABLED1" "$ENABLED2" || true

systemctl reload nginx || true

# ================================
# Remove Database
# ================================
ENV_FILE="${APP_ROOT}/.env"

DB_NAME=""
DB_USER=""
DB_PASS=""

if [[ -f "$ENV_FILE" ]]; then
    DB_NAME="$(grep '^DB_NAME=' "$ENV_FILE" | cut -d '=' -f2)"
    DB_USER="$(grep '^DB_USER=' "$ENV_FILE" | cut -d '=' -f2)"
    DB_PASS="$(grep '^DB_PASS=' "$ENV_FILE" | cut -d '=' -f2)"
fi

echo ""
echo "DATABASE DETECTED:"
echo "  DB Name : $DB_NAME"
echo "  DB User : $DB_USER"

if [[ -n "$DB_NAME" ]] && ask_yn "Hapus database '${DB_NAME}'?"; then
    mysql -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`;" || warn "Tidak bisa menghapus DB (mungkin salah password root?)"
fi

if [[ -n "$DB_USER" ]] && ask_yn "Hapus database user '${DB_USER}'?"; then
    mysql -e "DROP USER IF EXISTS '${DB_USER}'@'localhost';" || warn "Tidak bisa menghapus user DB."
fi

mysql -e "FLUSH PRIVILEGES;" || true

# ================================
# Remove Application Files
# ================================
if ask_yn "Hapus folder aplikasi PaserExpress? ($APP_ROOT)"; then
    rm -rf "$APP_ROOT"
    info "Folder aplikasi dihapus."
fi

# ================================
# Remove Firewall Rules (optional)
# ================================
echo ""
info "Firewall (UFW):"

if ask_yn "Reset UFW (firewall) ke default? (recommended)"; then
    ufw --force reset
    ufw --force enable
    ufw allow 22/tcp     # port aman SSH
    info "UFW telah direset, hanya port 22 yang dibuka."
else
    warn "UFW dibiarkan seperti apa adanya."
fi

# ================================
# Restore SSHD ports (safe)
# ================================
SSHD_DROPIN="/etc/ssh/sshd_config.d/99-paserexpress.conf"

if [[ -f "$SSHD_DROPIN" ]]; then
    if ask_yn "Hapus konfigurasi SSH tambahan (restore ke normal)?"; then
        rm -f "$SSHD_DROPIN"
        systemctl restart ssh || systemctl restart sshd || true
        info "SSH dikembalikan ke port default (22)."
    fi
fi

# ================================
# Remove logs (optional)
# ================================
if ask_yn "Hapus log Nginx PaserExpress?"; then
    rm -f /var/log/nginx/paserexpress.* || true
fi

echo ""
echo "==============================================="
echo "   UNINSTALL PASEREXPRESS SELESAI DENGAN AMAN   "
echo "==============================================="
echo "Semua file, konfigurasi, dan database telah dibersihkan."
echo "Terima kasih telah menggunakan PaserExpress."
echo ""

exit 0
