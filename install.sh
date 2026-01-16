#!/usr/bin/env bash
set -euo pipefail

###############################################
#  AURORA ROYALE INSTALLER ENGINE — PART 1  💜
###############################################

# ==============================
# 🎨 WARNA PREMIUM AURORA ROYALE
# ==============================
NC="\033[0m"
BOLD="\033[1m"

PINK="\033[38;5;212m"
PURPLE="\033[38;5;141m"
BLUE="\033[38;5;75m"
CYAN="\033[38;5;51m"
YELLOW="\033[38;5;220m"
RED="\033[38;5;196m"
GREEN="\033[38;5;83m"

# Aurora Gradient Function
aurora(){
    echo -e "${PINK}$1${PURPLE}$2${BLUE}$3${CYAN}$4${NC}"
}

# ==============================
# 💠 AURORA ROYALE BANNER
# ==============================
banner(){
    echo -e ""
    echo -e "$(aurora "━━" "━━━━━━" "━━━━━━" "━━━━━━")"
    echo -e "${PURPLE}${BOLD}    ✨ PASEREXPRESS INSTALLER — AURORA ROYALE EDITION ✨${NC}"
    echo -e "$(aurora "━━" "━━━━━━" "━━━━━━" "━━━━━━")"
    echo -e ""
}

# ==============================
# 📢 INFO / WARN / ERROR (ROYAL)
# ==============================
info()  { echo -e " ${BLUE}◆${NC} $1"; }
warn()  { echo -e " ${YELLOW}▲ WARNING:${NC} $1"; }
err()   { echo -e " ${RED}✖ ERROR:${NC} $1"; exit 1; }

# ==============================
# 🔐 ROOT VALIDATION
# ==============================
need_root(){
    if [[ $(id -u) -ne 0 ]]; then
        err "Installer harus dijalankan sebagai ROOT!"
    fi
}

# ==============================
# ⌨ INPUT HELPERS
# ==============================
ask_text(){
    local prompt="$1"
    local val=""
    echo -e "${PURPLE}${BOLD}${prompt}${NC}"
    read -rp "> " val
    echo "$val"
}

ask_password(){
    local prompt="$1"
    local val=""
    echo -e "${PINK}${BOLD}${prompt}${NC}"
    read -rsp "> " val
    echo
    echo "$val"
}

ask_confirm(){
    local prompt="$1"
    local ans=""
    echo -e "${BLUE}${prompt}${NC} (y/n)"
    read -r ans
    [[ "${ans,,}" == "y" ]]
}

# ==============================
# 🌍 DOMAIN VALIDATOR
# ==============================
valid_domain_regex='^([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}$'

ask_domain(){
    local prompt="$1"
    local domain=""

    while true; do
        echo -e "${CYAN}${prompt}${NC}"
        read -rp "> " domain

        if [[ "$domain" =~ $valid_domain_regex ]]; then
            echo "$domain"
            return
        fi

        warn "Format domain tidak valid!"
        echo -e "${YELLOW}Contoh: api.example.com, web.aziztech.us${NC}"
    done
}

# ==============================
# 🔌 PORT VALIDATOR
# ==============================
ask_port(){
    local prompt="$1"
    local port=""
    while true; do
        echo -e "${CYAN}${prompt}${NC}"
        read -rp "> " port

        if [[ "$port" =~ ^[0-9]{4,5}$ ]] && (( port >= 1024 && port <= 65535 )); then
            echo "$port"
            return
        fi

        warn "Port harus 1024 – 65535!"
    done
}

# ==============================
# 🎉 START AURORA ROYALE PART 1
# ==============================
banner
need_root

info "Memulai Input Wizard Aurora Royale…"

echo -e ""

###############################################
#           PART 2 — SYSTEM SETUP            #
#      AURORA ROYALE INSTALLER ENGINE        #
###############################################

info "Mengambil konfigurasi dari user…"

# ================
#  INPUT WIZARD
# ================

WEB_DOMAIN=$(ask_domain "Masukkan DOMAIN WEBSITE (misal: a.aziztech.us)")
DB_DOMAIN=$(ask_domain "Masukkan DOMAIN DATABASE (metadata saja)")
NODE_DOMAIN=$(ask_domain "Masukkan DOMAIN NODE API (wajib diisi)")

DB_NAME="paserexpress"
DB_USER="paser_user"
DB_PASS=$(ask_password "Masukkan PASSWORD database")
DB_EMAIL="admin@${DB_DOMAIN}"

