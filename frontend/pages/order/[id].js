import { useEffect, useState } from "react";
import ThemeSync from "../../components/ThemeSync";
import Link from "next/link";

export default function OrderDetail({ idFromServer }) {
  const [order, setOrder] = useState(null);
  const [settings, setSettings] = useState({});
  const [msg, setMsg] = useState("");
  const [file, setFile] = useState(null);

  const id = typeof window !== "undefined" ? window.location.pathname.split("/").pop() : idFromServer;

  async function load() {
    setSettings(await fetch("/api/public/settings").then(r => r.json()));
    setOrder(await fetch(`/api/public/orders/${id}`).then(r => r.json()));
  }

  useEffect(() => { if (id) load(); }, [id]);

  async function uploadProof() {
    setMsg("");
    if (!file) return setMsg("Pilih file bukti dulu");

    const fd = new FormData();
    fd.append("file", file);

    const res = await fetch(`/api/public/orders/${id}/proof`, {
      method:"POST",
      body: fd
    }).then(r=>r.json());

    if (res.error) return setMsg(res.error);
    setMsg("Bukti terkirim. Menunggu verifikasi admin.");
    load();
  }

  if (!order) {
    return (
      <>
        <ThemeSync />
        <div className="container"><div className="card">Loading...</div></div>
      </>
    );
  }

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
            <div>
              <h2>Order {order.id}</h2>
              <p className="small">Status: <span className="badge">{order.status}</span> | Pay: <span className="badge">{order.pay_method}</span></p>
              <p className="small">{settings["payment.warning"] || ""}</p>
            </div>
            <Link className="btn secondary" href="/">Beranda</Link>
          </div>

          <div className="grid grid2">
            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Detail</h3>
              <p className="small"><b>Nama:</b> {order.customer_name}</p>
              <p className="small"><b>WA:</b> {order.customer_phone}</p>
              <p className="small"><b>Pickup:</b> {order.pickup_address} ({order.pickup_city || "-"})</p>
              <p className="small"><b>Tujuan:</b> {order.dropoff_address} ({order.dropoff_city || "-"})</p>
              <p className="small"><b>Nominal:</b> Rp {order.amount}</p>
              {order.details && <p className="small"><b>Detail:</b> {order.details}</p>}

              {order.pickup_lat && order.dropoff_lat && (
                <a className="btn secondary" target="_blank" rel="noreferrer"
                  href={`https://www.google.com/maps/dir/?api=1&origin=${order.pickup_lat},${order.pickup_lng}&destination=${order.dropoff_lat},${order.dropoff_lng}`}>
                  Lihat Rute di Google Maps
                </a>
              )}
            </div>

            <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
              <h3>Pembayaran</h3>

              {order.pay_method === "WALLET" ? (
                <p className="small">Order ini dibayar via saldo. Tidak perlu upload bukti.</p>
              ) : (
                <>
                  <p className="small">Upload bukti transfer/QRIS. Admin akan konfirmasi manual.</p>
                  <input className="input" type="file" accept="image/*" onChange={e=>setFile(e.target.files?.[0] || null)} />
                  <div style={{ height:10 }} />
                  <button className="btn" onClick={uploadProof}>Upload</button>

                  {order.proof_image_path && (
                    <div style={{ marginTop:12 }}>
                      <a className="btn secondary" href={order.proof_image_path} target="_blank" rel="noreferrer">Lihat Bukti</a>
                    </div>
                  )}
                </>
              )}

              {msg && <p className="small" style={{ marginTop:12 }}>{msg}</p>}
              {order.admin_note && <p className="small" style={{ marginTop:12 }}>Catatan admin: {order.admin_note}</p>}
            </div>
          </div>
        </div>
      </div>
    </>
  );
}

export async function getServerSideProps(ctx) {
  return { props: { idFromServer: ctx.params.id } };
}
