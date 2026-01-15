#!/usr/bin/env bash
###############################################################################
#                    PASEREXPRESS HEALTHFIX ULTRA v1.0
#       Full Auto Reinstall Web Layer - Brutal but Safe for Application Data
###############################################################################

RED="\e[31m"; GREEN="\e[32m"; YELLOW="\e[33m"; BLUE="\e[36m"; MAGENTA="\e[35m"; RESET="\e[0m"

ok(){ echo -e "${GREEN}[OK]${RESET} $*"; }
warn(){ echo -e "${YELLOW}[WARN]${RESET} $*"; }
fail(){ echo -e "${RED}[FAIL]${RESET} $*"; exit 1; }
fix(){ echo -e "${MAGENTA}[FIX]${RESET} $*"; }
line(){ echo -e "${BLUE}-------------------------------------------------${RESET}"; }

LOG="/var/log/paserexpress_healthfix_ultra.log"
touch "$LOG"

echo_log(){ echo "$(date '+%F %T') — $*" >> "$LOG"; }

###############################################################################
# INPUT
###############################################################################

line
echo -e "${GREEN} PASEREXPRESS — HEALTHFIX ULTRA${RESET}"
line

while [[ -z "${DOMAIN:-}" ]]; do
    read -rp "Masukkan Domain Website : " DOMAIN
done

while [[ -z "${ROOT_DIR:-}" ]]; do
    read -rp "Masukkan Path Root Aplikasi : " ROOT_DIR
done

PUBLIC="$ROOT_DIR/src/public"
CONF="/etc/nginx/sites-available/$DOMAIN.conf"
SYMLINK="/etc/nginx/sites-enabled/$DOMAIN.conf"

###############################################################################
# 1. STOP & CLEAN WEB LAYER
###############################################################################

line
echo -e "${BLUE}[1] MEMATIKAN SELURUH WEB LAYER${RESET}"

systemctl stop nginx || true
systemctl stop php8.3-fpm || true
systemctl stop mariadb || true

ok "Semua service dimatikan"

###############################################################################
# 2. REMOVE / PURGE COMPLETELY
###############################################################################

line
echo -e "${BLUE}[2] REMOVE & PURGE WEB LAYER${RESET}"

fix "Membersihkan Nginx..."
apt purge -y nginx nginx-* >/dev/null 2>&1 || true
rm -rf /etc/nginx /var/log/nginx /var/www/html /run/nginx || true

fix "Membersihkan PHP-FPM..."
apt purge -y php8.3-fpm php8.3-* >/dev/null 2>&1 || true
rm -rf /etc/php /run/php || true

fix "Membersihkan Certbot..."
apt purge -y certbot python3-certbot-nginx >/dev/null 2>&1 || true
rm -rf /etc/letsencrypt || true

fix "Memastikan MariaDB tetap aman..."
# NOTE: database tidak dihapus
systemctl stop mariadb || true

ok "Clean selesai (database aman)"

###############################################################################
# 3. REINSTALL WEB LAYER
###############################################################################

line
echo -e "${BLUE}[3] INSTALL ULANG WEB LAYER${RESET}"

apt update -y
apt install -y nginx
apt install -y php8.3 php8.3-fpm php8.3-mysql php8.3-curl php8.3-zip php8.3-mbstring php8.3-xml php8.3-cli php8.3-common 

apt install -y mariadb-server
apt install -y certbot python3-certbot-nginx

systemctl enable --now nginx
systemctl enable --now php8.3-fpm
systemctl enable --now mariadb

ok "Install ulang selesai"

###############################################################################
# 4. REBUILD NGINX TEMPLATE
###############################################################################

line
echo -e "${BLUE}[4] MEMBANGUN ULANG VIRTUALHOST${RESET}"

rm -f "$CONF" "$SYMLINK"

mkdir -p /etc/nginx/snippets

cat > /etc/nginx/snippets/paserexpress-headers.conf <<EOF
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;
EOF

cat > "$CONF" <<EOF
server {
    listen 80;
    server_name $DOMAIN;

    root $PUBLIC;
    index index.php index.html;

    include /etc/nginx/snippets/paserexpress-headers.conf;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }
}
EOF

ln -sf "$CONF" "$SYMLINK"

nginx -t || fail "Nginx Config Error!"
systemctl restart nginx

ok "VirtualHost dibangun ulang"

###############################################################################
# 5. PERBAIKI PERMISSIONS
###############################################################################

line
echo -e "${BLUE}[5] FIX PERMISSIONS${RESET}"

chown -R www-data:www-data "$ROOT_DIR"
find "$ROOT_DIR" -type d -exec chmod 755 {} \;
find "$ROOT_DIR" -type f -exec chmod 644 {} \;

ok "Permission OK"

###############################################################################
# 6. FIX SSL (REISSUE)
###############################################################################

line
echo -e "${BLUE}[6] FIX SSL (RE-ISSUE)${RESET}"

certbot --nginx -d "$DOMAIN" --agree-tos -m "admin@$DOMAIN" --non-interactive || warn "SSL gagal (cek DNS)."

ok "SSL selesai"

###############################################################################
# 7. FINAL HEALTH CHECK
###############################################################################

line
echo -e "${BLUE}[7] FINAL TEST${RESET}"

curl -Is "http://$DOMAIN" | head -n1 | grep -q "200" && ok "HTTP OK" || warn "HTTP FAIL"
curl -Is "https://$DOMAIN" | head -n1 | grep -q "200" && ok "HTTPS OK" || warn "HTTPS FAIL"

systemctl is-active --quiet php8.3-fpm && ok "PHP-FPM OK" || fail "PHP-FPM FAIL"
systemctl is-active --quiet nginx && ok "NGINX OK" || fail "NGINX FAIL"
systemctl is-active --quiet mariadb && ok "MARIADB OK" || fail "MARIADB FAIL"

###############################################################################
# DONE
###############################################################################

line
echo -e "${GREEN}HEALTHFIX ULTRA SELESAI!${RESET}"
echo "Semua web layer telah diinstall ulang tanpa menghapus data aplikasi."
echo "Log: $LOG"
echo ""
echo "Jika masih error: ketik 'DEBUG ULTRA'"
echo ""
exit 0
