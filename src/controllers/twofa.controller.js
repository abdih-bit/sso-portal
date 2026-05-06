const speakeasy = require('speakeasy');
const QRCode    = require('qrcode');
const jwt       = require('jsonwebtoken');
const { v4: uuidv4 } = require('uuid');
const { prisma }     = require('../database/client');
const { createAuditLog } = require('../utils/audit.utils');

const APP_NAME = 'Portal HQ Medan';
const DEVICE_COOKIE = '_2fa_dev';
const DEVICE_TTL_MS = 24 * 60 * 60 * 1000; // 24 jam

/**
 * POST /api/auth/2fa/setup
 * Hanya SUPERADMIN. Generate secret + QR data URL (belum disimpan ke DB).
 */
async function setup(req, res) {
  try {
    if (req.user.role !== 'SUPERADMIN') {
      return res.status(403).json({ error: 'Hanya SUPERADMIN yang dapat mengaktifkan 2FA.' });
    }

    const secret = speakeasy.generateSecret({
      name: `${APP_NAME} (${req.user.username})`,
      length: 20,
    });

    // Buat QR code sebagai data URL (base64 PNG) agar bisa langsung di-embed
    const qrDataUrl = await QRCode.toDataURL(secret.otpauth_url);

    return res.json({
      secret: secret.base32,
      qr: qrDataUrl,
    });
  } catch (err) {
    console.error('2FA setup error:', err);
    res.status(500).json({ error: 'Gagal generate 2FA setup.' });
  }
}

/**
 * POST /api/auth/2fa/confirm
 * Body: { secret, otp_code }
 * Verifikasi OTP dari QR yang baru di-setup, lalu simpan secret ke DB.
 */
async function confirm(req, res) {
  try {
    if (req.user.role !== 'SUPERADMIN') {
      return res.status(403).json({ error: 'Hanya SUPERADMIN.' });
    }

    const { secret, otp_code } = req.body;
    if (!secret || !otp_code) {
      return res.status(400).json({ error: 'secret dan otp_code wajib diisi.' });
    }

    const valid = speakeasy.totp.verify({
      secret,
      encoding: 'base32',
      token: String(otp_code).replace(/\s/g, ''),
      window: 1,
    });

    if (!valid) {
      return res.status(400).json({ error: 'Kode OTP tidak valid. Pastikan waktu HP Anda sudah sinkron.' });
    }

    await prisma.user.update({
      where: { id: req.user.id },
      data: { totpSecret: secret, totpEnabled: true },
    });

    // Hapus semua trusted device lama — setelah aktifkan 2FA, semua device perlu verifikasi ulang
    await prisma.totpTrustedDevice.deleteMany({ where: { userId: req.user.id } });

    await createAuditLog({ userId: req.user.id, action: '2FA_ENABLED', req });

    return res.json({ success: true, message: 'Google Authenticator berhasil diaktifkan.' });
  } catch (err) {
    console.error('2FA confirm error:', err);
    res.status(500).json({ error: 'Gagal mengaktifkan 2FA.' });
  }
}

/**
 * POST /api/auth/2fa/disable
 * Body: { otp_code }
 * Nonaktifkan 2FA setelah verifikasi OTP.
 */
async function disable(req, res) {
  try {
    if (req.user.role !== 'SUPERADMIN') {
      return res.status(403).json({ error: 'Hanya SUPERADMIN.' });
    }

    const user = await prisma.user.findUnique({ where: { id: req.user.id } });
    if (!user.totpEnabled) {
      return res.status(400).json({ error: '2FA belum diaktifkan.' });
    }

    const { otp_code } = req.body;
    if (!otp_code) {
      return res.status(400).json({ error: 'Kode OTP wajib diisi untuk menonaktifkan 2FA.' });
    }

    const valid = speakeasy.totp.verify({
      secret: user.totpSecret,
      encoding: 'base32',
      token: String(otp_code).replace(/\s/g, ''),
      window: 1,
    });

    if (!valid) {
      return res.status(400).json({ error: 'Kode OTP tidak valid.' });
    }

    await prisma.user.update({
      where: { id: user.id },
      data: { totpSecret: null, totpEnabled: false },
    });

    // Hapus semua trusted device
    await prisma.totpTrustedDevice.deleteMany({ where: { userId: user.id } });

    // Hapus cookie device
    res.clearCookie(DEVICE_COOKIE, { path: '/' });

    await createAuditLog({ userId: user.id, action: '2FA_DISABLED', req });

    return res.json({ success: true, message: 'Google Authenticator berhasil dinonaktifkan.' });
  } catch (err) {
    console.error('2FA disable error:', err);
    res.status(500).json({ error: 'Gagal menonaktifkan 2FA.' });
  }
}

