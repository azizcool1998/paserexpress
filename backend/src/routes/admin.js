const express = require("express");
const path = require("path");
const multer = require("multer");
const { nanoid } = require("nanoid");
const { makePoolFromEnv } = require("../db/pool");
const { requireAuth, requireRole } = require("../middleware/auth");

const adminRouter = express.Router();
adminRouter.use(requireAuth, requireRole(["ADMIN","STAFF"]));

function adminOnly(req, res) {
  if (req.user.role !== "ADMIN") {
    res.status(403).json({ error: "ADMIN only" });
    return false;
  }
  return true;
}

// users
adminRouter.get("/users", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT id,first_name,last_name,email,username,role,is_active,driver_online,wallet_balance FROM users ORDER BY created_at DESC LIMIT 500");
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/users/:id/role", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const { role } = req.body || {};
  if (!["ADMIN","STAFF","DRIVER","CUSTOMER"].includes(role)) return res.status(400).json({ error: "role invalid" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("UPDATE users SET role=? WHERE id=?", [role, req.params.id]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/users/:id/active", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const { is_active } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("UPDATE users SET is_active=? WHERE id=?", [is_active ? 1 : 0, req.params.id]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// approve driver
adminRouter.post("/drivers/:id/approve", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT role FROM users WHERE id=? LIMIT 1", [req.params.id]);
    if (!rows[0]) return res.status(404).json({ error: "User tidak ditemukan" });
    if (rows[0].role !== "DRIVER") return res.status(400).json({ error: "Bukan driver" });
    await conn.query("UPDATE users SET is_active=1 WHERE id=?", [req.params.id]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// settings (theme + payment)
adminRouter.get("/settings", async (req, res) => {
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

adminRouter.post("/settings", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const { k, v } = req.body || {};
  if (!k) return res.status(400).json({ error: "k wajib" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("INSERT INTO settings (k,v) VALUES (?,?) ON DUPLICATE KEY UPDATE v=VALUES(v)", [k, String(v ?? "")]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// upload QRIS image setting
const storage = multer.diskStorage({
  destination: (req, file, cb) => cb(null, process.env.UPLOAD_DIR),
  filename: (req, file, cb) => cb(null, `qris_${Date.now()}_${file.originalname.replace(/[^a-zA-Z0-9._-]/g, "_")}`)
});
const upload = multer({ storage });

adminRouter.post("/settings/qris-image", upload.single("file"), async (req, res) => {
  if (!adminOnly(req,res)) return;
  const rel = `/uploads/${path.basename(req.file.path)}`;

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("INSERT INTO settings (k,v) VALUES ('payment.qris_image',?) ON DUPLICATE KEY UPDATE v=VALUES(v)", [rel]);
    res.json({ ok: true, url: rel });
  } finally {
    conn.release();
    await pool.end();
  }
});

// ===== SERVICES CRUD (Admin) =====
adminRouter.get("/services", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM services ORDER BY sort_order ASC, created_at DESC LIMIT 500");
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/services", async (req, res) => {
  if (!adminOnly(req, res)) return;
  const { title, description, sort_order, is_active } = req.body || {};
  if (!title) return res.status(400).json({ error: "title wajib" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const id = nanoid(12);
    await conn.query(
      "INSERT INTO services (id,title,description,is_active,sort_order) VALUES (?,?,?,?,?)",
      [
        id,
        String(title),
        description ? String(description) : null,
        is_active ? 1 : 0,
        Number(sort_order || 0)
      ]
    );
    res.json({ ok: true, id });
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/services/:id", async (req, res) => {
  if (!adminOnly(req, res)) return;
  const { title, description, sort_order, is_active } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query(
      `UPDATE services
       SET title=?, description=?, sort_order=?, is_active=?
       WHERE id=?`,
      [
        title ? String(title) : "",
        description ? String(description) : null,
        Number(sort_order || 0),
        is_active ? 1 : 0,
        req.params.id
      ]
    );
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/services/:id/delete", async (req, res) => {
  if (!adminOnly(req, res)) return;
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("DELETE FROM services WHERE id=?", [req.params.id]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// orders review payment (manual)
adminRouter.get("/orders", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 200");
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/orders/:id/confirm", async (req, res) => {
  const { admin_note } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("UPDATE orders SET status='PAID_CONFIRMED', admin_note=? WHERE id=?", [admin_note || "Pembayaran dikonfirmasi.", req.params.id]);
    await conn.query(
      `INSERT INTO order_events (id, order_id, actor_user_id, actor_role, event_type, detail)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), req.params.id, req.user.id, req.user.role, "PAYMENT_CONFIRMED", admin_note || ""]
    );
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/orders/:id/reject", async (req, res) => {
  const { admin_note } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("UPDATE orders SET status='REJECTED', admin_note=? WHERE id=?", [admin_note || "Pembayaran ditolak.", req.params.id]);
    await conn.query(
      `INSERT INTO order_events (id, order_id, actor_user_id, actor_role, event_type, detail)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), req.params.id, req.user.id, req.user.role, "PAYMENT_REJECTED", admin_note || ""]
    );
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// Admin refund wallet (override) - optional
adminRouter.post("/orders/:id/refund-wallet", async (req, res) => {
  if (!adminOnly(req, res)) return;
  const note = String((req.body || {}).note || "Refund wallet by admin");
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();
    const rows = await conn.query("SELECT id, customer_id, amount, pay_method, status FROM orders WHERE id=? FOR UPDATE", [req.params.id]);
    const o = rows[0];
    if (!o) { await conn.rollback(); return res.status(404).json({ error: "Order tidak ditemukan" }); }
    if (o.pay_method !== "WALLET") { await conn.rollback(); return res.status(400).json({ error: "Order ini bukan bayar wallet" }); }
    if (!o.customer_id) { await conn.rollback(); return res.status(400).json({ error: "Order tidak punya customer_id" }); }

    const u = await conn.query("SELECT wallet_balance FROM users WHERE id=? FOR UPDATE", [o.customer_id]);
    if (!u[0]) { await conn.rollback(); return res.status(404).json({ error: "Customer tidak ditemukan" }); }

    const refund = Number(o.amount);
    const newBal = Number(u[0].wallet_balance) + refund;
    await conn.query("UPDATE users SET wallet_balance=? WHERE id=?", [newBal, o.customer_id]);

    await conn.query(
      `INSERT INTO wallet_transactions (id,user_id,type,amount_delta,balance_after,note)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), o.customer_id, "REFUND", refund, newBal, `Refund order ${o.id}: ${note}`]
    );

    await conn.query("UPDATE orders SET status='REJECTED', admin_note=? WHERE id=?", [note, o.id]);

    await conn.commit();
    res.json({ ok: true, balance_after: newBal });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

// topups list + confirm/reject + wallet audit
adminRouter.get("/topups", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(`
      SELECT t.*, u.username, u.email, u.role
      FROM topup_requests t
      JOIN users u ON u.id = t.user_id
      ORDER BY t.created_at DESC
      LIMIT 200
    `);
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/topups/:id/confirm", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const { admin_note } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const rows = await conn.query("SELECT * FROM topup_requests WHERE id=? FOR UPDATE", [req.params.id]);
    if (!rows[0]) { await conn.rollback(); return res.status(404).json({ error: "Topup tidak ditemukan" }); }
    const t = rows[0];
    if (t.status !== "PENDING") { await conn.rollback(); return res.status(400).json({ error: "Topup sudah diproses" }); }

    const u = await conn.query("SELECT wallet_balance FROM users WHERE id=? FOR UPDATE", [t.user_id]);
    if (!u[0]) { await conn.rollback(); return res.status(404).json({ error: "User tidak ditemukan" }); }

    const newBal = Number(u[0].wallet_balance) + Number(t.amount);

    await conn.query("UPDATE users SET wallet_balance=? WHERE id=?", [newBal, t.user_id]);
    await conn.query("UPDATE topup_requests SET status='CONFIRMED', admin_note=? WHERE id=?", [admin_note || null, req.params.id]);

    await conn.query(
      `INSERT INTO wallet_transactions (id,user_id,type,amount_delta,balance_after,note)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), t.user_id, "TOPUP_CONFIRMED", Number(t.amount), newBal, admin_note || "Topup dikonfirmasi admin"]
    );

    await conn.commit();
    res.json({ ok: true, balance_after: newBal });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

adminRouter.post("/topups/:id/reject", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const { admin_note } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM topup_requests WHERE id=? LIMIT 1", [req.params.id]);
    if (!rows[0]) return res.status(404).json({ error: "Topup tidak ditemukan" });
    if (rows[0].status !== "PENDING") return res.status(400).json({ error: "Topup sudah diproses" });

    await conn.query("UPDATE topup_requests SET status='REJECTED', admin_note=? WHERE id=?", [admin_note || null, req.params.id]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// admin edit balance SET/ADD/SUB
adminRouter.post("/users/:id/balance", async (req, res) => {
  if (!adminOnly(req,res)) return;
  const { mode, amount, note } = req.body || {};
  const amt = Number(amount || 0);
  if (!["SET","ADD","SUB"].includes(mode)) return res.status(400).json({ error: "mode invalid" });
  if (!Number.isFinite(amt)) return res.status(400).json({ error: "amount invalid" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const rows = await conn.query("SELECT wallet_balance FROM users WHERE id=? FOR UPDATE", [req.params.id]);
    if (!rows[0]) { await conn.rollback(); return res.status(404).json({ error: "User tidak ditemukan" }); }
    const cur = Number(rows[0].wallet_balance);
    let newBal = cur, delta = 0;

    if (mode === "SET") { newBal = amt; delta = newBal - cur; }
    if (mode === "ADD") { newBal = cur + amt; delta = amt; }
    if (mode === "SUB") { newBal = cur - amt; delta = -amt; }

    if (newBal < 0) { await conn.rollback(); return res.status(400).json({ error: "Saldo tidak boleh minus" }); }

    await conn.query("UPDATE users SET wallet_balance=? WHERE id=?", [newBal, req.params.id]);

    await conn.query(
      `INSERT INTO wallet_transactions (id,user_id,type,amount_delta,balance_after,note)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), req.params.id, "ADMIN_ADJUST", delta, newBal, note || `Admin ${mode}`]
    );

    await conn.commit();
    res.json({ ok: true, balance_after: newBal });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

// admin view driver locations
adminRouter.get("/driver-locations", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(`
      SELECT
        u.id, u.username, u.first_name, u.last_name, u.driver_online, u.is_active,
        dl.lat, dl.lng, dl.accuracy, dl.heading, dl.speed, dl.updated_at
      FROM users u
      LEFT JOIN driver_locations dl ON dl.driver_id = u.id
      WHERE u.role='DRIVER'
      ORDER BY dl.updated_at DESC
      LIMIT 200
    `);
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

module.exports = { adminRouter };
