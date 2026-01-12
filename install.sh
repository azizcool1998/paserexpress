#!/usr/bin/env bash
set -euo pipefail

APP_NAME="PaserExpress"
APP_USER="paserexpress"
APP_DIR="/opt/PaserExpress"
UPLOAD_DIR="/opt/PaserExpress/uploads"

GREEN="\033[0;32m"; RED="\033[0;31m"; NC="\033[0m"

need_root() {
  if [[ "${EUID}" -ne 0 ]]; then
    echo -e "${RED}Harus dijalankan sebagai root: sudo bash install.sh${NC}"
    exit 1
  fi
}

ask() {
  local prompt="$1"; local default="$2"; local var
  read -r -p "$prompt [$default]: " var || true
  if [[ -z "${var}" ]]; then var="$default"; fi
  echo "$var"
}

ask_yn() {
  local prompt="$1"; local default="$2"; local var
  read -r -p "$prompt (y/n) [$default]: " var || true
  if [[ -z "${var}" ]]; then var="$default"; fi
  [[ "${var}" =~ ^[Yy]$ ]]
}

install_node() {
  if command -v node >/dev/null 2>&1; then
    echo "Node already installed: $(node -v)"
    return
  fi
  curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
  apt-get install -y nodejs
}

main() {
  need_root

  echo "=== ${APP_NAME} Installer (Ubuntu 24.04) ==="

  DOMAIN=$(ask "Masukkan domain (contoh: paserexpress.com)" "example.com")
  TZ=$(ask "Timezone" "Asia/Makassar")

  BACKEND_HOST=$(ask "Bind host backend (default 0.0.0.0)" "0.0.0.0")
  BACKEND_PORT=$(ask "Port backend (hindari tabrakan)" "8081")

  FRONTEND_HOST=$(ask "Bind host frontend (default 0.0.0.0)" "0.0.0.0")
  FRONTEND_PORT=$(ask "Port frontend (hindari tabrakan)" "3001")

  DB_HOST=$(ask "DB host (umumnya 127.0.0.1)" "127.0.0.1")
  DB_PORT=$(ask "DB port" "3306")
  DB_NAME=$(ask "Nama database" "paser_express")
  DB_USER=$(ask "Username database" "paser_user")
  DB_PASS=$(ask "Password database" "ChangeMe_12345!")
  DB_EMAIL=$(ask "Email database (catatan settings)" "db@example.com")

  ADMIN_FIRST=$(ask "Admin pertama: First name" "Admin")
  ADMIN_LAST=$(ask "Admin pertama: Last name" "Paser")
  ADMIN_EMAIL=$(ask "Admin pertama: Email" "admin@example.com")
  ADMIN_USERNAME=$(ask "Admin pertama: Username" "admin")
  ADMIN_PASS=$(ask "Admin pertama: Password" "AdminPass_12345!")

  SETUP_UFW=false
  if ask_yn "Setup firewall UFW (allow OpenSSH + Nginx Full)?" "y"; then
    SETUP_UFW=true
  fi

  ENABLE_SSL=false
  CERTBOT_EMAIL=""
  if ask_yn "Aktifkan SSL Let's Encrypt (certbot)?" "y"; then
    ENABLE_SSL=true
    CERTBOT_EMAIL=$(ask "Email untuk certbot" "ssl@example.com")
  fi

  echo -e "${GREEN}>> Install dependency OS...${NC}"
  apt-get update -y
  apt-get install -y curl git nginx mariadb-server mariadb-client build-essential ufw rsync

  echo -e "${GREEN}>> Install Node.js 20...${NC}"
  install_node

  echo -e "${GREEN}>> Set timezone...${NC}"
  timedatectl set-timezone "${TZ}"

  echo -e "${GREEN}>> Create user & directories...${NC}"
  id -u "${APP_USER}" >/dev/null 2>&1 || useradd -m -s /bin/bash "${APP_USER}"
  mkdir -p "${APP_DIR}"
  mkdir -p "${UPLOAD_DIR}"
  chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
  chown -R "${APP_USER}:${APP_USER}" "${UPLOAD_DIR}"

  echo -e "${GREEN}>> Copy project files to ${APP_DIR}...${NC}"
  rsync -a --delete --exclude "node_modules" --exclude ".next" ./ "${APP_DIR}/"
  chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"

  echo -e "${GREEN}>> Setup MariaDB database & user...${NC}"
  systemctl enable --now mariadb

  mysql -u root <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST}' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST}';
FLUSH PRIVILEGES;
SQL

  echo -e "${GREEN}>> Create backend .env...${NC}"
  cat > "${APP_DIR}/backend/.env" <<ENV
