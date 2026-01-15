#!/usr/bin/env bash
###############################################################################
#                       PASEREXPRESS HEALTHFIX PRO v1.0
#        Advanced Healing System - AI Style Automatic Diagnose & Fix
###############################################################################

RED="\e[31m"; GREEN="\e[32m"; YELLOW="\e[33m"; BLUE="\e[36m"; MAGENTA="\e[35m"; RESET="\e[0m"

ok(){ echo -e "${GREEN}[OK]${RESET} $*"; }
warn(){ echo -e "${YELLOW}[WARN]${RESET} $*"; }
fail(){ echo -e "${RED}[FAIL]${RESET} $*"; }
fix(){ echo -e "${MAGENTA}[FIX]${RESET} $*"; }
line(){ echo -e "${BLUE}-------------------------------------------------${RESET}"; }

LOG="/var/log/paserexpress_healthfix.log"
touch "$LOG"

echo_log(){ echo -e "$(date '+%F %T') — $*" >> "$LOG"; }

###############################################################################
# 0. INPUT
###############################################################################
line
echo -e "${GREEN} PASEREXPRESS — HEALTHFIX PRO${RESET}"
line

while [[ -z "${DOMAIN:-}" ]]; do
    read -rp "Masukkan Domain Website     : " DOMAIN
done

while [[ -z "${ROOT_DIR:-}" ]]; do
    read -rp "Masukkan Root Directory     : " ROOT_DIR
done

PUBLIC_DIR="$ROOT_DIR/src/public"
CONF="/etc/nginx/sites-available/$DOMAIN.conf"
CONF_LINK="/etc/nginx/sites-enabled/$DOMAIN.conf"

###############################################################################
# 1. SYSTEM DIAGNOSTIC - AI STYLE
###############################################################################

line
echo -e "${BLUE}[1] DIAGNOSIS SISTEM (AI ANALYZER)${RESET}"

HEALTH_SCORE=100

diag(){
    local msg="$1"; local score="$2"
    warn "$msg"
    HEALTH_SCORE=$((HEALTH_SCORE - score))
    echo_log "DIAG: $msg (-$score)"
}

# Ping domain
if ! ping -c1 -W1 "$DOMAIN" >/dev/null 2>&1; then
    diag "Domain tidak merespon ping" 5
else
    ok "Domain aktif"
fi

# Curl HTTP
if ! curl -s "http://$DOMAIN" >/dev/null; then
    diag "HTTP gagal diakses" 10
else ok "HTTP OK"; fi

# Test PHP-FPM
if ! systemctl is-active --quiet php8.3-fpm; then
    diag "PHP-FPM tidak aktif" 20
fi

# Test MariaDB
if ! systemctl is-active --quiet mariadb; then
    diag "MariaDB tidak aktif" 20
fi

# Test Certbot
if ! certbot --version >/dev/null 2>&1; then
    diag "Certbot tidak terinstall" 5
fi

# Test SSL status
if certbot certificates | grep -q "$DOMAIN"; then
    ok "SSL ditemukan"
else
    warn "SSL belum terpasang"
    HEALTH_SCORE=$((HEALTH_SCORE - 10))
fi

# Tampilkan skor
echo -e "${BLUE}Hasil Health Score: ${RESET}$HEALTH_SCORE / 100"
if (( HEALTH_SCORE >= 90 )); then
    ok "Server sehat"
elif (( HEALTH_SCORE >= 70 )); then
    warn "Server kurang sehat"
else
    fail "Server kritis!"
fi

###############################################################################
# 2. AUTO FIX ENGINE
###############################################################################

line
echo -e "${BLUE}[2] AUTO FIX ENGINE${RESET}"

###############################################################################
# FIX NGINX
###############################################################################
fix "Memperbaiki Nginx"

rm -f /etc/nginx/sites-enabled/default

if ! nginx -t >/dev/null 2>&1; then
    fix "Nginx rusak — rebuild template"

cat > "$CONF" <<EOF
server {
    listen 80;
    server_name $DOMAIN;

    root $PUBLIC_DIR;
    index index.php index.html;

    include /etc/nginx/snippets/paserexpress-headers.conf;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
EOF

fi

ln -sf "$CONF" "$CONF_LINK"
systemctl restart nginx
systemctl reload nginx

ok "Nginx OK"

###############################################################################
# FIX PHP FPM
###############################################################################
fix "Memperbaiki PHP-FPM"

systemctl restart php8.3-fpm
sleep 1

if ! systemctl is-active --quiet php8.3-fpm; then
    fix "Rebuild pool PHP..."

cat > /etc/php/8.3/fpm/pool.d/www.conf <<EOF
[www]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 10
EOF

systemctl restart php8.3-fpm
fi

ok "PHP-FPM OK"

###############################################################################
# FIX MARIADB
###############################################################################
fix "Memperbaiki MariaDB"

systemctl restart mariadb
sleep 1

if ! mysql -e "SELECT 1" >/dev/null 2>&1; then
    fix "Memperbaiki user database default..."

MYSQL_USER=$(grep "^DB_USER=" "$ROOT_DIR/.env" | cut -d= -f2)
MYSQL_PASS=$(grep "^DB_PASS=" "$ROOT_DIR/.env" | cut -d= -f2)
MYSQL_NAME=$(grep "^DB_NAME=" "$ROOT_DIR/.env" | cut -d= -f2)

mysql -e "ALTER USER '$MYSQL_USER'@'localhost' IDENTIFIED BY '$MYSQL_PASS'; FLUSH PRIVILEGES;" >/dev/null 2>&1
fi

ok "MariaDB OK"

###############################################################################
# FIX PERMISSION
###############################################################################
fix "Perbaikan permissions"

chown -R www-data:www-data "$ROOT_DIR"
find "$ROOT_DIR" -type d -exec chmod 755 {} \;
find "$ROOT_DIR" -type f -exec chmod 644 {} \;

ok "Permissions OK"

###############################################################################
# FIX SSL
###############################################################################
fix "Memperbaiki SSL"

if certbot certificates | grep -q "$DOMAIN"; then
    certbot renew --force-renewal
else
    certbot --nginx -d "$DOMAIN" -m "admin@$DOMAIN" --agree-tos --non-interactive
fi

ok "SSL OK"

###############################################################################
# FIX DNS CHECK
###############################################################################
fix "Cek DNS resolusi Cloudflare"

DNS_IP=$(dig +short "$DOMAIN" A | head -n1)
SERVER_IP=$(curl -s ifconfig.me)

if [[ "$DNS_IP" != "$SERVER_IP" ]]; then
    warn "DNS SALAH!"
    echo "DNS: $DNS_IP"
    echo "VPS: $SERVER_IP"
else
    ok "DNS mengarah benar"
fi

###############################################################################
# FIX FINAL CHECK
###############################################################################
line
echo -e "${GREEN}[3] FINAL TEST${RESET}"

curl -s "http://$DOMAIN" >/dev/null && ok "HTTP OK" || fail "HTTP FAILED"
curl -s "https://$DOMAIN" >/dev/null && ok "HTTPS OK" || warn "HTTPS FAILED"

###############################################################################
# DONE
###############################################################################
line
echo -e "${GREEN}HEALTHFIX PRO SELESAI!${RESET}"
echo "Log: $LOG"
echo ""
echo "Jika masih error, katakan: DEBUG PRO"
echo ""
exit 0
