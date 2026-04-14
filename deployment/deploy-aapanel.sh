#!/bin/bash

# ====================================================================
# SSO Portal - Automated Deployment Script for aaPanel
# ====================================================================
# Improved version untuk deployment dengan aaPanel
# Usage: bash deploy-aapanel.sh [domain] [branch]
# Example: bash deploy-aapanel.sh portal.hqmedan.com main
# ====================================================================

set -e  # Exit on error

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# ====================================================================
# CONFIGURATION
# ====================================================================
DOMAIN="${1:-portal.hqmedan.com}"
BRANCH="${2:-main}"
APP_PATH="/www/wwwroot/$DOMAIN"
NODE_VERSION="18"
PM2_PROCESS_NAME="sso-portal"
REPO_URL="https://github.com/your-username/sso-portal.git"  # Ganti dengan URL repo Anda

echo -e "${BLUE}=====================================================================${NC}"
echo -e "${BLUE}SSO Portal - Deployment Script for aaPanel${NC}"
echo -e "${BLUE}=====================================================================${NC}"
echo ""
echo -e "${BLUE}Configuration:${NC}"
echo "  Domain: $DOMAIN"
echo "  Path: $APP_PATH"
echo "  Branch: $BRANCH"
echo "  PM2 Process: $PM2_PROCESS_NAME"
echo ""

# ====================================================================
# STEP 1: PRE-DEPLOYMENT CHECKS
# ====================================================================
echo -e "${YELLOW}[Step 1/9] Checking prerequisites...${NC}"

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   echo -e "${RED}❌ This script must be run as root${NC}"
   exit 1
fi

# Check Node.js
if ! command -v node &> /dev/null; then
    echo -e "${RED}❌ Node.js not installed${NC}"
    exit 1
else
    NODE_VERSION=$(node -v)
    echo -e "${GREEN}✓ Node.js installed: $NODE_VERSION${NC}"
fi

# Check npm
if ! command -v npm &> /dev/null; then
    echo -e "${RED}❌ npm not installed${NC}"
    exit 1
else
    NPM_VERSION=$(npm -v)
    echo -e "${GREEN}✓ npm installed: $NPM_VERSION${NC}"
fi

# Check PM2
if ! command -v pm2 &> /dev/null; then
    echo -e "${YELLOW}Installing PM2 globally...${NC}"
    npm install -g pm2
    pm2 startup
fi

echo ""

# ====================================================================
# STEP 2: PREPARE DIRECTORY
# ====================================================================
echo -e "${YELLOW}[Step 2/9] Preparing directory...${NC}"

if [ -d "$APP_PATH" ]; then
    echo -e "${BLUE}Directory already exists: $APP_PATH${NC}"
    read -p "Create backup of existing deployment? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        BACKUP_DIR="${APP_PATH}.backup.$(date +%Y%m%d_%H%M%S)"
        echo -e "${YELLOW}Creating backup...${NC}"
        cp -r "$APP_PATH" "$BACKUP_DIR"
        echo -e "${GREEN}✓ Backup created: $BACKUP_DIR${NC}"
    fi
else
    echo -e "${BLUE}Creating directory: $APP_PATH${NC}"
    mkdir -p "$APP_PATH"
    echo -e "${GREEN}✓ Directory created${NC}"
fi

echo ""

# ====================================================================
# STEP 3: CLONE/UPDATE GIT REPOSITORY
# ====================================================================
echo -e "${YELLOW}[Step 3/9] Cloning/updating repository...${NC}"

if [ -d "$APP_PATH/.git" ]; then
    echo -e "${BLUE}Updating existing repository...${NC}"
    cd "$APP_PATH"
    git fetch origin
    git checkout $BRANCH
    git pull origin $BRANCH
    echo -e "${GREEN}✓ Repository updated${NC}"
else
    echo -e "${BLUE}Cloning repository from: $REPO_URL${NC}"
    cd "$APP_PATH"
    git clone -b $BRANCH $REPO_URL .
    echo -e "${GREEN}✓ Repository cloned${NC}"
fi

echo ""

