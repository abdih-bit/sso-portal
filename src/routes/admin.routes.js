const express = require('express');
const router = express.Router();
const adminController = require('../controllers/admin.controller');
const { authenticate, requireRole } = require('../middleware/auth.middleware');

// Semua route admin memerlukan autentikasi dan role ADMIN/SUPERADMIN
router.use(authenticate, requireRole('ADMIN', 'SUPERADMIN'));

// Statistics
router.get('/stats', adminController.getStats);

// Users
router.get('/users', adminController.getUsers);
router.post('/users', adminController.createUser);
router.put('/users/:id', adminController.updateUser);
router.delete('/users/:id', adminController.deleteUser);

// Applications
router.get('/applications', adminController.getApplications);
router.post('/applications', adminController.createApplication);
router.put('/applications/:id', adminController.updateApplication);
router.patch('/applications/:id/toggle', requireRole('SUPERADMIN'), adminController.toggleApplicationActive);
router.delete('/applications/:id', requireRole('SUPERADMIN'), adminController.deleteApplication);

// Audit Logs
router.get('/audit-logs', adminController.getAuditLogs);

// Password Reset Requests (hanya SUPERADMIN)
router.get('/password-reset-requests', requireRole('SUPERADMIN'), adminController.getPasswordResetRequests);
router.post('/password-reset-requests/:id/approve', requireRole('SUPERADMIN'), adminController.approvePasswordReset);
router.post('/password-reset-requests/:id/reject', requireRole('SUPERADMIN'), adminController.rejectPasswordReset);

// Areas
router.get('/areas', adminController.getAreas);
router.post('/areas', requireRole('SUPERADMIN'), adminController.createArea);
router.put('/areas/:id', requireRole('SUPERADMIN'), adminController.updateArea);
router.delete('/areas/:id', requireRole('SUPERADMIN'), adminController.deleteArea);

// Sales Offices
router.get('/sales-offices', adminController.getSalesOffices);
router.post('/sales-offices', requireRole('SUPERADMIN'), adminController.createSalesOffice);
router.put('/sales-offices/:id', requireRole('SUPERADMIN'), adminController.updateSalesOffice);
router.delete('/sales-offices/:id', requireRole('SUPERADMIN'), adminController.deleteSalesOffice);

module.exports = router;
