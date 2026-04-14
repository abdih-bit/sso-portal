# 🔗 SSO Portal Integration Guide

> **Panduan mengintegrasikan aplikasi lain ke SSO Portal**

---

## 📋 Daftar Isi
1. [Overview](#overview)
2. [Register Application](#register-application)
3. [Node.js Express Integration](#nodejs-express-integration)
4. [PHP Laravel Integration](#php-laravel-integration)
5. [JavaScript/React Integration](#javascriptreact-integration)
6. [Test Integration](#test-integration)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 Overview

### Cara Kerja SSO Portal

```
1. User buka aplikasi Anda (hrms.hqmedan.com)
   ↓
2. Aplikasi cek session user
   ↓
3. Jika belum ada session, redirect ke SSO Portal dengan callback URL
   ↓
4. SSO Portal tampilkan login form
   ↓
5. User login di SSO Portal
   ↓
6. SSO Portal redirect ke callback URL dengan one-time token
   ↓
7. Aplikasi Anda validate token ke SSO Portal API
   ↓
8. SSO Portal return user data + JWT token
   ↓
9. Aplikasi simpan JWT token di session/cookie
   ↓
10. User dapat akses aplikasi
```

### Flow Diagram

```
┌─────────────┐                    ┌──────────────────┐
│   Your App  │                    │  SSO Portal      │
│ (hrms.app)  │                    │(portal.hqmedan) │
└──────┬──────┘                    └────────┬─────────┘
       │                                    │
       │ 1. User akses /dashboard          │
       │    (belum login)                   │
       ├────────────────────────────────────→
       │ Redirect ke /api/sso/authorize    │
       │ ?app=hrms&redirect=...            │
       │                                    ├──→ Check session
       │                                    │
       │ 2. User redirect to SSO Login     │
       │←────────────────────────────────────┤
       │                                    │
       │ 3. User submit login form         │
       │    (username + password)          │
       ├────────────────────────────────────→
       │                                    ├──→ Verify credentials
       │                                    │
       │ 4. Redirect ke callback URL       │
       │    dengan sso_token=xxxx          │
       │←────────────────────────────────────┤
       │                                    │
       │ 5. POST /api/sso/validate        │
       │    {sso_token, client_id, ...}   │
       ├────────────────────────────────────→
       │                                    │
       │ 6. Return {user, token, ...}     │
       │←────────────────────────────────────┤
       │                                    │
       │ 7. Save token, create session     │
       │ Redirect ke /dashboard            │
       │                                    │
       └────────────────────────────────────┘
```

---

## 📱 Register Application

### Step 1: Login ke SSO Portal Admin

1. Buka https://portal.hqmedan.com
2. Login dengan admin account
3. Buka menu **Admin** atau **Applications**

### Step 2: Tambah Aplikasi Baru

1. Klik **Add Application** / **+ New App**
2. Isi form:
   - **Application Name**: HRMS (nama aplikasi)
   - **Application URL**: https://hrms.hqmedan.com (URL aplikasi)
   - **Callback URLs**: https://hrms.hqmedan.com/sso/callback
   - **Allowed Domains**: hrms.hqmedan.com
   - Description: Optional

3. Klik **Create** / **Save**

### Step 3: Catat Credentials

SSO Portal akan generate:
- **Client ID**: `xxxx-xxxx-xxxx-xxxx`
- **Client Secret**: `secret_xxxxx...`

**⚠️ IMPORTANT**: Simpan credentials ini di tempat aman!

---

## 🟢 Node.js Express Integration

### Installation

```bash
npm install axios dotenv jsonwebtoken
```

### Step 1: Setup Environment Variables

**.env file:**
```
SSO_PORTAL_URL=https://portal.hqmedan.com
SSO_CLIENT_ID=your-client-id-from-portal
SSO_CLIENT_SECRET=your-client-secret-from-portal
SSO_CALLBACK_URL=https://hrms.hqmedan.com/sso/callback
JWT_SECRET=your-app-jwt-secret
```

### Step 2: Create SSO Helper Module

**src/utils/sso.utils.js:**
```javascript
const axios = require('axios');
const jwt = require('jsonwebtoken');

const SSO_PORTAL = process.env.SSO_PORTAL_URL;
const CLIENT_ID = process.env.SSO_CLIENT_ID;
const CLIENT_SECRET = process.env.SSO_CLIENT_SECRET;

// Generate login URL untuk redirect ke SSO Portal
function getLoginUrl(redirectUri) {
  const params = new URLSearchParams({
    app: CLIENT_ID,
    redirect: redirectUri,
  });
  return `${SSO_PORTAL}/api/sso/authorize?${params.toString()}`;
}

// Validate SSO token dan ambil user data
async function validateToken(ssoToken) {
  try {
    const response = await axios.post(
      `${SSO_PORTAL}/api/sso/validate`,
      {
        sso_token: ssoToken,
        client_id: CLIENT_ID,
        client_secret: CLIENT_SECRET,
      },
      {
        headers: { 'Content-Type': 'application/json' },
        timeout: 5000,
      }
    );

    if (response.data.valid) {
      return response.data;
    } else {
      throw new Error('Invalid SSO token');
    }
  } catch (error) {
    console.error('SSO validation error:', error.message);
    throw error;
  }
}

// Generate local JWT token
function generateLocalToken(user) {
  return jwt.sign(
    {
      id: user.id,
      email: user.email,
      username: user.username,
      role: user.role,
    },
    process.env.JWT_SECRET,
    { expiresIn: '24h' }
  );
}

module.exports = {
  getLoginUrl,
  validateToken,
  generateLocalToken,
};
```

### Step 3: Create Routes

**src/routes/auth.routes.js:**
```javascript
const express = require('express');
const sso = require('../utils/sso.utils');
const router = express.Router();

// Login - Redirect ke SSO Portal
router.get('/login', (req, res) => {
  const redirectUri = encodeURIComponent(process.env.SSO_CALLBACK_URL);
  const loginUrl = sso.getLoginUrl(redirectUri);
  res.redirect(loginUrl);
});

// Callback dari SSO Portal
router.get('/callback', async (req, res) => {
  try {
    const { sso_token } = req.query;

    if (!sso_token) {
      return res.status(400).json({ error: 'Missing SSO token' });
    }

    // Validate token dengan SSO Portal
    const { user, token: ssoToken } = await sso.validateToken(sso_token);

    // Generate local JWT token
    const localToken = sso.generateLocalToken(user);

    // Save session (gunakan express-session atau custom)
    req.session.user = user;
    req.session.token = localToken;

    // Redirect ke dashboard
    res.redirect('/dashboard');
  } catch (error) {
    console.error('SSO callback error:', error);
    res.redirect(`/login?error=${encodeURIComponent(error.message)}`);
  }
});

// Logout
router.get('/logout', (req, res) => {
  req.session.destroy(() => {
    res.redirect('/');
  });
});

module.exports = router;
```

### Step 4: Create Middleware untuk Protect Routes

**src/middleware/auth.middleware.js:**
```javascript
function requireAuth(req, res, next) {
  if (!req.session.user) {
    return res.redirect('/auth/login');
  }
  next();
}

function requireAdmin(req, res, next) {
  if (!req.session.user || req.session.user.role !== 'ADMIN') {
    return res.status(403).json({ error: 'Unauthorized' });
  }
  next();
}

module.exports = {
  requireAuth,
  requireAdmin,
};
```

### Step 5: Use di Express App

**src/app.js:**
```javascript
const authRoutes = require('./routes/auth.routes');
const { requireAuth } = require('./middleware/auth.middleware');

// Auth routes
app.use('/auth', authRoutes);

// Protected routes
app.get('/dashboard', requireAuth, (req, res) => {
  res.json({
    message: 'Welcome ' + req.session.user.fullName,
    user: req.session.user,
  });
});

// API yang membutuhkan auth
app.get('/api/user/profile', requireAuth, (req, res) => {
  res.json(req.session.user);
});
```

---

## 🔵 PHP Laravel Integration

### Installation

```bash
composer require guzzlehttp/guzzle firebase/jwt
```

### Step 1: Setup .env

**.env:**
```
SSO_PORTAL_URL=https://portal.hqmedan.com
SSO_CLIENT_ID=your-client-id
SSO_CLIENT_SECRET=your-client-secret
SSO_CALLBACK_URL=https://hrms.hqmedan.com/sso/callback
```

### Step 2: Create SSO Service

**app/Services/SsoService.php:**
```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class SsoService
{
    private $client;
    private $portalUrl;
    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        $this->client = new Client();
        $this->portalUrl = config('services.sso.portal_url');
        $this->clientId = config('services.sso.client_id');
        $this->clientSecret = config('services.sso.client_secret');
    }

    public function getLoginUrl($redirectUri)
    {
        $params = http_build_query([
            'app' => $this->clientId,
            'redirect' => $redirectUri,
        ]);

        return "{$this->portalUrl}/api/sso/authorize?{$params}";
    }

    public function validateToken($ssoToken)
    {
        try {
            $response = $this->client->post(
                "{$this->portalUrl}/api/sso/validate",
                [
                    'json' => [
                        'sso_token' => $ssoToken,
                        'client_id' => $this->clientId,
                        'client_secret' => $this->clientSecret,
                    ],
                ]
            );

            $data = json_decode($response->getBody(), true);

            if ($data['valid'] ?? false) {
                return $data;
            }

            throw new \Exception('Invalid SSO token');
        } catch (\Exception $e) {
            throw new \Exception('SSO validation failed: ' . $e->getMessage());
        }
    }

    public function generateLocalToken($user)
    {
        $payload = [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'exp' => time() + 86400, // 24 hours
        ];

        return JWT::encode($payload, config('app.key'), 'HS256');
    }
}
```

### Step 3: Create Routes

**routes/web.php:**
```php
Route::get('/auth/login', 'AuthController@login');
Route::get('/sso/callback', 'AuthController@callback');
Route::get('/auth/logout', 'AuthController@logout');
```

### Step 4: Create Controller

**app/Http/Controllers/AuthController.php:**
```php
<?php

namespace App\Http\Controllers;

use App\Services\SsoService;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    private $ssoService;

    public function __construct(SsoService $ssoService)
    {
        $this->ssoService = $ssoService;
    }

    public function login()
    {
        $redirectUri = config('services.sso.callback_url');
        $loginUrl = $this->ssoService->getLoginUrl($redirectUri);

        return redirect($loginUrl);
    }

    public function callback(Request $request)
    {
        try {
            $ssoToken = $request->query('sso_token');

            if (!$ssoToken) {
                throw new \Exception('Missing SSO token');
            }

            // Validate dengan SSO Portal
            $response = $this->ssoService->validateToken($ssoToken);
            $user = $response['user'];

            // Generate local JWT
            $localToken = $this->ssoService->generateLocalToken($user);

            // Save session
            Session::put('user', $user);
            Session::put('token', $localToken);

            return redirect('/dashboard');
        } catch (\Exception $e) {
            return redirect('/login?error=' . urlencode($e->getMessage()));
        }
    }

    public function logout()
    {
        Session::flush();
        return redirect('/');
    }
}
```

### Step 5: Create Middleware

**app/Http/Middleware/SsoAuth.php:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SsoAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('user')) {
            return redirect('/auth/login');
        }

        return $next($request);
    }
}
```

---

## 🟡 JavaScript/React Integration

### Installation

```bash
npm install axios js-cookie
```

### Step 1: Create SSO Service

**src/services/sso.js:**
```javascript
import axios from 'axios';
import Cookies from 'js-cookie';

const SSO_PORTAL = process.env.REACT_APP_SSO_PORTAL;
const CLIENT_ID = process.env.REACT_APP_SSO_CLIENT_ID;
const CLIENT_SECRET = process.env.REACT_APP_SSO_CLIENT_SECRET;
const CALLBACK_URL = process.env.REACT_APP_SSO_CALLBACK_URL;

class SSOService {
  getLoginUrl() {
    const params = new URLSearchParams({
      app: CLIENT_ID,
      redirect: CALLBACK_URL,
    });
    return `${SSO_PORTAL}/api/sso/authorize?${params.toString()}`;
  }

  async validateToken(ssoToken) {
    try {
      const response = await axios.post(
        `${SSO_PORTAL}/api/sso/validate`,
        {
          sso_token: ssoToken,
          client_id: CLIENT_ID,
          client_secret: CLIENT_SECRET,
        }
      );

      if (response.data.valid) {
        // Save user & token
        sessionStorage.setItem('user', JSON.stringify(response.data.user));
        Cookies.set('token', response.data.token, { secure: true });
        return response.data;
      }

      throw new Error('Invalid token');
    } catch (error) {
      console.error('Token validation failed:', error);
      throw error;
    }
  }

  getUser() {
    const user = sessionStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  }

  getToken() {
    return Cookies.get('token');
  }

  logout() {
    sessionStorage.removeItem('user');
    Cookies.remove('token');
  }

  isAuthenticated() {
    return !!this.getUser() && !!this.getToken();
  }
}

export default new SSOService();
```

### Step 2: Create Auth Context

**src/context/AuthContext.js:**
```javascript
import React, { createContext, useState, useEffect } from 'react';
import ssoService from '../services/sso';

export const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const storedUser = ssoService.getUser();
    if (storedUser) {
      setUser(storedUser);
    }
    setLoading(false);
  }, []);

  const login = () => {
    window.location.href = ssoService.getLoginUrl();
  };

  const logout = () => {
    ssoService.logout();
    setUser(null);
    window.location.href = '/';
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}
```

### Step 3: Create Protected Route Component

**src/components/ProtectedRoute.js:**
```javascript
import React, { useContext } from 'react';
import { Navigate } from 'react-router-dom';
import { AuthContext } from '../context/AuthContext';

export function ProtectedRoute({ children }) {
  const { user, loading } = useContext(AuthContext);

  if (loading) return <div>Loading...</div>;

  if (!user) {
    return <Navigate to="/login" replace />;
  }

  return children;
}
```

### Step 4: Setup SSO Callback Page

**src/pages/SsoCallback.js:**
```javascript
import { useEffect } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import ssoService from '../services/sso';

export function SsoCallback() {
  const navigate = useNavigate();
  const [searchParams] = useSearchParams();

  useEffect(() => {
    const ssoToken = searchParams.get('sso_token');

    if (!ssoToken) {
      navigate('/login?error=missing_token');
      return;
    }

    ssoService
      .validateToken(ssoToken)
      .then(() => {
        navigate('/dashboard');
      })
      .catch((error) => {
        navigate(`/login?error=${encodeURIComponent(error.message)}`);
      });
  }, [searchParams, navigate]);

  return <div>Verifying SSO token...</div>;
}
```

### Step 5: Setup Routes

**src/App.js:**
```javascript
import { BrowserRouter, Routes, Route } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext';
import { ProtectedRoute } from './components/ProtectedRoute';
import Login from './pages/Login';
import SsoCallback from './pages/SsoCallback';
import Dashboard from './pages/Dashboard';

function App() {
  return (
    <BrowserRouter>
      <AuthProvider>
        <Routes>
          <Route path="/login" element={<Login />} />
          <Route path="/sso/callback" element={<SsoCallback />} />
          <Route
            path="/dashboard"
            element={
              <ProtectedRoute>
                <Dashboard />
              </ProtectedRoute>
            }
          />
        </Routes>
      </AuthProvider>
    </BrowserRouter>
  );
}

export default App;
```

---

## ✅ Test Integration

### Test Checklist

- [ ] Login redirect ke SSO Portal berhasil
- [ ] Username & password login di SSO Portal
- [ ] Redirect kembali ke aplikasi dengan sso_token
- [ ] Token validation berhasil
- [ ] User data tersimpan di session/storage
- [ ] Protected routes hanya accessible untuk authenticated users
- [ ] Logout menghapus session/token
- [ ] Re-login berfungsi dengan baik

### Test Script

```bash
# Test 1: Akses aplikasi tanpa login
curl -I https://hrms.hqmedan.com/dashboard
# Harusnya redirect ke /auth/login

# Test 2: Login URL
curl -I "https://hrms.hqmedan.com/auth/login"
# Harusnya redirect ke SSO Portal

# Test 3: Token validation
curl -X POST https://portal.hqmedan.com/api/sso/validate \
  -H "Content-Type: application/json" \
  -d '{
    "sso_token": "test_token",
    "client_id": "your-client-id",
    "client_secret": "your-client-secret"
  }'
