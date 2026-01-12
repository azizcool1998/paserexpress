import { useEffect, useState } from "react";
import ThemeSync from "../../components/ThemeSync";
import dynamic from "next/dynamic";
import Link from "next/link";

const DriverMap = dynamic(() => import("../../pages_shared/DriverPanelMap"), { ssr: false });

export default function DriverPanel() {
  const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;

  const [me, setMe] = useState(null);
  const [orders, setOrders] = useState([]);
  const [available, setAvailable] = useState([]);
  const [msg, setMsg] = useState("");

  const [filter, setFilter] = useState({ q: "", city: "", radius_km: 10, mode: "pickup" });
  const [geo, setGeo] = useState({ lat: null, lng: null });

  const [topForm, setTopForm] = useState({ amount: 0, method: "QRIS" });
  const [topups, setTopups] = useState([]);
  const [selectedTopupId, setSelectedTopupId] = useState("");
  const [file, setFile] = useState(null);
  const [tx, setTx] = useState([]);

  useEffect(() => {
    if (!token) return (window.location.href = "/driver/login");
    const role = localStorage.getItem("role");
    if (role && role !== "DRIVER") {
      if (role === "CUSTOMER") window.location.href = "/akun/dashboard";
      else window.location.href = "/admin/panel";
      return;
    }

    // track geolocation for filtering & sending location
    if (navigator.geolocation) {
      navigator.geolocation.watchPosition(
        (pos) => setGeo({ lat: pos.coords.latitude, lng: pos.coords.longitude, accuracy: pos.coords.accuracy, heading: pos.coords.heading, speed: pos.coords.speed }),
        () => {},
        { enableHighAccuracy: true, maximumAge: 5000, timeout: 15000 }
      );
    }

    loadAll();
    const t = setInterval(() => {
      loadOrders();
      loadAvailable();
      sendLocation();
    }, 6000);
    return () => clearInterval(t);
  }, []);

  async function loadMe() {
    const m = await fetch("/api/driver/me", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setMe(m);
  }

  async function loadOrders() {
    const o = await fetch("/api/driver/orders", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setOrders(o || []);
  }

  async function loadAvailable() {
    const params = new URLSearchParams();
    if (filter.q) params.set("q", filter.q);
    if (filter.city) params.set("city", filter.city);
    if (geo.lat != null && geo.lng != null) {
      params.set("lat", geo.lat);
      params.set("lng", geo.lng);
      params.set("radius_km", filter.radius_km);
      params.set("mode", filter.mode);
    }
    const a = await fetch(`/api/driver/orders/available?${params.toString()}`, { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setAvailable(a || []);
  }

  async function loadTopups() {
    const t = await fetch("/api/driver/topup", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setTopups(t || []);
    const w = await fetch("/api/driver/wallet/transactions", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setTx(w || []);
  }

  async function loadAll() {
    await loadMe();
    await loadOrders();
    await loadAvailable();
    await loadTopups();
  }

  async function setOnline(online) {
    setMsg("");
    const res = await fetch("/api/driver/status", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify({ online })
    }).then(r => r.json());
    if (res.error) return setMsg(res.error);
    loadMe();
  }

  async function sendLocation() {
    if (geo.lat == null || geo.lng == null) return;
    await fetch("/api/driver/location", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify({ lat: geo.lat, lng: geo.lng, accuracy: geo.accuracy, heading: geo.heading, speed: geo.speed })
    }).catch(()=>{});
  }

  async function takeOrder(id) {
    setMsg("");
    const res = await fetch(`/api/driver/orders/${id}/accept`, {
      method:"POST",
      headers: { Authorization: `Bearer ${token}` }
    }).then(r=>r.json());
    if (res.error) return setMsg(res.error);
    setMsg("Order diambil.");
    loadAll();
  }

  async function cancelOrder(id) {
    const reason = prompt("Alasan cancel?") || "";
    if (!reason.trim()) return;
    setMsg("");
    const res = await fetch(`/api/driver/orders/${id}/cancel`, {
      method:"POST",
      headers: { "Content-Type":"application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify({ reason })
    }).then(r=>r.json());
    if (res.error) return setMsg(res.error);
    setMsg("Order dibatalkan.");
    loadAll();
  }

  async function setStage(id, stage) {
    setMsg("");
    const res = await fetch(`/api/driver/orders/${id}/stage`, {
      method:"POST",
      headers: { "Content-Type":"application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify({ stage })
    }).then(r=>r.json());
    if (res.error) return setMsg(res.error);
    loadOrders();
  }

  async function createTopup() {
    setMsg("");
    const res = await fetch("/api/driver/topup", {
      method: "POST",
      headers: { "Content-Type":"application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify(topForm)
    }).then(r=>r.json());
    if (res.error) return setMsg(res.error);
    setSelectedTopupId(res.id);
    setMsg("Request topup dibuat. Upload bukti.");
    loadTopups();
  }

  async function uploadTopupProof() {
    setMsg("");
    if (!selectedTopupId) return setMsg("Pilih request topup dulu");
    if (!file) return setMsg("Pilih file bukti dulu");
    const fd = new FormData();
    fd.append("file", file);
    const res = await fetch(`/api/driver/topup/${selectedTopupId}/proof`, {
      method: "POST",
      headers: { Authorization: `Bearer ${token}` },
      body: fd
    }).then(r=>r.json());
    if (res.error) return setMsg(res.error);
    setMsg("Bukti topup dikirim. Menunggu verifikasi admin.");
    loadTopups();
  }

  function logout() {
    localStorage.removeItem("token");
    localStorage.removeItem("role");
    window.location.href = "/";
  }

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
            <div>
              <h2>Panel Driver</h2>
              {me && (
                <p className="small">
                  @{me.username} — Status akun: <span className="badge">{Number(me.is_active) === 1 ? "APPROVED" : "PENDING"}</span>{" "}
                  — Online: <span className="badge">{me.driver_online ? "ON" : "OFF"}</span>{" "}
                  — Saldo: <b>Rp {me.wallet_balance}</b>
                </p>
              )}
              <p className="small">Ambil order manual (cocolan). Tidak auto-assign.</p>
            </div>
            <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
              <button className="btn secondary" onClick={() => setOnline(true)}>Online</button>
              <button className="btn secondary" onClick={() => setOnline(false)}>Offline</button>
              <Link className="btn secondary" href="/">Beranda</Link>
              <button className="btn secondary" onClick={logout}>Logout</button>
            </div>
          </div>

          {msg && <p className="small" style={{ marginTop:10 }}>{msg}</p>}

          <div className="grid grid2" style={{ marginTop:12 }}>
            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Filter Order Tersedia</h3>
              <input className="input" placeholder="Cari alamat..." value={filter.q} onChange={e=>setFilter({...filter, q:e.target.value})} />
              <div style={{ height:10 }} />
              <input className="input" placeholder="Filter kota..." value={filter.city} onChange={e=>setFilter({...filter, city:e.target.value})} />
              <div style={{ height:10 }} />
              <div className="grid grid2">
                <input className="input" type="number" placeholder="Radius (km)" value={filter.radius_km}
                  onChange={e=>setFilter({...filter, radius_km:e.target.value})} />
                <select className="input" value={filter.mode} onChange={e=>setFilter({...filter, mode:e.target.value})}>
                  <option value="pickup">Dekat Pickup</option>
                  <option value="dropoff">Dekat Tujuan</option>
                </select>
              </div>
              <div style={{ height:10 }} />
              <button className="btn secondary" onClick={loadAvailable}>Refresh</button>

              <hr />
              <h3>Order Tersedia</h3>
              <div className="grid">
                {available.map(o => (
                  <div key={o.id} className="card" style={{ background:"rgba(0,0,0,.2)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>{o.id}</b>
                      {o._distance_km != null && <span className="badge">{o._distance_km} km</span>}
                    </div>
                    <p className="small"><b>Pickup:</b> {o.pickup_address} ({o.pickup_city || "-"})</p>
                    <p className="small"><b>Tujuan:</b> {o.dropoff_address} ({o.dropoff_city || "-"})</p>
                    <p className="small"><b>Nominal:</b> Rp {o.amount}</p>
                    <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
                      <button className="btn" onClick={() => takeOrder(o.id)}>Ambil Order</button>
                      <a className="btn secondary" target="_blank" rel="noreferrer"
                        href={`https://www.google.com/maps/dir/?api=1&origin=${o.pickup_lat},${o.pickup_lng}&destination=${o.dropoff_lat},${o.dropoff_lng}`}>
                        Maps
                      </a>
                    </div>
                  </div>
                ))}
                {available.length === 0 && <p className="small">Tidak ada order tersedia.</p>}
              </div>
            </div>

            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Order Saya</h3>
              <div className="grid">
                {orders.map(o => (
                  <div key={o.id} className="card" style={{ background:"rgba(0,0,0,.2)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                      <b>{o.id}</b>
                      <span className="badge">{o.driver_stage}</span>
                    </div>
                    <p className="small"><b>Pickup:</b> {o.pickup_address}</p>
                    <p className="small"><b>Tujuan:</b> {o.dropoff_address}</p>

                    <DriverMap order={o} driverGeo={geo} />

                    <div style={{ display:"flex", gap:10, flexWrap:"wrap", marginTop:10 }}>
                      {o.driver_stage === "ACCEPTED" && (
                        <button className="btn" onClick={()=>setStage(o.id,"ON_THE_WAY")}>Mulai Jalan</button>
                      )}
                      {o.driver_stage === "ON_THE_WAY" && (
                        <button className="btn" onClick={()=>setStage(o.id,"ARRIVED")}>Sudah Sampai</button>
                      )}
                      {o.driver_stage === "ARRIVED" && (
                        <button className="btn" onClick={()=>setStage(o.id,"DELIVERED")}>Konfirmasi Terkirim</button>
                      )}
                      {(o.driver_stage === "ACCEPTED") && (
                        <button className="btn secondary" onClick={()=>cancelOrder(o.id)}>Cancel</button>
                      )}
                    </div>
                  </div>
                ))}
                {orders.length === 0 && <p className="small">Belum ada order yang kamu ambil.</p>}
              </div>

              <hr />
              <h3>Top-up Saldo Driver</h3>
              <input className="input" type="number" placeholder="Nominal (Rp)" value={topForm.amount}
                onChange={e=>setTopForm({...topForm, amount:e.target.value})}/>
              <div style={{ height:10 }} />
              <select className="input" value={topForm.method} onChange={e=>setTopForm({...topForm, method:e.target.value})}>
                <option value="QRIS">QRIS</option>
                <option value="GOPAY">GoPay</option>
                <option value="TRANSFER">Transfer</option>
                <option value="OTHER">Lainnya</option>
              </select>
              <div style={{ height:10 }} />
              <button className="btn" onClick={createTopup}>Buat Request Topup</button>

              <div style={{ height:10 }} />
              <select className="input" value={selectedTopupId} onChange={e=>setSelectedTopupId(e.target.value)}>
                <option value="">Pilih request topup</option>
                {topups.map(t => (
                  <option key={t.id} value={t.id}>{t.id} — Rp {t.amount} — {t.status}</option>
                ))}
              </select>
              <div style={{ height:10 }} />
              <input className="input" type="file" accept="image/*" onChange={e=>setFile(e.target.files?.[0]||null)} />
              <div style={{ height:10 }} />
              <button className="btn secondary" onClick={uploadTopupProof}>Upload Bukti</button>

              <hr />
              <h3>Transaksi Saldo</h3>
              <div className="grid">
                {tx.map(x => (
                  <div key={x.id} className="card" style={{ background:"rgba(0,0,0,.2)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10 }}>
                      <b>{x.type}</b><span className="badge">{x.amount_delta}</span>
                    </div>
                    <p className="small">Saldo setelah: Rp {x.balance_after}</p>
                    {x.note && <p className="small">{x.note}</p>}
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
