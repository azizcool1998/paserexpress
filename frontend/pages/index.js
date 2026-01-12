import ThemeSync from "../components/ThemeSync";
import Link from "next/link";
import { useEffect, useState } from "react";

export default function Home() {
  const [services, setServices] = useState([]);
  const [settings, setSettings] = useState({});

  useEffect(() => {
    (async () => {
      const s = await fetch("/api/public/settings").then(r => r.json());
      setSettings(s || {});
      const sv = await fetch("/api/public/services").then(r => r.json());
      setServices(sv || []);
    })();
  }, []);

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <div style={{ display:"flex", justifyContent:"space-between", gap:12, flexWrap:"wrap" }}>
            <div>
              <h1 style={{ margin:"0 0 6px" }}>Paser Express</h1>
              <p className="small">{settings["payment.warning"] || ""}</p>
            </div>
            <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
              <Link className="btn secondary" href="/checkout">Checkout</Link>
              <Link className="btn secondary" href="/akun/register">Daftar Pelanggan</Link>
              <Link className="btn secondary" href="/akun/login">Login Pelanggan</Link>
              <Link className="btn secondary" href="/driver/register">Daftar Driver</Link>
              <Link className="btn secondary" href="/driver/login">Login Driver</Link>
              <Link className="btn secondary" href="/admin/login">Admin</Link>
            </div>
          </div>

          <hr />

          <h2 style={{ marginTop:0 }}>Layanan</h2>
          <div className="grid grid2">
            {services.map(s => (
              <div key={s.id} className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                <b>{s.title}</b>
                <p className="small">{s.description}</p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </>
  );
}