ADMIN_USERNAME="admin"
ADMIN_FIRST="Admin"
ADMIN_LAST="Paser"
ADMIN_EMAIL="admin@${WEB_DOMAIN}"
ADMIN_WA="62000000"
ADMIN_PASS=$(ask_password "Masukkan PASSWORD admin panel")

SSH_P1="22"
SSH_P2="9898"
SSH_P3=$(ask_port "Masukkan PORT SSH tambahan ke-3 (1024–65535)")


USE_HTTPS="y"
TELEMETRY="yes"

echo -e ""
info "✔ Input selesai — memulai instalasi sistem…"
echo -e ""


###############################################
#         UPDATE & INSTALL BASIC PACKAGES
###############################################

info "Mengupdate repositori…"
apt-get update -y

info "Menginstall paket inti (nginx, php, mariadb, git)…"
apt-get install -y \
    nginx git curl ufw \
    php8.3 php8.3-fpm php8.3-mysql php8.3-curl php8.3-zip php8.3-xml php8.3-mbstring \
    mariadb-server

systemctl enable --now nginx
systemctl enable --now php8.3-fpm
systemctl enable --now mariadb


###############################################
#        CONFIGURE SSH MULTI-PORT SECURE
###############################################

info "Mengkonfigurasi SSH multi-port…"

mkdir -p /etc/ssh/sshd_config.d

cat >/etc/ssh/sshd_config.d/99-paserexpress.conf <<EOF
Port ${SSH_P1}
Port ${SSH_P2}
Port ${SSH_P3}
EOF

sshd -t || err "Konfigurasi SSH error!"
systemctl restart ssh || systemctl restart sshd


###############################################
#        CONFIGURE FIREWALL (UFW)
###############################################

info "Mengatur firewall UFW…"

ufw allow ${SSH_P1}/tcp
ufw allow ${SSH_P2}/tcp
ufw allow ${SSH_P3}/tcp
ufw allow 80/tcp
ufw allow 443/tcp

ufw --force enable


###############################################
#         WRITE NGINX SNIPPETS (AURORA)
###############################################

info "Menulis Nginx Snippets Aurora Royale…"

mkdir -p /etc/nginx/snippets

cat >/etc/nginx/snippets/paserexpress-headers.conf <<'EOF'
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header Referrer-Policy "strict-origin-when-cross-origin";
EOF

cat >/etc/nginx/snippets/paserexpress-hsts.conf <<'EOF'
add_header Strict-Transport-Security "max-age=31536000" always;
EOF


###############################################
#          WRITE NGINX SITE CONFIG
###############################################

info "Menulis konfigurasi website Nginx…"

APP_ROOT="/var/www/paserexpress"
PUBLIC_ROOT="${APP_ROOT}/src/public"

mkdir -p /etc/nginx/sites-available
mkdir -p /etc/nginx/sites-enabled

