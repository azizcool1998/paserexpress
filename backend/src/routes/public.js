const express = require("express");
const path = require("path");
const multer = require("multer");
const jwt = require("jsonwebtoken");
const { nanoid } = require("nanoid");
const { makePoolFromEnv } = require("../db/pool");

const publicRouter = express.Router();

async function getOptionalCustomer(req, conn) {
  const hdr = req.headers.authorization || "";
  const token = hdr.startsWith("Bearer ") ? hdr.slice(7) : null;
  if (!token) return null;
  try {
    const payload = jwt.verify(token, process.env.JWT_SECRET);
    const rows = await conn.query(
      "SELECT id, first_name, last_name, email, username, role, is_active, wallet_balance FROM users WHERE id=? LIMIT 1",
      [payload.sub]
    );
    const u = rows[0];
    if (!u) return null;
    if (u.role !== "CUSTOMER") return null;
    if (Number(u.is_active) !== 1) return null;
    return u;
  } catch {
    return null;
  }
}

publicRouter.get("/settings", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT k,v FROM settings");
    const obj = {};
    for (const r of rows) obj[r.k] = r.v;
    res.json(obj);
  } finally {
    conn.release();
    await pool.end();
  }
});

publicRouter.get("/services", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM services WHERE is_active=1 ORDER BY sort_order ASC, created_at DESC");
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

// Create order (public + optional customer) 
// pay_method: MANUAL(default) or WALLET (only if customer login)
publicRouter.post("/orders", async (req, res) => {
  const {
    customer_name, customer_phone, service_id, details, amount,
    pickup_address, dropoff_address, pickup_note, dropoff_note,
    pickup_city, dropoff_city,
    pickup_lat, pickup_lng, dropoff_lat, dropoff_lng,
    pay_method
  } = req.body || {};

  if (!customer_name || !customer_phone || !amount) return res.status(400).json({ error: "Data wajib belum lengkap" });
  if (!pickup_address || !dropoff_address) return res.status(400).json({ error: "Alamat pickup & tujuan wajib" });
  if (pickup_lat == null || pickup_lng == null || dropoff_lat == null || dropoff_lng == null) {
    return res.status(400).json({ error: "Titik lokasi pickup & tujuan wajib dipilih di peta" });
  }

  const amt = Number(amount);
  if (!Number.isFinite(amt) || amt <= 0) return res.status(400).json({ error: "amount invalid" });

  const id = nanoid(12);
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const customer = await getOptionalCustomer(req, conn);
    const method = (pay_method === "WALLET") ? "WALLET" : "MANUAL";

    let status = "PENDING_PAYMENT";
    let customerId = null;

    // Wallet pay: only if logged-in customer
    if (method === "WALLET") {
      if (!customer) {
        await conn.rollback();
        return res.status(403).json({ error: "Pembayaran saldo hanya untuk customer yang login" });
      }
      customerId = customer.id;

      const bal = Number(customer.wallet_balance);
      if (bal < amt) {
        await conn.rollback();
        return res.status(400).json({ error: "Saldo tidak cukup" });
      }

      const newBal = bal - amt;
      await conn.query("UPDATE users SET wallet_balance=? WHERE id=?", [newBal, customer.id]);

      await conn.query(
        `INSERT INTO wallet_transactions (id,user_id,type,amount_delta,balance_after,note)
         VALUES (?,?,?,?,?,?)`,
        [nanoid(12), customer.id, "SPEND", -amt, newBal, `Payment order ${id}`]
      );

      status = "PAID_CONFIRMED";
    } else {
      // MANUAL pay: jika customer login, tetap simpan customer_id untuk tracking
      if (customer) customerId = customer.id;
      status = "PENDING_PAYMENT";
    }

    await conn.query(
      `INSERT INTO orders (
        id, customer_id, customer_name, customer_phone, service_id, details, amount, pay_method, status,
        pickup_address, pickup_city, pickup_note, pickup_lat, pickup_lng,
        dropoff_address, dropoff_city, dropoff_note, dropoff_lat, dropoff_lng
      )
      VALUES (?,?,?,?,?,?,?,?,?, ?,?,?,?, ?, ?,?,?,?, ?)`,
      [
        id,
        customerId,
        customer_name,
        customer_phone,
        service_id || null,
        details || null,
        amt,
        method,
        status,
        pickup_address || null,
        pickup_city || null,
        pickup_note || null,
        Number(pickup_lat),
        Number(pickup_lng),
        dropoff_address || null,
        dropoff_city || null,
        dropoff_note || null,
        Number(dropoff_lat),
        Number(dropoff_lng)
      ]
    );

    await conn.query(
      `INSERT INTO order_events (id, order_id, actor_user_id, actor_role, event_type, detail)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), id, customerId, customer ? "CUSTOMER" : "PUBLIC", "ORDER_CREATED", `pay_method=${method}`]
    );

    await conn.commit();
    res.json({ id, status, pay_method: method });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

publicRouter.get("/orders/:id", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM orders WHERE id=? LIMIT 1", [req.params.id]);
    res.json(rows[0] || null);
  } finally {
    conn.release();
    await pool.end();
  }
});

// Upload proof for manual payment (public)
const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, process.env.UPLOAD_DIR),
  filename: (req, file, cb) => cb(null, `order_${Date.now()}_${file.originalname.replace(/[^a-zA-Z0-9._-]/g, "_")}`)
});
const upload = multer({ storage });

publicRouter.post("/orders/:id/proof", upload.single("file"), async (req, res) => {
  const id = req.params.id;
  const rel = `/uploads/${path.basename(req.file.path)}`;

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT status, pay_method FROM orders WHERE id=? LIMIT 1", [id]);
    if (!rows[0]) return res.status(404).json({ error: "Order tidak ditemukan" });

    if (rows[0].pay_method === "WALLET") {
      return res.status(400).json({ error: "Order ini dibayar via saldo, tidak perlu bukti." });
    }

    await conn.query(
      "UPDATE orders SET proof_image_path=?, status='PENDING_REVIEW' WHERE id=?",
      [rel, id]
    );

    res.json({ ok: true, url: rel });
  } finally {
    conn.release();
    await pool.end();
  }
});

module.exports = { publicRouter };
