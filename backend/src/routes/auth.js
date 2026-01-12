const express = require("express");
const bcrypt = require("bcryptjs");
const jwt = require("jsonwebtoken");
const { makePoolFromEnv } = require("../db/pool");

const authRouter = express.Router();

authRouter.post("/login", async (req, res) => {
  const { username, password } = req.body || {};
  if (!username || !password) return res.status(400).json({ error: "Username/password wajib" });

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    const rows = await conn.query("SELECT * FROM users WHERE username=? LIMIT 1", [username]);
    const u = rows[0];
    if (!u) return res.status(400).json({ error: "Login gagal" });

    // CUSTOMER/ADMIN/STAFF wajib aktif, DRIVER boleh login walau pending
    if (Number(u.is_active) !== 1 && u.role !== "DRIVER") {
      return res.status(403).json({ error: "Akun belum aktif/ditolak" });
    }

    const ok = await bcrypt.compare(password, u.password_hash);
    if (!ok) return res.status(400).json({ error: "Login gagal" });

    const token = jwt.sign({ sub: u.id, role: u.role }, process.env.JWT_SECRET, { expiresIn: "7d" });
    res.json({
      token,
      user: { id: u.id, username: u.username, email: u.email, role: u.role, is_active: u.is_active }
    });
  } finally {
    conn.release();
    await pool.end();
  }
});

module.exports = { authRouter };
