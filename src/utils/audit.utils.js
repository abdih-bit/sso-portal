const { prisma } = require('../database/client');

/**
 * Catat audit log
 */
async function createAuditLog({ userId, action, resource, details, req }) {
  try {
    await prisma.auditLog.create({
      data: {
        userId: userId || null,
        action,
        resource: resource || null,
        details: details || null,
        ipAddress: req ? getClientIP(req) : null,
        userAgent: req ? req.headers['user-agent'] : null,
      }
    });
  } catch (error) {
    // Jangan sampai gagal audit log menghentikan proses utama
    console.error('Audit log error:', error);
  }
}

/**
 * Ambil IP client yang sebenarnya
 */
function getClientIP(req) {
  return req.headers['x-forwarded-for']?.split(',')[0]?.trim()
    || req.headers['x-real-ip']
    || req.connection?.remoteAddress
    || req.ip
    || 'unknown';
}

module.exports = { createAuditLog, getClientIP };
