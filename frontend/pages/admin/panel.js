import { useEffect, useMemo, useState } from "react";
import ThemeSync from "../../components/ThemeSync";
import dynamic from "next/dynamic";
import Link from "next/link";

const DriverLocationsMap = dynamic(() => import("../../pages_shared/DriverLocationsMap"), { ssr: false });

export default function AdminPanel() {
  const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;
  const role = typeof window !== "undefined" ? localStorage.getItem("role") : null;

  const [tab, setTab] = useState("orders");
  const [msg, setMsg] = useState("");

  const [users, setUsers] = useState([]);
  const [services, setServices] = useState([]);
  const [settings, setSettings] = useState({});
  const [orders, setOrders] = useState([]);
  const [topups, setTopups] = useState([]);
  const [driverLocs, setDriverLocs] = useState([]);

  const isAdmin = role === "ADMIN";

  useEffect(() => {
    if (!token) return (window.location.href = "/admin/login");
    if (!["ADMIN","STAFF"].includes(role)) return (window.location.href = "/");
    loadAll();
  }, []);

  async function apiGet(path) {
    return fetch(path, { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
  }

  async function apiPost(path, body, isForm = false) {
    const headers = { Authorization: `Bearer ${token}` };
    if (!isForm) headers["Content-Type"] = "application/json";
    return fetch(path, {
      method: "POST",
      headers,
      body: isForm ? body : JSON.stringify(body || {})
    }).then(r => r.json());
  }

  async function loadAll() {
    setMsg("");
    const [u, s, st, o, t, dl] = await Promise.all([
      apiGet("/api/admin/users"),
      apiGet("/api/admin/services"),
      apiGet("/api/admin/settings"),
      apiGet("/api/admin/orders"),
      apiGet("/api/admin/topups"),
      apiGet("/api/admin/driver-locations")
    ]);
    setUsers(u || []);
    setServices(s || []);
    setSettings(st || {});
    setOrders(o || []);
    setTopups(t || []);
    setDriverLocs(dl || []);
  }

  function logout() {
    localStorage.removeItem("token");
    localStorage.removeItem("role");
    window.location.href = "/";
  }

  async function setUserRole(id, r) {
    const res = await apiPost(`/api/admin/users/${id}/role`, { role: r });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function setUserActive(id, active) {
    const res = await apiPost(`/api/admin/users/${id}/active`, { is_active: active });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function approveDriver(id) {
    const res = await apiPost(`/api/admin/drivers/${id}/approve`, {});
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function saveSetting(k, v) {
    const res = await apiPost("/api/admin/settings", { k, v });
    if (res.error) return setMsg(res.error);
    setMsg("Setting tersimpan.");
    loadAll();
  }

  async function uploadQris(file) {
    if (!file) return;
    const fd = new FormData();
    fd.append("file", file);
    const res = await apiPost("/api/admin/settings/qris-image", fd, true);
    if (res.error) return setMsg(res.error);
    setMsg("QRIS image diupdate.");
    loadAll();
  }

  async function addService() {
    const title = prompt("Nama layanan?") || "";
    if (!title.trim()) return;
    const res = await apiPost("/api/admin/services", { title, description: "", sort_order: 0, is_active: true });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function editService(svc) {
    const title = prompt("Ubah title", svc.title) ?? svc.title;
    const description = prompt("Ubah deskripsi", svc.description || "") ?? (svc.description || "");
    const sort_order = Number(prompt("Sort order", String(svc.sort_order || 0)) ?? (svc.sort_order || 0));
    const is_active = confirm("Aktifkan layanan? (OK=aktif, Cancel=nonaktif)");
    const res = await apiPost(`/api/admin/services/${svc.id}`, { title, description, sort_order, is_active });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function deleteService(id) {
    if (!confirm("Hapus layanan?")) return;
    const res = await apiPost(`/api/admin/services/${id}/delete`, {});
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function confirmOrder(id) {
    const note = prompt("Catatan admin (opsional)", "Pembayaran dikonfirmasi.") || "";
    const res = await apiPost(`/api/admin/orders/${id}/confirm`, { admin_note: note });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function rejectOrder(id) {
    const note = prompt("Alasan penolakan", "Pembayaran ditolak.") || "";
    const res = await apiPost(`/api/admin/orders/${id}/reject`, { admin_note: note });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function refundWallet(id) {
    if (!isAdmin) return setMsg("ADMIN only");
    const note = prompt("Catatan refund", "Refund wallet by admin") || "";
    const res = await apiPost(`/api/admin/orders/${id}/refund-wallet`, { note });
    if (res.error) return setMsg(res.error);
    setMsg("Refund sukses.");
    loadAll();
  }

  async function confirmTopup(id) {
    if (!isAdmin) return setMsg("ADMIN only");
    const note = prompt("Catatan admin (opsional)", "Topup dikonfirmasi admin") || "";
    const res = await apiPost(`/api/admin/topups/${id}/confirm`, { admin_note: note });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function rejectTopup(id) {
    if (!isAdmin) return setMsg("ADMIN only");
    const note = prompt("Alasan reject", "Bukti tidak valid") || "";
    const res = await apiPost(`/api/admin/topups/${id}/reject`, { admin_note: note });
    if (res.error) return setMsg(res.error);
    loadAll();
  }

  async function editBalance(userId) {
    if (!isAdmin) return setMsg("ADMIN only");
    const mode = prompt("Mode: SET / ADD / SUB", "ADD") || "ADD";
    const amount = Number(prompt("Nominal (Rp)", "10000") || "0");
    const note = prompt("Catatan", "Admin adjust") || "";
    const res = await apiPost(`/api/admin/users/${userId}/balance`, { mode, amount, note });
    if (res.error) return setMsg(res.error);
    setMsg("Saldo diupdate.");
    loadAll();
  }

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
            <div>
              <h2>Admin Panel</h2>
              <p className="small">Role: <span className="badge">{role}</span></p>
            </div>
            <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
              <Link className="btn secondary" href="/">Beranda</Link>
              <button className="btn secondary" onClick={logout}>Logout</button>
            </div>
          </div>

          <div style={{ display:"flex", gap:10, flexWrap:"wrap", marginTop:10 }}>
            <button className="btn secondary" onClick={()=>setTab("orders")}>Orders</button>
            <button className="btn secondary" onClick={()=>setTab("topups")}>Topups</button>
            <button className="btn secondary" onClick={()=>setTab("users")}>Users</button>
            <button className="btn secondary" onClick={()=>setTab("services")}>Services</button>
            <button className="btn secondary" onClick={()=>setTab("settings")}>Settings/Theme</button>
            <button className="btn secondary" onClick={()=>setTab("drivers")}>Driver Locations</button>
            <button className="btn secondary" onClick={loadAll}>Refresh</button>
          </div>

          {msg && <p className="small" style={{ marginTop:10 }}>{msg}</p>}

          <hr />

          {tab === "orders" && (
            <>
              <h3>Orders</h3>
              <div className="grid">
                {orders.map(o => (
                  <div key={o.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>{o.id}</b>
                      <span className="badge">{o.status}</span>
                    </div>
                    <p className="small">
                      Pay: <span className="badge">{o.pay_method}</span> — Rp {o.amount} — Stage: <span className="badge">{o.driver_stage}</span>
                    </p>
                    <p className="small"><b>Nama:</b> {o.customer_name} — <b>WA:</b> {o.customer_phone}</p>
                    <p className="small"><b>Pickup:</b> {o.pickup_address} ({o.pickup_city || "-"})</p>
                    <p className="small"><b>Tujuan:</b> {o.dropoff_address} ({o.dropoff_city || "-"})</p>
                    {o.proof_image_path && <a className="btn secondary" href={o.proof_image_path} target="_blank" rel="noreferrer">Lihat Bukti</a>}
                    {o.admin_note && <p className="small">Catatan: {o.admin_note}</p>}

                    <div style={{ display:"flex", gap:10, flexWrap:"wrap", marginTop:10 }}>
                      {(o.status === "PENDING_REVIEW" || o.status === "PENDING_PAYMENT") && (
                        <>
                          <button className="btn" onClick={()=>confirmOrder(o.id)}>Confirm</button>
                          <button className="btn secondary" onClick={()=>rejectOrder(o.id)}>Reject</button>
                        </>
                      )}
                      {o.pay_method === "WALLET" && isAdmin && (
                        <button className="btn secondary" onClick={()=>refundWallet(o.id)}>Refund Wallet</button>
                      )}
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}

          {tab === "topups" && (
            <>
              <h3>Topup Requests</h3>
              <p className="small">Confirm topup akan menambah saldo otomatis + catat transaksi.</p>
              <div className="grid">
                {topups.map(t => (
                  <div key={t.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>{t.id}</b>
                      <span className="badge">{t.status}</span>
                    </div>
                    <p className="small">User: @{t.username} ({t.role}) — Rp {t.amount} — {t.method}</p>
                    {t.proof_image_path && <a className="btn secondary" href={t.proof_image_path} target="_blank" rel="noreferrer">Lihat Bukti</a>}
                    {t.admin_note && <p className="small">Catatan: {t.admin_note}</p>}
                    <div style={{ display:"flex", gap:10, flexWrap:"wrap", marginTop:10 }}>
                      {t.status === "PENDING" && (
                        <>
                          <button className="btn" disabled={!isAdmin} onClick={()=>confirmTopup(t.id)}>Confirm</button>
                          <button className="btn secondary" disabled={!isAdmin} onClick={()=>rejectTopup(t.id)}>Reject</button>
                        </>
                      )}
                    </div>
                    {!isAdmin && <p className="small">*Hanya ADMIN bisa confirm/reject topup.</p>}
                  </div>
                ))}
              </div>
            </>
          )}

          {tab === "users" && (
            <>
              <h3>Users</h3>
              <p className="small">ADMIN bisa ubah role, aktif/nonaktif, approve driver, dan edit saldo.</p>
              <div className="grid">
                {users.map(u => (
                  <div key={u.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>@{u.username}</b>
                      <span className="badge">{u.role}</span>
                    </div>
                    <p className="small">{u.first_name} {u.last_name} — {u.email}</p>
                    <p className="small">Active: <span className="badge">{u.is_active ? "YES" : "NO"}</span> — Saldo: <b>Rp {u.wallet_balance}</b></p>
                    <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
                      {isAdmin && (
                        <>
                          <select className="input" defaultValue={u.role} onChange={e=>setUserRole(u.id, e.target.value)}>
                            <option value="ADMIN">ADMIN</option>
                            <option value="STAFF">STAFF</option>
                            <option value="DRIVER">DRIVER</option>
                            <option value="CUSTOMER">CUSTOMER</option>
                          </select>
                          <button className="btn secondary" onClick={()=>setUserActive(u.id, !u.is_active)}>{u.is_active ? "Disable" : "Enable"}</button>
                          <button className="btn secondary" onClick={()=>editBalance(u.id)}>Edit Saldo</button>
                          {u.role === "DRIVER" && Number(u.is_active) !== 1 && (
                            <button className="btn" onClick={()=>approveDriver(u.id)}>Approve Driver</button>
                          )}
                        </>
                      )}
                      {!isAdmin && <span className="small">*Staff read-only.</span>}
                    </div>
                  </div>
                ))}
              </div>
            </>
          )}

          {tab === "services" && (
            <>
              <h3>Services (Layanan)</h3>
              {isAdmin && <button className="btn" onClick={addService}>Tambah Layanan</button>}
              {!isAdmin && <p className="small">*Staff read-only.</p>}
              <div className="grid" style={{ marginTop: 10 }}>
                {services.map(s => (
                  <div key={s.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>{s.title}</b>
                      <span className="badge">{s.is_active ? "ACTIVE" : "OFF"}</span>
                    </div>
                    <p className="small">{s.description}</p>
                    <p className="small">sort_order: {s.sort_order}</p>
                    {isAdmin && (
                      <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
                        <button className="btn secondary" onClick={()=>editService(s)}>Edit</button>
                        <button className="btn secondary" onClick={()=>deleteService(s.id)}>Delete</button>
                      </div>
                    )}
                  </div>
                ))}
              </div>
            </>
          )}

          {tab === "settings" && (
            <>
              <h3>Settings / Theme Editor</h3>
              <p className="small">Ubah warna tema, radius, dan setting pembayaran.</p>

              <div className="grid grid2">
                <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                  <h4>Theme</h4>
                  <label className="small">Primary</label>
                  <input className="input" defaultValue={settings["theme.primary"] || ""} onBlur={e=>saveSetting("theme.primary", e.target.value)} />
                  <div style={{ height: 10 }} />
                  <label className="small">Background</label>
                  <input className="input" defaultValue={settings["theme.bg"] || ""} onBlur={e=>saveSetting("theme.bg", e.target.value)} />
                  <div style={{ height: 10 }} />
                  <label className="small">Card</label>
                  <input className="input" defaultValue={settings["theme.card"] || ""} onBlur={e=>saveSetting("theme.card", e.target.value)} />
                  <div style={{ height: 10 }} />
                  <label className="small">Text</label>
                  <input className="input" defaultValue={settings["theme.text"] || ""} onBlur={e=>saveSetting("theme.text", e.target.value)} />
                  <div style={{ height: 10 }} />
                  <label className="small">Radius (px)</label>
                  <input className="input" defaultValue={settings["theme.radius"] || "16"} onBlur={e=>saveSetting("theme.radius", e.target.value)} />
                </div>

                <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                  <h4>Pembayaran</h4>
                  <label className="small">No GoPay</label>
                  <input className="input" defaultValue={settings["payment.gopay_number"] || ""} onBlur={e=>saveSetting("payment.gopay_number", e.target.value)} />
                  <div style={{ height: 10 }} />
                  <label className="small">Warning text</label>
                  <textarea className="input" rows={4} defaultValue={settings["payment.warning"] || ""} onBlur={e=>saveSetting("payment.warning", e.target.value)} />
                  <div style={{ height: 10 }} />
                  <label className="small">Upload QRIS Image</label>
                  <input className="input" type="file" accept="image/*" onChange={e=>uploadQris(e.target.files?.[0] || null)} />
                  {settings["payment.qris_image"] && (
                    <img src={settings["payment.qris_image"]} alt="QRIS" style={{ width:"100%", borderRadius: 12, marginTop: 10 }} />
                  )}
                </div>
              </div>
              <p className="small">*Perubahan theme akan ter-apply otomatis (ThemeSync) setelah refresh.</p>
            </>
          )}

          {tab === "drivers" && (
            <>
              <h3>Driver Locations</h3>
              <p className="small">Lihat posisi terakhir driver (yang mengirim lokasi). Refresh otomatis dengan tombol Refresh.</p>
              <DriverLocationsMap rows={driverLocs} />
              <div className="grid" style={{ marginTop: 10 }}>
                {driverLocs.map(d => (
                  <div key={d.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>@{d.username}</b>
                      <span className="badge">{d.driver_online ? "ONLINE" : "OFFLINE"}</span>
                    </div>
                    <p className="small">{d.first_name} {d.last_name} — Active: {d.is_active ? "YES" : "NO"}</p>
                    {d.lat != null ? (
                      <p className="small">LatLng: {Number(d.lat).toFixed(6)}, {Number(d.lng).toFixed(6)} (±{Math.round(d.accuracy || 0)}m)</p>
                    ) : (
                      <p className="small">Belum ada lokasi.</p>
                    )}
                  </div>
                ))}
              </div>
            </>
          )}
        </div>
      </div>
    </>
  );
}
