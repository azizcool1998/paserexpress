import { useEffect, useState } from "react";
import ThemeSync from "../components/ThemeSync";
import MapPicker from "../components/MapPicker";

export default function Checkout() {
  const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;
  const role = typeof window !== "undefined" ? localStorage.getItem("role") : null;

  const [services, setServices] = useState([]);
  const [settings, setSettings] = useState({});
  const [msg, setMsg] = useState("");

  const [me, setMe] = useState(null);

  const [form, setForm] = useState({
    customer_name:"",
    customer_phone:"",
    service_id:"",
    pickup_address:"",
    pickup_city:"",
    pickup_note:"",
    pickup_point:null,
    dropoff_address:"",
    dropoff_city:"",
    dropoff_note:"",
    dropoff_point:null,
    details:"",
    amount:0,
    pay_method: "MANUAL" // MANUAL or WALLET
  });

  useEffect(() => {
    (async () => {
      setSettings(await fetch("/api/public/settings").then(r => r.json()));
      setServices(await fetch("/api/public/services").then(r => r.json()));

      if (token && role === "CUSTOMER") {
        const m = await fetch("/api/customer/me", { headers: { Authorization: `Bearer ${token}` } }).then(r=>r.json());
        setMe(m || null);
        if (m?.first_name) {
          setForm(f => ({ ...f, customer_name: `${m.first_name} ${m.last_name}`.trim() || f.customer_name }));
        }
      }
    })();
  }, []);

  async function createOrder() {
    setMsg("");
    const payload = {
      ...form,
      amount: Number(form.amount),
      pickup_lat: form.pickup_point?.lat,
      pickup_lng: form.pickup_point?.lng,
      dropoff_lat: form.dropoff_point?.lat,
      dropoff_lng: form.dropoff_point?.lng
    };

    const headers = { "Content-Type":"application/json" };
    if (token) headers["Authorization"] = `Bearer ${token}`;

    const res = await fetch("/api/public/orders", {
      method:"POST",
      headers,
      body: JSON.stringify(payload)
    }).then(r=>r.json());

    if (res.error) return setMsg(res.error);

    // If customer logged-in -> go to customer tracking page
    if (token && role === "CUSTOMER") window.location.href = `/akun/order/${res.id}`;
    else window.location.href = `/order/${res.id}`;
  }

  const walletOk = me && Number(me.wallet_balance) >= Number(form.amount || 0);

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <h2>Checkout</h2>
          <p className="small">{settings["payment.warning"] || ""}</p>

          <div className="grid grid2">
            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Data Order</h3>

              <input className="input" placeholder="Nama" value={form.customer_name}
                onChange={e=>setForm({...form, customer_name:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" placeholder="No WhatsApp" value={form.customer_phone}
                onChange={e=>setForm({...form, customer_phone:e.target.value})}/>
              <div style={{ height:10 }} />
              <select className="input" value={form.service_id} onChange={e=>setForm({...form, service_id:e.target.value})}>
                <option value="">Pilih layanan</option>
                {services.map(s => <option key={s.id} value={s.id}>{s.title}</option>)}
              </select>

              <hr />
              <h4>Pickup (Asal)</h4>
              <input className="input" placeholder="Alamat pickup" value={form.pickup_address}
                onChange={e=>setForm({...form, pickup_address:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" placeholder="Kota pickup (contoh: Banjarmasin)" value={form.pickup_city}
                onChange={e=>setForm({...form, pickup_city:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" placeholder="Catatan pickup (opsional)" value={form.pickup_note}
                onChange={e=>setForm({...form, pickup_note:e.target.value})}/>
              <div style={{ height:10 }} />
              <MapPicker value={form.pickup_point} onChange={(p)=>setForm({...form, pickup_point:p})} />

              <hr />
              <h4>Tujuan (Antar ke)</h4>
              <input className="input" placeholder="Alamat tujuan" value={form.dropoff_address}
                onChange={e=>setForm({...form, dropoff_address:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" placeholder="Kota tujuan (contoh: Tanah Grogot)" value={form.dropoff_city}
                onChange={e=>setForm({...form, dropoff_city:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" placeholder="Catatan tujuan (opsional)" value={form.dropoff_note}
                onChange={e=>setForm({...form, dropoff_note:e.target.value})}/>
              <div style={{ height:10 }} />
              <MapPicker value={form.dropoff_point} onChange={(p)=>setForm({...form, dropoff_point:p})} />

              <hr />
              <textarea className="input" rows={4} placeholder="Detail barang / titipan (opsional)" value={form.details}
                onChange={e=>setForm({...form, details:e.target.value})}/>
              <div style={{ height:10 }} />
              <input className="input" type="number" placeholder="Nominal bayar (Rp)" value={form.amount}
                onChange={e=>setForm({...form, amount:e.target.value})}/>

              <div style={{ height:12 }} />
              <div className="small">Metode Bayar</div>
              <select className="input" value={form.pay_method} onChange={e=>setForm({...form, pay_method:e.target.value})}>
                <option value="MANUAL">Manual (GoPay/QRIS) - upload bukti</option>
                <option value="WALLET" disabled={!(token && role === "CUSTOMER")}>
                  Saldo (Wallet) - hanya customer login
                </option>
              </select>

              {form.pay_method === "WALLET" && (
                <p className="small" style={{ marginTop:10 }}>
                  Saldo kamu: <b>Rp {me?.wallet_balance ?? "-"}</b> — {walletOk ? <span className="badge">Cukup</span> : <span className="badge">Tidak cukup</span>}
                </p>
              )}

              <div style={{ height:12 }} />
              <button className="btn" onClick={createOrder}>Buat Order</button>
              {msg && <p className="small" style={{ marginTop:12 }}>{msg}</p>}
            </div>

            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Metode Pembayaran</h3>
              <p className="small">Setelan ini bisa diubah admin dari menu Settings.</p>
              <div className="card" style={{ background:"rgba(0,0,0,.2)" }}>
                <b>GoPay</b>
                <p className="small">No: {settings["payment.gopay_number"] || "-"}</p>
              </div>
              <div className="card" style={{ background:"rgba(0,0,0,.2)" }}>
                <b>QRIS</b>
                {settings["payment.qris_image"] ? (
                  <img src={settings["payment.qris_image"]} alt="QRIS" style={{ width:"100%", borderRadius:12, marginTop:8 }} />
                ) : (
                  <p className="small">QRIS belum di-set admin.</p>
                )}
              </div>
              <p className="small">Jika bayar manual, setelah bayar kamu upload bukti di halaman order.</p>
            </div>
          </div>
        </div>
      </div>
    </>
  );
}
