import { useEffect, useMemo, useState } from "react";
import ThemeSync from "../../../components/ThemeSync";
import Link from "next/link";
import dynamic from "next/dynamic";

const Map = dynamic(() => import("../../../pages_shared/OrderTrackMap"), { ssr: false });

export default function CustomerOrderTrack({ idFromServer }) {
  const token = typeof window !== "undefined" ? localStorage.getItem("token") : null;
  const id = typeof window !== "undefined" ? window.location.pathname.split("/").pop() : idFromServer;

  const [data, setData] = useState(null);
  const [msg, setMsg] = useState("");

  async function load() {
    setMsg("");
    const res = await fetch(`/api/customer/orders/${id}/track`, {
      headers: { Authorization: `Bearer ${token}` }
    }).then(r => r.json());

    if (res.error) return setMsg(res.error);
    setData(res);
  }

  useEffect(() => {
    if (!token) return (window.location.href = "/akun/login");
    load();
    const t = setInterval(load, 5000); // polling 5s
    return () => clearInterval(t);
  }, [id]);

  const order = data?.order;
  const driver = data?.driver;

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <div style={{ display:"flex", justifyContent:"space-between", gap:10, flexWrap:"wrap" }}>
            <div>
              <h2>Tracking Order {id}</h2>
              {order && (
                <p className="small">
                  Status: <span className="badge">{order.status}</span> | Stage: <span className="badge">{order.driver_stage}</span> | Pay: <span className="badge">{order.pay_method}</span>
                </p>
              )}
              <p className="small">Peta refresh otomatis tiap 5 detik.</p>
            </div>
            <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
              <Link className="btn secondary" href="/akun/dashboard">Back</Link>
              <Link className="btn secondary" href="/">Beranda</Link>
            </div>
          </div>

          {msg && <p className="small" style={{ marginTop:10 }}>{msg}</p>}

          {order && (
            <div className="grid grid2" style={{ marginTop: 12 }}>
              <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                <h3>Detail</h3>
                <p className="small"><b>Pickup:</b> {order.pickup_address} ({order.pickup_city || "-"})</p>
                <p className="small"><b>Tujuan:</b> {order.dropoff_address} ({order.dropoff_city || "-"})</p>
                <p className="small"><b>Nominal:</b> Rp {order.amount}</p>
                <p className="small"><b>Driver:</b> {order.assigned_driver_id ? (driver ? `${driver.first_name} ${driver.last_name} (@${driver.username})` : order.assigned_driver_id) : "Belum ada"}</p>

                {order.pickup_lat && order.dropoff_lat && (
                  <a className="btn secondary" target="_blank" rel="noreferrer"
                    href={`https://www.google.com/maps/dir/?api=1&origin=${order.pickup_lat},${order.pickup_lng}&destination=${order.dropoff_lat},${order.dropoff_lng}`}>
                    Buka Rute Google Maps
                  </a>
                )}

                {order.pay_method === "MANUAL" && (
                  <div style={{ marginTop:10 }}>
                    <Link className="btn secondary" href={`/order/${order.id}`}>Upload Bukti (Manual)</Link>
                  </div>
                )}
              </div>

              <div className="card" style={{ background:"rgba(255,255,255,.04)" }}>
                <h3>Live Map</h3>
                <Map order={order} driver={driver} />
                {driver?.lat != null && (
                  <p className="small" style={{ marginTop:10 }}>
                    Lokasi driver: {Number(driver.lat).toFixed(6)}, {Number(driver.lng).toFixed(6)} (±{Math.round(driver.accuracy || 0)}m)
                  </p>
                )}
                {!driver?.lat && <p className="small" style={{ marginTop:10 }}>Lokasi driver belum tersedia.</p>}
              </div>
            </div>
          )}
        </div>
      </div>
    </>
  );
}

export async function getServerSideProps(ctx) {
  return { props: { idFromServer: ctx.params.id } };
}
