# 🔐 SSO Portal - portal.hqmedan.com

Portal Single Sign-On (SSO) untuk domain `portal.hqmedan.com`. Login sekali, akses semua aplikasi HQ Medan.

## 🏗️ Teknologi

| Stack | Detail |
|---|---|
| **Runtime** | Node.js 20 |
| **Framework** | Express.js |
| **Database** | PostgreSQL + Prisma ORM |
| **Auth** | JWT + HttpOnly Cookie |
| **Frontend** | HTML + Tailwind CSS |
| **Process Manager** | PM2 |
| **Reverse Proxy** | Nginx |
| **SSL** | Let's Encrypt (Certbot) |

## 📁 Struktur Proyek

```
sso-portal/
├── src/
│   ├── server.js              # Entry point
│   ├── app.js                 # Express app setup
│   ├── controllers/
│   │   ├── auth.controller.js # Login, logout, reset password
│   │   ├── sso.controller.js  # SSO authorize & validate
│   │   └── admin.controller.js# Manajemen user & aplikasi
│   ├── routes/
│   │   ├── auth.routes.js
│   │   ├── sso.routes.js
│   │   ├── admin.routes.js
│   │   ├── portal.routes.js
│   │   └── application.routes.js
│   ├── middleware/
│   │   └── auth.middleware.js  # JWT verification
│   ├── utils/
│   │   ├── jwt.utils.js        # JWT helpers
│   │   ├── audit.utils.js      # Audit logging
│   │   └── email.utils.js      # Email (reset password)
│   └── database/
│       ├── client.js           # Prisma client
│       └── seed.js             # Data awal
├── public/                     # Frontend pages
│   ├── login.html
│   ├── dashboard.html
│   ├── admin.html
│   ├── profile.html
│   └── ...
├── prisma/
│   └── schema.prisma           # Database schema
├── deployment/
│   ├── nginx/                  # Nginx config
│   └── deploy.sh               # Script deploy ke VPS
└── ecosystem.config.js         # PM2 config
```

## 🚀 Setup Lokal

### 1. Prerequisites
- Node.js 18+
- PostgreSQL

### 2. Install
```bash
npm install
cp .env.example .env
# Edit .env sesuai konfigurasi Anda
```

### 3. Setup Database
```bash
# Buat database PostgreSQL
createdb sso_portal

# Generate Prisma client
npm run db:generate

# Jalankan migrasi
npm run db:migrate

# Seed data awal (superadmin + sample apps)
npm run db:seed
```

### 4. Jalankan
```bash
# Development
npm run dev

# Production
npm start
```

## 🌐 Deploy ke VPS

### DNS Setup
Tambahkan DNS record di `hqmedan.com`:
```
Type: A
Name: portal
Value: <IP VPS Anda>
TTL: 3600
```

### Deploy
```bash
# Upload files ke VPS via SCP/SFTP
# lalu jalankan:
bash deployment/deploy.sh
```

## 🔌 API Endpoints

### Auth
| Method | Endpoint | Deskripsi |
|---|---|---|
| POST | `/api/auth/login` | Login |
| POST | `/api/auth/logout` | Logout |
| GET | `/api/auth/me` | Data user aktif |
| POST | `/api/auth/refresh` | Refresh token |
| PUT | `/api/auth/change-password` | Ganti password |
| POST | `/api/auth/forgot-password` | Kirim link reset |
| POST | `/api/auth/reset-password` | Reset password |

### SSO
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/sso/authorize?app=slug&redirect=url` | Inisiasi SSO login |
| POST | `/api/sso/validate` | Validasi SSO token |

### Admin
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/admin/stats` | Statistik portal |
| GET/POST | `/api/admin/users` | CRUD users |
| PUT/DELETE | `/api/admin/users/:id` | Update/delete user |
| GET/POST | `/api/admin/applications` | CRUD aplikasi |
| GET | `/api/admin/audit-logs` | Audit log |

## 🔗 Cara Integrasi Aplikasi

### Alur SSO
```
Aplikasi → Redirect ke SSO Portal → Login → Redirect balik dengan token → Validasi token
```

### Step 1: Tambah aplikasi di Admin Panel
- Buka `portal.hqmedan.com/admin`
- Tambah aplikasi dengan nama, slug, URL, dan callback URL
- Simpan **Client ID** dan **Client Secret**

### Step 2: Redirect ke SSO dari aplikasi Anda
```javascript
// Saat user belum login di aplikasi Anda
window.location.href = 'https://portal.hqmedan.com/api/sso/authorize' +
  '?app=nama-slug-aplikasi' +
  '&redirect=https://apps-anda.hqmedan.com/sso/callback';
```

### Step 3: Handle callback di aplikasi Anda
```javascript
// Handler: GET /sso/callback?sso_token=xxx
app.get('/sso/callback', async (req, res) => {
  const { sso_token } = req.query;
  
  const response = await fetch('https://portal.hqmedan.com/api/sso/validate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      sso_token,
      client_id: process.env.SSO_CLIENT_ID,
      client_secret: process.env.SSO_CLIENT_SECRET,
    })
  });
  
  const data = await response.json();
  if (data.valid) {
    // Simpan session user di aplikasi Anda
    req.session.user = data.user;
    req.session.token = data.token;
    res.redirect('/dashboard');
  } else {
    res.redirect('/login?error=sso_failed');
  }
});
```

## 👤 Default Admin

```
URL      : https://portal.hqmedan.com/login
Email    : admin@hqmedan.com
Password : Admin@HQ2025!
```
> ⚠️ **Segera ganti password setelah login pertama!**

## 🔒 Keamanan

- ✅ JWT dengan expiry time
- ✅ HttpOnly cookies (tidak bisa diakses JavaScript)
- ✅ CSRF protection via SameSite cookie
- ✅ Rate limiting (login: 10x/15 menit)
- ✅ bcrypt password hashing (rounds: 12)
- ✅ SSO token one-time use (5 menit)
- ✅ Audit log semua aksi
- ✅ HTTPS enforced
- ✅ Security headers (Helmet.js)
- ✅ Role-based access control (USER / ADMIN / SUPERADMIN)
