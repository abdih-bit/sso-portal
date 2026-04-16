# 🔧 TROUBLESHOOTING GUIDE: Portal.hqmedan.com via aaPanel

## ❌ Problem 1: "Cannot reach portal.hqmedan.com"

### Diagnosis:
```bash
# Test 1: DNS Resolution
nslookup portal.hqmedan.com
ping portal.hqmedan.com

# Test 2: Domain pointing
nslookup portal.hqmedan.com | grep Address
# Should show: YOUR_VPS_IP_ADDRESS

# Test 3: HTTP connection
curl -v http://portal.hqmedan.com

# Test 4: HTTPS connection
curl -v https://portal.hqmedan.com
```

### Possible Causes & Solutions:

#### ❌ DNS not propagated yet
**Solution:**
- Domain changes take 24-48 hours to propagate
- In the meantime, test using IP directly: `http://YOUR_VPS_IP`
- Or add to local hosts file:
  ```
  YOUR_VPS_IP  portal.hqmedan.com
  ```

#### ❌ Domain pointing to wrong IP
**Solution:**
```bash
# Verify your VPS IP
ip addr show | grep "inet "

# Update domain registrar DNS to point to correct IP
# Go to: Domain Registrar → DNS Settings
# Update A record: portal.hqmedan.com → YOUR_VPS_IP
```

#### ❌ Firewall blocking
**Solution:**
```bash
# Check UFW firewall
sudo ufw status

# Allow HTTP & HTTPS
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Enable if not active
sudo ufw enable
```

#### ❌ aaPanel not installed
**Solution:**
```bash
# Check if aaPanel running
systemctl status aapanel
ps aux | grep aapanel

# If not running:
systemctl start aapanel
systemctl enable aapanel

# Check aaPanel is accessible
curl http://localhost:8888
```

---

## ❌ Problem 2: "502 Bad Gateway" Error

### Diagnosis:
```bash
# Check Node.js process
pm2 status
ps aux | grep node
netstat -tulnp | grep 3000

# Check if port 3000 is listening
lsof -i :3000

# Check logs
pm2 logs sso-portal
```

### Possible Causes & Solutions:

#### ❌ Node.js process not running
**Solution:**
```bash
# Check process status
pm2 status

# Restart process
pm2 restart sso-portal

# If restart doesn't work, delete and recreate via aaPanel:
pm2 delete sso-portal
# Then use aaPanel Supervisor to create new process
```

#### ❌ Application crashed
**Solution:**
```bash
# Check logs for errors
pm2 logs sso-portal --lines 50

# Common errors:
# - DATABASE_URL not set
# - Port already in use
# - Missing dependencies
# - Syntax errors in code

# Fix and restart
pm2 restart sso-portal
```

#### ❌ Database connection failed
**Solution:**
```bash
# Check DATABASE_URL in .env
cat /www/wwwroot/portal.hqmedan.com/.env | grep DATABASE_URL

# Test database connection
psql -U sso_user -d sso_portal -c "SELECT 1;"

# If error, check:
# 1. Username & password correct
# 2. Database exists: psql -l
# 3. PostgreSQL running: systemctl status postgresql
# 4. Port 5432 open: netstat -tulnp | grep 5432
```

#### ❌ Memory limit exceeded
**Solution:**
```bash
# Check memory usage
pm2 monit

# Increase memory limit in ecosystem.config.js:
# "max_memory_restart": "500M"  → "max_memory_restart": "1G"

# Restart application
pm2 restart sso-portal
```

#### ❌ Nginx reverse proxy misconfigured
**Solution:**
```bash
# Check nginx config
cat /www/server/nginx/conf/vhost/portal.hqmedan.com.conf | grep -A 5 "proxy_pass"

# Should have:
# proxy_pass http://127.0.0.1:3000;

# Test nginx syntax
nginx -t

# Reload nginx
systemctl reload nginx
```

---

## ❌ Problem 3: "Database connection failed"

