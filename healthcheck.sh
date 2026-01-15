#!/usr/bin/env bash

# ==========================================
#        PASEREXPRESS HEALTH CHECKER
# ==========================================

# Colors
RED="\e[31m"
GREEN="\e[32m"
YELLOW="\e[33m"
BLUE="\e[34m"
RESET="\e[0m"

ok(){ echo -e "  ${GREEN}✔${RESET} $*"; }
fail(){ echo -e "  ${RED}✘${RESET} $*"; }
warn(){ echo -e "  ${YELLOW}!${RESET} $*"; }

line(){
    echo -e "${BLUE}---------------------------------------------${RESET}"
}

echo ""
echo -e "${BLUE}=============================================${RESET}"
echo -e "${GREEN}     PASEREXPRESS - COMPLETE HEALTH CHECK    ${RESET}"
echo -e "${BLUE}=============================================${RESET}"
echo ""

# Ask for domain
while [[ -z "${DOMAIN:-}" ]]; do
    read -rp "Masukkan domain website (contoh: a.aziztech.us): " DOMAIN
done

# Ask root folder
while [[ -z "${ROOT_DIR:-}" ]]; do
    read -rp "Masukkan path root (contoh: /var/www/paserexpress): " ROOT_DIR
done


# =====================================================
# 1. CHECK NGINX
# =====================================================
line
echo -e "${BLUE}[1] NGINX STATUS${RESET}"

if systemctl is-active --quiet nginx; then
    ok "Nginx aktif"
else
    fail "Nginx tidak berjalan!"
fi

if nginx -t >/dev/null 2>&1; then
    ok "Konfigurasi Nginx valid"
else
    fail "Konfigurasi Nginx ERROR! Jalankan: nginx -t"
fi


# =====================================================
# 2. CHECK PHP-FPM
# =====================================================
line
echo -e "${BLUE}[2] PHP-FPM STATUS${RESET}"

if systemctl is-active --quiet php8.3-fpm; then
    ok "PHP-FPM 8.3 aktif"
else
    fail "PHP-FPM 8.3 tidak berjalan!"
fi


# =====================================================
# 3. CHECK MARIADB
# =====================================================
line
echo -e "${BLUE}[3] MARIADB STATUS${RESET}"

if systemctl is-active --quiet mariadb; then
    ok "MariaDB aktif"
else
    fail "MariaDB tidak berjalan!"
fi


# =====================================================
# 4. CHECK SSH PORTS
# =====================================================
line
echo -e "${BLUE}[4] SSH PORTS${RESET}"

SSH_PORTS=(22 9898 9922)

for p in "${SSH_PORTS[@]}"; do
    if ss -lntp | grep -q ":$p "; then
        ok "SSHD listen on port $p"
    else
        warn "SSHD tidak listen di port $p"
    fi
done


# =====================================================
# 5. CHECK UFW FIREWALL
# =====================================================
line
echo -e "${BLUE}[5] UFW FIREWALL${RESET}"

if ufw status | grep -q "Status: active"; then
    ok "UFW aktif"
else
    warn "UFW tidak aktif"
fi

echo ""
echo -e "${YELLOW}Daftar Rules:${RESET}"
ufw status numbered || true


# =====================================================
# 6. CHECK DOMAIN RESOLUTION
# =====================================================
line
echo -e "${BLUE}[6] DNS A RECORD${RESET}"

DNS_IP=$(dig +short "$DOMAIN" A | tail -n1)
SERVER_IP=$(curl -s https://ipinfo.io/ip)

echo -e "  Domain:  ${GREEN}$DOMAIN${RESET}"
echo -e "  DNS IP:  ${YELLOW}${DNS_IP:-N/A}${RESET}"
echo -e "  VPS IP:  ${GREEN}${SERVER_IP}${RESET}"

if [[ "$DNS_IP" == "$SERVER_IP" ]]; then
    ok "DNS mengarah ke VPS"
else
    fail "DNS TIDAK mengarah ke VPS!"
fi


# =====================================================
# 7. CHECK HTTP RESPONSE
# =====================================================
line
echo -e "${BLUE}[7] HTTP CHECK${RESET}"

HTTP_CODE=$(curl -o /dev/null -s -w "%{http_code}" "http://$DOMAIN")

echo -e "  Response HTTP: $HTTP_CODE"

if [[ "$HTTP_CODE" == "200" ]]; then
    ok "Website dapat diakses (HTTP 200)"
elif [[ "$HTTP_CODE" =~ 30* ]]; then
    ok "Website redirect ($HTTP_CODE)"
else
    warn "Website bermasalah (HTTP $HTTP_CODE)"
fi


# =====================================================
# 8. CHECK SSL
# =====================================================
line
echo -e "${BLUE}[8] SSL / HTTPS CHECK${RESET}"

if openssl s_client -connect "${DOMAIN}:443" -servername "$DOMAIN" <<< "QUIT" >/dev/null 2>&1; then
    SSL_EXP=$(echo | openssl s_client -connect "$DOMAIN:443" -servername "$DOMAIN" 2>/dev/null \
        | openssl x509 -noout -enddate | cut -d= -f2)

    ok "SSL ditemukan"
    echo -e "  Masa berlaku: ${GREEN}$SSL_EXP${RESET}"
else
    warn "SSL TIDAK ditemukan di port 443"
fi


# =====================================================
# 9. CHECK PERMISSIONS
# =====================================================
line
echo -e "${BLUE}[9] CEK PERMISSIONS${RESET}"

if [[ -d "$ROOT_DIR" ]]; then
    OWNER=$(stat -c %U "$ROOT_DIR")
    GROUP=$(stat -c %G "$ROOT_DIR")
    echo -e "  Owner folder: $OWNER"
    echo -e "  Group folder: $GROUP"

    if [[ "$OWNER" == "www-data" ]]; then
        ok "Permission benar"
    else
        warn "Permission folder SALAH, harus www-data"
    fi
else
    fail "Folder $ROOT_DIR tidak ditemukan!"
fi


# =====================================================
# 10. PHP INFO CHECK
# =====================================================
line
echo -e "${BLUE}[10] CEK PHP EXECUTION${RESET}"

TEST_FILE="$ROOT_DIR/src/public/info.php"

if [[ ! -f "$TEST_FILE" ]]; then
    warn "info.php tidak ditemukan, membuat sementara..."
    echo "<?php phpinfo(); ?>" > "$TEST_FILE"
fi

PHP_OK=$(curl -s "http://$DOMAIN/info.php" | grep -ci "php version")

if (( PHP_OK > 0 )); then
    ok "PHP berjalan normal (phpinfo OK)"
else
    fail "PHP TIDAK berjalan! (info.php tidak tampil)"
fi

# hapus info php untuk keamanan
rm -f "$TEST_FILE"


# =====================================================
# FINAL RESULT
# =====================================================
echo ""
line
echo -e "${GREEN}HEALTH CHECK SELESAI${RESET}"
line

echo ""
echo "Jika mau pengecekan otomatis harian, aku bisa buatkan cronjob."
echo "👉 Ketik:  buatkan cron healthcheck"
echo ""
exit 0
