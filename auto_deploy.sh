#!/usr/bin/env bash
set -euo pipefail

############################################
# Terraf / OXP Final Clean Deployment Script v3
#
# Purpose:
#   Clean production-style single-server deployment.
#
# Deployment model, matching the working dev server:
#   http://SERVER/          -> Next.js on 127.0.0.1:3000
#   http://SERVER/api/      -> Laravel on 127.0.0.1:8000
#   http://SERVER/storage/  -> Laravel storage on 127.0.0.1:8000
#   http://SERVER:8000/admin -> Laravel admin panel
#
# Important principles:
#   - Does NOT patch project source code.
#   - Does NOT run composer update.
#   - Does NOT use artisan tinker.
#   - Does NOT manually ALTER application tables.
#  - Creates Laravel public/storage symlink with root ln -s, not artisan storage:link.
#  - Uses absolute paths for storage symlink creation to avoid copied hidden-character path errors.
#   - DB schema fixes must be committed as Laravel migrations.
#   - Frontend build fixes must be committed in the repo.
#
# Required repo fixes before using this as the final installer:
#   1. composer.lock must match composer.json and PHP version.
#   2. carts.session_key must be long enough via migration, recommended VARCHAR(512).
#   3. frontend server API resolver should support NEXT_SERVER_API_BASE_URL.
#   4. legal/privacy pages should not crash next build when CMS API is unavailable.
#
# Usage:
#   sudo bash deploy_terraf_final_clean.sh 172.204.80.106
#
# Optional env overrides:
#   APP_DIR=/var/www/terraf_shell
#   REPO_URL=https://github.com/xingtuan/terraf_shell.git
#   BRANCH=main
#   PHP_VERSION=8.3
#   NODE_MAJOR=20
#   DB_NAME=oxp_local
#   DB_USER=oxp_user
#   DB_PASS=your-password
#   RUN_SEED=1
#   RESET_WORKTREE=0
#   STRICT_PREFLIGHT=1
############################################

export COMPOSER_ALLOW_SUPERUSER=1
export DEBIAN_FRONTEND=noninteractive

if [ "$EUID" -ne 0 ]; then
  echo "ERROR: Please run as root:"
  echo "  sudo bash deploy_terraf_final_clean.sh your-domain-or-ip"
  exit 1
fi

SERVER_NAME="${1:-}"
if [ -z "$SERVER_NAME" ]; then
  SERVER_NAME="$(hostname -I | awk '{print $1}')"
fi

APP_DIR="${APP_DIR:-/var/www/terraf_shell}"
BACKEND_DIR="$APP_DIR/B2C_backend"
FRONTEND_DIR="$APP_DIR/B2C_frontend"

REPO_URL="${REPO_URL:-https://github.com/xingtuan/terraf_shell.git}"
BRANCH="${BRANCH:-main}"

PHP_VERSION="${PHP_VERSION:-8.3}"
NODE_MAJOR="${NODE_MAJOR:-20}"

DB_NAME="${DB_NAME:-oxp_local}"
DB_USER="${DB_USER:-oxp_user}"
DB_PASS="${DB_PASS:-$(openssl rand -base64 32 | tr -dc 'A-Za-z0-9' | head -c 24)}"

RUN_SEED="${RUN_SEED:-1}"
RESET_WORKTREE="${RESET_WORKTREE:-0}"
STRICT_PREFLIGHT="${STRICT_PREFLIGHT:-1}"

DEPLOY_USER="${SUDO_USER:-victor}"

FRONTEND_URL="http://${SERVER_NAME}"
BACKEND_URL="http://${SERVER_NAME}:8000"
BACKEND_LOCAL_URL="http://127.0.0.1:8000"

echo "============================================"
echo "Terraf / OXP Final Clean Deployment v3"
echo "============================================"
echo "Server:             ${SERVER_NAME}"
echo "Frontend:           ${FRONTEND_URL}"
echo "Frontend API:       ${FRONTEND_URL}/api"
echo "Backend/admin:      ${BACKEND_URL}/admin"
echo "Backend local API:  ${BACKEND_LOCAL_URL}/api"
echo "App dir:            ${APP_DIR}"
echo "Repo:               ${REPO_URL}"
echo "Branch:             ${BRANCH}"
echo "PHP:                ${PHP_VERSION}"
echo "Node:               ${NODE_MAJOR}"
echo "Database:           ${DB_NAME}"
echo "DB user:            ${DB_USER}"
echo "Run seed:           ${RUN_SEED}"
echo "Strict preflight:   ${STRICT_PREFLIGHT}"
echo "Reset worktree:     ${RESET_WORKTREE}"
echo "============================================"