/**
 * GET /api/auth/2fa/status
 * Return status 2FA user saat ini.
 */
async function status(req, res) {
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.user.id },
      select: { totpEnabled: true },
    });
    return res.json({ totpEnabled: user?.totpEnabled ?? false });
  } catch (err) {
    res.status(500).json({ error: 'Gagal mengambil status 2FA.' });
  }
}

/**
 * POST /api/auth/2fa/verify
 * Dipanggil dari halaman verify-2fa setelah login password berhasil.
 * Body: { temp_token, otp_code }
 * Jika valid → complete login: set auth cookies + trusted device cookie.
 */
async function verify(req, res) {
  try {
    const { temp_token, otp_code } = req.body;
    if (!temp_token || !otp_code) {
      return res.status(400).json({ error: 'temp_token dan otp_code wajib diisi.' });
    }

    // Decode & verifikasi temp token
    let payload;
    try {
      payload = jwt.verify(temp_token, process.env.JWT_SECRET, {
        issuer: 'portal.hqmedan.com',
        audience: 'hqmedan-apps',
      });
    } catch {
      return res.status(401).json({ error: 'Token sesi login tidak valid atau sudah kedaluwarsa. Silakan login ulang.' });
    }

    if (payload.purpose !== '2fa_pending') {
      return res.status(401).json({ error: 'Token tidak valid.' });
    }

    // Ambil user + secret
    const user = await prisma.user.findUnique({ where: { id: payload.userId } });
    if (!user || !user.isActive || !user.totpEnabled) {
      return res.status(401).json({ error: 'Akun tidak ditemukan atau 2FA tidak aktif.' });
    }

    // Verifikasi OTP
    const valid = speakeasy.totp.verify({
      secret: user.totpSecret,
      encoding: 'base32',
      token: String(otp_code).replace(/\s/g, ''),
      window: 1,
    });

    if (!valid) {
      await createAuditLog({ userId: user.id, action: '2FA_FAILED', req });
      return res.status(400).json({ error: 'Kode OTP salah atau sudah kedaluwarsa.' });
    }

    // ── Complete login: generate tokens ──
    const { generateAccessToken, generateRefreshToken } = require('../utils/jwt.utils');

    const accessToken  = generateAccessToken({ userId: user.id, role: user.role });
    const refreshToken = generateRefreshToken({ userId: user.id });

    const sessionExpiry = new Date(Date.now() + 7 * 24 * 60 * 60 * 1000);
    await prisma.userSession.create({
      data: {
        userId: user.id,
        token: refreshToken,
        ipAddress: req.ip,
        userAgent: req.headers['user-agent'],
        expiresAt: sessionExpiry,
      },
    });

    await prisma.user.update({
      where: { id: user.id },
      data: { lastLoginAt: new Date() },
    });

    // ── Trusted device: simpan ke DB + set cookie 24h ──
    const deviceToken  = uuidv4();
    const deviceExpiry = new Date(Date.now() + DEVICE_TTL_MS);

    // Hapus expired device tokens untuk user ini dulu
    await prisma.totpTrustedDevice.deleteMany({
      where: { userId: user.id, expiresAt: { lte: new Date() } },
    });

    await prisma.totpTrustedDevice.create({
      data: { userId: user.id, deviceToken, expiresAt: deviceExpiry },
    });

    const cookieOpts = {
      httpOnly: true,
      secure: process.env.NODE_ENV === 'production',
      sameSite: process.env.NODE_ENV === 'production' ? 'strict' : 'lax',
      path: '/',
    };

    res.cookie('access_token', accessToken, { ...cookieOpts, maxAge: 24 * 60 * 60 * 1000 });
    res.cookie('refresh_token', refreshToken, { ...cookieOpts, maxAge: 7 * 24 * 60 * 60 * 1000 });
    res.cookie(DEVICE_COOKIE, deviceToken, { ...cookieOpts, maxAge: DEVICE_TTL_MS });

    await createAuditLog({ userId: user.id, action: '2FA_LOGIN_SUCCESS', req });

    return res.json({
      message: 'Verifikasi 2FA berhasil.',
      user: {
        id: user.id,
        username: user.username,
        fullName: user.fullName,
        role: user.role,
      },
    });
  } catch (err) {
    console.error('2FA verify error:', err);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

module.exports = { setup, confirm, disable, status, verify, DEVICE_COOKIE, DEVICE_TTL_MS };
