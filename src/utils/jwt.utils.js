const jwt = require('jsonwebtoken');

/**
 * Generate JWT Access Token
 */
function generateAccessToken(payload) {
  return jwt.sign(payload, process.env.JWT_SECRET, {
    expiresIn: process.env.JWT_EXPIRES_IN || '24h',
    issuer: 'portal.hqmedan.com',
    audience: 'hqmedan-apps',
  });
}

/**
 * Generate JWT Refresh Token
 */
function generateRefreshToken(payload) {
  return jwt.sign(payload, process.env.JWT_REFRESH_SECRET, {
    expiresIn: process.env.JWT_REFRESH_EXPIRES_IN || '7d',
    issuer: 'portal.hqmedan.com',
  });
}

/**
 * Verify JWT Access Token
 */
function verifyAccessToken(token) {
  return jwt.verify(token, process.env.JWT_SECRET, {
    issuer: 'portal.hqmedan.com',
    audience: 'hqmedan-apps',
  });
}

/**
 * Verify Refresh Token
 */
function verifyRefreshToken(token) {
  return jwt.verify(token, process.env.JWT_REFRESH_SECRET, {
    issuer: 'portal.hqmedan.com',
  });
}

/**
 * Decode token tanpa verifikasi (untuk debugging)
 */
function decodeToken(token) {
  return jwt.decode(token, { complete: true });
}

module.exports = {
  generateAccessToken,
  generateRefreshToken,
  verifyAccessToken,
  verifyRefreshToken,
  decodeToken,
};
