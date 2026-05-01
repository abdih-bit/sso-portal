/**
 * setup-apps.js
 * 
 * Script untuk mendaftarkan aplikasi STL & SPD ke SSO Portal
 * dengan fixed credentials agar config.php bisa dikonfigurasi.
 * 
 * Jalankan: node setup-apps.js
 */

require('dotenv').config();
const { prisma } = require('./src/database/client');

// ======================================================
// FIXED CREDENTIALS — salin ke config.php masing-masing
// ======================================================
const STL_CLIENT_ID     = 'stl-client-hqmedan-2025';
const STL_CLIENT_SECRET = 'stl-secret-hqmedan-2025-xK9mP';

// SPD sudah punya credentials di config.php — gunakan yang sama
const SPD_CLIENT_ID     = '0a76c993-b85d-4c27-8b9b-ea9748d4c266';
const SPD_CLIENT_SECRET = 'c81e0366-412a-4c4b-9610-0f192d208a90';

async function setup() {
  console.log('🔧 Setup Aplikasi SSO Portal\n');

  try {
    // === Aplikasi Serah Terima Laporan (STL) ===
    const stl = await prisma.application.upsert({
      where: { slug: 'serah-terima' },
      update: {
        clientId:     STL_CLIENT_ID,
        clientSecret: STL_CLIENT_SECRET,
        allowedRoles: ['USER', 'ADMIN', 'SUPERADMIN'],
        isActive:     true,
      },
      create: {
        name:         'Serah Terima Laporan',
        slug:         'serah-terima',
        description:  'Aplikasi manajemen serah terima dokumen laporan',
        url:          'https://stl.hqmedan.com',
        callbackUrl:  'https://stl.hqmedan.com',
        clientId:     STL_CLIENT_ID,
        clientSecret: STL_CLIENT_SECRET,
        allowedRoles: ['USER', 'ADMIN', 'SUPERADMIN'],
        isActive:     true,
        sortOrder:    10,
      },
    });
    console.log('✅ Aplikasi STL berhasil didaftarkan:');
    console.log(`   Nama       : ${stl.name}`);
    console.log(`   Slug       : ${stl.slug}`);
    console.log(`   Client ID  : ${stl.clientId}`);
    console.log(`   Client Secret: ${stl.clientSecret}`);
    console.log();

    // === Aplikasi SPD ===
    const spd = await prisma.application.upsert({
      where: { slug: 'spd' },
      update: {
        clientId:     SPD_CLIENT_ID,
        clientSecret: SPD_CLIENT_SECRET,
        allowedRoles: ['USER', 'ADMIN', 'SUPERADMIN'],
        isActive:     true,
      },
      create: {
        name:         'Aplikasi SPD',
        slug:         'spd',
        description:  'Aplikasi Surat Perintah Dinas',
        url:          'https://spd.hqmedan.com',
        callbackUrl:  'https://spd.hqmedan.com',
        clientId:     SPD_CLIENT_ID,
        clientSecret: SPD_CLIENT_SECRET,
        allowedRoles: ['USER', 'ADMIN', 'SUPERADMIN'],
        isActive:     true,
        sortOrder:    11,
      },
    });
    console.log('✅ Aplikasi SPD berhasil didaftarkan:');
    console.log(`   Nama       : ${spd.name}`);
    console.log(`   Slug       : ${spd.slug}`);
    console.log(`   Client ID  : ${spd.clientId}`);
    console.log(`   Client Secret: ${spd.clientSecret}`);
    console.log();

    // === Verifikasi superadmin ===
    const superadmin = await prisma.user.findFirst({ where: { role: 'SUPERADMIN' } });
    if (superadmin) {
      console.log('✅ Superadmin ditemukan:');
      console.log(`   Username : ${superadmin.username}`);
      console.log(`   Email    : ${superadmin.email}`);
      console.log(`   Aktif    : ${superadmin.isActive}`);
    } else {
      console.log('⚠️  Superadmin belum ada. Jalankan: npm run db:seed');
    }

    console.log('\n═══════════════════════════════════════════════');
    console.log('📋 KONFIGURASI config.php APLIKASI STL:');
    console.log('═══════════════════════════════════════════════');
    console.log(`define('SSO_CLIENT_ID',     '${STL_CLIENT_ID}');`);
    console.log(`define('SSO_CLIENT_SECRET', '${STL_CLIENT_SECRET}');`);
    console.log(`define('SSO_APP_SLUG',      '${stl.slug}');`);

    console.log('\n═══════════════════════════════════════════════');
    console.log('📋 KONFIGURASI config.php APLIKASI SPD:');
    console.log('═══════════════════════════════════════════════');
    console.log(`define('SSO_CLIENT_ID',     '${SPD_CLIENT_ID}');`);
    console.log(`define('SSO_CLIENT_SECRET', '${SPD_CLIENT_SECRET}');`);
    console.log(`define('SSO_APP_SLUG',      '${spd.slug}');`);
    console.log();

  } catch (err) {
    console.error('❌ Error:', err.message);
  } finally {
    await prisma.$disconnect();
  }
}

setup();
