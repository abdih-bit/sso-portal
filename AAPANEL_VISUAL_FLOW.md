# 🎯 Visual Deployment Flow untuk portal.hqmedan.com via aaPanel

## 📊 Arsitektur Sistem

```
┌─────────────────────────────────────────────────────────────────┐
│                         INTERNET                                │
│                    portal.hqmedan.com                           │
└────────────────────────┬────────────────────────────────────────┘
                         │
                    (HTTPS/SSL)
                         │
         ┌───────────────┼───────────────┐
         │               │               │
    Port 443         Port 80            Port 8888
         │               │               │
    (HTTPS)          (Redirect)      (aaPanel)
         │               │               │
    ┌────▼───────────────▼────────┐ ┌──▼──────────────┐
    │     NGINX Web Server        │ │  aaPanel Panel  │
    │   (Reverse Proxy)           │ │  (Management)   │
    │  127.0.0.1:80 → :3000      │ │  Port 8888      │
    └────┬─────────────────────────┘ └─────────────────┘
         │
         │ (Proxy Pass)
         │ 127.0.0.1:3000
         │
    ┌────▼──────────────────────────┐
    │  Node.js Application (PM2)    │
    │  Port 3000                    │
    │  - Express.js Server          │
    │  - SSO Portal Logic           │
    │  - API Routes                 │
    └────┬─────────────────────────┬┘
         │                         │
    ┌────▼──────────────┐  ┌──────▼─────────────┐
    │  PostgreSQL DB    │  │  Session Storage   │
    │  sso_portal       │  │  (In Memory/DB)    │
    │  Port 5432        │  │                    │
    └───────────────────┘  └────────────────────┘
```

---

## 🚀 Deployment Timeline

```
START
  │
  ├─ 5 min  → [PHASE 1] Persiapan Domain & aaPanel
  │          ├─ Verifikasi DNS pointing
  │          ├─ Login aaPanel
  │          └─ Pastikan Node.js installed
  │
  ├─ 10 min → [PHASE 2] Setup Infrastruktur
  │          ├─ Buat website di aaPanel
  │          ├─ Setup PostgreSQL database
  │          └─ Upload project via Git/SFTP
  │
  ├─ 10 min → [PHASE 3] Konfigurasi
  │          ├─ Setup .env file
  │          ├─ NPM install dependencies
  │          └─ Prisma setup & migrations
  │
  ├─ 5 min  → [PHASE 4] Launching
  │          ├─ Setup PM2 process di aaPanel
  │          ├─ Configure Nginx reverse proxy
  │          └─ Request SSL certificate
  │
  ├─ 5 min  → [PHASE 5] Testing & Verification
  │          ├─ Access https://portal.hqmedan.com
  │          ├─ Test database connection
  │          └─ Verify login functionality
  │
  └─ END → ✅ LIVE!
     (Total: ~35 minutes)
```

---

## 📋 Checklist dengan Status Tracker

### PHASE 1: PERSIAPAN (5 menit)
```
[ ] Step 1.1: Verifikasi domain pointing
    Command: nslookup portal.hqmedan.com
    Expected: Returns YOUR_VPS_IP
    
[ ] Step 1.2: Login aaPanel
    URL: http://YOUR_VPS_IP:8888
    
[ ] Step 1.3: Cek Node.js terinstall
    Via: App Store → Node.js → Status: Installed
```

### PHASE 2: INFRASTRUKTUR (10 menit)
```
[ ] Step 2.1: Buat Website
    Websites → Add Site
    - Domain: portal.hqmedan.com
    - Path: /www/wwwroot/portal.hqmedan.com
    Status: ✅ Website Created
    
[ ] Step 2.2: Setup Database
    Database → Add Database
    - Name: sso_portal
    - User: sso_user
    - Password: [SAVED]
    Status: ✅ Database Ready
    
[ ] Step 2.3: Upload Project
    Option A: git clone https://your-repo.git .
    Option B: Upload via SFTP
    
[ ] Step 2.4: Verifikasi Files
    Command: ls -la /www/wwwroot/portal.hqmedan.com
    Expected: package.json, src/, prisma/, public/ visible
```

### PHASE 3: KONFIGURASI (10 menit)
```
[ ] Step 3.1: Setup .env
    Command: nano /www/wwwroot/portal.hqmedan.com/.env
    - DATABASE_URL: postgresql://sso_user:PASSWORD@localhost:5432/sso_portal
    - JWT_SECRET: [32 char random]
    - NODE_ENV: production
    
[ ] Step 3.2: Install NPM
    Command: npm install --omit=dev
    Status: ✅ All modules installed
    
[ ] Step 3.3: Setup Prisma
    Command: npx prisma generate
    Status: ✅ Prisma client generated
    
[ ] Step 3.4: Database Migration
    Command: npx prisma migrate deploy
    Status: ✅ X migrations applied
```

### PHASE 4: LAUNCHING (5 menit)
```
[ ] Step 4.1: Setup PM2 Process
    Via aaPanel: Supervisor → Add Process
    - Name: sso-portal
    - Command: npm start
    - Autostart: ✅
    Status: ✅ Process created & running
    
[ ] Step 4.2: Configure Reverse Proxy
    Via aaPanel: Websites → portal.hqmedan.com → Reverse Proxy
    - Name: node
    - IP: 127.0.0.1:3000
    Status: ✅ Proxy configured
    
[ ] Step 4.3: Request SSL Certificate
    Via aaPanel: Websites → portal.hqmedan.com → SSL
    Provider: Let's Encrypt
    Status: ✅ Certificate installed
```

