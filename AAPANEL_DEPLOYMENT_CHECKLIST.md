# 📋 DEPLOYMENT CHECKLIST: portal.hqmedan.com via aaPanel
## Print & Reference Guide

---

## ✅ PRE-DEPLOYMENT CHECKLIST

### Domain & Network
```
□ Domain portal.hqmedan.com already registered
□ Domain nameservers updated to point to VPS
□ DNS A record created: portal.hqmedan.com → YOUR_VPS_IP
□ Test DNS: nslookup portal.hqmedan.com (returns IP)
□ VPS firewall allows: Port 22 (SSH), 80 (HTTP), 443 (HTTPS)
□ Domain propagation confirmed (might take 24-48 hours)
```

### VPS & aaPanel
```
□ VPS with Linux OS installed (Ubuntu 20.04+ recommended)
□ aaPanel installation complete
□ aaPanel accessible at http://YOUR_VPS_IP:8888
□ Node.js v18+ installed via aaPanel
□ PostgreSQL installed and running
```

### Local Machine
```
□ Project ready in d:\SSO Portal
□ All source files included (src/, prisma/, public/, etc)
□ .env.example configured with required variables
□ package.json exists with dependencies
□ .gitignore properly configured (excludes .env, node_modules)
□ Project pushed to GitHub/GitLab
```

---

## 🚀 PHASE 1: DOMAIN & DNS VERIFICATION (5 min)

```
TASK 1.1: Verify Domain Pointing
□ Command: nslookup portal.hqmedan.com
□ Expected: Shows YOUR_VPS_IP address
□ Status: ✅ / ❌

TASK 1.2: Domain Registrar Check
□ Login to domain registrar
□ Check A record points to: YOUR_VPS_IP
□ Check nameservers are correct
□ Status: ✅ / ❌

TASK 1.3: Wait for Propagation (if new)
□ DNS changes take up to 48 hours
□ Can test with: http://YOUR_VPS_IP (while waiting)
□ Status: ✅ / ❌
```

---

## 🌐 PHASE 2: AAPANEL SETUP (10 min)

```
TASK 2.1: Access aaPanel
□ Open browser: http://YOUR_VPS_IP:8888
□ Login with credentials
□ Verify dashboard loads
□ Status: ✅ / ❌

TASK 2.2: Verify Node.js
□ App Store → Search "Node.js"
□ Status should show: "Installed"
□ If not installed: Click Install (v18+ recommended)
□ Status: ✅ / ❌

TASK 2.3: Create Website
□ Go to: Websites → Add Site
□ Domain: portal.hqmedan.com
□ Path: /www/wwwroot/portal.hqmedan.com
□ PHP: ❌ (unchecked - Node.js app)
□ Click Submit
□ Website created successfully
□ Status: ✅ / ❌

TASK 2.4: Create Database
□ Go to: Database → Add Database
□ Type: PostgreSQL
□ Name: sso_portal
□ User: sso_user
□ Password: [GENERATE & SAVE]
□ Click Submit
□ Database created
□ Status: ✅ / ❌
```

---

## 📤 PHASE 3: PROJECT UPLOAD (10 min)

```
TASK 3.1: Upload via Git (Recommended)
□ SSH to VPS: ssh root@YOUR_VPS_IP
□ Go to folder: cd /www/wwwroot/portal.hqmedan.com
□ Clone repository: git clone https://your-repo.git .
□ Verify files: ls -la
□ Check package.json exists
□ Status: ✅ / ❌

TASK 3.2: Alternative - Upload via SFTP
□ Use WinSCP or FileZilla
□ Connect to YOUR_VPS_IP via SSH (port 22)
□ Navigate to: /www/wwwroot/portal.hqmedan.com
□ Upload all project files
□ Verify all files uploaded
□ Status: ✅ / ❌

TASK 3.3: Verify Project Structure
□ Command: ls -la /www/wwwroot/portal.hqmedan.com
□ Required files present:
  ✓ package.json
  ✓ src/
  ✓ prisma/
  ✓ public/
  ✓ .env.example
□ Status: ✅ / ❌
```

---

## ⚙️ PHASE 4: CONFIGURATION (10 min)

