#!/usr/bin/env bash
set -euo pipefail

###############################################
#  AURORA ROYALE INSTALLER — FINAL FIX v2 💜
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

# Aurora Gradient Header
aurora(){
    echo -e "${PINK}$1${PURPLE}$2${BLUE}$3${CYAN}$4${NC}"
}

banner(){
    echo ""
    echo "$(aurora "━━" "━━━━━━" "━━━━━━" "━━━━━━")"
    echo -e "${PURPLE}${BOLD}    ✨  PASEREXPRESS INSTALLER — AURORA ROYALE EDITION ✨ ${NC}"
    echo "$(aurora "━━" "━━━━━━" "━━━━━━" "━━━━━━")"
    echo ""
}

info() { echo -e " ${BLUE}◆${NC} $1"; }
warn() { echo -e " ${YELLOW}▲ WARNING:${NC} $1"; }
err()  { echo -e " ${RED}✖ ERROR:${NC} $1"; exit 1; }

# ============
# ROOT CHECK
# ============
need_root(){
    if [[ $(id -u) -ne 0 ]]; then
        err "Installer harus dijalankan sebagai ROOT!"
    fi
}

# =======================
# FIXED INPUT FUNCTIONS ✔
# =======================

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
    echo ""
    echo "$val"
}

ask_confirm(){
    local prompt="$1"
    local ans=""
    echo -e "${BLUE}${prompt}${NC} (y/n)"
    read -rp "> " ans
    [[ "${ans,,}" == "y" ]]
}

# ==========================
# DOMAIN VALIDATOR
# ==========================
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

# ==========================
# PORT VALIDATOR
# ==========================
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

###############################################
#           PART 1 — INPUT COLLECTOR
###############################################
banner
need_root
info "Memulai Input Wizard Aurora Royale…"
echo ""

info "Mengambil konfigurasi dari user…"
echo ""

WEB_DOMAIN=$(ask_domain "Masukkan DOMAIN WEBSITE (misal: a.aziztech.us)")
DB_DOMAIN=$(ask_domain "Masukkan DOMAIN DATABASE (metadata)")
NODE_DOMAIN=$(ask_domain "Masukkan DOMAIN NODE API")

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
SSH_P3=$(ask_port "Masukkan PORT SSH tambahan ke-3")

USE_HTTPS="y"
TELEMETRY="yes"

echo ""
info "✔ Input selesai — memulai instalasi sistem…"
echo ""

###############################################
#   PART 2 — SYSTEM SETUP
###############################################
info "Mengupdate repositori…"
apt-get update -y

info "Menginstall paket inti…"
apt-get install -y \
    nginx git curl ufw \
    php8.3 php8.3-fpm php8.3-mysql php8.3-curl php8.3-zip php8.3-xml php8.3-mbstring \
    mariadb-server

systemctl enable --now nginx
systemctl enable --now php8.3-fpm
systemctl enable --now mariadb

###############################################
#   PART 3 — SSH SECURITY
###############################################
info "Mengkonfigurasi SSH multi-port…"

mkdir -p /etc/ssh/sshd_config.d
cat >/etc/ssh/sshd_config.d/99-paserexpress.conf <<EOF
Port ${SSH_P1}
Port ${SSH_P2}
Port ${SSH_P3}
EOF

systemctl restart ssh || systemctl restart sshd

###############################################
#   PART 4 — FIREWALL
###############################################
info "Mengaktifkan firewall…"

ufw allow "${SSH_P1}/tcp"
ufw allow "${SSH_P2}/tcp"
ufw allow "${SSH_P3}/tcp"
ufw allow 80/tcp
ufw allow 443/tcp

ufw --force enable

###############################################
#   PART 5 — DEPLOY FROM GITHUB
###############################################
APP_ROOT="/var/www/paserexpress"

info "Mengambil source code dari GitHub…"

if [[ -d "$APP_ROOT/.git" ]]; then
    (cd "$APP_ROOT" && git fetch --all && git reset --hard origin/main)
else
    rm -rf "$APP_ROOT"
    git clone --depth 1 -b main https://github.com/azizcool1998/paserexpress.git "$APP_ROOT"
fi

###############################################
#   PART 6 — WRITE ENV
###############################################
info "Menulis file konfigurasi .env…"

cat > "$APP_ROOT/.env" <<EOF
APP_NAME="Paser Express"
APP_ENV=production
APP_DEBUG=false

APP_BASE_URL=http://${WEB_DOMAIN}

DB_HOST=127.0.0.1
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}
DB_DOMAIN=${DB_DOMAIN}
DB_EMAIL=${DB_EMAIL}

NODE_DOMAIN=${NODE_DOMAIN}

ADMIN_USERNAME=${ADMIN_USERNAME}
ADMIN_PASSWORD=${ADMIN_PASS}
ADMIN_EMAIL=${ADMIN_EMAIL}
ADMIN_FIRST_NAME=${ADMIN_FIRST}
ADMIN_LAST_NAME=${ADMIN_LAST}
ADMIN_WHATSAPP=${ADMIN_WA}

TELEMETRY_ENABLED=${TELEMETRY}
EOF

chmod 600 "$APP_ROOT/.env"

###############################################
#   PART 7 — DATABASE SETUP
###############################################
info "Menyiapkan database…"

mysql -e "CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost'; FLUSH PRIVILEGES;"

mysql -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < "$APP_ROOT/config/schema.sql"

###############################################
#   PART 8 — ADMIN SEED
###############################################
info "Membuat admin default…"

php "$APP_ROOT/src/cli/seed_admin.php" \
    --username="$ADMIN_USERNAME" \
    --password="$ADMIN_PASS" \
    --email="$ADMIN_EMAIL" \
    --first="$ADMIN_FIRST" \
    --last="$ADMIN_LAST" \
    --wa="$ADMIN_WA"

###############################################
#   PART 9 — NGINX CONFIG
###############################################
info "Menulis konfigurasi Nginx…"

cat >/etc/nginx/sites-available/${WEB_DOMAIN}.conf <<EOF
server {
    listen 80;
    server_name ${WEB_DOMAIN};

    root ${APP_ROOT}/src/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \\.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
EOF

ln -sf "/etc/nginx/sites-available/${WEB_DOMAIN}.conf" "/etc/nginx/sites-enabled/${WEB_DOMAIN}.conf"
rm -f /etc/nginx/sites-enabled/default || true

systemctl reload nginx

###############################################
#   PART 10 — HTTPS
###############################################
if [[ "$USE_HTTPS" == "y" ]]; then
    info "Mengaktifkan HTTPS…"
    apt-get install -y certbot python3-certbot-nginx
    certbot --nginx -d "$WEB_DOMAIN" --non-interactive --agree-tos -m "$ADMIN_EMAIL" || true
    systemctl reload nginx
fi

###############################################
#   PART 11 — BACKUP SYSTEM
###############################################
info "Menginstall backup system…"

mkdir -p /var/backups/paserexpress
curl -fsSL "https://raw.githubusercontent.com/azizcool1998/paserexpress/main/scripts/paserexpress-backup.sh" \
    -o /usr/local/bin/paserexpress-backup.sh

chmod +x /usr/local/bin/paserexpress-backup.sh

cat >/etc/cron.d/paserexpress-backup <<EOF
# Auto-backup: nonaktif (atur via Admin Panel)
EOF

###############################################
#   DONE
###############################################
echo ""
aurora "✨" " INSTALLASI " "BERHASIL 🎉" "✨"
echo ""
info "Website: http://${WEB_DOMAIN}"
info "Login Admin: http://${WEB_DOMAIN}/?page=login"
echo ""
