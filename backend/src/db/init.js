require("dotenv").config();
const bcrypt = require("bcryptjs");
const { nanoid } = require("nanoid");
const { makePoolFromEnv } = require("./pool");

function arg(name, fallback = "") {
  const idx = process.argv.findIndex(x => x === `--${name}`);
  if (idx >= 0) return process.argv[idx + 1] || fallback;
  return fallback;
}

async function main() {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();

  try {
    // USERS
    await conn.query(`
      CREATE TABLE IF NOT EXISTS users (
        id VARCHAR(36) PRIMARY KEY,
        first_name VARCHAR(80) NOT NULL,
        last_name VARCHAR(80) NOT NULL,
        email VARCHAR(191) NOT NULL UNIQUE,
        username VARCHAR(80) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role ENUM('ADMIN','STAFF','DRIVER','CUSTOMER') NOT NULL DEFAULT 'CUSTOMER',
        driver_online TINYINT(1) NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        wallet_balance INT NOT NULL DEFAULT 0,

        home_address TEXT NULL,
        home_city VARCHAR(80) NULL,
        home_lat DOUBLE NULL,
        home_lng DOUBLE NULL,

        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // SERVICES
    await conn.query(`
      CREATE TABLE IF NOT EXISTS services (
        id VARCHAR(36) PRIMARY KEY,
        title VARCHAR(120) NOT NULL,
        description TEXT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // SETTINGS
    await conn.query(`
      CREATE TABLE IF NOT EXISTS settings (
        k VARCHAR(120) PRIMARY KEY,
        v TEXT NOT NULL
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // ORDERS
    await conn.query(`
      CREATE TABLE IF NOT EXISTS orders (
        id VARCHAR(36) PRIMARY KEY,
        customer_id VARCHAR(36) NULL,
        customer_name VARCHAR(120) NOT NULL,
        customer_phone VARCHAR(40) NOT NULL,

        pickup_address TEXT NULL,
        pickup_city VARCHAR(80) NULL,
        pickup_note TEXT NULL,
        pickup_lat DOUBLE NULL,
        pickup_lng DOUBLE NULL,

        dropoff_address TEXT NULL,
        dropoff_city VARCHAR(80) NULL,
        dropoff_note TEXT NULL,
        dropoff_lat DOUBLE NULL,
        dropoff_lng DOUBLE NULL,

        service_id VARCHAR(36) NULL,
        details TEXT NULL,
        amount INT NOT NULL DEFAULT 0,

        pay_method ENUM('MANUAL','WALLET') NOT NULL DEFAULT 'MANUAL',

        status ENUM('PENDING_PAYMENT','PENDING_REVIEW','PAID_CONFIRMED','REJECTED','IN_PROGRESS','DELIVERED')
          NOT NULL DEFAULT 'PENDING_PAYMENT',

        proof_image_path TEXT NULL,
        admin_note TEXT NULL,
        assigned_driver_id VARCHAR(36) NULL,

        driver_stage ENUM('NONE','ASSIGNED','ACCEPTED','ON_THE_WAY','ARRIVED','DELIVERED','CANCELLED')
          NOT NULL DEFAULT 'NONE',
        delivered_confirmed TINYINT(1) NOT NULL DEFAULT 0,
        delivered_confirmed_at TIMESTAMP NULL,

        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // Safe migrations for existing installs
    await conn.query(`ALTER TABLE orders ADD COLUMN IF NOT EXISTS pay_method ENUM('MANUAL','WALLET') NOT NULL DEFAULT 'MANUAL'`);

    // TOPUP REQUESTS
    await conn.query(`
      CREATE TABLE IF NOT EXISTS topup_requests (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        amount INT NOT NULL,
        method ENUM('GOPAY','QRIS','TRANSFER','OTHER') NOT NULL DEFAULT 'QRIS',
        status ENUM('PENDING','CONFIRMED','REJECTED') NOT NULL DEFAULT 'PENDING',
        proof_image_path TEXT NULL,
        admin_note TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // WALLET TX
    await conn.query(`
      CREATE TABLE IF NOT EXISTS wallet_transactions (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        type ENUM('TOPUP_CONFIRMED','ADMIN_ADJUST','SPEND','REFUND') NOT NULL,
        amount_delta INT NOT NULL,
        balance_after INT NOT NULL,
        note TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // ORDER EVENTS (audit)
    await conn.query(`
      CREATE TABLE IF NOT EXISTS order_events (
        id VARCHAR(36) PRIMARY KEY,
        order_id VARCHAR(36) NOT NULL,
        actor_user_id VARCHAR(36) NULL,
        actor_role VARCHAR(20) NULL,
        event_type VARCHAR(40) NOT NULL,
        detail TEXT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // DRIVER LOCATIONS
    await conn.query(`
      CREATE TABLE IF NOT EXISTS driver_locations (
        driver_id VARCHAR(36) PRIMARY KEY,
        lat DOUBLE NOT NULL,
        lng DOUBLE NOT NULL,
        accuracy DOUBLE NULL,
        heading DOUBLE NULL,
        speed DOUBLE NULL,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    `);

    // default settings
    const defaults = [
      ["theme.primary", "#2563eb"],
      ["theme.bg", "#0b1220"],
      ["theme.card", "#111a2e"],
      ["theme.text", "#e5e7eb"],
      ["theme.radius", "16"],
      ["payment.qris_image", ""],
      ["payment.gopay_number", ""],
      ["payment.warning", "WAJIB BAYAR DI AWAL. Tidak bisa COD. Setelah bayar, upload bukti. Admin akan verifikasi manual."],
      ["meta.db_email", process.env.DB_EMAIL || ""]
    ];
    for (const [k, v] of defaults) {
      await conn.query(`INSERT IGNORE INTO settings (k,v) VALUES (?,?)`, [k, v]);
    }

    // seed services
    const [{ cnt }] = await conn.query(`SELECT COUNT(*) AS cnt FROM services`);
    if (Number(cnt) === 0) {
      await conn.query(
        `INSERT INTO services (id,title,description,is_active,sort_order) VALUES (?,?,?,?,?)`,
        [nanoid(12), "Jasa Titip", "Titip belanja & kirim ke Tanah Grogot", 1, 1]
      );
      await conn.query(
        `INSERT INTO services (id,title,description,is_active,sort_order) VALUES (?,?,?,?,?)`,
        [nanoid(12), "Ekspedisi Barang", "Barang diterima, difoto, diteruskan ke kantor", 1, 2]
      );
    }

    // create first admin if not exists
    const adminUsername = arg("admin_username", "");
    const adminPass = arg("admin_pass", "");
    const adminEmail = arg("admin_email", "");
    const adminFirst = arg("admin_first", "Admin");
    const adminLast = arg("admin_last", "Paser");

    if (adminUsername && adminPass && adminEmail) {
      const rows = await conn.query("SELECT id FROM users WHERE username=? LIMIT 1", [adminUsername]);
      if (rows.length === 0) {
        const id = nanoid(12);
        const hash = await bcrypt.hash(adminPass, 12);
        await conn.query(
          `INSERT INTO users (id, first_name, last_name, email, username, password_hash, role, is_active)
           VALUES (?,?,?,?,?,?, 'ADMIN', 1)`,
          [id, adminFirst, adminLast, adminEmail, adminUsername, hash]
        );
        console.log("✅ Admin dibuat:", adminUsername);
      } else {
        console.log("ℹ️ Admin sudah ada:", adminUsername);
      }
    } else {
      console.log("ℹ️ Admin args tidak lengkap, skip create admin.");
    }

    console.log("✅ DB init ok");
  } finally {
    conn.release();
    await pool.end();
  }
}

main().catch((e) => {
  console.error(e);
  process.exit(1);
});
