<?php $title = "Monitoring ULTRA PRO MAX"; ?>

<div class="god-section-title mb-4">
    <i class="bi bi-activity"></i> Monitoring ULTRA PRO MAX
</div>

<div id="monitor-data" class="row g-3">
    <div class="col-12">
        <div class="god-card p-4 text-center">
            <h4>Loading monitoring data...</h4>
        </div>
    </div>
</div>

<!-- ACTION BUTTONS -->
<div class="mt-4">
    <div class="row g-3">

        <div class="col-md-3">
            <button onclick="doAction('restart_nginx')" class="btn btn-warning w-100">
                🔄 Restart Nginx
            </button>
        </div>

        <div class="col-md-3">
            <button onclick="doAction('restart_php')" class="btn btn-warning w-100">
                🔄 Restart PHP-FPM
            </button>
        </div>

        <div class="col-md-3">
            <button onclick="doAction('restart_mariadb')" class="btn btn-warning w-100">
                🔄 Restart MariaDB
            </button>
        </div>

        <div class="col-md-3">
            <button onclick="doAction('reboot_server')" class="btn btn-danger w-100">
                ⚠ Restart Server
            </button>
        </div>

    </div>
</div>


<script>
async function loadData() {
    const res = await fetch("?page=api_monitoring_ultra");
    const json = await res.json();

    if (!json.success) {
        document.getElementById("monitor-data").innerHTML =
            `<div class='god-card p-4 text-danger text-center'><b>Error loading data</b></div>`;
        return;
    }

    const d = json;

    document.getElementById("monitor-data").innerHTML = `
        <div class="col-md-3">
            <div class="god-card p-4">
                <h5>CPU Load</h5>
                <p>${d.cpu.load_1m} / ${d.cpu.load_5m} / ${d.cpu.load_15m}</p>
                <small>Temp: ${d.cpu.temperature}</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="god-card p-4">
                <h5>RAM</h5>
                <p>${d.ram.used} MB / ${d.ram.total} MB</p>
                <small>Free: ${d.ram.free} MB</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="god-card p-4">
                <h5>Disk</h5>
                <p>${d.disk.used} / ${d.disk.total} GB</p>
                <small>Free: ${d.disk.free} GB</small>
            </div>
        </div>

        <div class="col-md-3">
            <div class="god-card p-4">
                <h5>Uptime</h5>
                <p>${d.uptime}</p>
            </div>
        </div>

        <div class="col-12">
            <div class="god-card p-4">
                <h5>Services</h5>
                <p>Nginx: <b>${d.services.nginx}</b></p>
                <p>PHP-FPM: <b>${d.services.php_fpm}</b></p>
                <p>MariaDB: <b>${d.services.mariadb}</b></p>
            </div>
        </div>
    `;
}

async function doAction(action) {
    if (!confirm("Yakin menjalankan aksi ini?")) return;

    const res = await fetch("?page=api_monitoring_actions&action=" + action);
    const json = await res.json();

    alert(json.message || json.error);

    if (action !== 'reboot_server') {
        loadData();
    }
}

loadData();
setInterval(loadData, 5000);
</script>
