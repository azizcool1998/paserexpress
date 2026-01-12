import { useEffect } from "react";

export default function ThemeSync() {
  useEffect(() => {
    (async () => {
      try {
        const s = await fetch("/api/public/settings").then(r => r.json());
        const root = document.documentElement;
        if (s["theme.primary"]) root.style.setProperty("--primary", s["theme.primary"]);
        if (s["theme.bg"]) root.style.setProperty("--bg", s["theme.bg"]);
        if (s["theme.card"]) root.style.setProperty("--card", s["theme.card"]);
        if (s["theme.text"]) root.style.setProperty("--text", s["theme.text"]);
        if (s["theme.radius"]) root.style.setProperty("--radius", `${s["theme.radius"]}px`);
      } catch {}
    })();
  }, []);
  return null;
}