APP_NAME=Paser Express
NODE_ENV=production
PORT=${BACKEND_PORT}
BIND_HOST=${BACKEND_HOST}

JWT_SECRET=$(openssl rand -hex 32)

DB_HOST=${DB_HOST}
DB_PORT=${DB_PORT}
DB_NAME=${DB_NAME}
DB_USER=${DB_USER}
DB_PASS=${DB_PASS}

UPLOAD_DIR=${UPLOAD_DIR}

TIMEZONE=${TZ}
DB_EMAIL=${DB_EMAIL}

PUBLIC_URL=https://${DOMAIN}
ENV
  chown "${APP_USER}:${APP_USER}" "${APP_DIR}/backend/.env"
  chmod 600 "${APP_DIR}/backend/.env"

  echo -e "${GREEN}>> Install backend deps & init DB...${NC}"
  sudo -u "${APP_USER}" bash -lc "cd ${APP_DIR}/backend && npm install && npm run db:init     -- --admin_first='${ADMIN_FIRST}' --admin_last='${ADMIN_LAST}' --admin_email='${ADMIN_EMAIL}' --admin_username='${ADMIN_USERNAME}' --admin_pass='${ADMIN_PASS}'"

  echo -e "${GREEN}>> Install frontend deps & build...${NC}"
  sudo -u "${APP_USER}" bash -lc "cd ${APP_DIR}/frontend && npm install && npm run build"

  echo -e "${GREEN}>> Create systemd services...${NC}"
  cat > /etc/systemd/system/paserexpress-backend.service <<SVC
[Unit]
Description=Paser Express Backend
After=network.target mariadb.service

[Service]
Type=simple
User=${APP_USER}
WorkingDirectory=${APP_DIR}/backend
EnvironmentFile=${APP_DIR}/backend/.env
ExecStart=/usr/bin/node ${APP_DIR}/backend/src/index.js
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
SVC

  cat > /etc/systemd/system/paserexpress-frontend.service <<SVC
[Unit]
Description=Paser Express Frontend (Next.js)
After=network.target

[Service]
Type=simple
User=${APP_USER}
WorkingDirectory=${APP_DIR}/frontend
Environment=NODE_ENV=production
Environment=PORT=${FRONTEND_PORT}
Environment=HOSTNAME=${FRONTEND_HOST}
Environment=BACKEND_INTERNAL_URL=http://127.0.0.1:${BACKEND_PORT}
ExecStart=/usr/bin/npm run start -- -p ${FRONTEND_PORT} -H ${FRONTEND_HOST}
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
SVC

  systemctl daemon-reload
  systemctl enable --now paserexpress-backend
  systemctl enable --now paserexpress-frontend

  echo -e "${GREEN}>> Configure Nginx vhost...${NC}"
  cat > /etc/nginx/sites-available/paserexpress.conf <<NGINX
server {
  listen 80;
  server_name ${DOMAIN};

  client_max_body_size 25m;

  location / {
    proxy_pass http://127.0.0.1:${FRONTEND_PORT};
    proxy_http_version 1.1;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
    proxy_set_header Upgrade \$http_upgrade;
    proxy_set_header Connection "upgrade";
  }

  location /api/ {
    proxy_pass http://127.0.0.1:${BACKEND_PORT}/api/;
    proxy_http_version 1.1;
    proxy_set_header Host \$host;
    proxy_set_header X-Real-IP \$remote_addr;
    proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto \$scheme;
  }

  location /uploads/ {
    proxy_pass http://127.0.0.1:${BACKEND_PORT}/uploads/;
    proxy_set_header Host \$host;
  }
}
NGINX

  ln -sf /etc/nginx/sites-available/paserexpress.conf /etc/nginx/sites-enabled/paserexpress.conf
  nginx -t
  systemctl reload nginx

  if [[ "${SETUP_UFW}" == "true" ]]; then
    echo -e "${GREEN}>> Setup UFW...${NC}"
    ufw allow OpenSSH
    ufw allow 'Nginx Full'
    ufw --force enable
  fi

  if [[ "${ENABLE_SSL}" == "true" ]]; then
    echo -e "${GREEN}>> Install certbot...${NC}"
    apt-get install -y certbot python3-certbot-nginx
    certbot --nginx -d "${DOMAIN}" --non-interactive --agree-tos -m "${CERTBOT_EMAIL}" --redirect
    systemctl reload nginx
  fi

  echo -e "${GREEN}=== SELESAI ===${NC}"
  echo "Website: http(s)://${DOMAIN}"
  echo "Admin login: ${ADMIN_USERNAME} / ${ADMIN_PASS}"
  echo "Backend port: ${BACKEND_PORT} | Frontend port: ${FRONTEND_PORT}"
  echo "Uploads: ${UPLOAD_DIR}"
}

main "$@"
