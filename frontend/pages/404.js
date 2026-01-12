import ThemeSync from "../components/ThemeSync";
import Link from "next/link";

export default function NotFound() {
  return (
    <>
      <ThemeSync />
      <div className="container">
        <div className="card">
          <h2>404</h2>
          <p className="small">Halaman tidak ditemukan.</p>
          <Link className="btn secondary" href="/">Beranda</Link>
        </div>
      </div>
    </>
  );
}
