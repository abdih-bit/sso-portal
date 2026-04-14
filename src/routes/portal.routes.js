const express = require('express');
const router = express.Router();
const path = require('path');
const { requireAuth, redirectIfAuthenticated } = require('../middleware/auth.middleware');

// Login page - redirect ke dashboard jika sudah login
router.get('/login', redirectIfAuthenticated, (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/login.html'));
});

// Reset password page
router.get('/reset-password', (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/reset-password.html'));
});

// Dashboard / Portal utama - harus login
router.get('/', requireAuth, (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/dashboard.html'));
});

router.get('/dashboard', requireAuth, (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/dashboard.html'));
});

// Halaman profile
router.get('/profile', requireAuth, (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/profile.html'));
});

// Halaman admin panel
router.get('/admin', requireAuth, (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/admin.html'));
});

// Halaman manajemen user (ADMIN & SUPERADMIN)
router.get('/users', requireAuth, (req, res) => {
  res.sendFile(path.join(__dirname, '../../public/users.html'));
});

module.exports = router;
