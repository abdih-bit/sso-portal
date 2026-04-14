# 🔧 SSO Portal - Troubleshooting Guide

## 🔴 Common Issues & Solutions

---

## 1️⃣ Application tidak bisa diakses (HTTP 502 Bad Gateway)

### Penyebab Umum:
- Node.js process tidak berjalan
- Port 3000 tidak listening
- Nginx configuration salah
- Database connection failed

### Solusi:

**Step 1: Cek PM2 process**
```bash
pm2 status
pm2 logs sso-portal
```

**Expected output:**
```
sso-portal | ...
sso-portal | Server running on port 3000
sso-portal | Database connected
```

**Step 2: Jika process down, restart**
```bash
pm2 restart sso-portal
sleep 2
pm2 status
```

**Step 3: Cek port 3000**
```bash
netstat -tulnp | grep 3000
# atau
lsof -i :3000
```

Jika ada proses lain, kill terlebih dahulu:
```bash
kill -9 <PID>
pm2 start /www/wwwroot/portal.hqmedan.com/src/server.js --name sso-portal
```

**Step 4: Cek Nginx configuration**
```bash
nginx -t
# Output should be: "test is successful"

# Reload Nginx
systemctl reload nginx
```

**Step 5: Cek file konfigurasi Nginx**
```bash
cat /www/server/nginx/conf/vhost/portal.hqmedan.com.conf | grep -A 10 "upstream\|proxy_pass"
```

Pastikan ada:
```nginx
upstream sso_portal_backend {
    server 127.0.0.1:3000;
}

location / {
    proxy_pass http://sso_portal_backend;
    ...
}
```

**Step 6: Test koneksi langsung**
```bash
curl http://127.0.0.1:3000
# Seharusnya return HTML atau JSON, bukan connection refused
```

---

## 2️⃣ Database Connection Error

### Error:
```
❌ Error: connect ECONNREFUSED 127.0.0.1:5432
```

### Solusi:

**Step 1: Cek PostgreSQL status**
```bash
systemctl status postgresql
# Output harus: "active (running)"

# Jika tidak running, start
systemctl start postgresql
systemctl enable postgresql
```

**Step 2: Verifikasi .env DATABASE_URL**
```bash
cat /www/wwwroot/portal.hqmedan.com/.env | grep DATABASE_URL
```

Format harus:
```
DATABASE_URL=postgresql://USERNAME:PASSWORD@localhost:5432/DATABASE_NAME
```

**Step 3: Test connection manual**
```bash
psql -U sso_user -d sso_portal -c "SELECT 1;"
# Output: 
#  ?column?
# ----------
#         1
```

Jika error "password authentication failed":
```bash
# Reset password PostgreSQL user
sudo -u postgres psql -c "ALTER USER sso_user WITH PASSWORD 'new-password';"

# Update .env dengan password baru
nano /www/wwwroot/portal.hqmedan.com/.env

# Restart app
pm2 restart sso-portal
```

**Step 4: Cek database & user permissions**
```bash
sudo -u postgres psql -c "\l"  # List databases
sudo -u postgres psql -c "\du" # List users

# Jika user tidak punya privileges:
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;"
```

**Step 5: Cek Prisma migration**
```bash
cd /www/wwwroot/portal.hqmedan.com
npx prisma migrate status
```

Jika ada pending migrations:
```bash
npx prisma migrate deploy
```

---

## 3️⃣ Port 3000 Already in Use

### Error:
```
Error: listen EADDRINUSE :::3000
```

### Solusi:

**Step 1: Find process using port 3000**
```bash
lsof -i :3000
netstat -tulnp | grep 3000
```

**Step 2: Kill the process**
```bash
kill -9 <PID>
# or
fuser -k 3000/tcp
```

**Step 3: Restart PM2**
```bash
pm2 delete sso-portal
pm2 start /www/wwwroot/portal.hqmedan.com/src/server.js --name sso-portal
```

**Alternative: Use different port**
```bash
# Edit .env
nano /www/wwwroot/portal.hqmedan.com/.env
# Change: PORT=3001

# Update Nginx upstream
# Change: server 127.0.0.1:3001;

# Restart
pm2 restart sso-portal
systemctl reload nginx
```

---

## 4️⃣ SSL Certificate Issues

### Error: ERR_SSL_PROTOCOL_ERROR atau NET::ERR_CERT_AUTHORITY_INVALID

### Solusi:

**Step 1: Check certificate status**
```bash
certbot certificates
# atau via aaPanel: Websites → SSL

# Check expiry date
openssl s_client -connect portal.hqmedan.com:443 -showcerts 2>/dev/null | grep -A2 "Validity"
```

