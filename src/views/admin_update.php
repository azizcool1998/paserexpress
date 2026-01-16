<h2>🔄 Auto Update System</h2>

<div id="update-check-box">Checking for updates...</div>
<br>

<button onclick="runUpdate()">Update Sekarang</button>

<pre id="update-log" style="background:#111;color:#0f0;padding:10px;margin-top:20px;height:200px;overflow:auto"></pre>

<script>
async function checkUpdate() {
    const res = await fetch("?page=update_check");
    const j = await res.json();

    if (!j.success) {
        document.getElementById("update-check-box").innerHTML =
            "<span style='color:red'>Gagal memeriksa update.</span>";
        return;
    }

    let html = `
        <b>Versi Saat Ini:</b> ${j.current}<br>
        <b>Versi Terbaru:</b> ${j.latest}<br><br>
    `;

    if (j.update_available) {
        html += `<span style='color:yellow'>Update tersedia!</span>`;
    } else {
        html += `<span style='color:lightgreen'>Sudah versi terbaru.</span>`;
    }

    document.getElementById("update-check-box").innerHTML = html;
}

async function runUpdate() {
    if (!confirm("Lanjutkan update? Auto-backup akan dibuat terlebih dahulu.")) return;

    const res = await fetch("?page=update_run");
    const j = await res.json();

    document.getElementById("update-log").innerText = j.output;
}

checkUpdate();
</script>