cat >/etc/nginx/sites-available/${WEB_DOMAIN}.conf <<NGINX
server {
    listen 80;
    server_name ${WEB_DOMAIN};

    root ${PUBLIC_ROOT};
    index index.php index.html;

    include /etc/nginx/snippets/paserexpress-headers.conf;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
NGINX

ln -sf /etc/nginx/sites-available/${WEB_DOMAIN}.conf /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx


###############################################
#         CLONE PASEREXPRESS FROM GITHUB
###############################################

info "Mendownload source-code PaserExpress dari GitHub…"

if [[ -d "${APP_ROOT}/.git" ]]; then
    (cd ${APP_ROOT} && git fetch --all && git reset --hard origin/main)
else
    rm -rf ${APP_ROOT}
    git clone --depth 1 -b main \
        https://github.com/azizcool1998/paserexpress.git \
        ${APP_ROOT}
fi


###############################################
#         WRITE .ENV CONFIGURATION
###############################################

info "Menulis file .env…"

cat >${APP_ROOT}/.env <<EOF
APP_NAME="Paser Express"
APP_ENV=production
APP_DEBUG=false
APP_BASE_URL=http://${WEB_DOMAIN}

DB_HOST=127.0.0.1
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_EMAIL=${DB_EMAIL}
DB_DOMAIN=${DB_DOMAIN}

NODE_DOMAIN=${NODE_DOMAIN}

ADMIN_USERNAME=${ADMIN_USERNAME}
ADMIN_PASSWORD=${ADMIN_PASS}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_FIRST_NAME=${ADMIN_FIRST}
ADMIN_LAST_NAME=${ADMIN_LAST}
ADMIN_WHATSAPP=${ADMIN_WA}

TELEMETRY_ENABLED=${TELEMETRY}
EOF

chmod 600 ${APP_ROOT}/.env


###############################################
#             DATABASE INITIALIZER
###############################################

info "Membuat database + user…"

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

info "Mengimpor schema.sql…"
mysql -u${DB_USER} -p${DB_PASS} ${DB_NAME} < ${APP_ROOT}/config/schema.sql


###############################################
#                SEED ADMIN USER
###############################################

info "Membuat akun admin…"

php ${APP_ROOT}/src/cli/seed_admin.php \
    --username="${ADMIN_USERNAME}" \
    --password="${ADMIN_PASS}" \
    --email="${ADMIN_EMAIL}" \
    --first="${ADMIN_FIRST}" \
    --last="${ADMIN_LAST}" \
    --wa="${ADMIN_WA}"

info "✔ Admin created!"


###############################################
#     END OF PART 2 — READY FOR PART 3
###############################################

info "PART 2 selesai ✔"
echo -e "${GREEN}${BOLD}Installer siap ke PART 3 (HTTPS + Backup + Final Output).${NC}"

echo ""
echo "Untuk melanjutkan: ketik →   \"Kirim PART 3\""

###############################################
#        PART 3 — HTTPS + BACKUP ENGINE       #
#          AURORA ROYALE FINAL MODULE         #
###############################################

info "Memulai PART 3…"


################################################
#                HTTPS (Certbot)
################################################

if [[ "$USE_HTTPS" == "y" ]]; then
    info "Menginstall Certbot (HTTPS)…"
    apt-get install -y certbot python3-certbot-nginx

    info "Mendaftarkan SSL untuk ${WEB_DOMAIN}…"
    if certbot --nginx -d "$WEB_DOMAIN" \
        --non-interactive --agree-tos -m "$ADMIN_EMAIL"; then

        info "HTTPS berhasil diaktifkan."
        systemctl reload nginx

    else
        warn "Gagal membuat sertifikat HTTPS!"
        warn "Periksa DNS • Port 80 harus terbuka."
    fi
else
    warn "HTTPS dinonaktifkan oleh user."
fi


################################################
#           BACKUP SYSTEM (AURORA)
################################################

info "Menyiapkan sistem Auto-Backup Aurora…"

BACKUP_SCRIPT_URL="https://raw.githubusercontent.com/azizcool1998/paserexpress/main/scripts/paserexpress-backup.sh"
BACKUP_TARGET="/usr/local/bin/paserexpress-backup.sh"

mkdir -p /var/backups/paserexpress

if curl -fsSL "$BACKUP_SCRIPT_URL" -o "$BACKUP_TARGET"; then
    chmod +x "$BACKUP_TARGET"
    info "✔ Backup script berhasil diinstall."
else
    warn "Backup script gagal diambil!"
fi


################################################
#           CRON BACKUP PLACEHOLDER
################################################

info "Membuat cron placeholder…"

cat >/etc/cron.d/paserexpress-backup <<EOF
# PaserExpress Auto Backup (disabled by default)
# Silakan aktifkan melalui Admin Panel
#
# Contoh format:
# */5 * * * * root /usr/local/bin/paserexpress-backup.sh
EOF

chmod 644 /etc/cron.d/paserexpress-backup


################################################
#             FINAL SUMMARY OUTPUT
################################################

echo -e ""
echo -e "============================================="
echo -e "     🎉 INSTALASI AURORA ROYALE SELESAI 🎉   "
echo -e "============================================="
echo ""
echo "🌐 Website   : http://${WEB_DOMAIN}"
[[ "$USE_HTTPS" == "y" ]] && echo "🔐 HTTPS     : https://${WEB_DOMAIN}"
echo ""
echo "👤 Admin Panel:"
echo "  → http://${WEB_DOMAIN}/?page=login"
echo ""
echo "📁 App Root  : ${APP_ROOT}"
echo ""
echo "🔑 SSH Ports : ${SSH_P1}, ${SSH_P2}, ${SSH_P3}"
echo ""
echo "📦 Backup Directory:"
echo "  → /var/backups/paserexpress"
echo ""
echo "🛠 Backup Script:"
echo "  → /usr/local/bin/paserexpress-backup.sh"
echo ""
echo -e "============================================="
echo -e "        💜 PaserExpress v3 Installer          "
echo -e "============================================="
echo ""

exit 0

