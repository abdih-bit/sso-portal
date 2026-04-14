require('dotenv').config();
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const { prisma } = require('./client');

async function seed() {
  console.log('🌱 Starting database seeding...');

  try {
    // ===========================
    // CREATE SUPERADMIN
    // ===========================
    const hashedPassword = await bcrypt.hash('Admin@HQ2025!', 12);

    const superAdmin = await prisma.user.upsert({
      where: { email: 'admin@hqmedan.com' },
      update: {},
      create: {
        email: 'admin@hqmedan.com',
        username: 'superadmin',
        password: hashedPassword,
        fullName: 'Super Administrator',
        role: 'SUPERADMIN',
        isActive: true,
        isVerified: true,
      }
    });
    console.log('✅ SuperAdmin created:', superAdmin.email);

    // ===========================
    // CREATE SAMPLE APPLICATIONS
    // ===========================
    const apps = [
      {
        name: 'HR Management System',
        slug: 'hrms',
        description: 'Sistem manajemen sumber daya manusia',
        url: 'https://hrms.hqmedan.com',
        callbackUrl: 'https://hrms.hqmedan.com/sso/callback',
        logoUrl: null,
        clientId: uuidv4(),
        clientSecret: uuidv4(),
        allowedRoles: ['USER', 'ADMIN', 'SUPERADMIN'],
        sortOrder: 1,
      },
      {
        name: 'Finance & Accounting',
        slug: 'finance',
        description: 'Sistem keuangan dan akuntansi',
        url: 'https://finance.hqmedan.com',
        callbackUrl: 'https://finance.hqmedan.com/sso/callback',
        logoUrl: null,
        clientId: uuidv4(),
        clientSecret: uuidv4(),
        allowedRoles: ['ADMIN', 'SUPERADMIN'],
        sortOrder: 2,
      },
      {
        name: 'Inventory System',
        slug: 'inventory',
        description: 'Sistem manajemen stok dan inventaris',
        url: 'https://inventory.hqmedan.com',
        callbackUrl: 'https://inventory.hqmedan.com/sso/callback',
        logoUrl: null,
        clientId: uuidv4(),
        clientSecret: uuidv4(),
        allowedRoles: ['USER', 'ADMIN', 'SUPERADMIN'],
        sortOrder: 3,
      },
    ];

    for (const appData of apps) {
      const app = await prisma.application.upsert({
        where: { slug: appData.slug },
        update: {},
        create: appData,
      });
      console.log(`✅ Application created: ${app.name} (Client ID: ${app.clientId})`);
    }

    console.log('\n🎉 Seeding completed successfully!');
    console.log('\n📋 Login Credentials:');
    console.log('   Email    : admin@hqmedan.com');
    console.log('   Password : Admin@HQ2025!');
    console.log('\n⚠️  PENTING: Ganti password setelah login pertama!');

  } catch (error) {
    console.error('❌ Seeding failed:', error);
    throw error;
  } finally {
    await prisma.$disconnect();
  }
}

seed();
