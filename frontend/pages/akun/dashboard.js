import { useEffect, useState } from "react";
import ThemeSync from "../../components/ThemeSync";
import Link from "next/link";
import MapPicker from "../../components/MapPicker";

export default function CustomerDashboard() {
  const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;

  const [me, setMe] = useState(null);
  const [orders, setOrders] = useState([]);
  const [msg, setMsg] = useState("");

  const [home, setHome] = useState({
    home_address: "",
    home_city: "",
    point: null
  });

  const [topups, setTopups] = useState([]);
  const [tx, setTx] = useState([]);
  const [topForm, setTopForm] = useState({ amount: 0, method: "QRIS" });
  const [selectedTopupId, setSelectedTopupId] = useState("");
  const [file, setFile] = useState(null);

  async function load() {
    const m = await fetch("/api/customer/me", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setMe(m);

    setHome({
      home_address: m?.home_address || "",
      home_city: m?.home_city || "",
      point: (m?.home_lat != null && m?.home_lng != null) ? { lat: Number(m.home_lat), lng: Number(m.home_lng) } : null
    });

    const o = await fetch("/api/customer/orders", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setOrders(o || []);

    const t = await fetch("/api/customer/topup", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setTopups(t || []);
    const w = await fetch("/api/customer/wallet/transactions", { headers: { Authorization: `Bearer ${token}` } }).then(r => r.json());
    setTx(w || []);
  }

  useEffect(() => {
    if (!token) return (window.location.href = "/akun/login");
    const role = localStorage.getItem("role");
    if (role && role !== "CUSTOMER") {
      if (role === "DRIVER") window.location.href = "/driver/panel";
      else window.location.href = "/admin/panel";
      return;
    }
    load();
  }, []);

  function logout() {
    localStorage.removeItem("token");
    localStorage.removeItem("role");
    window.location.href = "/";
  }

  async function saveHome() {
    setMsg("");
    if (!home.home_address || !home.home_city || !home.point) return setMsg("Alamat, kota, dan titik rumah wajib diisi");

    const res = await fetch("/api/customer/profile", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify({
        home_address: home.home_address,
        home_city: home.home_city,
        home_lat: home.point.lat,
        home_lng: home.point.lng
      })
    }).then(r => r.json());

    if (res.error) return setMsg(res.error);
    setMsg("Lokasi rumah tersimpan.");
    load();
  }

  async function createTopup() {
    setMsg("");
    const res = await fetch("/api/customer/topup", {
      method: "POST",
      headers: { "Content-Type": "application/json", Authorization: `Bearer ${token}` },
      body: JSON.stringify(topForm)
    }).then(r => r.json());

    if (res.error) return setMsg(res.error);
    setSelectedTopupId(res.id);
    setMsg("Request topup dibuat. Pilih request lalu upload bukti.");
    load();
  }

  async function uploadProof() {
    setMsg("");
    if (!selectedTopupId) return setMsg("Pilih request topup dulu");
    if (!file) return setMsg("Pilih file bukti dulu");

    const fd = new FormData();
    fd.append("file", file);

    const res = await fetch(`/api/customer/topup/${selectedTopupId}/proof`, {
      method: "POST",
      headers: { Authorization: `Bearer ${token}` },
      body: fd
    }).then(r => r.json());

    if (res.error) return setMsg(res.error);
    setMsg("Bukti topup dikirim. Menunggu verifikasi admin.");
    load();
  }

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
            <div>
              <h2>Dashboard Pelanggan</h2>
              {me && (
                <p className="small">
                  Halo, <b>{me.first_name} {me.last_name}</b> — Saldo: <b>Rp {me.wallet_balance}</b>
                </p>
              )}
              <p className="small">Lokasi rumah wajib diisi agar driver mudah menemukan tujuan.</p>
            </div>
            <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
              <Link className="btn secondary" href="/checkout">Checkout</Link>
              <button className="btn secondary" onClick={logout}>Logout</button>
            </div>
          </div>

          <hr />
          <h3>Pesanan Saya</h3>
          <div className="grid">
            {orders.map(o => (
              <div key={o.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
                  <b>{o.id}</b>
                  <span className="badge">{o.status}</span>
                </div>
                <p className="small">Pay: <span className="badge">{o.pay_method}</span> | Nominal: Rp {o.amount}</p>
                <p className="small"><b>Pickup:</b> {o.pickup_address} ({o.pickup_city || "-"})</p>
                <p className="small"><b>Tujuan:</b> {o.dropoff_address} ({o.dropoff_city || "-"})</p>
                <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
                  <Link className="btn secondary" href={`/akun/order/${o.id}`}>Detail & Tracking</Link>
                  {o.pay_method === "MANUAL" && (
                    <Link className="btn secondary" href={`/order/${o.id}`}>Upload Bukti</Link>
                  )}
                </div>
              </div>
            ))}
            {orders.length === 0 && <p className="small">Belum ada pesanan. Buat di menu Checkout.</p>}
          </div>

          <div className="grid grid2" style={{ marginTop: 12 }}>
            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Lokasi Rumah (Wajib)</h3>
              <input className="input" placeholder="Alamat rumah" value={home.home_address}
                onChange={e=>setHome({...home, home_address:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" placeholder="Kota (contoh: Tanah Grogot)" value={home.home_city}
                onChange={e=>setHome({...home, home_city:e.target.value})}/>
              <div style={{ height:10 }} />
              <MapPicker value={home.point} onChange={(p)=>setHome({...home, point:p})} height={280} />
              <div style={{ height:10 }} />
              <button className="btn" onClick={saveHome}>Simpan Lokasi</button>
            </div>

            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Top-up Saldo</h3>
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

              <hr />
              <h4>Upload Bukti</h4>
              <select className="input" value={selectedTopupId} onChange={e=>setSelectedTopupId(e.target.value)}>
                <option value="">Pilih request topup</option>
                {topups.map(t => (
                  <option key={t.id} value={t.id}>{t.id} — Rp {t.amount} — {t.status}</option>
                ))}
              </select>
              <div style={{ height:10 }} />
              <input className="input" type="file" accept="image/*" onChange={e=>setFile(e.target.files?.[0]||null)} />
              <div style={{ height:10 }} />
              <button className="btn secondary" onClick={uploadProof}>Upload Bukti</button>

              <hr />
              <h4>Riwayat Topup</h4>
              <div className="grid">
                {topups.map(t => (
                  <div key={t.id} className="card" style={{ background:"rgba(0,0,0,.2)" }}>
                    <div style={{ display:"flex", justifyContent:"space-between", gap:10 }}>
                      <b>{t.id}</b><span className="badge">{t.status}</span>
                    </div>
                    <p className="small">Rp {t.amount} — {t.method}</p>
                    {t.proof_image_path && <a className="btn secondary" href={t.proof_image_path} target="_blank" rel="noreferrer">Lihat Bukti</a>}
                    {t.admin_note && <p className="small">Catatan: {t.admin_note}</p>}
                  </div>
                ))}
              </div>
            </div>
          </div>

          <hr />
          <h3>Transaksi Saldo</h3>
          <div className="grid">
            {tx.map(x => (
              <div key={x.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                <div style={{ display:"flex", justifyContent:"space-between", gap:10 }}>
                  <b>{x.type}</b>
                  <span className="badge">{x.amount_delta}</span>
                </div>
                <p className="small">Saldo setelah: Rp {x.balance_after}</p>
                {x.note && <p className="small">{x.note}</p>}
              </div>
            ))}
          </div>

          {msg && <p className="small" style={{ marginTop: 12 }}>{msg}</p>}
        </div>
      </div>
    </>
  );
}
