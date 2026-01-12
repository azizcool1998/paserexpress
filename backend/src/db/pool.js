const mariadb = require("mariadb");

function makePoolFromEnv() {
  return mariadb.createPool({
    host: process.env.DB_HOST,
    port: Number(process.env.DB_PORT || 3306),
    user: process.env.DB_USER,
    password: process.env.DB_PASS,
    database: process.env.DB_NAME,
    connectionLimit: 10,
    multipleStatements: true
  });
}

module.exports = { makePoolFromEnv };
