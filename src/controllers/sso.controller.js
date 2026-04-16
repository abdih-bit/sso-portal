const { v4: uuidv4 } = require('uuid');
const { prisma } = require('../database/client');
const { generateAccessToken } = require('../utils/jwt.utils');
const { createAuditLog } = require('../utils/audit.utils');

/**
 * GET /api/sso/authorize?app=<slug>&redirect=<url>
 * 
 * Alur SSO:
 * 1. Aplikasi redirect user ke portal SSO dengan parameter app & redirect URL
 * 2. Portal SSO cek apakah user sudah login
 * 3. Jika sudah login, generate SSO token dan redirect kembali ke aplikasi
 * 4. Aplikasi validasi SSO token ke portal
 */
async function authorize(req, res) {
  try {
    const { app: appSlug, redirect } = req.query;

    if (!appSlug) {
      return res.status(400).json({ error: 'Parameter app wajib diisi.' });
    }

    // Cari aplikasi
    const application = await prisma.application.findUnique({
      where: { slug: appSlug, isActive: true }
    });

    if (!application) {
      return res.status(404).json({ error: 'Aplikasi tidak ditemukan.' });
    }

    // Validasi redirect URL (harus berasal dari origin yang sama dengan callbackUrl)
    const redirectUrl = redirect || application.callbackUrl;
    const allowedOrigin = new URL(application.callbackUrl).origin;
    const redirectOrigin = new URL(redirectUrl).origin;
    if (allowedOrigin !== redirectOrigin) {
      return res.status(400).json({ error: 'Redirect URL tidak diizinkan.' });
    }

    // Cek apakah user sudah login
    const token = req.cookies?.access_token;
    if (!token) {
      // Redirect ke halaman login dengan parameter return
      const loginUrl = `/login?app=${appSlug}&redirect=${encodeURIComponent(redirectUrl)}`;
      return res.redirect(loginUrl);
    }

    // Verifikasi token user
    const { verifyAccessToken } = require('../utils/jwt.utils');
    let decoded;
    try {
      decoded = verifyAccessToken(token);
    } catch {
      return res.redirect(`/login?app=${appSlug}&redirect=${encodeURIComponent(redirectUrl)}`);
    }

    const user = await prisma.user.findUnique({
      where: { id: decoded.userId }
    });

    if (!user || !user.isActive) {
      return res.redirect(`/login?app=${appSlug}&redirect=${encodeURIComponent(redirectUrl)}`);
    }

    // Cek role permission — SUPERADMIN bypass semua restriksi role
    if (user.role !== 'SUPERADMIN' && !application.allowedRoles.includes(user.role)) {
      return res.status(403).sendFile(require('path').join(__dirname, '../../public/403.html'));
    }

    // Generate SSO token (one-time use)
    const ssoToken = uuidv4();
    const expiresAt = new Date();
    expiresAt.setSeconds(expiresAt.getSeconds() + (parseInt(process.env.SSO_TOKEN_EXPIRES) || 300));

    await prisma.sSOToken.create({
      data: {
        token: ssoToken,
        userId: user.id,
        applicationId: application.id,
        expiresAt,
      }
    });

    await createAuditLog({
      userId: user.id,
      action: 'SSO_TOKEN_GENERATED',
      resource: application.name,
      req
    });

    // Redirect ke aplikasi dengan SSO token
    const separator = redirectUrl.includes('?') ? '&' : '?';
    return res.redirect(`${redirectUrl}${separator}sso_token=${ssoToken}`);

  } catch (error) {
    console.error('SSO authorize error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/sso/validate
 * 
 * Dipanggil oleh aplikasi untuk validasi SSO token
 * Body: { sso_token, client_id, client_secret }
 */
async function validateToken(req, res) {
  try {
    const { sso_token, client_id, client_secret } = req.body;

    if (!sso_token || !client_id || !client_secret) {
      return res.status(400).json({ error: 'sso_token, client_id, dan client_secret wajib diisi.' });
    }

    // Verifikasi aplikasi
    const application = await prisma.application.findFirst({
      where: {
        clientId: client_id,
        clientSecret: client_secret,
        isActive: true
      }
    });

    if (!application) {
      return res.status(401).json({ error: 'Client credentials tidak valid.' });
    }

    // Cari dan validasi SSO token
    const ssoRecord = await prisma.sSOToken.findFirst({
      where: {
        token: sso_token,
        applicationId: application.id,
        isUsed: false,
        expiresAt: { gt: new Date() }
      },
      include: {
        user: {
          select: {
            id: true,
            username: true,
            fullName: true,
            role: true,
            avatar: true,
            isActive: true,
            jabatan: true,
            divisi: true,
          }
        }
      }
    });

    if (!ssoRecord || !ssoRecord.user.isActive) {
      return res.status(401).json({ error: 'SSO token tidak valid atau sudah kadaluarsa.' });
    }

    // Mark token as used (one-time use)
    await prisma.sSOToken.update({
      where: { id: ssoRecord.id },
      data: { isUsed: true }
    });

    // Generate JWT untuk aplikasi tersebut
    const appJwt = generateAccessToken({
      userId: ssoRecord.user.id,
      role: ssoRecord.user.role,
      app: application.slug,
    });

    await createAuditLog({
      userId: ssoRecord.user.id,
      action: 'SSO_LOGIN_SUCCESS',
      resource: application.name,
      req
    });

    return res.json({
      valid: true,
      user: ssoRecord.user,
      token: appJwt,
    });

  } catch (error) {
    console.error('SSO validate error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/sso/apps
 * Daftar aplikasi yang bisa diakses user (berdasarkan role)
 */
async function getAccessibleApps(req, res) {
  try {
    // SUPERADMIN dapat melihat semua aplikasi tanpa filter role
    const roleFilter = req.user.role === 'SUPERADMIN'
      ? {}
      : { allowedRoles: { has: req.user.role } };

    const applications = await prisma.application.findMany({
      where: {
        isActive: true,
        ...roleFilter
      },
      select: {
        id: true,
        name: true,
        slug: true,
        description: true,
        url: true,
        logoUrl: true,
        sortOrder: true,
      },
      orderBy: { sortOrder: 'asc' }
    });

    return res.json({ applications });
  } catch (error) {
    console.error('Get apps error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

module.exports = { authorize, validateToken, getAccessibleApps };