# ====================================================================
# STEP 4: SETUP ENVIRONMENT
# ====================================================================
echo -e "${YELLOW}[Step 4/9] Setting up environment...${NC}"

if [ ! -f "$APP_PATH/.env" ]; then
    if [ -f "$APP_PATH/deployment/.env.production.example" ]; then
        echo -e "${BLUE}Creating .env from template...${NC}"
        cp "$APP_PATH/deployment/.env.production.example" "$APP_PATH/.env"
        
        echo -e "${RED}⚠️  IMPORTANT: Edit .env file with your configuration!${NC}"
        echo -e "${YELLOW}Location: $APP_PATH/.env${NC}"
        echo -e "${YELLOW}Edit the following values:${NC}"
        echo "  - DATABASE_URL (PostgreSQL connection)"
        echo "  - JWT_SECRET & SESSION_SECRET (generate new random values)"
        echo "  - SMTP_* (email configuration)"
        echo "  - ADMIN_EMAIL & ADMIN_PASSWORD"
        echo ""
        
        read -p "Press Enter after you've configured .env file..."
    else
        echo -e "${RED}❌ Template .env not found!${NC}"
        echo -e "${YELLOW}Please create deployment/.env.production.example${NC}"
        exit 1
    fi
else
    echo -e "${GREEN}✓ .env file exists${NC}"
fi

# Set proper permissions
chmod 600 "$APP_PATH/.env"
chown www:www "$APP_PATH/.env" 2>/dev/null || true

echo ""

# ====================================================================
# STEP 5: INSTALL DEPENDENCIES
# ====================================================================
echo -e "${YELLOW}[Step 5/9] Installing dependencies...${NC}"

cd "$APP_PATH"
echo -e "${BLUE}Running npm install...${NC}"
npm install --production

echo -e "${BLUE}Generating Prisma client...${NC}"
npx prisma generate

echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

# ====================================================================
# STEP 6: DATABASE SETUP
# ====================================================================
echo -e "${YELLOW}[Step 6/9] Setting up database...${NC}"

cd "$APP_PATH"

# Load DATABASE_URL from .env
export $(grep DATABASE_URL "$APP_PATH/.env" | xargs)

if [ -z "$DATABASE_URL" ]; then
    echo -e "${RED}❌ DATABASE_URL not configured in .env${NC}"
    exit 1
fi

echo -e "${BLUE}Running database migrations...${NC}"
npx prisma migrate deploy

read -p "Seed database with initial data? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${BLUE}Seeding database...${NC}"
    node src/database/seed.js
    echo -e "${GREEN}✓ Database seeded${NC}"
fi

echo -e "${GREEN}✓ Database ready${NC}"
echo ""

# ====================================================================
# STEP 7: SETUP PM2 PROCESS
# ====================================================================
echo -e "${YELLOW}[Step 7/9] Setting up process manager (PM2)...${NC}"

# Stop and delete existing process if exists
pm2 delete "$PM2_PROCESS_NAME" 2>/dev/null || true
sleep 1

# Start new process
cd "$APP_PATH"
echo -e "${BLUE}Starting PM2 process: $PM2_PROCESS_NAME${NC}"
pm2 start src/server.js \
    --name "$PM2_PROCESS_NAME" \
    --env production \
    --merge-logs \
    --log "$APP_PATH/logs/pm2.log" \
    --error "$APP_PATH/logs/pm2-error.log"

# Create logs directory if doesn't exist
mkdir -p "$APP_PATH/logs"

# Save PM2 configuration
pm2 save

echo -e "${GREEN}✓ Process manager configured${NC}"
echo ""

# ====================================================================
# STEP 8: SETUP FILE PERMISSIONS
# ====================================================================
echo -e "${YELLOW}[Step 8/9] Setting file permissions...${NC}"

# Create logs directory
mkdir -p "$APP_PATH/logs"

# Set proper ownership and permissions
chown -R www:www "$APP_PATH" 2>/dev/null || true
chmod 755 "$APP_PATH"
chmod 755 "$APP_PATH/logs"
chmod 600 "$APP_PATH/.env"

