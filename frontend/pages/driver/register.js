import { useState } from "react";
import ThemeSync from "../../components/ThemeSync";
import Link from "next/link";

export default function DriverRegister() {
  const [form, setForm] = useState({
    first_name: "",
    last_name: "",
    email: "",
    username: "",
    password: ""
  });
  const [msg, setMsg] = useState("");

  async function submit() {
    setMsg("");
    const res = await fetch("/api/register/driver", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(form)
    }).then(r => r.json());

    if (res.error) return setMsg(res.error);
    setMsg(res.message || "Pendaftaran driver sukses. Menunggu persetujuan admin. Silakan login.");
  }

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <h2>Daftar Driver</h2>
          <div className="grid grid2">
            <input className="input" placeholder="First Name" value={form.first_name}
              onChange={e=>setForm({...form, first_name:e.target.value})}/>
            <input className="input" placeholder="Last Name" value={form.last_name}
              onChange={e=>setForm({...form, last_name:e.target.value})}/>
          </div>
          <div style={{ height: 10 }} />
          <div className="grid grid2">
            <input className="input" placeholder="Email" value={form.email}
              onChange={e=>setForm({...form, email:e.target.value})}/>
            <input className="input" placeholder="Username" value={form.username}
              onChange={e=>setForm({...form, username:e.target.value})}/>
          </div>
          <div style={{ height: 10 }} />
          <input className="input" type="password" placeholder="Password" value={form.password}
            onChange={e=>setForm({...form, password:e.target.value})}/>
          <div style={{ height: 12 }} />
          <button className="btn" onClick={submit}>Daftar</button>

          {msg && <p className="small" style={{ marginTop: 12 }}>{msg}</p>}

          <hr />
          <div style={{ display:"flex", gap:10, flexWrap:"wrap" }}>
            <Link className="btn secondary" href="/driver/login">Login</Link>
            <Link className="btn secondary" href="/">Beranda</Link>
          </div>
        </div>
      </div>
    </>
  );
}
