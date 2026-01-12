const express = require("express");
const path = require("path");
const multer = require("multer");
const { nanoid } = require("nanoid");
const { makePoolFromEnv } = require("../db/pool");
const { requireAuth, requireRole } = require("../middleware/auth");

const customerRouter = express.Router();
customerRouter.use(requireAuth, requireRole(["CUSTOMER"]));

customerRouter.get("/me", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(
      "SELECT id,first_name,last_name,email,username,wallet_balance,home_address,home_city,home_lat,home_lng,created_at FROM users WHERE id=? LIMIT 1",
      [req.user.id]
    );
    res.json(rows[0] || null);
  } finally {
    conn.release();
    await pool.end();
  }
});

// wajib simpan titik rumah
customerRouter.post("/profile", async (req, res) => {
  const { home_address, home_city, home_lat, home_lng } = req.body || {};
  if (!home_address || !home_city || home_lat == null || home_lng == null) {
    return res.status(400).json({ error: "Alamat, kota, dan titik lokasi rumah wajib diisi" });
  }

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query(
      `UPDATE users SET home_address=?, home_city=?, home_lat=?, home_lng=? WHERE id=?`,
      [home_address, home_city, Number(home_lat), Number(home_lng), req.user.id]
    );
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// list orders for this customer
customerRouter.get("/orders", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(
      "SELECT * FROM orders WHERE customer_id=? ORDER BY created_at DESC LIMIT 200",
      [req.user.id]
    );
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

customerRouter.get("/orders/:id", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(
      "SELECT * FROM orders WHERE id=? AND customer_id=? LIMIT 1",
      [req.params.id, req.user.id]
    );
    res.json(rows[0] || null);
  } finally {
    conn.release();
    await pool.end();
  }
});

// tracking driver for customer order
customerRouter.get("/orders/:id/track", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(
      "SELECT * FROM orders WHERE id=? AND customer_id=? LIMIT 1",
      [req.params.id, req.user.id]
    );
    const o = rows[0];
    if (!o) return res.status(404).json({ error: "Order tidak ditemukan" });

    let driver = null;
    if (o.assigned_driver_id) {
      const d = await conn.query(
        `SELECT u.id,u.username,u.first_name,u.last_name,u.driver_online,dl.lat,dl.lng,dl.accuracy,dl.updated_at
         FROM users u
         LEFT JOIN driver_locations dl ON dl.driver_id=u.id
         WHERE u.id=? LIMIT 1`,
        [o.assigned_driver_id]
      );
      driver = d[0] || null;
    }

    res.json({ order: o, driver });
  } finally {
    conn.release();
    await pool.end();
  }
});

// customer topup
customerRouter.post("/topup", async (req, res) => {
  const { amount, method } = req.body || {};
  const amt = Number(amount || 0);
  if (!amt || amt < 1000) return res.status(400).json({ error: "Nominal minimal 1000" });

  const id = nanoid(12);
  const m = ["GOPAY", "QRIS", "TRANSFER", "OTHER"].includes(method) ? method : "QRIS";

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query(
      `INSERT INTO topup_requests (id,user_id,amount,method,status) VALUES (?,?,?,?, 'PENDING')`,
      [id, req.user.id, amt, m]
    );
    res.json({ ok: true, id });
  } finally {
    conn.release();
    await pool.end();
  }
});

customerRouter.get("/topup", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM topup_requests WHERE user_id=? ORDER BY created_at DESC LIMIT 50", [req.user.id]);
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

customerRouter.get("/wallet/transactions", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM wallet_transactions WHERE user_id=? ORDER BY created_at DESC LIMIT 100", [req.user.id]);
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, process.env.UPLOAD_DIR),
  filename: (req, file, cb) => cb(null, `topup_${Date.now()}_${file.originalname.replace(/[^a-zA-Z0-9._-]/g, "_")}`)
});
const upload = multer({ storage });

customerRouter.post("/topup/:id/proof", upload.single("file"), async (req, res) => {
  const id = req.params.id;
  const rel = `/uploads/${path.basename(req.file.path)}`;

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM topup_requests WHERE id=? AND user_id=? LIMIT 1", [id, req.user.id]);
    if (!rows[0]) return res.status(404).json({ error: "Topup tidak ditemukan" });
    if (rows[0].status !== "PENDING") return res.status(400).json({ error: "Topup sudah diproses admin" });

    await conn.query("UPDATE topup_requests SET proof_image_path=? WHERE id=? AND user_id=?", [rel, id, req.user.id]);
    res.json({ ok: true, url: rel });
  } finally {
    conn.release();
    await pool.end();
  }
});

module.exports = { customerRouter };
