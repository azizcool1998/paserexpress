<?php
$title = "Monitoring PRO";

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold">Monitoring PRO</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?page=admin_dashboard">Admin</a></li>
            <li class="breadcrumb-item active">Monitoring PRO</li>
        </ol>
    </nav>
</div>

<!-- STATUS LAYANAN -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <h6>Nginx</h6>
                <span id="sv-nginx" class="badge bg-secondary">Loading...</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <h6>PHP-FPM</h6>
                <span id="sv-php" class="badge bg-secondary">Loading...</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <h6>MariaDB</h6>
                <span id="sv-mdb" class="badge bg-secondary">Loading...</span>
            </div>
        </div>
    </div>
</div>


<!-- CHARTS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="fw-bold">CPU Load</h6>
                <canvas id="cpuChart" height="140"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="fw-bold">RAM Usage</h6>
                <canvas id="ramChart" height="140"></canvas>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="fw-bold">Disk Usage</h6>
                <canvas id="diskChart" height="140"></canvas>
            </div>
        </div>
    </div>
</div>


<!-- PROCESS TOP LIST -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="fw-bold">Top 5 Processes</h5>
        <pre id="proc-list" class="bg-dark text-light p-3 rounded" style="height:160px; overflow:auto;">Loading...</pre>
    </div>
</div>


<!-- LOG VIEWER -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="fw-bold">Log Viewer (Realtime)</h5>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#log-nginx">Nginx Log</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#log-php">PHP-FPM Log</a></li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="log-nginx">
                <pre id="log-nginx-box" class="bg-dark text-light p-3 rounded" style="height:220px; overflow:auto;">Loading...</pre>
            </div>

            <div class="tab-pane fade" id="log-php">
                <pre id="log-php-box" class="bg-dark text-light p-3 rounded" style="height:220px; overflow:auto;">Loading...</pre>
            </div>
        </div>
    </div>
</div>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>

<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let cpuChart, ramChart, diskChart;

function initCharts() {
    cpuChart = new Chart(document.getElementById("cpuChart"), {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: "1m Load",
                data: [],
                borderColor: "#0d6efd"
            }]
        }
    });

    ramChart = new Chart(document.getElementById("ramChart"), {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: "RAM Used MB",
                data: [],
                borderColor: "#dc3545"
            }]
        }
    });

    diskChart = new Chart(document.getElementById("diskChart"), {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: "Disk Used GB",
                data: [],
                borderColor: "#198754"
            }]
        }
    });
}

initCharts();

// ======================= FETCH DATA =======================
async function fetchMonitoring() {
    const res = await fetch("?page=api_monitoring");
    const json = await res.json();
    if (!json.success) return;

    const d = json.data;

    // Update service status
    setBadge("sv-nginx", d.services.nginx);
    setBadge("sv-php", d.services.php_fpm);
    setBadge("sv-mdb", d.services.mariadb);

    // Charts
    addData(cpuChart, d.timestamp, d.cpu_load["1min"]);
    addData(ramChart, d.timestamp, d.ram.used_mb);
    addData(diskChart, d.timestamp, d.disk.used_gb);

    // Processes
    document.getElementById("proc-list").innerText = d.processes.join("\n");

    // Logs
    document.getElementById("log-nginx-box").innerText = d.logs.nginx;
    document.getElementById("log-php-box").innerText = d.logs.php;
}

function addData(chart, label, value) {
    chart.data.labels.push(label);
    chart.data.datasets[0].data.push(value);

    if (chart.data.labels.length > 20) {
        chart.data.labels.shift();
        chart.data.datasets[0].data.shift();
    }

    chart.update();
}

function setBadge(id, status) {
    const el = document.getElementById(id);
    el.innerText = status;

    if (status === "running") {
        el.className = "badge bg-success";
    } else {
        el.className = "badge bg-danger";
    }
}

// Auto refresh setiap 10 detik
fetchMonitoring();
setInterval(fetchMonitoring, 10000);
</script>