### Diagnosis:
```bash
# Test PostgreSQL connection
psql -U sso_user -d sso_portal -c "SELECT 1;"

# Check PostgreSQL status
systemctl status postgresql

# Check database exists
psql -U postgres -c "\l" | grep sso_portal

# Check user exists
psql -U postgres -c "\du" | grep sso_user
```

### Possible Causes & Solutions:

#### ❌ Wrong database URL
**Solution:**
```bash
# Check .env file
nano /www/wwwroot/portal.hqmedan.com/.env

# DATABASE_URL should be:
# postgresql://sso_user:PASSWORD@localhost:5432/sso_portal?schema=public

# Common mistakes:
# ❌ postgresql://sso_user:PASSWORD@127.0.0.1:5432/sso_portal
#    (use "localhost" not "127.0.0.1")
# ❌ postgresql://sso_user:PASSWORD@localhost:3306/sso_portal
#    (5432 not 3306 - that's MySQL)
# ❌ Missing password or username
```

#### ❌ Database doesn't exist
**Solution:**
```bash
# Create database
psql -U postgres -c "CREATE DATABASE sso_portal;"

# Check it was created
psql -l | grep sso_portal

# Run Prisma migration
cd /www/wwwroot/portal.hqmedan.com
npx prisma migrate deploy
```

#### ❌ User doesn't exist or wrong password
**Solution:**
```bash
# List all users
psql -U postgres -c "\du"

# Create user if doesn't exist
psql -U postgres -c "CREATE USER sso_user WITH PASSWORD 'your-password';"

# Update password
psql -U postgres -c "ALTER USER sso_user WITH PASSWORD 'your-password';"

# Grant privileges
psql -U postgres -c "ALTER USER sso_user CREATEDB;"
psql -U postgres -c "GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;"

# Test connection with new password
psql -U sso_user -d sso_portal -c "SELECT 1;"
```

#### ❌ PostgreSQL not running
**Solution:**
```bash
# Check status
systemctl status postgresql

# Start if not running
systemctl start postgresql

# Enable on boot
systemctl enable postgresql

# Check listening on port 5432
netstat -tulnp | grep 5432
```

#### ❌ Prisma client not generated
**Solution:**
```bash
cd /www/wwwroot/portal.hqmedan.com

# Generate Prisma client
npx prisma generate

# Check if node_modules/.prisma exists
ls -la node_modules/.prisma/client/

# Restart application
pm2 restart sso-portal
```

---

## ❌ Problem 4: "Cannot read package.json"

### Diagnosis:
```bash
# Check if file exists
ls -la /www/wwwroot/portal.hqmedan.com/package.json

# Check file permissions
stat /www/wwwroot/portal.hqmedan.com/package.json

# Check folder contents
ls -la /www/wwwroot/portal.hqmedan.com/ | head -20
```

### Possible Causes & Solutions:

#### ❌ Project not uploaded
**Solution:**
```bash
# Upload via Git
cd /www/wwwroot/portal.hqmedan.com
git clone https://github.com/YOUR_USERNAME/sso-portal.git .

# Or if repository already exists
git pull origin main

# Verify files
ls -la package.json
```

#### ❌ Wrong directory
**Solution:**
```bash
# Check current directory
pwd

# Should be: /www/wwwroot/portal.hqmedan.com

# Change to correct directory
cd /www/wwwroot/portal.hqmedan.com

# Verify contents
ls -la
```

#### ❌ File permissions issue
**Solution:**
```bash
# Fix permissions
chmod 755 /www/wwwroot/portal.hqmedan.com
chmod 644 /www/wwwroot/portal.hqmedan.com/package.json
chmod 755 /www/wwwroot/portal.hqmedan.com/src
chmod 755 /www/wwwroot/portal.hqmedan.com/prisma

# Change ownership
chown -R www:www /www/wwwroot/portal.hqmedan.com

# Verify
ls -la /www/wwwroot/portal.hqmedan.com/package.json
```

---

## ❌ Problem 5: "npm ERR! code EACCES"