```

---

## 🔧 Troubleshooting

### 1. "Invalid SSO token"

**Penyebab**:
- Client ID/Secret salah
- Token sudah expired
- Token invalid/corrupted

**Solusi**:
```bash
# Verify credentials di SSO Portal admin
# Re-generate token di SSO Portal

# Check logs
tail -100 /www/wwwroot/portal.hqmedan.com/logs/pm2.log | grep -i "token\|error"
```

### 2. Callback URL tidak match

**Penyebab**:
- URL di .env beda dengan registered callback URL

**Solusi**:
```bash
# Update di SSO Portal Admin:
# Applications → Your App → Edit → Callback URLs

# Pastikan URL exact match:
# https://hrms.hqmedan.com/sso/callback (with https!)
```

### 3. CORS Error

**Penyebab**:
- CORS_ORIGIN di SSO Portal belum include aplikasi Anda

**Solusi**:
```bash
# Di VPS, edit .env SSO Portal:
nano /www/wwwroot/portal.hqmedan.com/.env
# Update CORS_ORIGIN:
# CORS_ORIGIN=https://portal.hqmedan.com,https://hrms.hqmedan.com,https://other-app.hqmedan.com

# Restart
pm2 restart sso-portal
```

### 4. Session tidak persist

**Solusi**:
- Pastikan secure cookies configured
- Check cookie domain settings
- Verify HTTPS enabled

---

## 📚 Complete Example

Lihat folder `/examples` untuk complete integration examples:
- Node.js Express → `/examples/express-integration/`
- Laravel → `/examples/laravel-integration/`
- React → `/examples/react-integration/`

---

**Status**: ✅ Integration Guide Complete!

Semoga integrasi aplikasi Anda ke SSO Portal berhasil! 🎉