############################################
# Helpers
############################################

fail() {
  echo ""
  echo "ERROR: $*"
  echo ""
  exit 1
}

warn() {
  echo "WARNING: $*"
}

set_env() {
  local file="$1"
  local key="$2"
  local value="$3"

  touch "$file"

  if grep -q "^${key}=" "$file"; then
    sed -i "s|^${key}=.*|${key}=${value}|g" "$file"
  else
    echo "${key}=${value}" >> "$file"
  fi
}

generate_app_key() {
  php -r 'echo "base64:".base64_encode(random_bytes(32));'
}

wait_for_url() {
  local url="$1"
  local label="$2"
  local attempts="${3:-20}"
  local sleep_seconds="${4:-2}"

  echo "==> Checking ${label}: ${url}"

  for i in $(seq 1 "$attempts"); do
    if curl -fsS --max-time 5 "$url" >/dev/null 2>&1; then
      echo "OK: ${label} is reachable."
      return 0
    fi

    echo "Waiting for ${label}... attempt ${i}/${attempts}"
    sleep "$sleep_seconds"
  done

  return 1
}

fix_laravel_runtime_permissions() {
  echo "==> Preparing Laravel runtime permissions..."

  cd "$BACKEND_DIR"

  mkdir -p storage/logs
  mkdir -p storage/framework/cache
  mkdir -p storage/framework/sessions
  mkdir -p storage/framework/views
  mkdir -p bootstrap/cache

  touch storage/logs/laravel.log || true

  # .env should be readable by PHP-FPM, but not writable by the web process.
  chown root:www-data .env
  chmod 640 .env

  chown -R www-data:www-data storage bootstrap/cache
  chmod -R ug+rwX storage bootstrap/cache

  if id "$DEPLOY_USER" >/dev/null 2>&1; then
    usermod -aG www-data "$DEPLOY_USER" || true
  fi

  if command -v setfacl >/dev/null 2>&1; then
    setfacl -m u:www-data:r .env || true
    if id "$DEPLOY_USER" >/dev/null 2>&1; then
      setfacl -m u:"$DEPLOY_USER":r .env || true
    fi

    setfacl -R -m u:www-data:rwx storage bootstrap/cache || true
    setfacl -R -d -m u:www-data:rwx storage bootstrap/cache || true

    if id "$DEPLOY_USER" >/dev/null 2>&1; then
      setfacl -R -m u:"$DEPLOY_USER":rwx storage bootstrap/cache || true
      setfacl -R -d -m u:"$DEPLOY_USER":rwx storage bootstrap/cache || true
    fi
  fi
}

artisan_as_www_data() {
  cd "$BACKEND_DIR"
  sudo -u www-data php artisan "$@"
}

create_storage_symlink() {
  echo "==> Creating Laravel storage symlink with root..."

  local storage_public_dir="${BACKEND_DIR}/storage/app/public"
  local public_dir="${BACKEND_DIR}/public"
  local public_storage_link="${public_dir}/storage"
  local link_target="../storage/app/public"

  # Use absolute paths and install -d to avoid hidden/control-character issues
  # from copied shell snippets.
  install -d -m 2775 -o www-data -g www-data "$storage_public_dir"
  install -d -m 2775 -o www-data -g www-data "$public_dir"

  if [ -L "$public_storage_link" ] || [ ! -e "$public_storage_link" ]; then
    rm -f -- "$public_storage_link"
  else
    fail "${public_storage_link} exists and is not a symlink. Move or remove it before deployment."
  fi

  ln -s "$link_target" "$public_storage_link"
  chown -h www-data:www-data "$public_storage_link"

  echo "Storage symlink:"
  ls -la "$public_storage_link"

  if [ ! -d "${public_storage_link}" ]; then
    fail "Storage symlink was created but target is not reachable: ${public_storage_link}"
  fi
}

strict_or_warn() {
  local message="$1"
  if [ "$STRICT_PREFLIGHT" = "1" ]; then
    fail "$message"
  else
    warn "$message"
  fi
}

############################################
# System packages
############################################

echo "==> Installing system packages..."

apt update
apt upgrade -y

apt install -y \
  git curl unzip zip ca-certificates gnupg lsb-release \
  software-properties-common nginx mysql-server supervisor \
  openssl cron python3 acl

