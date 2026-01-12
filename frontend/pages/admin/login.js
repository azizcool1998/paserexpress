import { useState } from "react";
import ThemeSync from "../../components/ThemeSync";
import Link from "next/link";

export default function AdminLogin() {
  const [username, setUsername] = useState("");
  const [password, setPassword] = useState("");
  const [msg, setMsg] = useState("");

  async function login() {
    setMsg("");
    const res = await fetch("/api/auth/login", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password })
    }).then(r => r.json());

    if (res.error) return setMsg(res.error);

    localStorage.setItem("token", res.token);
    localStorage.setItem("role", res.user.role);

    if (["ADMIN","STAFF"].includes(res.user.role)) window.location.href = "/admin/panel";
    else if (res.user.role === "CUSTOMER") window.location.href = "/akun/dashboard";
    else window.location.href = "/driver/panel";
  }

  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <h2>Login Admin/Staff</h2>
          <input className="input" placeholder="Username" value={username} onChange={e=>setUsername(e.target.value)} />
          <div style={{ height: 10 }} />
          <input className="input" type="password" placeholder="Password" value={password} onChange={e=>setPassword(e.target.value)} />
          <div style={{ height: 12 }} />
          <button className="btn" onClick={login}>Login</button>
          {msg && <p className="small" style={{ marginTop: 12 }}>{msg}</p>}
          <hr />
          <Link className="btn secondary" href="/">Beranda</Link>
        </div>
      </div>
    </>
  );
}
