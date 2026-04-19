<?php
// =============================================================
// Konfigurasi Aplikasi Serah Terima Laporan (STL)
// Isi nilai di bawah sesuai dengan konfigurasi server Anda.
// =============================================================

// URL internal SSO Portal (server-to-server, tidak perlu HTTPS)
// Gunakan localhost karena PHP dan Node.js berada di server yang sama
define('SSO_INTERNAL_URL', 'http://localhost:3000');

// Client ID & Secret dari SSO Portal
// Dapatkan setelah mendaftarkan aplikasi di: Admin Panel → Kelola Aplikasi
define('SSO_CLIENT_ID',     'ISI_CLIENT_ID_DARI_ADMIN_PANEL');
define('SSO_CLIENT_SECRET', 'ISI_CLIENT_SECRET_DARI_ADMIN_PANEL');

// URL publik SSO Portal (untuk redirect login dari browser)
define('SSO_PUBLIC_URL', 'https://portal.hqmedan.com');

// Slug aplikasi yang didaftarkan di SSO Portal
define('SSO_APP_SLUG', 'serah-terima');

// Koneksi PostgreSQL — sama dengan yang digunakan SSO Portal
// Format: pgsql:host=HOST;port=PORT;dbname=DBNAME
define('DB_DSN',      'pgsql:host=localhost;port=5433;dbname=sso_portal');
define('DB_USERNAME', 'ISI_USERNAME_POSTGRES');
define('DB_PASSWORD', 'ISI_PASSWORD_POSTGRES');

// Konfigurasi session PHP
define('SESSION_NAME',     'STL_SESSION');
define('SESSION_LIFETIME', 86400); // 24 jam
