#!/usr/bin/env bash

# ==========================================
#        PASEREXPRESS AUTO FIXER
# ==========================================

RED="\e[31m"; GREEN="\e[32m"; YELLOW="\e[33m"; BLUE="\e[34m"; RESET="\e[0m"

ok(){ echo -e "${GREEN}[OK]${RESET} $*"; }
fail(){ echo -e "${RED}[FAIL]${RESET} $*"; }
fix(){ echo -e "${YELLOW}[FIX]${RESET} $*"; }
line(){ echo -e "${BLUE}--------------------------------------------${RESET}"; }

echo ""
line
echo -e "${GREEN}      PASEREXPRESS — AUTO HEAL SYSTEM       ${RESET}"
line
echo ""

# Ask domain
while [[ -z "${DOMAIN:-}" ]]; do
    read -rp "Masukkan domain website (contoh: a.aziztech.us): " DOMAIN
done

# Ask path
while [[ -z "${ROOT_DIR:-}" ]]; do
    read -rp "Masukkan path root (contoh: /var/www/paserexpress): " ROOT_DIR
done

PUBLIC_DIR="$ROOT_DIR/src/public"
CONF="/etc/nginx/sites-available/$DOMAIN.conf"
CONF_LINK="/etc/nginx/sites-enabled/$DOMAIN.conf"

# ===============================================================
# 1. FIX NGINX
# ===============================================================
line
echo -e "${BLUE}[1] MEMPERBAIKI NGINX${RESET}"

if ! nginx -t >/dev/null 2>&1; then
    fix "Konfigurasi Nginx rusak. Memperbaiki..."
    rm -f /etc/nginx/sites-enabled/default
    ln -sf "$CONF" "$CONF_LINK" 2>/dev/null
    systemctl reload nginx 2>/dev/null || systemctl restart nginx
fi

if systemctl is-active --quiet nginx; then ok "Nginx berjalan"; else
    fix "Restart Nginx..."
    systemctl restart nginx
fi

# ===============================================================
# 2. FIX PHP-FPM
# ===============================================================
line
echo -e "${BLUE}[2] MEMPERBAIKI PHP-FPM${RESET}"

if ! systemctl is-active --quiet php8.3-fpm; then
    fix "PHP-FPM tidak aktif, coba restart..."
    systemctl restart php8.3-fpm
    sleep 2
fi

php_sock="/run/php/php8.3-fpm.sock"
if [[ ! -S "$php_sock" ]]; then
    fail "Socket PHP hilang: $php_sock"
    fix "Regenerating PHP-FPM..."
    systemctl restart php8.3-fpm
fi

ok "PHP-FPM OK"

# ===============================================================
# 3. FIX MARIADB
# ===============================================================
line
echo -e "${BLUE}[3] MEMPERBAIKI MARIADB${RESET}"

if ! systemctl is-active --quiet mariadb; then
    fix "Mariadb tidak running — memperbaiki..."
    systemctl restart mariadb
    sleep 2
fi

mysql -e "SELECT 1" >/dev/null 2>&1 \
    && ok "MariaDB OK" \
    || fail "MariaDB masih mengalami error!"

# ===============================================================
# 4. FIX PERMISSION
# ===============================================================
line
echo -e "${BLUE}[4] MEMPERBAIKI PERMISSIONS${RESET}"

if [[ -d "$ROOT_DIR" ]]; then
    fix "Set ownership ke www-data..."
    chown -R www-data:www-data "$ROOT_DIR"
    find "$ROOT_DIR" -type d -exec chmod 755 {} \;
    find "$ROOT_DIR" -type f -exec chmod 644 {} \;
    ok "Permission perbaikan selesai"
else
    fail "Root directory tidak ditemukan!"
fi

# ===============================================================
# 5. FIX FIREWALL
# ===============================================================
line
echo -e "${BLUE}[5] MEMPERBAIKI FIREWALL${RESET}"

ufw allow 80/tcp >/dev/null 2>&1
ufw allow 443/tcp >/dev/null 2>&1
ufw allow 22/tcp >/dev/null 2>&1
ufw allow 9898/tcp >/dev/null 2>&1
ufw allow 9922/tcp >/dev/null 2>&1

ok "Firewall rule diperbaiki"

# ===============================================================
# 6. FIX SSL LET'S ENCRYPT
# ===============================================================
line
echo -e "${BLUE}[6] MEMPERBAIKI SSL (CERTBOT)${RESET}"

if certbot certificates | grep -q "$DOMAIN"; then
    fix "Renew SSL..."
    certbot renew --force-renewal >/dev/null 2>&1 && ok "SSL diperbarui"
else
    warn "SSL belum terpasang. Menginstall..."
    certbot --nginx -d "$DOMAIN" -m "admin@$DOMAIN" --agree-tos --non-interactive || warn "Gagal pasang SSL"
fi

# ===============================================================
# 7. FIX MISSING/WRONG INFO.PHP
# ===============================================================
line
echo -e "${BLUE}[7] MEMPERBAIKI PHP INFO CHECK${RESET}"

mkdir -p "$PUBLIC_DIR"

TMP_INFO="$PUBLIC_DIR/info.php"
echo "<?php phpinfo(); ?>" > "$TMP_INFO"

HTTP_OK=$(curl -s "http://$DOMAIN/info.php" | grep -ci "php version")

if (( HTTP_OK > 0 )); then
    ok "PHP berjalan normal"
else
    fail "PHP belum berjalan!"
fi

rm -f "$TMP_INFO"

# ===============================================================
# 8. FIX NGINX ROOT ERROR
# ===============================================================
line
echo -e "${BLUE}[8] FIX ROOT PATH${RESET}"

if [[ -f "$CONF" ]]; then
    CURRENT_ROOT=$(grep 'root ' "$CONF" | head -n1 | awk '{print $2}' | tr -d ';')
    if [[ "$CURRENT_ROOT" != "$PUBLIC_DIR" ]]; then
        fix "Root salah → memperbaiki..."
        sed -i "s|root .*|root $PUBLIC_DIR;|" "$CONF"
        systemctl reload nginx
    fi
fi

ok "Root path OK"

# ===============================================================
# 9. FIX BROKEN SYMLINK
# ===============================================================
line
echo -e "${BLUE}[9] FIX NGINX SYMLINK${RESET}"

if [[ ! -L "$CONF_LINK" ]]; then
    fix "Symlink tidak ada, membuat..."
    ln -sf "$CONF" "$CONF_LINK"
    systemctl reload nginx
fi

ok "Symlink OK"

# ===============================================================
# 10. FINAL
# ===============================================================
line
echo -e "${GREEN}PERBAIKAN SELESAI!${RESET}"
echo -e "Website: http://${DOMAIN}"
echo -e "Jika SSL aktif: https://${DOMAIN}"
line
echo ""
echo "Jika masih error, jalankan:  healthcheck.sh"
echo ""
exit 0
