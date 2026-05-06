require('dotenv').config();
const app = require('./app');
const { prisma } = require('./database/client');

const PORT = process.env.PORT || 3000;

async function startServer() {
  try {
    // Test database connection
    await prisma.$connect();
    console.log('✅ Database connected successfully');

    app.listen(PORT, () => {
      console.log('');
      console.log('╔════════════════════════════════════════╗');
      console.log('║        SSO Portal - HQ Medan           ║');
      console.log('╠════════════════════════════════════════╣');
      console.log(`║  Server  : http://localhost:${PORT}       ║`);
      console.log(`║  Domain  : portal.hqmedan.com          ║`);
      console.log(`║  Env     : ${process.env.NODE_ENV || 'development'}                 ║`);
      console.log('╚════════════════════════════════════════╝');
      console.log('');
    });
  } catch (error) {
    console.error('❌ Failed to start server:', error);
    process.exit(1);
  }
}

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('SIGTERM received. Closing HTTP server...');
  await prisma.$disconnect();
  process.exit(0);
});

process.on('SIGINT', async () => {
  console.log('\nSIGINT received. Closing HTTP server...');
  await prisma.$disconnect();
  process.exit(0);
});

startServer();
