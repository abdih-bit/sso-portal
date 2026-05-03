const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const { prisma } = require('../database/client');
const { createAuditLog } = require('../utils/audit.utils');

/**
 * GET /api/admin/users
 */
async function getUsers(req, res) {
  try {
    const { page = 1, limit = 20, search, role, isActive } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);
    const requester = req.user;

    const where = {};

    // ── Scope filter berdasarkan jabatan yang sedang login ──
    if (requester.role === 'ADMIN') {
      if (requester.jabatan === 'Head AR') {
        // Head AR: hanya lihat user dengan role USER dalam PT yang sama (semua area/DC)
        where.pt   = requester.pt;
        where.role = 'USER';
      } else if (requester.jabatan === 'Head ACC') {
        // Head ACC: lihat semua user (ADMIN & USER) di dept FAT, lintas semua PT
        where.divisi = 'FAT';
      } else {
        // ADMIN biasa: hanya lihat dirinya sendiri
        where.id = requester.id;
      }
    }
    // SUPERADMIN: tidak ada filter → lihat semua user

    if (search) {
      where.AND = [
        {
          OR: [
            { username: { contains: search, mode: 'insensitive' } },
            { fullName: { contains: search, mode: 'insensitive' } },
          ]
        }
      ];
    }
    // Filter role dari query param hanya berlaku jika scope belum mengunci role
    if (role && !where.role) where.role = role;
    if (isActive !== undefined) where.isActive = isActive === 'true';

    const [users, total] = await Promise.all([
      prisma.user.findMany({
        where,
        skip,
        take: parseInt(limit),
        select: {
          id: true,
          username: true,
          fullName: true,
          role: true,
          pt: true,
          area: true,
          jabatan: true,
          divisi: true,
          isActive: true,
          isVerified: true,
          lastLoginAt: true,
          createdAt: true,
        },
        orderBy: { createdAt: 'desc' }
      }),
      prisma.user.count({ where })
    ]);

    return res.json({
      users,
      pagination: {
        total,
        page: parseInt(page),
        limit: parseInt(limit),
        totalPages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    console.error('Get users error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/admin/users
 */
async function createUser(req, res) {
  try {
    const { username, password, fullName, role, pt, area, jabatan, divisi } = req.body;

    if (!username || !password || !fullName) {
      return res.status(400).json({ error: 'Nama lengkap, username, dan password wajib diisi.' });
    }

    if (password.length < 6) {
      return res.status(400).json({ error: 'Password minimal 6 karakter.' });
    }

    // Head ACC tidak wajib area (akses lintas PT), jabatan lain wajib area
    const requiresArea = jabatan !== 'Head ACC';
    if (!pt || (requiresArea && !area) || !jabatan || !divisi) {
      return res.status(400).json({ error: requiresArea ? 'PT, Area/DC, Jabatan, dan Departemen wajib dipilih.' : 'PT, Jabatan, dan Departemen wajib dipilih.' });
    }

    // Batasi: role SUPERADMIN hanya boleh ada 1
    if (role === 'SUPERADMIN') {
      const existingSuperadmin = await prisma.user.findFirst({ where: { role: 'SUPERADMIN' } });
      if (existingSuperadmin) {
        return res.status(403).json({ error: 'Hanya boleh ada 1 akun SUPERADMIN.' });
      }
    }

    // Hanya SUPERADMIN yang boleh membuat akun ADMIN atau SUPERADMIN
    if (['ADMIN', 'SUPERADMIN'].includes(role) && req.user.role !== 'SUPERADMIN') {
      return res.status(403).json({ error: 'Hanya SUPERADMIN yang dapat membuat akun dengan role ADMIN atau SUPERADMIN.' });
    }

    // Cek username duplikat
    const existing = await prisma.user.findFirst({
      where: { username: username.toLowerCase() }
    });

    if (existing) {
      return res.status(400).json({ error: 'Username sudah terdaftar.' });
    }

    const hashedPassword = await bcrypt.hash(password, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    const user = await prisma.user.create({
      data: {
        username: username.toLowerCase(),
        password: hashedPassword,
        fullName,
        role: role || 'USER',
        pt: pt || null,
        area: area || null,
        jabatan: jabatan || null,
        divisi: divisi || null,
        isVerified: true,
      },
      select: {
        id: true, username: true, fullName: true, role: true,
        pt: true, area: true, jabatan: true, divisi: true, createdAt: true
      }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'USER_CREATED',
      resource: user.username,
      req
    });

    return res.status(201).json({ message: 'User berhasil dibuat.', user });
  } catch (error) {
    console.error('Create user error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * PUT /api/admin/users/:id
 */
async function updateUser(req, res) {
  try {
    const { id } = req.params;
    const { fullName, role, isActive, pt, area, jabatan, divisi, newPassword } = req.body;

    // Cek user yang akan diupdate
    const existing = await prisma.user.findUnique({ where: { id }, select: { role: true } });
    if (!existing) return res.status(404).json({ error: 'User tidak ditemukan.' });

    // SUPERADMIN tidak dapat dinonaktifkan
    if (existing.role === 'SUPERADMIN' && isActive === false) {
      return res.status(403).json({ error: 'User SUPERADMIN tidak dapat dinonaktifkan.' });
    }

    // ADMIN tidak dapat mengedit/menonaktifkan user ber-role ADMIN atau SUPERADMIN
    if (req.user.role === 'ADMIN' && existing.role !== 'USER') {
      return res.status(403).json({ error: 'ADMIN hanya dapat mengedit user dengan role USER.' });
    }

    // Hanya SUPERADMIN yang boleh mengubah role
    if (role && role !== existing.role && req.user.role !== 'SUPERADMIN') {
      return res.status(403).json({ error: 'Hanya SUPERADMIN yang dapat mengubah role user.' });
    }

    // Tidak boleh assign role SUPERADMIN ke user lain
    if (role === 'SUPERADMIN' && existing.role !== 'SUPERADMIN') {
      return res.status(403).json({ error: 'Role SUPERADMIN tidak dapat diberikan ke user lain.' });
    }

    // ADMIN hanya boleh edit data penempatan user (bukan role/status)
    if (req.user.role === 'ADMIN') {
      const allowedFields = { pt, area, jabatan, divisi, fullName };
      const user = await prisma.user.update({
        where: { id },
        data: {
          ...(fullName && { fullName }),
          ...(pt !== undefined && { pt: pt || null }),
          ...(area !== undefined && { area: area || null }),
          ...(jabatan !== undefined && { jabatan: jabatan || null }),
          ...(divisi !== undefined && { divisi: divisi || null }),
        },
        select: { id: true, username: true, fullName: true, role: true, pt: true, area: true, jabatan: true, divisi: true, isActive: true }
      });
      await createAuditLog({ userId: req.user.id, action: 'USER_UPDATED', resource: user.username, details: { changes: allowedFields }, req });
      return res.json({ message: 'User berhasil diperbarui.', user });
    }

    // Hanya SUPERADMIN yang dapat mereset password
    let hashedNewPassword;
    if (newPassword !== undefined && newPassword !== '') {
      if (req.user.role !== 'SUPERADMIN') {
        return res.status(403).json({ error: 'Hanya SUPERADMIN yang dapat mereset password user.' });
      }
      if (newPassword.length < 6) {
        return res.status(400).json({ error: 'Password baru minimal 6 karakter.' });
      }
      hashedNewPassword = await bcrypt.hash(newPassword, parseInt(process.env.BCRYPT_ROUNDS) || 12);
    }

    const user = await prisma.user.update({
      where: { id },
      data: {
        ...(fullName && { fullName }),
        ...(role && { role }),
        ...(isActive !== undefined && { isActive }),
        ...(pt !== undefined && { pt: pt || null }),
        ...(area !== undefined && { area: area || null }),
        ...(jabatan !== undefined && { jabatan: jabatan || null }),
        ...(divisi !== undefined && { divisi: divisi || null }),
        ...(hashedNewPassword && { password: hashedNewPassword }),
      },
      select: {
        id: true, username: true, fullName: true, role: true,
        pt: true, area: true, jabatan: true, divisi: true, isActive: true
      }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'USER_UPDATED',
      resource: user.username,
      details: { changes: req.body },
      req
    });

    return res.json({ message: 'User berhasil diperbarui.', user });
  } catch (error) {
    if (error.code === 'P2025') {
      return res.status(404).json({ error: 'User tidak ditemukan.' });
    }
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * DELETE /api/admin/users/:id
 */
async function deleteUser(req, res) {
  try {
    const { id } = req.params;

    if (id === req.user.id) {
      return res.status(400).json({ error: 'Tidak bisa menghapus akun sendiri.' });
    }

    const user = await prisma.user.findUnique({ where: { id }, select: { id: true, username: true, role: true } });
    if (!user) return res.status(404).json({ error: 'User tidak ditemukan.' });

    // SUPERADMIN tidak bisa dihapus oleh siapa pun
    if (user.role === 'SUPERADMIN') {
      return res.status(403).json({ error: 'Akun SUPERADMIN tidak dapat dihapus.' });
    }

    // ADMIN hanya bisa menghapus user dengan role USER
    if (req.user.role === 'ADMIN' && user.role !== 'USER') {
      return res.status(403).json({ error: 'ADMIN hanya dapat menghapus akun dengan role USER.' });
    }

    await prisma.user.delete({ where: { id } });

    await createAuditLog({
      userId: req.user.id,
      action: 'USER_DELETED',
      resource: user.username,
      req
    });

    return res.json({ message: 'User berhasil dihapus.' });
  } catch (error) {
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/admin/applications
 */
async function getApplications(req, res) {
  try {
    const applications = await prisma.application.findMany({
      orderBy: { sortOrder: 'asc' }
    });
    return res.json({ applications });
  } catch (error) {
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/admin/applications
 */
async function createApplication(req, res) {
  try {
    const { name, slug, description, url, callbackUrl, logoUrl, allowedRoles, allowedDepartemen, allowedJabatan, areaLinks, appType, sortOrder } = req.body;

    // Link App hanya butuh name & slug; SSO App butuh url & callbackUrl juga
    if (appType === 'LINK') {
      if (!name || !slug) {
        return res.status(400).json({ error: 'name dan slug wajib diisi.' });
      }
    } else {
      if (!name || !slug || !url || !callbackUrl) {
        return res.status(400).json({ error: 'name, slug, url, dan callbackUrl wajib diisi.' });
      }
    }

    const app = await prisma.application.create({
      data: {
        name,
        slug,
        description,
        url: url || '',
        callbackUrl: callbackUrl || '',
        logoUrl,
        clientId: uuidv4(),
        clientSecret: uuidv4(),
        allowedRoles: allowedRoles || ['USER'],
        allowedDepartemen: allowedDepartemen || [],
        allowedJabatan: allowedJabatan || [],
        areaLinks: areaLinks || null,
        sortOrder: sortOrder || 0,
      }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'APP_CREATED',
      resource: app.name,
      req
    });

    return res.status(201).json({ message: 'Aplikasi berhasil ditambahkan.', application: app });
  } catch (error) {
    if (error.code === 'P2002') {
      return res.status(400).json({ error: 'Slug sudah digunakan.' });
    }
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * PUT /api/admin/applications/:id
 */
async function updateApplication(req, res) {
  try {
    const { id } = req.params;
    const { name, description, url, callbackUrl, logoUrl, isActive, allowedRoles, allowedDepartemen, allowedJabatan, areaLinks, sortOrder } = req.body;

    const app = await prisma.application.update({
      where: { id },
      data: {
        ...(name && { name }),
        ...(description !== undefined && { description }),
        ...(url && { url }),
        ...(callbackUrl && { callbackUrl }),
        ...(logoUrl !== undefined && { logoUrl }),
        ...(isActive !== undefined && { isActive }),
        ...(allowedRoles && { allowedRoles }),
        ...(allowedDepartemen !== undefined && { allowedDepartemen }),
        ...(allowedJabatan !== undefined && { allowedJabatan }),
        ...(areaLinks !== undefined && { areaLinks: areaLinks || null }),
        ...(sortOrder !== undefined && { sortOrder }),
      }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'APP_UPDATED',
      resource: app.name,
      req
    });

    return res.json({ message: 'Aplikasi berhasil diperbarui.', application: app });
  } catch (error) {
    if (error.code === 'P2025') {
      return res.status(404).json({ error: 'Aplikasi tidak ditemukan.' });
    }
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * PATCH /api/admin/applications/:id/toggle
 * Toggle isActive status — SUPERADMIN only
 */
async function toggleApplicationActive(req, res) {
  try {
    const { id } = req.params;
    const app = await prisma.application.findUnique({ where: { id } });
    if (!app) return res.status(404).json({ error: 'Aplikasi tidak ditemukan.' });

    const updated = await prisma.application.update({
      where: { id },
      data: { isActive: !app.isActive }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'APP_UPDATED',
      resource: `${updated.name} [${updated.isActive ? 'AKTIF' : 'NONAKTIF'}]`,
      req
    });

    return res.json({
      message: `Aplikasi berhasil ${updated.isActive ? 'diaktifkan' : 'dinonaktifkan'}.`,
      application: updated
    });
  } catch (error) {
    if (error.code === 'P2025') {
      return res.status(404).json({ error: 'Aplikasi tidak ditemukan.' });
    }
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * DELETE /api/admin/applications/:id
 */
async function deleteApplication(req, res) {
  try {
    const { id } = req.params;
    const app = await prisma.application.findUnique({ where: { id } });
    if (!app) return res.status(404).json({ error: 'Aplikasi tidak ditemukan.' });

    await prisma.application.delete({ where: { id } });

    await createAuditLog({
      userId: req.user.id,
      action: 'APP_DELETED',
      resource: app.name,
      req
    });

    return res.json({ message: 'Aplikasi berhasil dihapus.' });
  } catch (error) {
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/admin/audit-logs
 */
async function getAuditLogs(req, res) {
  try {
    const { page = 1, limit = 50, userId, action } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {};
    if (userId) where.userId = userId;
    if (action) where.action = { contains: action, mode: 'insensitive' };

    const [logs, total] = await Promise.all([
      prisma.auditLog.findMany({
        where,
        skip,
        take: parseInt(limit),
        include: {
          user: {
            select: { username: true, fullName: true }
          }
        },
        orderBy: { createdAt: 'desc' }
      }),
      prisma.auditLog.count({ where })
    ]);

    return res.json({
      logs,
      pagination: { total, page: parseInt(page), limit: parseInt(limit) }
    });
  } catch (error) {
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/admin/stats
 */
async function getStats(req, res) {
  try {
    const [totalUsers, activeUsers, totalApps, activeApps, totalLogins] = await Promise.all([
      prisma.user.count(),
      prisma.user.count({ where: { isActive: true } }),
      prisma.application.count(),
      prisma.application.count({ where: { isActive: true } }),
      prisma.auditLog.count({ where: { action: 'LOGIN_SUCCESS' } }),
    ]);

    return res.json({
      stats: {
        totalUsers,
        activeUsers,
        totalApps,
        activeApps,
        totalLogins,
      }
    });
  } catch (error) {
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/admin/password-reset-requests
 */
async function getPasswordResetRequests(req, res) {
  try {
    const { status } = req.query;
    const where = {};
    if (status) where.status = status;

    const requests = await prisma.passwordResetRequest.findMany({
      where,
      include: {
        user: {
          select: {
            id: true,
            username: true,
            fullName: true,
            role: true,
            divisi: true,
          }
        }
      },
      orderBy: { requestedAt: 'desc' }
    });

    return res.json({ requests });
  } catch (error) {
    console.error('Get reset requests error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/admin/password-reset-requests/:id/approve
 */
async function approvePasswordReset(req, res) {
  try {
    const { id } = req.params;
    const { newPassword } = req.body;

    if (!newPassword || newPassword.length < 6) {
      return res.status(400).json({ error: 'Password baru minimal 6 karakter.' });
    }

    const request = await prisma.passwordResetRequest.findUnique({
      where: { id },
      include: { user: { select: { id: true, username: true, fullName: true } } }
    });

    if (!request) return res.status(404).json({ error: 'Request tidak ditemukan.' });
    if (request.status !== 'PENDING') {
      return res.status(400).json({ error: 'Request ini sudah diproses.' });
    }

    const hashedPassword = await bcrypt.hash(newPassword, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    await prisma.$transaction([
      prisma.user.update({
        where: { id: request.userId },
        data: { password: hashedPassword }
      }),
      prisma.passwordResetRequest.update({
        where: { id },
        data: {
          status: 'APPROVED',
          resolvedAt: new Date(),
          resolvedBy: req.user.id,
        }
      }),
      // Invalidate semua session aktif milik user tersebut
      prisma.userSession.updateMany({
        where: { userId: request.userId },
        data: { isValid: false }
      }),
    ]);

    await createAuditLog({
      userId: req.user.id,
      action: 'PASSWORD_RESET_APPROVED',
      resource: 'password_reset_requests',
      details: { requestId: id, targetUser: request.user.username },
      ipAddress: req.ip,
      userAgent: req.headers['user-agent'],
    });

    return res.json({ message: `Password untuk ${request.user.fullName} berhasil direset.` });
  } catch (error) {
    console.error('Approve reset error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/admin/password-reset-requests/:id/reject
 */
async function rejectPasswordReset(req, res) {
  try {
    const { id } = req.params;

    const request = await prisma.passwordResetRequest.findUnique({
      where: { id },
      include: { user: { select: { username: true } } }
    });

    if (!request) return res.status(404).json({ error: 'Request tidak ditemukan.' });
    if (request.status !== 'PENDING') {
      return res.status(400).json({ error: 'Request ini sudah diproses.' });
    }

    await prisma.passwordResetRequest.update({
      where: { id },
      data: {
        status: 'REJECTED',
        resolvedAt: new Date(),
        resolvedBy: req.user.id,
      }
    });

    await createAuditLog({
      userId: req.user.id,
      action: 'PASSWORD_RESET_REJECTED',
      resource: 'password_reset_requests',
      details: { requestId: id, targetUser: request.user.username },
      ipAddress: req.ip,
      userAgent: req.headers['user-agent'],
    });

    return res.json({ message: 'Request reset password ditolak.' });
  } catch (error) {
    console.error('Reject reset error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/admin/areas
 */
async function getAreas(req, res) {
  try {
    const areas = await prisma.area.findMany({
      orderBy: [{ pt: 'asc' }, { isHo: 'desc' }, { name: 'asc' }]
    });
    return res.json({ areas });
  } catch (error) {
    console.error('Get areas error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/admin/areas
 */
async function createArea(req, res) {
  try {
    const { name, pt, isHo } = req.body;
    if (!name || !name.trim()) {
      return res.status(400).json({ error: 'Nama area wajib diisi.' });
    }
    const area = await prisma.area.create({
      data: { name: name.trim(), pt: pt || null, isHo: !!isHo }
    });
    await createAuditLog({ userId: req.user.id, action: 'AREA_CREATED', resource: area.name, req });
    return res.status(201).json({ message: 'Area berhasil ditambahkan.', area });
  } catch (error) {
    if (error.code === 'P2002') return res.status(400).json({ error: 'Nama area sudah ada.' });
    console.error('Create area error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * PUT /api/admin/areas/:id
 */
async function updateArea(req, res) {
  try {
    const { id } = req.params;
    const { name, pt, isHo } = req.body;
    const area = await prisma.area.update({
      where: { id: parseInt(id) },
      data: {
        ...(name && { name: name.trim() }),
        ...(pt !== undefined && { pt: pt || null }),
        ...(isHo !== undefined && { isHo: !!isHo }),
      }
    });
    await createAuditLog({ userId: req.user.id, action: 'AREA_UPDATED', resource: area.name, req });
    return res.json({ message: 'Area berhasil diperbarui.', area });
  } catch (error) {
    if (error.code === 'P2025') return res.status(404).json({ error: 'Area tidak ditemukan.' });
    if (error.code === 'P2002') return res.status(400).json({ error: 'Nama area sudah ada.' });
    console.error('Update area error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * DELETE /api/admin/areas/:id
 */
async function deleteArea(req, res) {
  try {
    const { id } = req.params;
    const area = await prisma.area.findUnique({ where: { id: parseInt(id) } });
    if (!area) return res.status(404).json({ error: 'Area tidak ditemukan.' });
    await prisma.area.delete({ where: { id: parseInt(id) } });
    await createAuditLog({ userId: req.user.id, action: 'AREA_DELETED', resource: area.name, req });
    return res.json({ message: 'Area berhasil dihapus.' });
  } catch (error) {
    console.error('Delete area error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * GET /api/admin/sales-offices
 */
async function getSalesOffices(req, res) {
  try {
    const salesOffices = await prisma.salesOffice.findMany({
      orderBy: [{ areaId: 'asc' }, { name: 'asc' }],
      include: { area: { select: { id: true, name: true } } }
    });
    return res.json({ salesOffices });
  } catch (error) {
    console.error('Get sales offices error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * POST /api/admin/sales-offices
 */
async function createSalesOffice(req, res) {
  try {
    const { name, areaId } = req.body;
    if (!name || !name.trim()) return res.status(400).json({ error: 'Nama sales office wajib diisi.' });
    if (!areaId) return res.status(400).json({ error: 'Area induk wajib dipilih.' });
    const so = await prisma.salesOffice.create({
      data: { name: name.trim(), areaId: parseInt(areaId) }
    });
    await createAuditLog({ userId: req.user.id, action: 'SALES_OFFICE_CREATED', resource: so.name, req });
    return res.status(201).json({ message: 'Sales Office berhasil ditambahkan.', salesOffice: so });
  } catch (error) {
    if (error.code === 'P2002') return res.status(400).json({ error: 'Nama sales office sudah ada.' });
    if (error.code === 'P2003') return res.status(400).json({ error: 'Area induk tidak ditemukan.' });
    console.error('Create sales office error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * PUT /api/admin/sales-offices/:id
 */
async function updateSalesOffice(req, res) {
  try {
    const { id } = req.params;
    const { name, areaId } = req.body;
    const so = await prisma.salesOffice.update({
      where: { id: parseInt(id) },
      data: {
        ...(name && { name: name.trim() }),
        ...(areaId !== undefined && { areaId: parseInt(areaId) }),
      }
    });
    await createAuditLog({ userId: req.user.id, action: 'SALES_OFFICE_UPDATED', resource: so.name, req });
    return res.json({ message: 'Sales Office berhasil diperbarui.', salesOffice: so });
  } catch (error) {
    if (error.code === 'P2025') return res.status(404).json({ error: 'Sales Office tidak ditemukan.' });
    if (error.code === 'P2002') return res.status(400).json({ error: 'Nama sales office sudah ada.' });
    console.error('Update sales office error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

/**
 * DELETE /api/admin/sales-offices/:id
 */
async function deleteSalesOffice(req, res) {
  try {
    const { id } = req.params;
    const so = await prisma.salesOffice.findUnique({ where: { id: parseInt(id) } });
    if (!so) return res.status(404).json({ error: 'Sales Office tidak ditemukan.' });
    await prisma.salesOffice.delete({ where: { id: parseInt(id) } });
    await createAuditLog({ userId: req.user.id, action: 'SALES_OFFICE_DELETED', resource: so.name, req });
    return res.json({ message: 'Sales Office berhasil dihapus.' });
  } catch (error) {
    console.error('Delete sales office error:', error);
    res.status(500).json({ error: 'Terjadi kesalahan server.' });
  }
}

module.exports = {
  getUsers,
  createUser,
  updateUser,
  deleteUser,
  getApplications,
  createApplication,
  updateApplication,
  toggleApplicationActive,
  deleteApplication,
  getAuditLogs,
  getStats,
  getPasswordResetRequests,
  approvePasswordReset,
  rejectPasswordReset,
  getAreas,
  createArea,
  updateArea,
  deleteArea,
  getSalesOffices,
  createSalesOffice,
  updateSalesOffice,
  deleteSalesOffice,
};