### Diagnosis:
```bash
# Check npm permissions
npm config get prefix

# Check ownership of npm directory
ls -la ~/.npm
ls -la ~/.npm-global
```

### Possible Causes & Solutions:

#### ❌ Permission denied error
**Solution:**
```bash
# Option 1: Fix npm permissions
mkdir ~/.npm-global
npm config set prefix '~/.npm-global'
export PATH=~/.npm-global/bin:$PATH

# Option 2: Use sudo (not recommended)
sudo npm install

# Option 3: Change directory ownership
sudo chown -R $(whoami) /www/wwwroot/portal.hqmedan.com
npm install
```

#### ❌ Node modules corrupted
**Solution:**
```bash
cd /www/wwwroot/portal.hqmedan.com

# Clear npm cache
npm cache clean --force

# Remove node_modules
rm -rf node_modules package-lock.json

# Reinstall
npm install --omit=dev
```

---

## ❌ Problem 6: "SSL Certificate Error"

### Diagnosis:
```bash
# Check SSL certificate
curl -v https://portal.hqmedan.com 2>&1 | grep -i certificate

# Check expiration
echo | openssl s_client -servername portal.hqmedan.com -connect portal.hqmedan.com:443 2>/dev/null | openssl x509 -noout -dates

# Check in aaPanel
# Websites → portal.hqmedan.com → SSL
```

### Possible Causes & Solutions:

#### ❌ Let's Encrypt validation failed
**Solution:**
```bash
# Via aaPanel:
# 1. Websites → portal.hqmedan.com → SSL
# 2. Delete current certificate
# 3. Re-request new certificate
# 4. Wait 1-2 minutes

# Manual renewal
certbot renew --force-renewal

# Or with Let's Encrypt
certbot certonly -d portal.hqmedan.com
```

#### ❌ Certificate expired
**Solution:**
```bash
# Renew certificate
certbot renew

# Or auto-renewal via cron
# (aaPanel handles this automatically)

# Check renewal status
certbot certificates
```

#### ❌ Domain validation failed
**Solution:**
```bash
# Verify domain is accessible
curl http://portal.hqmedan.com
curl https://portal.hqmedan.com

# Ensure port 80 is open for verification
sudo ufw allow 80/tcp

# Request certificate again via aaPanel
```

---

## ❌ Problem 7: "CORS Error"

### Error Message:
```
Access to XMLHttpRequest at 'https://portal.hqmedan.com/api/...'
from origin 'https://other-domain.com' has been blocked by CORS policy
```

### Diagnosis:
```bash
# Check CORS headers
curl -I https://portal.hqmedan.com

# Test CORS preflight request
curl -X OPTIONS https://portal.hqmedan.com -H "Origin: https://other-domain.com" -v
```

### Possible Causes & Solutions:

#### ❌ CORS not configured
**Solution:**
```bash
# Edit app.js
nano /www/wwwroot/portal.hqmedan.com/src/app.js

# Add CORS configuration:
const cors = require('cors');
app.use(cors({
  origin: ['https://portal.hqmedan.com', 'https://other-domain.com'],
  credentials: true
}));

# Restart application
pm2 restart sso-portal
```

#### ❌ Wrong origin allowed
**Solution:**
```bash
# Check current CORS configuration
cat /www/wwwroot/portal.hqmedan.com/src/app.js | grep -A 5 "cors"

# Update to allow correct domains
nano /www/wwwroot/portal.hqmedan.com/src/app.js
# Modify origin array to include needed domains

# Restart
pm2 restart sso-portal
```

---

## ❌ Problem 8: "Cannot login / authentication failing"

### Diagnosis:
```bash
# Check database has users table
psql -U sso_user -d sso_portal -c "SELECT COUNT(*) FROM users;"

# Check admin user exists
psql -U sso_user -d sso_portal -c "SELECT email, role FROM users LIMIT 5;"

# Check logs for auth errors
pm2 logs sso-portal | grep -i "auth\|login"
```

