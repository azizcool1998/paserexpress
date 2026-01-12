const express = require("express");
const path = require("path");
const multer = require("multer");
const { nanoid } = require("nanoid");
const { makePoolFromEnv } = require("../db/pool");
const { requireAuth, requireRole } = require("../middleware/auth");

const driverRouter = express.Router();
driverRouter.use(requireAuth, requireRole(["DRIVER"]));

function haversineKm(lat1, lon1, lat2, lon2) {
  const toRad = (x) => (x * Math.PI) / 180;
  const R = 6371;
  const dLat = toRad(lat2 - lat1);
  const dLon = toRad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) ** 2 +
    Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
  return 2 * R * Math.asin(Math.sqrt(a));
}

driverRouter.get("/me", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(
      "SELECT id,first_name,last_name,email,username,driver_online,is_active,wallet_balance FROM users WHERE id=? LIMIT 1",
      [req.user.id]
    );
    res.json(rows[0] || null);
  } finally {
    conn.release();
    await pool.end();
  }
});

driverRouter.post("/status", async (req, res) => {
  const { online } = req.body || {};
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query("UPDATE users SET driver_online=? WHERE id=?", [online ? 1 : 0, req.user.id]);
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

// realtime location (only if approved)
driverRouter.post("/location", async (req, res) => {
  const { lat, lng, accuracy, heading, speed } = req.body || {};
  const la = Number(lat), ln = Number(lng);
  if (!Number.isFinite(la) || !Number.isFinite(ln)) return res.status(400).json({ error: "lat/lng invalid" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const d = await conn.query("SELECT is_active FROM users WHERE id=? AND role='DRIVER' LIMIT 1", [req.user.id]);
    if (!d[0] || Number(d[0].is_active) !== 1) return res.status(403).json({ error: "Driver belum aktif" });

    await conn.query(
      `INSERT INTO driver_locations (driver_id, lat, lng, accuracy, heading, speed)
       VALUES (?,?,?,?,?,?)
       ON DUPLICATE KEY UPDATE
         lat=VALUES(lat), lng=VALUES(lng),
         accuracy=VALUES(accuracy), heading=VALUES(heading), speed=VALUES(speed),
         updated_at=CURRENT_TIMESTAMP`,
      [req.user.id, la, ln, accuracy == null ? null : Number(accuracy), heading == null ? null : Number(heading), speed == null ? null : Number(speed)]
    );
    res.json({ ok: true });
  } finally {
    conn.release();
    await pool.end();
  }
});

driverRouter.get("/location", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM driver_locations WHERE driver_id=? LIMIT 1", [req.user.id]);
    res.json(rows[0] || null);
  } finally {
    conn.release();
    await pool.end();
  }
});

// driver topup
driverRouter.post("/topup", async (req, res) => {
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

driverRouter.get("/topup", async (req, res) => {
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

driverRouter.get("/wallet/transactions", async (req, res) => {
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

driverRouter.post("/topup/:id/proof", upload.single("file"), async (req, res) => {
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

// available orders (cocolan) + filter
driverRouter.get("/orders/available", async (req, res) => {
  const q = (req.query.q || "").toString().trim().toLowerCase();
  const city = (req.query.city || "").toString().trim().toLowerCase();
  const lat = req.query.lat != null ? Number(req.query.lat) : null;
  const lng = req.query.lng != null ? Number(req.query.lng) : null;
  const radiusKm = req.query.radius_km != null ? Number(req.query.radius_km) : null;
  const mode = (req.query.mode || "pickup").toString(); // pickup|dropoff

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    let rows = await conn.query(
      `SELECT * FROM orders
       WHERE status='PAID_CONFIRMED'
         AND (assigned_driver_id IS NULL OR assigned_driver_id = '')
       ORDER BY created_at DESC
       LIMIT 200`
    );

    if (q) {
      rows = rows.filter(r =>
        String(r.pickup_address || "").toLowerCase().includes(q) ||
        String(r.dropoff_address || "").toLowerCase().includes(q)
      );
    }
    if (city) {
      rows = rows.filter(r =>
        String(r.pickup_city || "").toLowerCase().includes(city) ||
        String(r.dropoff_city || "").toLowerCase().includes(city)
      );
    }
    if (lat != null && lng != null && radiusKm != null && Number.isFinite(radiusKm)) {
      rows = rows.filter(r => {
        const refLat = mode === "dropoff" ? r.dropoff_lat : r.pickup_lat;
        const refLng = mode === "dropoff" ? r.dropoff_lng : r.pickup_lng;
        if (refLat == null || refLng == null) return false;
        const d = haversineKm(lat, lng, Number(refLat), Number(refLng));
        r._distance_km = Math.round(d * 10) / 10;
        return d <= radiusKm;
      });
      rows.sort((a, b) => (a._distance_km ?? 999999) - (b._distance_km ?? 999999));
    }

    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

// driver take order (accept) - only if approved
driverRouter.post("/orders/:id/accept", async (req, res) => {
  const id = req.params.id;
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const d = await conn.query("SELECT is_active FROM users WHERE id=? AND role='DRIVER' FOR UPDATE", [req.user.id]);
    if (!d[0] || Number(d[0].is_active) !== 1) { await conn.rollback(); return res.status(403).json({ error: "Driver belum aktif / belum di-approve" }); }

    const rows = await conn.query("SELECT status, assigned_driver_id FROM orders WHERE id=? FOR UPDATE", [id]);
    if (!rows[0]) { await conn.rollback(); return res.status(404).json({ error: "Order tidak ditemukan" }); }
    const o = rows[0];
    if (o.status !== "PAID_CONFIRMED") { await conn.rollback(); return res.status(400).json({ error: "Order belum siap diambil" }); }
    if (o.assigned_driver_id) { await conn.rollback(); return res.status(400).json({ error: "Order sudah diambil driver lain" }); }

    await conn.query(
      `UPDATE orders SET assigned_driver_id=?, driver_stage='ACCEPTED', status='IN_PROGRESS' WHERE id=?`,
      [req.user.id, id]
    );

    await conn.query(
      `INSERT INTO order_events (id, order_id, actor_user_id, actor_role, event_type, detail)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), id, req.user.id, "DRIVER", "TAKE_ORDER", "Driver mengambil order"]
    );

    await conn.commit();
    res.json({ ok: true });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

// driver cancel before ON_THE_WAY
driverRouter.post("/orders/:id/cancel", async (req, res) => {
  const id = req.params.id;
  const reason = String((req.body || {}).reason || "").trim();
  if (!reason) return res.status(400).json({ error: "reason wajib" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const rows = await conn.query("SELECT assigned_driver_id, driver_stage, status FROM orders WHERE id=? FOR UPDATE", [id]);
    if (!rows[0]) { await conn.rollback(); return res.status(404).json({ error: "Order tidak ditemukan" }); }
    const o = rows[0];

    if (o.assigned_driver_id !== req.user.id) { await conn.rollback(); return res.status(403).json({ error: "Bukan order kamu" }); }
    if (!["ASSIGNED","ACCEPTED","NONE"].includes(o.driver_stage)) {
      await conn.rollback();
      return res.status(400).json({ error: "Tidak bisa cancel setelah mulai jalan" });
    }

    await conn.query(
      `UPDATE orders SET assigned_driver_id=NULL, driver_stage='NONE', status='PAID_CONFIRMED' WHERE id=?`,
      [id]
    );

    await conn.query(
      `INSERT INTO order_events (id, order_id, actor_user_id, actor_role, event_type, detail)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), id, req.user.id, "DRIVER", "DRIVER_CANCELLED", reason]
    );

    await conn.commit();
    res.json({ ok: true });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

driverRouter.get("/orders", async (req, res) => {
  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query(
      `SELECT * FROM orders WHERE assigned_driver_id=? AND status IN ('IN_PROGRESS','DELIVERED') ORDER BY created_at DESC LIMIT 100`,
      [req.user.id]
    );
    res.json(rows);
  } finally {
    conn.release();
    await pool.end();
  }
});

// stage flow ala Maxim
const ALLOWED_NEXT = {
  ASSIGNED: ["ACCEPTED"],
  ACCEPTED: ["ON_THE_WAY"],
  ON_THE_WAY: ["ARRIVED"],
  ARRIVED: ["DELIVERED"],
  DELIVERED: []
};

driverRouter.post("/orders/:id/stage", async (req, res) => {
  const id = req.params.id;
  const stage = (req.body || {}).stage;

  if (!["ACCEPTED","ON_THE_WAY","ARRIVED","DELIVERED"].includes(stage)) {
    return res.status(400).json({ error: "stage invalid" });
  }

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.beginTransaction();

    const rows = await conn.query("SELECT driver_stage,status FROM orders WHERE id=? AND assigned_driver_id=? FOR UPDATE", [id, req.user.id]);
    if (!rows[0]) { await conn.rollback(); return res.status(404).json({ error: "Order tidak ditemukan" }); }

    const curStage = rows[0].driver_stage || "NONE";
    const status = rows[0].status;

    const effectiveCur = curStage === "NONE" ? "ASSIGNED" : curStage;
    const nextAllowed = ALLOWED_NEXT[effectiveCur] || [];
    if (!nextAllowed.includes(stage)) {
      await conn.rollback();
      return res.status(400).json({ error: `Transisi tidak valid: ${effectiveCur} -> ${stage}` });
    }

    if (stage === "DELIVERED") {
      await conn.query(
        "UPDATE orders SET driver_stage='DELIVERED', delivered_confirmed=1, delivered_confirmed_at=NOW(), status='DELIVERED' WHERE id=?",
        [id]
      );
    } else {
      await conn.query("UPDATE orders SET driver_stage=?, status='IN_PROGRESS' WHERE id=?", [stage, id]);
    }

    await conn.query(
      `INSERT INTO order_events (id, order_id, actor_user_id, actor_role, event_type, detail)
       VALUES (?,?,?,?,?,?)`,
      [nanoid(12), id, req.user.id, "DRIVER", "STAGE_CHANGE", `${effectiveCur} -> ${stage}`]
    );

    await conn.commit();
    res.json({ ok: true, stage });
  } catch (e) {
    try { await conn.rollback(); } catch {}
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

module.exports = { driverRouter };
