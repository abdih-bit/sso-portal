#!/bin/bash
# =======================================================
# DEPLOYMENT SCRIPT - SSO Portal HQ Medan
# Jalankan di VPS: bash deploy.sh
# =======================================================

set -e

echo "╔════════════════════════════════════════╗"
echo "║   Deploy SSO Portal - portal.hqmedan.com  ║"
echo "╚════════════════════════════════════════╝"

APP_DIR="/var/www/sso-portal"
NODE_VERSION="20"

echo ""
echo "▶ [1/8] Update system packages..."
sudo apt update -y && sudo apt upgrade -y

echo ""
echo "▶ [2/8] Install Node.js $NODE_VERSION..."
curl -fsSL https://deb.nodesource.com/setup_${NODE_VERSION}.x | sudo -E bash -
sudo apt install -y nodejs

echo ""
echo "▶ [3/8] Install PM2..."
sudo npm install -g pm2

echo ""
echo "▶ [4/8] Install Nginx..."
sudo apt install -y nginx

echo ""
echo "▶ [5/8] Install PostgreSQL..."
sudo apt install -y postgresql postgresql-contrib

echo ""
echo "▶ [6/8] Setup database..."
sudo -u postgres psql <<EOF
CREATE USER sso_user WITH PASSWORD 'GANTI_PASSWORD_AMAN';
CREATE DATABASE sso_portal OWNER sso_user;
GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;
EOF

echo ""
echo "▶ [7/8] Setup aplikasi..."
mkdir -p $APP_DIR
mkdir -p $APP_DIR/logs

# Clone atau copy files ke $APP_DIR
# git clone https://github.com/yourrepo/sso-portal.git $APP_DIR
# Atau upload manual via SFTP/SCP

cd $APP_DIR

# Install dependencies
npm install --production

# Setup environment
if [ ! -f .env ]; then
  cp .env.example .env
  echo "⚠️  PENTING: Edit file .env sesuai konfigurasi Anda!"
  echo "   nano $APP_DIR/.env"
fi

# Generate Prisma client & migrate database
npx prisma generate
npx prisma migrate deploy

# Seed data awal
node src/database/seed.js

echo ""
echo "▶ [8/8] Setup Nginx & SSL..."

# Copy nginx config
sudo cp deployment/nginx/portal.hqmedan.com.conf /etc/nginx/sites-available/portal.hqmedan.com
sudo ln -sf /etc/nginx/sites-available/portal.hqmedan.com /etc/nginx/sites-enabled/

# Test & reload nginx
sudo nginx -t && sudo systemctl reload nginx

# Install Certbot untuk SSL
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d portal.hqmedan.com --non-interactive --agree-tos --email admin@hqmedan.com

# Start aplikasi dengan PM2
pm2 start ecosystem.config.js --env production
pm2 save
sudo pm2 startup systemd -u $USER --hp $HOME

echo ""
echo "╔════════════════════════════════════════════╗"
echo "║   ✅ Deploy Berhasil!                      ║"
echo "║   🌐 https://portal.hqmedan.com            ║"
echo "╚════════════════════════════════════════════╝"
echo ""
echo "📋 Default Login:"
echo "   Email    : admin@hqmedan.com"
echo "   Password : Admin@HQ2025!"
echo ""
echo "⚠️  Segera ganti password setelah login!"