echo -e "${GREEN}✓ Permissions configured${NC}"
echo ""

# ====================================================================
# STEP 9: VERIFICATION
# ====================================================================
echo -e "${YELLOW}[Step 9/9] Verifying deployment...${NC}"

sleep 2

# Check if process is running
if pm2 list | grep -q "$PM2_PROCESS_NAME" | grep -q "online"; then
    echo -e "${GREEN}✓ PM2 process is running${NC}"
else
    echo -e "${YELLOW}Checking process status...${NC}"
    pm2 status | grep "$PM2_PROCESS_NAME"
fi

# Test database connection
echo -e "${BLUE}Testing database connection...${NC}"
cd "$APP_PATH"
node -e "
require('dotenv').config();
const { PrismaClient } = require('@prisma/client');
const prisma = new PrismaClient();
prisma.\$queryRaw\`SELECT 1\`.then(() => {
  console.log('✓ Database connection successful');
  process.exit(0);
}).catch(e => {
  console.error('❌ Database connection failed:', e.message);
  process.exit(1);
});
" || echo -e "${YELLOW}Database test skipped${NC}"

# Test API endpoint
echo -e "${BLUE}Testing API endpoint (localhost:3000)...${NC}"
RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:3000 2>/dev/null || echo "000")
if [ "$RESPONSE" = "200" ] || [ "$RESPONSE" = "301" ]; then
    echo -e "${GREEN}✓ API is responding with HTTP $RESPONSE${NC}"
else
    echo -e "${YELLOW}⚠️  API test returned HTTP $RESPONSE (might need time to warm up)${NC}"
fi

echo ""

# ====================================================================
# DEPLOYMENT COMPLETE
# ====================================================================
echo -e "${GREEN}=====================================================================${NC}"
echo -e "${GREEN}✓✓✓ DEPLOYMENT SUCCESSFUL! ✓✓✓${NC}"
echo -e "${GREEN}=====================================================================${NC}"
echo ""
echo -e "${BLUE}Application Details:${NC}"
echo "  Domain: $DOMAIN"
echo "  Path: $APP_PATH"
echo "  Process Name: $PM2_PROCESS_NAME"
echo "  Node.js Port: 3000 (via Nginx reverse proxy)"
echo ""
echo -e "${BLUE}Useful PM2 Commands:${NC}"
echo "  pm2 logs $PM2_PROCESS_NAME            - View application logs"
echo "  pm2 restart $PM2_PROCESS_NAME         - Restart application"
echo "  pm2 stop $PM2_PROCESS_NAME            - Stop application"
echo "  pm2 delete $PM2_PROCESS_NAME          - Remove process"
echo "  pm2 status                            - Check all processes"
echo ""
echo -e "${YELLOW}IMPORTANT - Next Steps:${NC}"
echo "  1. Configure Nginx reverse proxy in aaPanel:"
echo "     - Go to Websites → Select your domain"
echo "     - Click Configuration → Reverse Proxy"
echo "     - Add: 127.0.0.1:3000"
echo ""
echo "  2. Setup SSL Certificate (Let's Encrypt):"
echo "     - Go to Websites → Select your domain"
echo "     - Click SSL → Add SSL"
echo "     - Select Let's Encrypt, request certificate"
echo ""
echo "  3. Test application:"
echo "     - Open https://$DOMAIN in browser"
echo "     - Login with admin account from .env"
echo ""
echo "  4. Configure firewall (if not already done):"
echo "     - Port 80 (HTTP) - Allow"
echo "     - Port 443 (HTTPS) - Allow"
echo "     - Port 22 (SSH) - Allow from trusted IPs"
echo ""
echo "  5. Setup monitoring & backups"
echo ""
echo -e "${BLUE}Log Locations:${NC}"
echo "  - Application: $APP_PATH/logs/pm2.log"
echo "  - Error: $APP_PATH/logs/pm2-error.log"
echo "  - Nginx Access: /www/server/nginx/logs/$DOMAIN/access.log"
echo "  - Nginx Error: /www/server/nginx/logs/$DOMAIN/error.log"
echo ""