############################################
# PHP
############################################

echo "==> Installing PHP ${PHP_VERSION}..."

if ! apt-cache show "php${PHP_VERSION}-cli" >/dev/null 2>&1; then
  add-apt-repository -y ppa:ondrej/php
  apt update
fi

apt install -y \
  php${PHP_VERSION}-fpm \
  php${PHP_VERSION}-cli \
  php${PHP_VERSION}-mysql \
  php${PHP_VERSION}-mbstring \
  php${PHP_VERSION}-xml \
  php${PHP_VERSION}-curl \
  php${PHP_VERSION}-zip \
  php${PHP_VERSION}-bcmath \
  php${PHP_VERSION}-intl \
  php${PHP_VERSION}-gd

systemctl enable --now "php${PHP_VERSION}-fpm"

############################################
# Composer
############################################

echo "==> Installing Composer..."

if ! command -v composer >/dev/null 2>&1; then
  cd /tmp
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  php composer-setup.php --install-dir=/usr/local/bin --filename=composer
  rm -f composer-setup.php
fi

composer --version

############################################
# Node.js + pnpm
############################################

echo "==> Installing Node.js ${NODE_MAJOR} and pnpm..."

NEED_NODE_INSTALL=0

if ! command -v node >/dev/null 2>&1; then
  NEED_NODE_INSTALL=1
else
  CURRENT_NODE_MAJOR="$(node -v | sed 's/v//' | cut -d. -f1)"
  if [ "$CURRENT_NODE_MAJOR" != "$NODE_MAJOR" ]; then
    NEED_NODE_INSTALL=1
  fi
fi

if [ "$NEED_NODE_INSTALL" = "1" ]; then
  curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash -
  apt install -y nodejs
fi

if ! command -v pnpm >/dev/null 2>&1; then
  rm -f /usr/bin/pnpm /usr/bin/pnpx /usr/local/bin/pnpm /usr/local/bin/pnpx
  npm install -g pnpm --force
fi

PNPM_BIN="$(command -v pnpm)"

node -v
npm -v
pnpm -v

############################################
# MySQL
############################################

echo "==> Creating local MySQL database..."

systemctl enable --now mysql

mysql <<MYSQL_SCRIPT
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost'
  IDENTIFIED BY '${DB_PASS}';

ALTER USER '${DB_USER}'@'localhost'
  IDENTIFIED BY '${DB_PASS}';

GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';

FLUSH PRIVILEGES;
MYSQL_SCRIPT

############################################
# Clone/update repo
############################################

echo "==> Preparing project code..."

mkdir -p "$(dirname "$APP_DIR")"

if [ -d "$APP_DIR/.git" ]; then
  cd "$APP_DIR"

  if [ "$RESET_WORKTREE" = "1" ]; then
    echo "RESET_WORKTREE=1: resetting local changes."
    git fetch origin
    git checkout "$BRANCH"
    git reset --hard "origin/${BRANCH}"
    git clean -fd
  else
    if ! git diff --quiet || ! git diff --cached --quiet; then
      fail "Existing repo has local modifications. Commit/stash them, or rerun with RESET_WORKTREE=1."
    fi

    git fetch origin
    git checkout "$BRANCH"
    git pull --ff-only origin "$BRANCH"
  fi
else
  git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi

[ -d "$BACKEND_DIR" ] || fail "Backend directory missing: $BACKEND_DIR"
[ -d "$FRONTEND_DIR" ] || fail "Frontend directory missing: $FRONTEND_DIR"

############################################
# Source-level preflight checks
############################################

echo "==> Running source-level preflight checks..."

if [ ! -f "$BACKEND_DIR/composer.lock" ]; then
  strict_or_warn "composer.lock is missing. Clean deployment requires composer.lock."
fi

if [ -f "$FRONTEND_DIR/lib/api/server-base-url.ts" ]; then
  if ! grep -q "NEXT_SERVER_API_BASE_URL" "$FRONTEND_DIR/lib/api/server-base-url.ts"; then
    strict_or_warn "Frontend server API resolver does not reference NEXT_SERVER_API_BASE_URL. Commit this source fix before final deployment."
  fi
else
  warn "Frontend server-base-url.ts not found. Skipping NEXT_SERVER_API_BASE_URL source check."
fi

LEGAL_FALLBACK_OK=0
if [ -f "$FRONTEND_DIR/lib/api/legal-pages.ts" ] && grep -q "fallbackLegalContent\|using local fallback" "$FRONTEND_DIR/lib/api/legal-pages.ts"; then
  LEGAL_FALLBACK_OK=1
