const express = require('express');
const router = express.Router();
const { prisma } = require('../database/client');
const { authenticate } = require('../middleware/auth.middleware');

// GET /api/apps - Daftar semua aplikasi aktif (filter by role, jabatan, departemen & area)
router.get('/', authenticate, async (req, res) => {
  try {
    const userRole = req.user.role || null;
    const userRecord = await prisma.user.findUnique({
      where: { id: req.user.id },
      select: { divisi: true, area: true, pt: true, jabatan: true }
    });
    const userDivisi  = userRecord?.divisi  || null;
    const userArea    = userRecord?.area    || null;
    const userPt      = userRecord?.pt      || null;
    const userJabatan = userRecord?.jabatan || null;

    // Gunakan jabatan efektif (sama seperti sso.controller.js)
    const effectiveJabatan = userJabatan && userJabatan.startsWith('Admin FAT - ')
      ? userJabatan.replace('Admin FAT - ', '')
      : userJabatan;

    // Scope berdasarkan jabatan ADMIN
    const isHeadACC = userRole === 'ADMIN' && effectiveJabatan === 'Head ACC';
    const isHeadAR  = userRole === 'ADMIN' && effectiveJabatan === 'Head AR';
    const isKaAdmin = effectiveJabatan === 'KA Admin';

    const apps = await prisma.application.findMany({
      where: { isActive: true },
      select: {
        id: true,
        name: true,
        slug: true,
        description: true,
        url: true,
        callbackUrl: true,
        logoUrl: true,
        sortOrder: true,
        allowedDepartemen: true,
        areaLinks: true,
      },
      orderBy: { sortOrder: 'asc' }
    });

    // Helper: detect Link app vs SSO app
    function isLinkApp(app) {
      return app.areaLinks && typeof app.areaLinks === 'object' &&
             Object.keys(app.areaLinks).length > 0 &&
             (!app.callbackUrl || app.callbackUrl === '');
    }

    // Helper: get all areas within the same PT as user (for Head AR)
    function getAreasInUserPt(areaLinks) {
      // We need to check which areaLinks keys match areas in userPt
      // The PT-area mapping comes from master data (stored in localStorage on frontend)
      // Backend uses a hardcoded fallback mapping matching the master data defaults
      const PT_AREA_MAP = {
        'MDR 1': ['DC Medan', 'DC Stabat'],
        'MDR 2': ['DC Siantar', 'DC Tanjung Balai Asahan', 'DC Kabanjahe'],
        'MDR 3': ['DC Sibolga', 'DC Padang Sidimpuan', 'DC Rantau Prapat'],
        'MDR 4': ['DC Pancur Batu', 'DC Nias', 'DC Sei Rampah'],
      };
      if (!userPt || !PT_AREA_MAP[userPt]) return [];
      return PT_AREA_MAP[userPt].filter(a => areaLinks[a]);
    }

    // Filter rules:
    // SUPERADMIN         → semua tanpa filter
    // ADMIN Head ACC     → hanya app departemen FAT (semua area/PT)
    // ADMIN Head AR      → SSO app harus ada FAT:Head AR; Link App filter by area dalam PT-nya
    // KA Admin           → SSO app harus ada FAT:KA Admin; Link App filter by DC-nya sendiri
    // ADMIN lain / USER  → filter by departemen; Link App juga filter by area
    const HEAD_ACC_DEPT = 'FAT';

    const filtered = apps.filter(app => {
      if (userRole === 'SUPERADMIN') return true;

      if (isHeadACC) {
        // Head ACC: hanya app yang allowedDepartemen mengandung FAT
        if (!app.allowedDepartemen || app.allowedDepartemen.length === 0) return false;
        return app.allowedDepartemen.includes(HEAD_ACC_DEPT);
      }

      if (isHeadAR) {
        // Head AR: SSO App hanya jika FAT checked; Link App filter by area dalam PT-nya
        if (isLinkApp(app)) {
          const ptAreas = getAreasInUserPt(app.areaLinks);
          return ptAreas.length > 0;
        }
        return app.allowedDepartemen && app.allowedDepartemen.includes(HEAD_ACC_DEPT);
      }

      if (isKaAdmin) {
        // KA Admin: SSO App hanya jika FAT checked; Link App hanya DC-nya sendiri
        if (isLinkApp(app)) {
          if (!userArea) return false;
          return !!app.areaLinks[userArea];
        }
        return app.allowedDepartemen && app.allowedDepartemen.includes(HEAD_ACC_DEPT);
      }

      // Regular ADMIN & USER: filter by departemen
      if (!app.allowedDepartemen || app.allowedDepartemen.length === 0) return false;
      if (!userDivisi) return false;
      if (!app.allowedDepartemen.includes(userDivisi)) return false;

      // Tambahan filter sub-jabatan untuk FAT
      if (userDivisi === 'FAT') {
        const fatSubs = app.allowedDepartemen.filter(d => d.startsWith('FAT:'));
        // Jika tidak ada sub-jabatan FAT yang dipilih → tidak tampil ke siapapun
        if (fatSubs.length === 0) return false;
        // Ekstrak sub-jabatan user dari jabatan lengkap ("Admin FAT - Admin Kasir" → "Admin Kasir")
        const subJabatan = effectiveJabatan !== userJabatan
          ? effectiveJabatan
          : null;
        if (!subJabatan) return false;
        if (!fatSubs.includes('FAT:' + subJabatan)) return false;
      }

      // Untuk Link App: tambahan filter by area
      if (isLinkApp(app)) {
        if (!userArea) return false;
        if (!app.areaLinks[userArea]) return false;
      }

      return true;
    });

    // Build response
    const result = [];
    for (const { allowedDepartemen, callbackUrl, areaLinks, ...rest } of filtered) {
      const linkApp = areaLinks && typeof areaLinks === 'object' &&
                      Object.keys(areaLinks).length > 0 &&
                      (!callbackUrl || callbackUrl === '');

      if (linkApp) {
        if (userRole === 'SUPERADMIN' || isHeadACC) {
          // Expand semua area
          for (const [areaName, areaUrl] of Object.entries(areaLinks)) {
            result.push({ ...rest, url: areaUrl, appType: 'LINK', hasAreaLinks: true, areaName });
          }
        } else if (isHeadAR) {
          // Expand hanya area dalam PT-nya
          const ptAreas = getAreasInUserPt(areaLinks);
          for (const areaName of ptAreas) {
            result.push({ ...rest, url: areaLinks[areaName], appType: 'LINK', hasAreaLinks: true, areaName });
          }
        } else {
          // Regular user: hanya area mereka
          if (userArea && areaLinks[userArea]) {
            result.push({ ...rest, url: areaLinks[userArea], appType: 'LINK', hasAreaLinks: true, areaName: userArea });
          }
        }
      } else {
        result.push({
          ...rest,
          url: rest.url,
          appType: 'SSO',
          hasAreaLinks: false,
          areaName: null,
        });
      }
    }

    return res.json({ applications: result });
  } catch (error) {
    console.error('Get apps error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
});

module.exports = router;
