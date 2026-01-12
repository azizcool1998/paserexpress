require("dotenv").config();
const express = require("express");
const helmet = require("helmet");

const { publicRouter } = require("./routes/public");
const { authRouter } = require("./routes/auth");
const { registerRouter } = require("./routes/register");
const { customerRouter } = require("./routes/customer");
const { driverRouter } = require("./routes/driver");
const { adminRouter } = require("./routes/admin");

const app = express();

app.use(helmet({ crossOriginResourcePolicy: false }));
app.use(express.json({ limit: "2mb" }));

// serve uploads
app.use("/uploads", express.static(process.env.UPLOAD_DIR));

// API routers
app.use("/api/public", publicRouter);
app.use("/api/auth", authRouter);
app.use("/api/register", registerRouter);
app.use("/api/customer", customerRouter);
app.use("/api/driver", driverRouter);
app.use("/api/admin", adminRouter);

app.get("/api/health", (req, res) => res.json({ ok: true, name: process.env.APP_NAME || "Paser Express" }));

const host = process.env.BIND_HOST || "0.0.0.0";
const port = Number(process.env.PORT || 8081);
app.listen(port, host, () => {
  console.log(`✅ Backend listening on http://${host}:${port}`);
});