```
TASK 4.1: Create & Edit .env File
□ SSH: ssh root@YOUR_VPS_IP
□ Command: cd /www/wwwroot/portal.hqmedan.com
□ Copy: cp .env.example .env
□ Edit: nano .env
□ File created and readable
□ Status: ✅ / ❌

TASK 4.2: Configure Environment Variables
□ NODE_ENV=production
□ PORT=3000
□ APP_URL=https://portal.hqmedan.com
□ DATABASE_URL=postgresql://sso_user:PASSWORD@localhost:5432/sso_portal
□ JWT_SECRET=[32-char random string]
□ SESSION_SECRET=[random string]
□ SMTP configuration (optional for email)
□ All variables set correctly
□ Status: ✅ / ❌

TASK 4.3: Save .env and Set Permissions
□ Save file: Ctrl+X → Y → Enter
□ Command: chmod 600 .env
□ Command: chown www:www .env
□ File secured
□ Status: ✅ / ❌
```

---

## 📦 PHASE 5: NPM & DATABASE SETUP (15 min)

```
TASK 5.1: Install NPM Packages
□ Command: npm install --omit=dev
□ Wait for completion (5-10 minutes)
□ No error messages
□ node_modules created
□ Status: ✅ / ❌

TASK 5.2: Generate Prisma Client
□ Command: npx prisma generate
□ Output shows: "✅ Generated Prisma Client"
□ Prisma client ready
□ Status: ✅ / ❌

TASK 5.3: Test Database Connection
□ Command: psql -U sso_user -d sso_portal -c "SELECT 1;"
□ Expected output: 1
□ Connection successful
□ Status: ✅ / ❌

TASK 5.4: Run Database Migrations
□ Command: npx prisma migrate deploy
□ Output shows migration status
□ Tables created in database
□ Status: ✅ / ❌

TASK 5.5: Seed Database (Optional)
□ Command: node src/database/seed.js
□ Initial data loaded
□ Admin user created
□ Status: ✅ / ❌
```

---

## ▶️ PHASE 6: PROCESS MANAGEMENT (5 min)

```
TASK 6.1: Create PM2 Process via aaPanel
□ Go to: Supervisor (or Process Manager)
□ Click: Add Process
□ Process Name: sso-portal
□ Run User: www
□ Working Directory: /www/wwwroot/portal.hqmedan.com
□ Command: npm start
□ Autostart: ✓ Checked
□ Autorestart: ✓ Checked
□ Click Submit
□ Status: ✅ / ❌

TASK 6.2: Verify Process Running
□ Command: pm2 status
□ Process 'sso-portal' should show: online ✓
□ PID should be visible
□ Memory/CPU showing usage
□ Status: ✅ / ❌

TASK 6.3: Check Process Logs
□ Command: pm2 logs sso-portal
□ Look for: "✅ Database connected successfully"
□ Look for: "Server running on port 3000"
□ No ERROR messages
□ Status: ✅ / ❌
```

---

## 🌐 PHASE 7: NGINX REVERSE PROXY (5 min)

```
TASK 7.1: Configure Reverse Proxy via aaPanel
□ Go to: Websites → portal.hqmedan.com
□ Click: Configuration (⚙️ icon)
□ Tab: Reverse Proxy
□ Click: Add Reverse Proxy
□ Proxy Name: node
□ Proxy IP: 127.0.0.1:3000
□ Click Submit
□ Status: ✅ / ❌

TASK 7.2: Verify Reverse Proxy
□ Command: cat /www/server/nginx/conf/vhost/portal.hqmedan.com.conf | grep proxy_pass
□ Output should show: proxy_pass http://127.0.0.1:3000;
□ Configuration correct
□ Status: ✅ / ❌

TASK 7.3: Reload Nginx
□ Command: systemctl reload nginx
□ No errors displayed
□ Nginx reloaded
□ Status: ✅ / ❌
```

---

## 🔒 PHASE 8: SSL CERTIFICATE (5 min)