**Step 2: Re-request certificate**
```bash
# Via aaPanel: Websites → Select domain → SSL → Re-request

# Manual:
certbot revoke --cert-path /path/to/cert.pem
certbot certonly --webroot -w /www/wwwroot/portal.hqmedan.com -d portal.hqmedan.com
```

**Step 3: Reload Nginx**
```bash
systemctl reload nginx
```

**Step 4: Verify certificate**
```bash
curl -I https://portal.hqmedan.com
# HTTP/2 200 berarti berhasil
```

---

## 5️⃣ Prisma Migration Stuck/Failed

### Error:
```
Error: P3001
Prisma could not connect to the database server.
```

### Solusi:

**Step 1: Check database connection**
```bash
psql -U sso_user -d sso_portal -c "SELECT 1;"
```

**Step 2: View migration history**
```bash
cd /www/wwwroot/portal.hqmedan.com
npx prisma migrate status
```

**Step 3: Reset migrations (⚠️ WARNING: Deletes all data)**
```bash
# Only if you want to start fresh!
npx prisma migrate reset

# Answer "y" when prompted
```

**Step 4: Or resolve specific migration**
```bash
# Resolve migration failure
npx prisma migrate resolve --rolled-back 20260408065428_init

# Then retry
npx prisma migrate deploy
```

**Step 5: If schema is wrong**
```bash
# Edit schema.prisma
nano /www/wwwroot/portal.hqmedan.com/prisma/schema.prisma

# Create new migration
npx prisma migrate dev --name fix_schema

# Deploy
npx prisma migrate deploy
```

---

## 6️⃣ Nginx 502 Bad Gateway (Persistent)

### Solusi Lengkap:

**Step 1: Cek Nginx error log**
```bash
tail -50 /www/server/nginx/logs/error.log
```

Common errors:
- `upstream timed out` → Node.js process slow
- `no live upstreams` → Node.js process down
- `permission denied` → File permissions wrong

**Step 2: Cek Nginx configuration syntax**
```bash
nginx -t -c /www/server/nginx/conf/nginx.conf
```

**Step 3: Validate upstream configuration**
```bash
grep -n "upstream\|server 127" /www/server/nginx/conf/vhost/portal.hqmedan.com.conf
```

Should show:
```
upstream sso_portal_backend {
    server 127.0.0.1:3000;
}
```

**Step 4: Test Node.js response directly**
```bash
# From VPS
curl -v http://127.0.0.1:3000

# Check response time
curl -w "Time: %{time_total}s\n" http://127.0.0.1:3000
```

**Step 5: Increase timeouts if slow**
```bash
nano /www/server/nginx/conf/vhost/portal.hqmedan.com.conf

# Add inside location block:
proxy_connect_timeout 10s;
proxy_send_timeout 60s;
proxy_read_timeout 60s;
```

**Step 6: Reload and test**
```bash
systemctl reload nginx
curl -I https://portal.hqmedan.com
```

---

## 7️⃣ Out of Memory / Process Crashing

### Error:
```
FATAL ERROR: CALL_AND_RETRY_LAST Allocation failed
JavaScript heap out of memory
```

### Solusi:

**Step 1: Check memory usage**
```bash
free -h
top -n 1 | head -20
```

**Step 2: Increase Node.js heap size**
```bash
# Edit PM2 start command
pm2 delete sso-portal
pm2 start /www/wwwroot/portal.hqmedan.com/src/server.js \
  --name sso-portal \
  --max-memory-restart 500M \
  --merge-logs
pm2 save
```

**Step 3: Add swap if needed**
```bash
# Check current swap
free -h | grep Swap

# If less than 2GB, add swap:
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab
```

**Step 4: Optimize Node.js**
```bash
# Edit /www/wwwroot/portal.hqmedan.com/src/server.js
# Add at top:
process.on('warning', (warning) => {
  console.warn(warning.name, warning.message);
});
```

**Step 5: Monitor memory**
```bash
pm2 monit
# or
pm2 list
```

---

## 8️⃣ High CPU Usage

### Solusi:

**Step 1: Identify problematic code**
```bash
pm2 logs sso-portal | grep -i "error\|loop\|while"
```

**Step 2: Check for infinite loops**
```bash
# Enable profiling
pm2 start /www/wwwroot/portal.hqmedan.com/src/server.js \
  --name sso-portal \
  --instance-var INSTANCE_ID
```

**Step 3: Use PM2 monitoring**
```bash
pm2 monit
# Press 's' untuk sort by memory/CPU
```

**Step 4: Profile with clinic.js (if available)**
```bash
npm install -g clinic
cd /www/wwwroot/portal.hqmedan.com
clinic doctor -- node src/server.js
```

---

## 9️⃣ CORS Error: Access Denied

### Error:
```
Access to XMLHttpRequest from origin 'https://app.hqmedan.com' 
has been blocked by CORS policy
```