### PHASE 5: VERIFICATION (5 menit)
```
[ ] Step 5.1: Test HTTPS Access
    Browser: https://portal.hqmedan.com
    Expected: Login page visible, SSL padlock green
    
[ ] Step 5.2: Test Database
    Command: psql -U sso_user -d sso_portal -c "SELECT 1;"
    Expected: 1
    
[ ] Step 5.3: Test API
    Command: curl -I https://portal.hqmedan.com/api/auth/login
    Expected: HTTP/2 200 or 404 (not 502)
    
[ ] Step 5.4: Check Logs
    Command: pm2 logs sso-portal
    Expected: No ERROR lines, see "Server running on port 3000"
```

---

## 🔄 Workflow Diagram

### Local Machine → GitHub
```
d:\SSO Portal/
    ├─ git add .
    ├─ git commit -m "Deploy ready"
    └─ git push origin main
          │
          └─→ GitHub Repository
```

### GitHub → VPS
```
VPS /www/wwwroot/portal.hqmedan.com/
    ├─ git clone https://github.com/username/sso-portal.git .
    ├─ cp .env.example .env
    ├─ nano .env (edit dengan DB credentials)
    ├─ npm install --omit=dev
    ├─ npx prisma generate
    ├─ npx prisma migrate deploy
    └─ (PM2 starts automatically via aaPanel)
```

### Request Flow
```
Browser Request
    │
    ↓
Internet (HTTPS)
    │
    ↓
Nginx (127.0.0.1:80/443)
    │ Reverse Proxy
    ↓
Node.js (127.0.0.1:3000)
    │
    ├─→ Route Handler
    │    │
    │    ├─→ Check Authentication
    │    │
    │    ├─→ Query Database
    │    │
    │    └─→ Return Response
    │
    ↓
Browser (HTML/JSON)
```

---

## 🎯 Success Indicators

✅ **Deployment adalah SUKSES jika:**

1. **Domain Access**
   ```bash
   curl -I https://portal.hqmedan.com
   # Output: HTTP/2 200 atau HTTP/1.1 200
   ```

2. **SSL Certificate**
   ```
   Browser: https://portal.hqmedan.com
   Visual: Green padlock 🔒
   ```

3. **Database Connection**
   ```bash
   pm2 logs sso-portal
   # Output: "✅ Database connected successfully"
   ```

4. **Process Running**
   ```bash
   pm2 status
   # sso-portal: online ✓
   ```

5. **Login Page Displays**
   ```
   https://portal.hqmedan.com
   Shows: Login form with email/password fields
   ```

6. **No Error Messages**
   ```bash
   pm2 logs sso-portal
   No ERROR, ECONNREFUSED, or 502 messages
   ```

---

## 🚨 If Something Goes Wrong

### Quick Diagnosis Script:

```bash
#!/bin/bash
echo "🔍 DIAGNOSA DEPLOYMENT..."

echo ""
echo "1️⃣ DNS Check:"
nslookup portal.hqmedan.com | grep -i "address"

echo ""
echo "2️⃣ Process Status:"
pm2 status

echo ""
echo "3️⃣ Port 3000 (Node.js):"
netstat -tulnp | grep 3000

echo ""
echo "4️⃣ Port 80/443 (Nginx):"
systemctl status nginx

echo ""
echo "5️⃣ Database Connection:"
psql -U sso_user -d sso_portal -c "SELECT 1;" 2>&1

echo ""
echo "6️⃣ Recent Logs:"
pm2 logs sso-portal --lines 10

echo ""
echo "✅ Diagnosa selesai!"
```

---

## 📊 Monitoring Dashboard

### Metrics to Track:

```
CPU Usage:        [████░░░░░░] 40%
Memory Usage:     [███░░░░░░░] 30%
Disk Usage:       [██░░░░░░░░] 20%
Uptime:           45 days 12 hours
Request/sec:      127
Error Rate:       0.01%
Database Ping:    2ms
```

---

## 🎓 Learning Path

Jika ingin paham lebih dalam:

```
1. Understand Linux basics
   ↓
2. Learn SSH & terminal commands
   ↓
3. Understand Node.js & npm
   ↓
4. Learn Git workflow
   ↓
5. Understand aaPanel GUI
   ↓
6. Learn Nginx reverse proxy
   ↓
7. Learn PostgreSQL basics
   ↓
8. Master PM2 process management
   ↓
9. Understand SSL/HTTPS
   ↓
10. Full deployment mastery! ✅
```

---

## 💡 Pro Tips

1. **Always backup before changes:**
   ```bash
   pg_dump -U sso_user sso_portal > backup_$(date +%Y%m%d).sql
   ```

2. **Monitor logs in real-time:**
   ```bash
   pm2 logs sso-portal --follow
   ```

3. **Keep secrets secure:**
   - Never commit `.env` to Git
   - Use `.gitignore` for sensitive files
   - Rotate secrets regularly

4. **Plan for scaling:**
   - Monitor CPU/Memory usage
   - Plan database backups
   - Setup monitoring alerts

5. **Document your setup:**
   - Save all passwords (encrypted)
   - Document any custom configs
   - Keep deployment logs

---

## 📞 Support Resources

| Resource | Link |
|----------|------|
| aaPanel Docs | https://www.aapanel.com/ |
| Node.js Docs | https://nodejs.org/en/docs/ |
| Nginx Docs | https://nginx.org/en/docs/ |
| Prisma Docs | https://www.prisma.io/docs/ |
| Express Docs | https://expressjs.com/ |
| PostgreSQL | https://www.postgresql.org/docs/ |

---

**Last Updated:** April 14, 2026  
**Status:** ✅ Production Ready
