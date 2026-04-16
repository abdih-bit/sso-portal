# 🔧 SOLUSI: PostgreSQL Role/User "sso_user" Does Not Exist

## ❌ Error yang Anda Alami:
```
psql: error: connection to server at "localhost" (::1), port 5432 failed
FATAL: role "sso_user" does not exist
```

## 🔍 Penyebab:
User database `sso_user` belum dibuat di PostgreSQL, atau dibuat dengan nama berbeda.

---

## ✅ SOLUSI CEPAT (3 Langkah)

### **Langkah 1: Login sebagai PostgreSQL Admin**
```bash
# Login sebagai postgres superuser
psql -U postgres
```

Jika minta password, gunakan password postgres admin.

### **Langkah 2: Buat Database & User**

**Jalankan command ini di psql prompt:**

```sql
-- 1. Create database
CREATE DATABASE sso_portal;

-- 2. Create user
CREATE USER sso_user WITH PASSWORD 'YOUR_SECURE_PASSWORD_HERE';

-- 3. Grant privileges
ALTER USER sso_user CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;

-- 4. Verify
\du
-- Seharusnya lihat: sso_user | ... | Create DB

-- 5. Exit
\q
```

**Ganti `YOUR_SECURE_PASSWORD_HERE` dengan password yang kuat!**

### **Langkah 3: Test Koneksi**
```bash
psql -U sso_user -d sso_portal -h localhost -c "SELECT 1;"
```

**Output yang benar:**
```
 ?column? 
----------
        1
(1 row)
```

---

## 📋 LENGKAP STEP-BY-STEP

### **Jika Password Postgres Lupa/Tidak Tahu:**

#### Option A: Reset Password Postgres
```bash
# Become root
sudo su -

# Stop PostgreSQL
systemctl stop postgresql

# Edit pg_hba.conf untuk trust authentication
nano /etc/postgresql/*/main/pg_hba.conf

# Cari baris dengan "local   all   postgres"
# Ubah METHOD dari "md5" atau "scram-sha-256" menjadi "trust"
# Contoh:
# local   all             postgres                         trust

# Simpan: Ctrl+X → Y → Enter

# Start PostgreSQL
systemctl start postgresql

# Login tanpa password
psql -U postgres

# Set password baru
ALTER USER postgres WITH PASSWORD 'newpassword';
\q

# Revert pg_hba.conf ke semula (ubah "trust" kembali ke "md5" atau "scram-sha-256")
nano /etc/postgresql/*/main/pg_hba.conf
# Change trust back to md5

# Reload PostgreSQL
systemctl reload postgresql
```

---

### **Jika Ingin Reset Semua (Nuclear Option):**

```bash
# 1. Backup data lama (jika ada)
pg_dumpall -U postgres > /home/backup_all_databases.sql

# 2. Restart PostgreSQL
sudo systemctl restart postgresql

# 3. Login sebagai postgres
psql -U postgres

# 4. Drop database & user lama jika ada
DROP DATABASE IF EXISTS sso_portal;
DROP USER IF EXISTS sso_user;

# 5. Create baru
CREATE DATABASE sso_portal;
CREATE USER sso_user WITH PASSWORD 'NewPassword123!';
ALTER USER sso_user CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;

# 6. Verify
\l
-- Seharusnya lihat: sso_portal | sso_user

\du
-- Seharusnya lihat: sso_user

# 7. Exit
\q
```

---

## 🎯 LENGKAP FLOW FIX

**Copy-paste commands ini ke terminal VPS:**

```bash
# 1. Jadi root
sudo su -

# 2. Login ke PostgreSQL sebagai admin
psql -U postgres

# 3. Jalankan SQL commands berikut:
```

**Masuk ke psql, jalankan satu-satu:**

```sql
-- Check existing databases
\l

-- Check existing users
\du

-- Drop jika ada (optional, jika sudah ada sebelumnya)
DROP DATABASE IF EXISTS sso_portal;
DROP USER IF EXISTS sso_user;

-- Create database
CREATE DATABASE sso_portal;

-- Create user dengan password
CREATE USER sso_user WITH PASSWORD 'MySecurePassword123!';

-- Grant privileges
ALTER USER sso_user CREATEDB;
GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;

-- Verify
\l
\du

-- Exit
\q
```

---

## ✔️ TEST SETIAP STEP

### **Step 1: Verify Database Exists**
```bash
psql -U postgres -l | grep sso_portal
```

**Output yang benar:**
```
 sso_portal | sso_user  | UTF8   | en_US.UTF-8 | en_US.UTF-8 |
```

