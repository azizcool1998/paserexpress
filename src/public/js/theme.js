(function () {
    const html = document.documentElement;
    const saved = localStorage.getItem("theme");

    if (saved) {
        html.setAttribute("data-bs-theme", saved);
    } else {
        // follow system theme
        const prefersDark = window.matchMedia("(prefers-color-scheme: dark)").matches;
        html.setAttribute("data-bs-theme", prefersDark ? "dark" : "light");
    }

    document.getElementById("themeToggle").onclick = () => {
        let now = html.getAttribute("data-bs-theme");
        let next = now === "dark" ? "light" : "dark";
        html.setAttribute("data-bs-theme", next);
        localStorage.setItem("theme", next);
    };
})();
