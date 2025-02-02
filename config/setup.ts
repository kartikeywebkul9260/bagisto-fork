// config.js
const config = {
  // The base URL of the application under test
  baseUrl: process.env.APP_URL || 'http://dev1.amit.com',

  // Admin credentials
  adminEmail: process.env.ADMIN_EMAIL || 'admin@example.com',
  adminPassword: process.env.ADMIN_PASSWORD || 'admin123',
};

export default config;