### **Step 2: Verify User Exists**
```bash
psql -U postgres -c "\du" | grep sso_user
```

**Output yang benar:**
```
 sso_user  | ... | Create DB
```

### **Step 3: Test Connection dengan Password**
```bash
psql -U sso_user -d sso_portal -h localhost -c "SELECT 1;"
```

**Jika minta password, masukkan password yang Anda set!**

**Output yang benar:**
```
 ?column? 
----------
        1
(1 row)
```

---

## 🔒 UPDATE .env FILE

Setelah database & user berhasil dibuat:

```bash
cd /www/wwwroot/sso-portal

# Edit .env
nano .env
```

**Update baris DATABASE_URL dengan password yang benar:**

```bash
DATABASE_URL="postgresql://sso_user:MySecurePassword123!@localhost:5432/sso_portal?schema=public"
```

**Ganti `MySecurePassword123!` dengan password yang Anda gunakan!**

**Simpan:** Ctrl+X → Y → Enter

---

## 🔄 RETRY PRISMA MIGRATION

Setelah user & password benar:

```bash
cd /www/wwwroot/sso-portal

# Verify .env
cat .env | grep DATABASE_URL

# Test connection
psql -U sso_user -d sso_portal -c "SELECT 1;"

# If OK, run prisma
npx prisma generate
npx prisma migrate deploy
```

**Output yang benar:**
```
✅ Generated Prisma Client
✅ 5 migrations applied successfully
```

---

## 🆘 JIKA MASIH ERROR

### **Error: "password authentication failed"**
- Password salah → Check .env DATABASE_URL
- Password salah di SQL → Reset password:
  ```bash
  psql -U postgres
  ALTER USER sso_user WITH PASSWORD 'new-password';
  \q
  ```

### **Error: "database does not exist"**
- Database belum dibuat
- Jalankan: `CREATE DATABASE sso_portal;`

### **Error: "role does not exist"**
- User belum dibuat
- Jalankan: `CREATE USER sso_user WITH PASSWORD 'password';`

### **Error: "peer authentication failed"**
- Login harus dari localhost atau dengan password
- Gunakan: `psql -U sso_user -d sso_portal -h localhost`
- Atau edit pg_hba.conf

---

## 📋 COMPLETE CHECKLIST

```bash
# 1. Verify PostgreSQL running
systemctl status postgresql
# Output: active (running)

# 2. Verify can login as postgres admin
psql -U postgres -c "SELECT 1;"
# Output: 1

# 3. Verify database exists
psql -U postgres -l | grep sso_portal
# Output: sso_portal listed

# 4. Verify user exists
psql -U postgres -c "\du" | grep sso_user
# Output: sso_user listed

# 5. Verify user privileges
psql -U postgres -c "\du sso_user"
# Output: Shows "Create DB" privilege

# 6. Verify connection dengan password
psql -U sso_user -d sso_portal -h localhost -c "SELECT 1;"
# Output: 1

# 7. Verify .env DATABASE_URL correct
cat /www/wwwroot/sso-portal/.env | grep DATABASE_URL

# 8. Verify Prisma
cd /www/wwwroot/sso-portal
npx prisma migrate deploy
# Output: Migrations applied successfully
```

---

## 🎯 QUICK REFERENCE

| Masalah | Solusi |
|---------|--------|
| User tidak ada | `CREATE USER sso_user WITH PASSWORD '...';` |
| Database tidak ada | `CREATE DATABASE sso_portal;` |
| Password salah | `ALTER USER sso_user WITH PASSWORD '...';` |
| Privilege tidak cukup | `GRANT ALL PRIVILEGES ON DATABASE sso_portal TO sso_user;` |
| Connection refused | Check PostgreSQL running: `systemctl status postgresql` |
| Can't connect localhost | Use: `psql -U sso_user -d sso_portal -h localhost` |

---

## 🚀 NEXT STEP

Setelah berhasil test connection:

```bash
cd /www/wwwroot/sso-portal

# Run Prisma migration
npx prisma migrate deploy

# If success, seed database (optional)
node src/database/seed.js

# Restart PM2
pm2 restart sso-portal
```

---

## 💾 SAVE THIS FOR REFERENCE

Password yang Anda gunakan:
```
User: sso_user
Password: ___________________
Database: sso_portal
Host: localhost
Port: 5432
```

---

**Selamat! Sekarang database siap digunakan! 🎉**

Jika masih ada masalah, bagikan output dari:
```bash
psql -U postgres -l
psql -U postgres -c "\du"
cat /www/wwwroot/sso-portal/.env | grep DATABASE_URL
```