fi

PRIVACY_DYNAMIC_OK=0
if [ -f "$FRONTEND_DIR/app/[locale]/privacy/page.tsx" ] && grep -q "force-dynamic" "$FRONTEND_DIR/app/[locale]/privacy/page.tsx"; then
  PRIVACY_DYNAMIC_OK=1
fi

if [ "$LEGAL_FALLBACK_OK" = "0" ] && [ "$PRIVACY_DYNAMIC_OK" = "0" ]; then
  strict_or_warn "Privacy/legal pages may still crash next build if CMS API is unavailable. Commit legal fallback or dynamic rendering before final deployment."
fi

############################################
# Backend .env
############################################

echo "==> Writing backend .env..."

cd "$BACKEND_DIR"

if [ ! -f ".env" ]; then
  cp .env.example .env
fi

cp ".env" ".env.bak.final.$(date +%Y%m%d%H%M%S)" || true

EXISTING_APP_KEY="$(grep '^APP_KEY=' .env | cut -d= -f2- || true)"
if [ -z "$EXISTING_APP_KEY" ] || [ "$EXISTING_APP_KEY" = "" ]; then
  APP_KEY_VALUE="$(generate_app_key)"
else
  APP_KEY_VALUE="$EXISTING_APP_KEY"
fi

set_env ".env" "APP_NAME" "OXP"
set_env ".env" "APP_ENV" "production"
set_env ".env" "APP_DEBUG" "false"
set_env ".env" "APP_KEY" "$APP_KEY_VALUE"
set_env ".env" "APP_URL" "$BACKEND_URL"

set_env ".env" "DB_CONNECTION" "mysql"
set_env ".env" "DB_HOST" "127.0.0.1"
set_env ".env" "DB_PORT" "3306"
set_env ".env" "DB_DATABASE" "$DB_NAME"
set_env ".env" "DB_USERNAME" "$DB_USER"
set_env ".env" "DB_PASSWORD" "$DB_PASS"

set_env ".env" "FRONTEND_URL" "$FRONTEND_URL"
set_env ".env" "CORS_ALLOWED_ORIGINS" "$FRONTEND_URL"
set_env ".env" "CORS_SUPPORTS_CREDENTIALS" "true"

# Match the working dev pattern: production API is same-origin through /api,
# so do not force production host into Sanctum stateful SPA mode.
set_env ".env" "SANCTUM_STATEFUL_DOMAINS" "localhost:3000,127.0.0.1:3000"

set_env ".env" "SESSION_DRIVER" "database"
set_env ".env" "SESSION_DOMAIN" "null"
set_env ".env" "SESSION_SECURE_COOKIE" "false"
set_env ".env" "SESSION_SAME_SITE" "lax"

set_env ".env" "CACHE_STORE" "database"
set_env ".env" "QUEUE_CONNECTION" "database"

set_env ".env" "STORAGE_DISK" "public"
set_env ".env" "FILESYSTEM_DISK" "public"
set_env ".env" "MEDIA_DRIVER" "public"
set_env ".env" "COMMUNITY_UPLOAD_DISK" "public"
set_env ".env" "LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK" "public"

set_env ".env" "MAIL_MAILER" "log"
set_env ".env" "NZPOST_ENABLED" "false"

fix_laravel_runtime_permissions

############################################
# Backend dependencies
############################################

echo "==> Installing backend dependencies with composer install..."

cd "$BACKEND_DIR"

if ! composer install --no-dev --optimize-autoloader --no-interaction; then
  fail "composer install failed. Do not fix this on the server with composer update. Fix composer.json/composer.lock in the repo and redeploy."
fi

fix_laravel_runtime_permissions

############################################
# Laravel setup
############################################

echo "==> Running Laravel migrations and cache setup..."

create_storage_symlink
artisan_as_www_data migrate --force

if [ "$RUN_SEED" = "1" ]; then
  artisan_as_www_data db:seed --force
fi

# Concrete schema verification for the known cart cookie issue.
echo "==> Verifying carts.session_key column length..."

CART_SESSION_LENGTH="$(mysql -N -B "$DB_NAME" -e "SELECT COALESCE(CHARACTER_MAXIMUM_LENGTH, 0) FROM information_schema.columns WHERE table_schema='${DB_NAME}' AND table_name='carts' AND column_name='session_key' LIMIT 1;" 2>/dev/null || echo 0)"