### Possible Causes & Solutions:

#### ❌ Database not seeded
**Solution:**
```bash
cd /www/wwwroot/portal.hqmedan.com

# Seed database with initial data
node src/database/seed.js

# Verify users created
psql -U sso_user -d sso_portal -c "SELECT email, role FROM users;"
```

#### ❌ Wrong credentials
**Solution:**
```bash
# Create admin user manually
psql -U sso_user -d sso_portal << EOF
INSERT INTO users (email, password, role, created_at, updated_at)
VALUES ('admin@hqmedan.com', 'hashed_password_here', 'admin', NOW(), NOW());
EOF

# Or update existing user to admin
psql -U sso_user -d sso_portal << EOF
UPDATE users SET role = 'admin' WHERE email = 'your-email@hqmedan.com';
EOF
```

#### ❌ JWT secret not configured
**Solution:**
```bash
# Check .env has JWT_SECRET
cat /www/wwwroot/portal.hqmedan.com/.env | grep JWT

# Generate new secrets if empty
node -e "console.log('JWT_SECRET=' + require('crypto').randomBytes(32).toString('hex'))"

# Update .env
nano /www/wwwroot/portal.hqmedan.com/.env

# Restart
pm2 restart sso-portal
```

---

## ❌ Problem 9: "High Memory Usage"

### Diagnosis:
```bash
# Check memory usage
pm2 monit

# Check with free command
free -h

# Check process memory
ps aux | grep node

# Check memory limit
cat /www/wwwroot/portal.hqmedan.com/ecosystem.config.js | grep memory
```

### Possible Causes & Solutions:

#### ❌ Memory leak in application
**Solution:**
```bash
# Increase restart threshold temporarily
nano /www/wwwroot/portal.hqmedan.com/ecosystem.config.js

# Change:
# "max_memory_restart": "500M" → "max_memory_restart": "1G"

# Restart
pm2 restart sso-portal

# Monitor
pm2 monit
```

#### ❌ Too many database connections
**Solution:**
```bash
# Check active connections
psql -U sso_user -d sso_portal -c "SELECT datname, count(*) FROM pg_stat_activity GROUP BY datname;"

# Close idle connections
psql -U postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = 'sso_portal' AND state = 'idle';"
```

#### ❌ Cache not being cleared
**Solution:**
```bash
# Clear npm cache
npm cache clean --force

# Restart application
pm2 restart sso-portal
```

---

## ⚡ Emergency Recovery Checklist

Jika deployment completely broken, ikuti ini:

### 1. Stop Application
```bash
pm2 stop sso-portal
pm2 delete sso-portal
systemctl reload nginx
```

### 2. Check Backups
```bash
# Database backup
ls -la /backup/ | grep sso_portal

# Application backup (via Git)
git log --oneline | head -10
```

### 3. Restore from Backup
```bash
# Restore database
dropdb -U sso_user sso_portal
createdb -U sso_user sso_portal
psql -U sso_user sso_portal < /backup/sso_portal_backup.sql

# Restore application
git reset --hard HEAD~1  # Go to previous commit
```

### 4. Verify Everything
```bash
pm2 status
psql -U sso_user -d sso_portal -c "SELECT 1;"
curl https://portal.hqmedan.com
```

### 5. Restart
```bash
pm2 restart sso-portal
systemctl reload nginx
```

---

## 📞 Getting Help

Jika masalah tidak terselesaikan:

1. **Collect Logs:**
   ```bash
   pm2 logs sso-portal > /tmp/logs.txt
   systemctl status nginx > /tmp/nginx.txt
   ```

2. **Document Error:**
   - Exact error message
   - Steps to reproduce
   - What was changed before error

3. **Contact Support:**
   - aaPanel Support: support@aapanel.com
   - Node.js Community: nodejs.org/community
   - PostgreSQL: postgresql.org/support

---

**Remember:**
- Always backup before changes
- Monitor logs regularly
- Keep system updated
- Test in staging first

**Last Updated:** April 14, 2026
