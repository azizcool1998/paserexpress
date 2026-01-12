const express = require("express");
const bcrypt = require("bcryptjs");
const { nanoid } = require("nanoid");
const { makePoolFromEnv } = require("../db/pool");

const registerRouter = express.Router();

registerRouter.post("/customer", async (req, res) => {
  const { first_name, last_name, email, username, password } = req.body || {};
  if (!first_name || !last_name || !email || !username || !password) {
    return res.status(400).json({ error: "Data belum lengkap" });
  }
  const id = nanoid(12);
  const hash = await bcrypt.hash(password, 12);

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query(
      `INSERT INTO users (id, first_name, last_name, email, username, password_hash, role, is_active)
       VALUES (?,?,?,?,?,?, 'CUSTOMER', 1)`,
      [id, first_name, last_name, email, username, hash]
    );
    res.json({ ok: true, id });
  } catch (e) {
    if (String(e).includes("Duplicate")) return res.status(400).json({ error: "Email/Username sudah dipakai" });
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

registerRouter.post("/driver", async (req, res) => {
  const { first_name, last_name, email, username, password } = req.body || {};
  if (!first_name || !last_name || !email || !username || !password) {
    return res.status(400).json({ error: "Data belum lengkap" });
  }
  const id = nanoid(12);
  const hash = await bcrypt.hash(password, 12);

  const pool = makePoolFromEnv();
  const conn = await pool.getConnection();
  try {
    await conn.query(
      `INSERT INTO users (id, first_name, last_name, email, username, password_hash, role, is_active)
       VALUES (?,?,?,?,?,?, 'DRIVER', 0)`,
      [id, first_name, last_name, email, username, hash]
    );
    res.json({ ok: true, id, message: "Pendaftaran driver diterima. Menunggu persetujuan admin." });
  } catch (e) {
    if (String(e).includes("Duplicate")) return res.status(400).json({ error: "Email/Username sudah dipakai" });
    throw e;
  } finally {
    conn.release();
    await pool.end();
  }
});

module.exports = { registerRouter };
