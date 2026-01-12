const jwt = require("jsonwebtoken");
const { makePoolFromEnv } = require("../db/pool");

async function requireAuth(req, res, next) {
  const hdr = req.headers.authorization || "";
  const token = hdr.startsWith("Bearer ") ? hdr.slice(7) : null;
  if (!token) return res.status(401).json({ error: "Unauthorized" });

  try {
    const payload = jwt.verify(token, process.env.JWT_SECRET);
    const pool = makePoolFromEnv();
    const conn = await pool.getConnection();
    try {
      const rows = await conn.query(
        "SELECT id, username, email, role, is_active, driver_online FROM users WHERE id=? LIMIT 1",
        [payload.sub]
      );
      const u = rows[0];
      if (!u) return res.status(401).json({ error: "Unauthorized" });

      // CUSTOMER/ADMIN/STAFF harus aktif.
      // DRIVER boleh login walau belum aktif (biar bisa lihat status pending), tapi beberapa aksi akan diblokir di route.
      if (Number(u.is_active) !== 1 && u.role !== "DRIVER") {
        return res.status(401).json({ error: "Account inactive" });
      }

      req.user = u;
      next();
    } finally {
      conn.release();
      await pool.end();
    }
  } catch {
    return res.status(401).json({ error: "Invalid token" });
  }
}

function requireRole(roles) {
  return (req, res, next) => {
    if (!req.user) return res.status(401).json({ error: "Unauthorized" });
    if (!roles.includes(req.user.role)) return res.status(403).json({ error: "Forbidden" });
    next();
  };
}

module.exports = { requireAuth, requireRole };
