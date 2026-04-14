const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const { prisma } = require('../database/client');
const { generateAccessToken, generateRefreshToken, verifyRefreshToken } = require('../utils/jwt.utils');
const { createAuditLog } = require('../utils/audit.utils');
const { sendPasswordResetEmail } = require('../utils/email.utils');

/**
 * POST /api/auth/login
 */
async function login(req, res) {
  try {
    const { identifier, password } = req.body;

    if (!identifier || !password) {
      return res.status(400).json({ error: 'Username dan password wajib diisi.' });
    }

    // Cari user berdasarkan username
    const user = await prisma.user.findFirst({
      where: {
        username: identifier.toLowerCase()
      }
    });

    if (!user) {
      return res.status(401).json({ error: 'Username atau password salah.' });
    }

    if (!user.isActive) {
      return res.status(401).json({ error: 'Akun Anda telah dinonaktifkan. Hubungi administrator.' });
    }

    // Verifikasi password
    const isPasswordValid = await bcrypt.compare(password, user.password);
    if (!isPasswordValid) {
      await createAuditLog({
        userId: user.id,
        action: 'LOGIN_FAILED',
        details: { reason: 'wrong_password' },
        req
      });
      return res.status(401).json({ error: 'Username atau password salah.' });
    }

    // Generate tokens
    const tokenPayload = {
      userId: user.id,
      role: user.role,
    };

    const accessToken = generateAccessToken(tokenPayload);
    const refreshToken = generateRefreshToken({ userId: user.id });

    // Simpan session ke database
    const expiresAt = new Date();
    expiresAt.setDate(expiresAt.getDate() + 7);

    await prisma.userSession.create({
      data: {
        userId: user.id,
        token: refreshToken,
        ipAddress: req.ip,
        userAgent: req.headers['user-agent'],
        expiresAt,
      }
    });

    // Update last login
    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() }
    });

    // Audit log
    await createAuditLog({
      userId: user.id,
      action: 'LOGIN_SUCCESS',
      req
    });

    // Set cookie
    const cookieOptions = {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: process.env.NODE_ENV === 'production' ? 'strict' : 'lax',
      maxAge: 24 * 60 * 60 * 1000, // 24 jam
    };

    res.cookie('access_token', accessToken, cookieOptions);
    res.cookie('refresh_token', refreshToken, {
      ...cookieOptions,
      maxAge: 7 * 24 * 60 * 60 * 1000, // 7 hari
    });

    return res.json({
      message: 'Login berhasil',
      user: {
        id: user.id,
        username: user.username,
        fullName: user.fullName,
        role: user.role,
        avatar: user.avatar,
      },
      accessToken,
    });

  } catch (error) {
    console.error('Login error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/auth/logout
 */
async function logout(req, res) {
  try {
    const refreshToken = req.cookies?.refresh_token;

    if (refreshToken) {
      // Invalidate session di database
      await prisma.userSession.updateMany({
        where: { token: refreshToken },
        data: { isValid: false }
      });
    }

    if (req.user) {
      await createAuditLog({
        userId: req.user.id,
        action: 'LOGOUT',
        req
      });
    }

    const clearOptions = {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: process.env.NODE_ENV === 'production' ? 'strict' : 'lax',
    };
    res.clearCookie('access_token', clearOptions);
    res.clearCookie('refresh_token', clearOptions);

    return res.json({ message: 'Logout berhasil' });
  } catch (error) {
    console.error('Logout error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/auth/refresh
 */
async function refreshToken(req, res) {
  try {
    const token = req.cookies?.refresh_token
      || req.body?.refreshToken;

    if (!token) {
      return res.status(401).json({ error: 'Refresh token tidak ditemukan.' });
    }

    // Verifikasi refresh token
    const decoded = verifyRefreshToken(token);

    // Cek session di database
    const session = await prisma.userSession.findFirst({
      where: {
        token,
        isValid: true,
        expiresAt: { gt: new Date() }
      },
      include: {
        user: {
          select: {
            id: true,
            role: true,
            isActive: true,
          }
        }
      }
    });

    if (!session || !session.user.isActive) {
      return res.status(401).json({ error: 'Session tidak valid.' });
    }

    // Generate access token baru
    const newAccessToken = generateAccessToken({
      userId: session.user.id,
      role: session.user.role,
    });

    res.cookie('access_token', newAccessToken, {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: process.env.NODE_ENV === 'production' ? 'strict' : 'lax',
      maxAge: 24 * 60 * 60 * 1000,
    });

    return res.json({ message: 'Token diperbarui', accessToken: newAccessToken });
  } catch (error) {
    return res.status(401).json({ error: 'Refresh token tidak valid atau kadaluarsa.' });
  }
}

/**
 * GET /api/auth/me
 */
async function getProfile(req, res) {
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.user.id },
      select: {
        id: true,
        username: true,
        fullName: true,
        role: true,
        avatar: true,
        isVerified: true,
        lastLoginAt: true,
        createdAt: true,
      }
    });

    return res.json({ user });
  } catch (error) {
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * PUT /api/auth/change-password
 */
async function changePassword(req, res) {
  try {
    const { currentPassword, newPassword } = req.body;

    if (!currentPassword || !newPassword) {
      return res.status(400).json({ error: 'Password lama dan baru wajib diisi.' });
    }

    if (newPassword.length < 8) {
      return res.status(400).json({ error: 'Password baru minimal 8 karakter.' });
    }

    const user = await prisma.user.findUnique({ where: { id: req.user.id } });

    const isValid = await bcrypt.compare(currentPassword, user.password);
    if (!isValid) {
      return res.status(400).json({ error: 'Password lama tidak benar.' });
    }

    const hashedPassword = await bcrypt.hash(newPassword, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    await prisma.user.update({
      where: { id: req.user.id },
      data: { password: hashedPassword }
    });

    // Invalidate semua session lain
    await prisma.userSession.updateMany({
      where: { userId: req.user.id },
      data: { isValid: false }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'PASSWORD_CHANGED',
      req
    });

    const clearOptions = {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: process.env.NODE_ENV === 'production' ? 'strict' : 'lax',
    };
    res.clearCookie('access_token', clearOptions);
    res.clearCookie('refresh_token', clearOptions);

    return res.json({ message: 'Password berhasil diubah. Silakan login kembali.' });
  } catch (error) {
    console.error('Change password error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/auth/forgot-password
 */
async function forgotPassword(req, res) {
  try {
    const { email } = req.body;

    if (!email) {
      return res.status(400).json({ error: 'Email wajib diisi.' });
    }

    const user = await prisma.user.findUnique({
      where: { email: email.toLowerCase() }
    });

    // Selalu response OK untuk keamanan (tidak memberitahu apakah email terdaftar)
    if (!user) {
      return res.json({ message: 'Jika email terdaftar, link reset akan dikirim.' });
    }

    // Generate reset token
    const resetToken = uuidv4();
    const expiresAt = new Date();
    expiresAt.setHours(expiresAt.getHours() + 1);

    await prisma.passwordReset.create({
      data: {
        userId: user.id,
        token: resetToken,
        expiresAt,
      }
    });

    // Kirim email
    await sendPasswordResetEmail(user.email, user.fullName, resetToken);

    await createAuditLog({
      userId: user.id,
      action: 'PASSWORD_RESET_REQUESTED',
      req
    });

    return res.json({ message: 'Jika email terdaftar, link reset akan dikirim.' });
  } catch (error) {
    console.error('Forgot password error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/auth/reset-password
 */
async function resetPassword(req, res) {
  try {
    const { token, newPassword } = req.body;

    if (!token || !newPassword) {
      return res.status(400).json({ error: 'Token dan password baru wajib diisi.' });
    }

    if (newPassword.length < 8) {
      return res.status(400).json({ error: 'Password minimal 8 karakter.' });
    }

    const resetRecord = await prisma.passwordReset.findFirst({
      where: {
        token,
        isUsed: false,
        expiresAt: { gt: new Date() }
      },
      include: { user: true }
    });

    if (!resetRecord) {
      return res.status(400).json({ error: 'Token tidak valid atau sudah kadaluarsa.' });
    }

    const hashedPassword = await bcrypt.hash(newPassword, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    await prisma.$transaction([
      prisma.user.update({
        where: { id: resetRecord.userId },
        data: { password: hashedPassword }
      }),
      prisma.passwordReset.update({
        where: { id: resetRecord.id },
        data: { isUsed: true }
      }),
      prisma.userSession.updateMany({
        where: { userId: resetRecord.userId },
        data: { isValid: false }
      })
    ]);

    await createAuditLog({
      userId: resetRecord.userId,
      action: 'PASSWORD_RESET_SUCCESS',
      req
    });

    return res.json({ message: 'Password berhasil direset. Silakan login.' });
  } catch (error) {
    console.error('Reset password error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/auth/request-password-reset
 * User input username → buat request ke SUPERADMIN
 */
async function requestPasswordReset(req, res) {
  try {
    const { username } = req.body;

    if (!username || !username.trim()) {
      return res.status(400).json({ error: 'Username wajib diisi.' });
    }

    const user = await prisma.user.findUnique({
      where: { username: username.trim().toLowerCase() },
      select: { id: true, username: true, role: true, isActive: true }
    });

    // Username tidak ditemukan
    if (!user) {
      return res.status(404).json({ error: `Username "${username.trim()}" tidak ditemukan. Periksa kembali username Anda.` });
    }

    // Akun tidak aktif
    if (!user.isActive) {
      return res.status(403).json({ error: 'Akun Anda telah dinonaktifkan. Hubungi administrator secara langsung.' });
    }

    // SUPERADMIN tidak bisa request reset via form ini
    if (user.role === 'SUPERADMIN') {
      return res.status(403).json({ error: 'Username tidak dapat melakukan reset password melalui form ini.' });
    }

    // Cek apakah sudah ada request PENDING
    const existing = await prisma.passwordResetRequest.findFirst({
      where: { userId: user.id, status: 'PENDING' }
    });

    if (existing) {
      return res.json({ message: 'Permintaan reset password Anda sedang dalam antrian. Mohon tunggu persetujuan administrator.' });
    }

    await prisma.passwordResetRequest.create({
      data: {
        userId: user.id,
        status: 'PENDING',
      }
    });

    return res.json({ message: 'Permintaan reset password berhasil dikirim. Administrator akan memproses dalam waktu dekat.' });
  } catch (error) {
    console.error('Request password reset error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

module.exports = {
  login,
  logout,
  refreshToken,
  getProfile,
  changePassword,
  forgotPassword,
  resetPassword,
  requestPasswordReset,
};
