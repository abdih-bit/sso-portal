# ⚡ Quick Reference: Deploy SSO Portal ke portal.hqmedan.com via aaPanel

## 🚀 Ringkasan Langkah-Langkah Deployment

### **FASE 1: Persiapan (5 menit)**
```
1. Verifikasi domain pointing ke VPS
2. Login ke aaPanel Dashboard
3. Pastikan Node.js terinstall
```

### **FASE 2: Setup Infrastruktur (10 menit)**
```
1. Buat Website: portal.hqmedan.com di aaPanel
2. Setup Database: sso_portal (PostgreSQL)
3. Upload Project: via Git atau SFTP
```

### **FASE 3: Configuration (10 menit)**
```
1. Setup .env file dengan database credentials
2. Install NPM packages
3. Run Prisma migrations
```

### **FASE 4: Launching (5 menit)**
```
1. Setup PM2 Process di aaPanel
2. Setup Nginx Reverse Proxy
3. Setup SSL Certificate
```

### **FASE 5: Verification (5 menit)**
```
1. Akses https://portal.hqmedan.com
2. Verifikasi database connection
3. Test login functionality
```

---

## 🎯 Step-by-Step Commands untuk VPS

### **Masuk ke VPS:**
```bash
ssh root@YOUR_VPS_IP
cd /www/wwwroot/portal.hqmedan.com
```

### **Upload Project via Git:**
```bash
git clone https://github.com/YOUR_USERNAME/sso-portal.git .
```

### **Setup Environment:**
```bash
cp .env.example .env
nano .env
# Edit dengan database credentials dan secrets
```

### **Install & Setup Database:**
```bash
npm install --omit=dev
npx prisma generate
npx prisma migrate deploy
```

### **Verifikasi:**
```bash
# Test database
psql -U sso_user -d sso_portal -c "SELECT 1;"

# Test process
pm2 status

# Test web
curl https://portal.hqmedan.com
```

---

## 📋 Checklist Cepat

### Domain & DNS
- [ ] Domain `portal.hqmedan.com` pointing ke VPS
- [ ] DNS sudah propagate (test: `nslookup portal.hqmedan.com`)

### aaPanel Setup
- [ ] Website dibuat di aaPanel
- [ ] Node.js terinstall
- [ ] Database PostgreSQL siap

### Project Setup
- [ ] Project ter-upload di `/www/wwwroot/portal.hqmedan.com`
- [ ] `.env` file configured
- [ ] NPM dependencies installed
- [ ] Prisma migrations ran

### Running
- [ ] PM2 process created dan status "online"
- [ ] Nginx reverse proxy configured
- [ ] SSL certificate active
- [ ] Can access https://portal.hqmedan.com

### Database
- [ ] Database connection works
- [ ] Migrations applied
- [ ] Tables created

---

## 🔍 Testing Commands

```bash
# Test domain DNS
nslookup portal.hqmedan.com
ping portal.hqmedan.com

# Test HTTP/HTTPS
curl http://portal.hqmedan.com
curl -I https://portal.hqmedan.com

# Test database
psql -U sso_user -d sso_portal -c "SELECT version();"

# Test Node.js process
pm2 status
ps aux | grep node

# Test ports
netstat -tulnp | grep 3000
netstat -tulnp | grep nginx

# Test logs
pm2 logs sso-portal
tail -f /www/wwwroot/portal.hqmedan.com/logs/error.log
```

---

## 🆘 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| "Cannot connect to portal.hqmedan.com" | Check DNS propagation, verify firewall |
| "502 Bad Gateway" | Restart PM2 process: `pm2 restart sso-portal` |
| "Database connection error" | Check DATABASE_URL in .env, test psql connection |
| "SSL certificate not found" | Request new certificate in aaPanel > SSL |
| "Port 3000 in use" | Kill process: `lsof -i :3000 \| kill -9 PID` |
| "Permission denied" | Check file permissions: `chmod 755 /www/wwwroot/portal.hqmedan.com` |

---

## 📱 Access Points

| URL | Purpose |
|-----|---------|
| `https://portal.hqmedan.com` | Main Application |
| `http://YOUR_VPS_IP:8888` | aaPanel Dashboard |
| `https://portal.hqmedan.com/api/auth/login` | Login API |
| `https://portal.hqmedan.com/api/sso/token` | SSO Token API |

---

## 🔐 Important Credentials to Save

```
VPS Root Password: [SAVE THIS]
aaPanel Username: [SAVE THIS]
aaPanel Password: [SAVE THIS]
Database User: sso_user
Database Password: [SAVE THIS]
Domain: portal.hqmedan.com
```

---

## 📞 When Something Goes Wrong

### 1. Check Process Status:
```bash
pm2 status
pm2 logs sso-portal
```

### 2. Check Nginx Status:
```bash
systemctl status nginx
cat /www/server/nginx/conf/vhost/portal.hqmedan.com.conf
```

### 3. Check Database Connection:
```bash
psql -U sso_user -d sso_portal -c "SELECT 1;"
```

### 4. Check Node.js is Running:
```bash
netstat -tulnp | grep 3000
```

### 5. Restart Everything:
```bash
pm2 restart sso-portal
systemctl reload nginx
```

---

**✅ After deployment is complete, verify:**
- Application accessible at https://portal.hqmedan.com
- SSL certificate active (green padlock)
- Database connection working
- All features functional
