const { verifyAccessToken } = require('../utils/jwt.utils');
const { prisma } = require('../database/client');

/**
 * Middleware: Verifikasi JWT Token dari cookie atau header
 */
async function authenticate(req, res, next) {
  try {
    // Ambil token dari cookie atau Authorization header
    const token = req.cookies?.access_token
      || req.headers.authorization?.replace('Bearer ', '');

    if (!token) {
      return res.status(401).json({ error: 'Akses ditolak. Token tidak ditemukan.' });
    }

    // Verifikasi token
    const decoded = verifyAccessToken(token);

    // Cek apakah user masih aktif di database
    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      select: {
        id: true,
        email: true,
        username: true,
        fullName: true,
        role: true,
        isActive: true,
        avatar: true,
        jabatan: true,
        pt: true,
        divisi: true,
      }
    });

    if (!user || !user.isActive) {
      return res.status(401).json({ error: 'Akun tidak ditemukan atau tidak aktif.' });
    }

    req.user = user;
    next();
  } catch (error) {
    if (error.name === 'TokenExpiredError') {
      return res.status(401).json({ error: 'Token kadaluarsa. Silakan login kembali.', code: 'TOKEN_EXPIRED' });
    }
    if (error.name === 'JsonWebTokenError') {
      return res.status(401).json({ error: 'Token tidak valid.', code: 'INVALID_TOKEN' });
    }
    next(error);
  }
}

/**
 * Middleware: Cek apakah user sudah login (untuk halaman web, bukan API)
 * Redirect ke login jika belum
 */
async function requireAuth(req, res, next) {
  try {
    const token = req.cookies?.access_token;

    if (!token) {
      return res.redirect(`/login?redirect=${encodeURIComponent(req.originalUrl)}`);
    }

    const decoded = verifyAccessToken(token);

    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      select: {
        id: true,
        email: true,
        username: true,
        fullName: true,
        role: true,
        isActive: true,
        avatar: true,
        jabatan: true,
        pt: true,
        divisi: true,
      }
    });

    if (!user || !user.isActive) {
      res.clearCookie('access_token');
      return res.redirect('/login');
    }

    req.user = user;
    next();
  } catch (error) {
    res.clearCookie('access_token');
    res.redirect('/login');
  }
}

/**
 * Middleware: Cek apakah sudah login (untuk redirect ke dashboard)
 */
async function redirectIfAuthenticated(req, res, next) {
  try {
    const token = req.cookies?.access_token;
    if (!token) return next();

    verifyAccessToken(token);
    return res.redirect('/dashboard');
  } catch {
    next();
  }
}

/**
 * Middleware: Role-based access control
 */
function requireRole(...roles) {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Unauthorized' });
    }

    if (!roles.includes(req.user.role)) {
      return res.status(403).json({
        error: 'Akses ditolak. Anda tidak memiliki izin untuk melakukan aksi ini.'
      });
    }

    next();
  };
}

module.exports = {
  authenticate,
  requireAuth,
  redirectIfAuthenticated,
  requireRole,
};