```
TASK 8.1: Request SSL Certificate
□ Go to: Websites → portal.hqmedan.com
□ Tab: SSL
□ Click: Add SSL
□ Provider: Let's Encrypt
□ Domain: portal.hqmedan.com (auto-detected)
□ Click: Request Certificate
□ Wait 1-2 minutes
□ Status: ✅ / ❌

TASK 8.2: Verify SSL Certificate
□ Browser: https://portal.hqmedan.com
□ Check: Green padlock 🔒 visible
□ Certificate valid
□ Status: ✅ / ❌

TASK 8.3: Test HTTPS
□ Command: curl -I https://portal.hqmedan.com
□ Expected: HTTP/2 200 or HTTP/1.1 200
□ Not 502 or 404
□ Status: ✅ / ❌
```

---

## ✅ PHASE 9: FINAL VERIFICATION (5 min)

```
TASK 9.1: Access Application
□ Browser: https://portal.hqmedan.com
□ Expected: Login page loads
□ HTTPS connection (green padlock)
□ Page fully loaded
□ Status: ✅ / ❌

TASK 9.2: Test Database Connection
□ Command: cd /www/wwwroot/portal.hqmedan.com
□ Command: npm test (if test available)
□ Or: node -e "require('dotenv').config(); const { PrismaClient } = require('@prisma/client'); const p = new PrismaClient(); p.\$queryRaw\`SELECT 1\`.then(() => { console.log('✅ Database connected!'); process.exit(0); });"
□ Output: "✅ Database connected!"
□ Status: ✅ / ❌

TASK 9.3: Test Login Functionality
□ Open: https://portal.hqmedan.com
□ Default email: admin@hqmedan.com (if seeded)
□ Enter credentials
□ Click Login
□ Should redirect to dashboard
□ Status: ✅ / ❌

TASK 9.4: Check Application Logs
□ Command: pm2 logs sso-portal --lines 50
□ Look for errors: ❌ (should be none)
□ Look for warnings: ⚠️ (acceptable if minor)
□ Application healthy
□ Status: ✅ / ❌

TASK 9.5: System Resources
□ Command: pm2 monit
□ CPU usage: < 50% (normal)
□ Memory usage: < 500 MB (normal)
□ System healthy
□ Status: ✅ / ❌
```

---

## 📊 FINAL STATUS SUMMARY

```
TOTAL DEPLOYMENT CHECKLIST:
Total Items: 50+
Completed:   ___ / 50+
Percentage:  ___ %

CRITICAL ITEMS STATUS:
✓ Domain accessible: YES / NO
✓ HTTPS working: YES / NO
✓ Database connected: YES / NO
✓ Application running: YES / NO
✓ Login functional: YES / NO

READY FOR PRODUCTION?
✓ YES - All checks passed
❌ NO - Review failed items above
```

---

## 🛟 TROUBLESHOOTING QUICK REFERENCE

| Issue | Quick Check |
|-------|-------------|
| Cannot access domain | `nslookup portal.hqmedan.com` |
| 502 Bad Gateway | `pm2 status` & `pm2 logs sso-portal` |
| Database error | `psql -U sso_user -d sso_portal -c "SELECT 1;"` |
| SSL not working | Check Let's Encrypt cert in aaPanel |
| High memory | `pm2 monit` |

---

## 📞 SUPPORT CONTACTS

| Issue | Reference |
|-------|-----------|
| aaPanel help | See AAPANEL_TROUBLESHOOTING.md |
| Database issues | See AAPANEL_TROUBLESHOOTING.md |
| Process problems | See AAPANEL_TROUBLESHOOTING.md |
| Detailed guide | See AAPANEL_SETUP_GUIDE.md |

---

## 🎉 DEPLOYMENT COMPLETE!

Once all checkboxes are marked ✅:

```
The SSO Portal is now:
✅ Running on https://portal.hqmedan.com
✅ Secured with HTTPS/SSL
✅ Connected to PostgreSQL database
✅ Managed by PM2 process manager
✅ Ready for production use!

Next Steps:
1. Create user accounts
2. Test SSO integration
3. Setup monitoring
4. Configure backups
5. Document custom configs
```

---

**Date Started:** ___/___/_____  
**Date Completed:** ___/___/_____  
**Deployed By:** _________________  
**VPS IP:** _____________________  
**Domain:** portal.hqmedan.com  
**Database:** sso_portal  

---

**Status:** ✅ READY FOR PRODUCTION

*Keep this checklist safe for future reference*
