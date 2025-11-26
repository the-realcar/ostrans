const { Pool } = require('pg');
require('dotenv').config();

const pool = new Pool({
  connectionString: process.env.DATABASE_URL || process.env.PG_CONNECTION || 'postgresql://user:pass@localhost:5432/ostrans'
});

module.exports = {
  query: (text, params) => pool.query(text, params),
  pool
};