### Solusi:

**Step 1: Check current CORS configuration**
```bash
grep -n "CORS\|CORS_ORIGIN" /www/wwwroot/portal.hqmedan.com/.env
```

**Step 2: Update .env**
```bash
nano /www/wwwroot/portal.hqmedan.com/.env

# Change CORS_ORIGIN to:
CORS_ORIGIN=https://portal.hqmedan.com,https://app.hqmedan.com,https://other-app.hqmedan.com
```

**Step 3: Check app.js CORS setup**
```bash
grep -A 10 "cors(" /www/wwwroot/portal.hqmedan.com/src/app.js
```

**Step 4: Restart application**
```bash
pm2 restart sso-portal
```

**Step 5: Test CORS headers**
```bash
curl -H "Origin: https://app.hqmedan.com" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: X-Custom-Header" \
  -X OPTIONS \
  -v https://portal.hqmedan.com/api/
```

Should include:
```
Access-Control-Allow-Origin: https://app.hqmedan.com
Access-Control-Allow-Methods: ...
```

---

## 🔟 Email/SMTP Not Working

### Error:
```
Email verification failed
Invalid credentials or server rejected...
```

### Solusi:

**Step 1: Check SMTP configuration**
```bash
grep "SMTP" /www/wwwroot/portal.hqmedan.com/.env
```

**Step 2: Test SMTP credentials**
```bash
cd /www/wwwroot/portal.hqmedan.com
node -e "
require('dotenv').config();
const nodemailer = require('nodemailer');

const transporter = nodemailer.createTransport({
  host: process.env.SMTP_HOST,
  port: process.env.SMTP_PORT,
  secure: true,
  auth: {
    user: process.env.SMTP_USER,
    pass: process.env.SMTP_PASS
  }
});

transporter.verify((error, success) => {
  if (error) console.log('❌', error);
  else console.log('✓ Email configured correctly');
});
"
```

**Step 3: For Gmail, use App Password**
```bash
# 1. Go to https://myaccount.google.com/apppasswords
# 2. Select Mail → Windows Computer
# 3. Copy password
# 4. Update .env:
nano /www/wwwroot/portal.hqmedan.com/.env
# SMTP_PASS=xxxx xxxx xxxx xxxx (16 chars with spaces)

# 5. Restart
pm2 restart sso-portal
```

**Step 4: For other email providers**
```bash
# Check documentation for SMTP settings
# Common:
# - Outlook: smtp.office365.com:587
# - SendGrid: smtp.sendgrid.net:587
# - MailGun: smtp.mailgun.org:587
```

**Step 5: Check firewall**
```bash
# Ensure SMTP port is allowed outbound
ufw allow out 587/tcp
```

---

## 📋 Automated Health Check Script

Save as `/usr/local/bin/sso-portal-health-check.sh`:

```bash
#!/bin/bash

echo "=== SSO Portal Health Check ==="
echo ""

# Check PM2
echo "✓ PM2 Status:"
pm2 status | grep sso-portal

# Check database
echo ""
echo "✓ Database:"
psql -U sso_user -d sso_portal -c "SELECT 1;" 2>&1 | grep -i "^\s*1\|error"

# Check API
echo ""
echo "✓ API Response:"
curl -s -o /dev/null -w "HTTP %{http_code}\n" http://127.0.0.1:3000

# Check Nginx
echo ""
echo "✓ Nginx Status:"
systemctl is-active nginx

# Check SSL
echo ""
echo "✓ SSL Certificate:"
openssl s_client -connect portal.hqmedan.com:443 -showcerts 2>/dev/null | \
  grep -A 2 "Validity"

echo ""
echo "=== Health Check Complete ==="
```

Setup cron job:
```bash
chmod +x /usr/local/bin/sso-portal-health-check.sh

# Run every 6 hours
0 */6 * * * /usr/local/bin/sso-portal-health-check.sh >> /var/log/sso-portal-health.log 2>&1
```

---

## 🆘 Quick Restart Commands

```bash
# Soft restart (graceful)
pm2 restart sso-portal

# Hard restart (immediate)
pm2 restart sso-portal --kill-timeout 1000

# Restart all processes
pm2 restart all

# Full restart
pm2 delete sso-portal
pm2 start /www/wwwroot/portal.hqmedan.com/src/server.js --name sso-portal

# Restart with fresh environment
pm2 restart sso-portal --update-env
```

---

## 📞 Getting Help

1. **Check logs first**: `pm2 logs sso-portal`
2. **Check status**: `pm2 status`
3. **Check errors**: `tail -50 /www/server/nginx/logs/error.log`
4. **Test connectivity**: `curl -I https://portal.hqmedan.com`

---

**Remember**: Most issues are solved by restarting the application or checking logs! 🚀