if [ -z "$CART_SESSION_LENGTH" ]; then
  CART_SESSION_LENGTH=0
fi

if [ "$CART_SESSION_LENGTH" -lt 512 ]; then
  strict_or_warn "carts.session_key length is ${CART_SESSION_LENGTH}; expected >=512. Add a Laravel migration to change it to VARCHAR(512). This script will not ALTER application tables."
fi

artisan_as_www_data optimize:clear
artisan_as_www_data config:cache
artisan_as_www_data route:cache || true
artisan_as_www_data view:cache || true

fix_laravel_runtime_permissions

############################################
# Nginx configs, matching dev model
############################################

echo "==> Writing Nginx configs..."

cat > /etc/nginx/sites-available/front <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${SERVER_NAME};

    charset utf-8;

    client_max_body_size 50M;

    location = /api {
        return 301 /api/;
    }

    location ^~ /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;

        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location ^~ /storage/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;

        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
    }

    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;

        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;

        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

cat > /etc/nginx/sites-available/laravel <<NGINX
server {
    listen 8000;
    listen [::]:8000;
    server_name 127.0.0.1 ${SERVER_NAME};

    root ${BACKEND_DIR}/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    client_max_body_size 50M;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php\$ {
        fastcgi_pass unix:/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT \$realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

rm -f /etc/nginx/sites-enabled/default
rm -f /etc/nginx/sites-enabled/terraf
rm -f /etc/nginx/sites-enabled/terraf-backend-8000
rm -f /etc/nginx/sites-enabled/terraf-frontend-80

ln -sf /etc/nginx/sites-available/front /etc/nginx/sites-enabled/front
ln -sf /etc/nginx/sites-available/laravel /etc/nginx/sites-enabled/laravel

nginx -t

systemctl enable --now nginx
systemctl restart "php${PHP_VERSION}-fpm"
systemctl reload nginx

if command -v ufw >/dev/null 2>&1; then
  ufw allow 80/tcp || true
  ufw allow 8000/tcp || true
fi

wait_for_url "${BACKEND_LOCAL_URL}/up" "backend local health" 20 2 || {
  tail -n 120 "$BACKEND_DIR/storage/logs/laravel.log" || true
  fail "Backend local health failed."
}

############################################
# Queue worker and scheduler
############################################

echo "==> Configuring queue worker..."

cat > /etc/supervisor/conf.d/terraf-queue.conf <<SUPERVISOR
[program:terraf-queue]
process_name=%(program_name)s_%(process_num)02d
command=php ${BACKEND_DIR}/artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=90
directory=${BACKEND_DIR}
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=${BACKEND_DIR}/storage/logs/queue-worker.log
stopwaitsecs=3600
SUPERVISOR

supervisorctl reread
supervisorctl update
supervisorctl restart terraf-queue:* || true

echo "==> Configuring scheduler..."

cat > /etc/cron.d/terraf-scheduler <<CRON
* * * * * www-data cd ${BACKEND_DIR} && php artisan schedule:run >> /dev/null 2>&1
CRON

chmod 644 /etc/cron.d/terraf-scheduler
systemctl enable --now cron

############################################
# Frontend .env
############################################

echo "==> Writing frontend .env.local..."

cd "$FRONTEND_DIR"

if [ ! -f ".env.local" ]; then
  if [ -f ".env.example" ]; then
    cp .env.example .env.local
  else
    touch .env.local
  fi
fi

cp ".env.local" ".env.local.bak.final.$(date +%Y%m%d%H%M%S)" || true

set_env ".env.local" "NEXT_PUBLIC_API_BASE_URL" "/api"
set_env ".env.local" "NEXT_SERVER_API_BASE_URL" "${BACKEND_LOCAL_URL}/api"
set_env ".env.local" "NEXT_PUBLIC_MEDIA_BASE_URL" ""
set_env ".env.local" "NEXT_PUBLIC_BRAND_CONTACT_EMAIL" ""
set_env ".env.local" "NEXT_PUBLIC_SITE_URL" "${FRONTEND_URL}"

############################################
# Frontend install/build
############################################

echo "==> Installing and building frontend..."

cd "$FRONTEND_DIR"

pnpm install --frozen-lockfile=false

NEXT_TELEMETRY_DISABLED=1 \
NEXT_PUBLIC_API_BASE_URL="/api" \
NEXT_SERVER_API_BASE_URL="${BACKEND_LOCAL_URL}/api" \
NEXT_PUBLIC_MEDIA_BASE_URL="" \
NEXT_PUBLIC_SITE_URL="${FRONTEND_URL}" \
pnpm build

chown -R www-data:www-data "$FRONTEND_DIR/.next" || true

############################################
# Frontend systemd
############################################

echo "==> Configuring frontend systemd service..."

cat > /etc/systemd/system/terraf-frontend.service <<SERVICE
[Unit]
Description=Terraf OXP Next.js Frontend
After=network.target nginx.service
Wants=nginx.service

[Service]
Type=simple
WorkingDirectory=${FRONTEND_DIR}
ExecStart=${PNPM_BIN} start --hostname 127.0.0.1 --port 3000
Restart=always
RestartSec=5
Environment=NODE_ENV=production
User=www-data
Group=www-data

[Install]
WantedBy=multi-user.target
SERVICE

systemctl daemon-reload
systemctl enable --now terraf-frontend

############################################
# Final checks
############################################

echo "==> Restarting final services..."

fix_laravel_runtime_permissions
create_storage_symlink
systemctl restart "php${PHP_VERSION}-fpm"
systemctl restart terraf-frontend
supervisorctl restart terraf-queue:* || true
systemctl reload nginx

sleep 3

wait_for_url "${FRONTEND_URL}" "frontend" 10 2 || warn "Frontend URL did not respond during final check."
wait_for_url "${FRONTEND_URL}/api/cart" "frontend API proxy" 10 2 || warn "API proxy did not respond during final check."
wait_for_url "${BACKEND_LOCAL_URL}/up" "backend local health" 10 2 || warn "Backend local health did not respond during final check."

mkdir -p /root/terraf-install

cat > /root/terraf-install/credentials.txt <<CREDS
Terraf / OXP final clean deployment

Frontend:
${FRONTEND_URL}

Frontend API:
${FRONTEND_URL}/api

Frontend storage:
${FRONTEND_URL}/storage

Backend/admin:
${BACKEND_URL}/admin

Backend direct API:
${BACKEND_URL}/api

Backend health:
${BACKEND_URL}/up

Database:
DB_DATABASE=${DB_NAME}
DB_USERNAME=${DB_USER}
DB_PASSWORD=${DB_PASS}

Nginx:
front:   /etc/nginx/sites-available/front
laravel: /etc/nginx/sites-available/laravel

Backend env:
${BACKEND_DIR}/.env

Frontend env:
${FRONTEND_DIR}/.env.local

Important frontend env:
NEXT_PUBLIC_API_BASE_URL=/api
NEXT_SERVER_API_BASE_URL=${BACKEND_LOCAL_URL}/api
NEXT_PUBLIC_MEDIA_BASE_URL=
NEXT_PUBLIC_SITE_URL=${FRONTEND_URL}

Recommended server commands:
cd ${BACKEND_DIR}
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan migrate --force

Storage symlink repair:
cd ${BACKEND_DIR}
sudo install -d -m 2775 -o www-data -g www-data storage/app/public public
sudo rm -f public/storage
sudo ln -s ../storage/app/public public/storage
sudo chown -h www-data:www-data public/storage

Logs:
Laravel:
tail -f ${BACKEND_DIR}/storage/logs/laravel.log

Frontend:
journalctl -u terraf-frontend -f

Queue:
tail -f ${BACKEND_DIR}/storage/logs/queue-worker.log
CREDS

chmod 600 /root/terraf-install/credentials.txt

echo ""
echo "============================================"
echo "Deployment completed."
echo "============================================"
echo "Frontend:        ${FRONTEND_URL}"
echo "Frontend API:    ${FRONTEND_URL}/api"
echo "Storage proxy:   ${FRONTEND_URL}/storage"
echo "Admin:           ${BACKEND_URL}/admin"
echo "Backend health:  ${BACKEND_URL}/up"
echo ""
echo "Credentials saved to:"
echo "  /root/terraf-install/credentials.txt"
echo ""
echo "Important checks:"
echo "  curl -I ${FRONTEND_URL}/api/cart"
echo "  curl -I ${BACKEND_URL}/admin"
echo "  curl -s ${FRONTEND_URL}/en | grep -o 'src=\"[^\"]*hero-material.jpg\"' | head"
echo ""
echo "Expected image path:"
echo "  src=\"/images/hero-material.jpg\""
echo "============================================"
echo ""
echo "Browser note:"
echo "  Clear old cookies/site data for ${FRONTEND_URL} before testing cart."
echo ""
