const express = require('express');
const router = express.Router();
const ssoController = require('../controllers/sso.controller');
const { authenticate } = require('../middleware/auth.middleware');

// SSO Authorize - dipanggil dari browser (redirect)
router.get('/authorize', ssoController.authorize);

// SSO Validate Token - dipanggil oleh aplikasi (server-to-server)
router.post('/validate', ssoController.validateToken);

// Daftar aplikasi yang bisa diakses user
router.get('/apps', authenticate, ssoController.getAccessibleApps);

module.exports = router;
