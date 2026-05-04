<?php
// =============================================================
// Konfigurasi Aplikasi ARsync Portal
// Isi nilai di bawah sesuai dengan konfigurasi server Anda.
// =============================================================

// URL internal SSO Portal (server-to-server, tidak perlu HTTPS)
// Gunakan localhost karena PHP dan Node.js berada di server yang sama
define('SSO_INTERNAL_URL', 'http://localhost:3000');

// Client ID & Secret dari SSO Portal
// Di-generate oleh setup-apps.js
define('SSO_CLIENT_ID',     'f4e3d2c1-b0a9-4f8e-7d6c-5b4a3f2e1d0c');
define('SSO_CLIENT_SECRET', 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d');

// URL publik SSO Portal (untuk redirect login dari browser)
define('SSO_PUBLIC_URL', 'https://portal.hqmedan.com');

// Slug aplikasi yang didaftarkan di SSO Portal
define('SSO_APP_SLUG', 'arsync');

// Koneksi PostgreSQL — shared dengan SSO Portal
// Format: pgsql:host=HOST;port=PORT;dbname=DBNAME
define('DB_DSN',      'pgsql:host=localhost;port=5433;dbname=sso_portal');
define('DB_USERNAME', 'sso_user');
define('DB_PASSWORD', 'P@ssW0rd!!');

// Konfigurasi session PHP
define('SESSION_NAME',     'ARSYNC_SESSION');
define('SESSION_LIFETIME', 86400); // 24 jam
