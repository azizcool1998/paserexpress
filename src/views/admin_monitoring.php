<?php $title = "Monitoring PRO"; ob_start(); ?>

<h2 class="fw-bold mb-4">
    <i class="bi bi-activity"></i> Monitoring ULTRA PRO MAX
</h2>

<div class="monitor-box" id="monitor-box">
    <p><b>Loading monitoring data...</b></p>
</div>

<script>
async function loadMonitor() {
    const res = await fetch("?page=api_monitoring");
    const json = await res.json();

    if (!json.success) {
        document.getElementById("monitor-box").innerHTML =
            "<div class='alert alert-danger'>Failed to load monitoring data.</div>";
        return;
    }

    const d = json.data;

    document.getElementById("monitor-box").innerHTML = `
        <h4 class="fw-bold mb-3">
            System Overview <span class="text-muted fs-6">(${d.timestamp})</span>
        </h4>

        <div class="row">

            <div class="col-md-4">
                <div class="widget-card text-center">
                    <div class="fs-1">🖥️</div>
                    <h5 class="fw-bold mt-2">CPU Load</h5>
                    <p>${d.cpu_load['1min']} | ${d.cpu_load['5min']} | ${d.cpu_load['15min']}</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="widget-card text-center">
                    <div class="fs-1">💾</div>
                    <h5 class="fw-bold mt-2">Memory</h5>
                    <p>${d.ram.used_mb} MB / ${d.ram.total_mb} MB</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="widget-card text-center">
                    <div class="fs-1">📀</div>
                    <h5 class="fw-bold mt-2">Disk</h5>
                    <p>${d.disk.used_gb} GB / ${d.disk.total_gb} GB</p>
                </div>
            </div>

        </div>

        <hr class="my-4">

        <h5 class="fw-bold">Services Status</h5>
        <ul class="list-group mt-3">
            <li class="list-group-item">
                Nginx:
                <b class="${d.services.nginx === 'running' ? 'text-success' : 'text-danger'}">
                    ${d.services.nginx}
                </b>
            </li>

            <li class="list-group-item">
                PHP-FPM:
                <b class="${d.services.php_fpm === 'running' ? 'text-success' : 'text-danger'}">
                    ${d.services.php_fpm}
                </b>
            </li>

            <li class="list-group-item">
                MariaDB:
                <b class="${d.services.mariadb === 'running' ? 'text-success' : 'text-danger'}">
                    ${d.services.mariadb}
                </b>
            </li>
        </ul>
    `;
}

loadMonitor();
setInterval(loadMonitor, 10000);
</script>

<?php $content = ob_get_clean(); include __DIR__ . "/layout.php"; ?>
