<?php
$title = "Monitoring — Ultra Pro Max";
ob_start();
?>

<style>
/* --- ULTRA PRO MAX VISUALS --- */

.monitor-card {
    background: rgba(255,255,255,0.05);
    border-radius: 18px;
    padding: 25px;
    backdrop-filter: blur(25px);
    border: 1px solid rgba(255,255,255,0.12);
    margin-bottom: 25px;
    box-shadow: 0 0 25px rgba(0,0,0,0.35);
    animation: fadeIn 0.7s ease;
}

.monitor-title {
    font-size: 1.7rem;
    font-weight: 700;
    margin-bottom: 12px;
    color: #4cc9f0;
}

.metric-label {
    font-size: 0.9rem;
    opacity: 0.7;
}

.service {
    padding: 10px 15px;
    border-radius: 10px;
    font-weight: 600;
    display: inline-block;
    margin-right: 8px;
}

.service.ok {
    background: rgba(67, 244, 120, 0.18);
    border: 1px solid #25d366;
    color: #25d366;
}

.service.down {
    background: rgba(255, 80, 80, 0.18);
    border: 1px solid #ff4e4e;
    color: #ff4e4e;
}

/* GAUGE */
.gauge-box {
    width: 180px;
    height: 180px;
    margin: auto;
    position: relative;
}

.gauge-box canvas {
    width: 100% !important;
    height: 100% !important;
}

.gauge-value {
    position: absolute;
    top: 62px;
    width: 100%;
    text-align: center;
    font-size: 1.3rem;
    font-weight: bold;
    color: #4cc9f0;
}

/* Fade */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>


<h2 class="mb-4">📡 Monitoring Ultra Pro Max</h2>

<!-- CPU -->
<div class="monitor-card">
    <div class="monitor-title">🧠 CPU Usage</div>
    <canvas id="cpuChart" height="90"></canvas>
</div>

<!-- RAM -->
<div class="monitor-card">
    <div class="monitor-title">🧬 RAM Usage</div>
    <canvas id="ramChart" height="90"></canvas>
</div>

<!-- DISK -->
<div class="monitor-card text-center">
    <div class="monitor-title">💽 Disk Usage</div>
    <div class="gauge-box">
        <canvas id="diskGauge"></canvas>
        <div class="gauge-value" id="diskValue">--%</div>
    </div>
</div>

<!-- SERVICES -->
<div class="monitor-card">
    <div class="monitor-title">🛡 Service Status</div>

    <div id="servicesBox">
        Loading...
    </div>
</div>

<!-- SYSTEM INFO -->
<div class="monitor-card">
    <div class="monitor-title">🖥 System Information</div>

    <div id="sysInfo">
        Updating...
    </div>
</div>


<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
async function getData() {
    const res = await fetch("?page=api_monitoring");
    return res.json();
}

let cpuChart, ramChart;

// --- INIT CHARTS ---
function initCharts() {
    const ctx1 = document.getElementById("cpuChart").getContext("2d");
    const ctx2 = document.getElementById("ramChart").getContext("2d");

    cpuChart = new Chart(ctx1, {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: "CPU %",
                data: [],
                borderColor: "#4cc9f0",
                tension: 0.3
            }]
        },
        options: { animation: false }
    });

    ramChart = new Chart(ctx2, {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label: "RAM %",
                data: [],
                borderColor: "#f72585",
                tension: 0.3
            }]
        },
        options: { animation: false }
    });
}

function updateGauge(percentage) {
    const canvas = document.getElementById("diskGauge");
    const ctx = canvas.getContext("2d");
    canvas.width = 200;
    canvas.height = 200;

    const angle = (percentage / 100) * Math.PI * 1.5;

    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // background arc
    ctx.beginPath();
    ctx.strokeStyle = "#333";
    ctx.lineWidth = 15;
    ctx.arc(100, 100, 70, Math.PI, Math.PI * 2);
    ctx.stroke();

    // value arc
    ctx.beginPath();
    ctx.strokeStyle = "#4cc9f0";
    ctx.lineWidth = 15;
    ctx.arc(100, 100, 70, Math.PI, Math.PI + (Math.PI * percentage / 100));
    ctx.stroke();

    document.getElementById("diskValue").innerText = percentage + "%";
}


async function refresh() {
    const data = await getData();
    if (!data.success) return;

    const d = data.data;

    // CPU chart
    cpuChart.data.labels.push("");
    cpuChart.data.datasets[0].data.push(d.cpu_percent);
    if (cpuChart.data.labels.length > 20) cpuChart.data.labels.shift();
    cpuChart.update();

    // RAM chart
    ramChart.data.labels.push("");
    ramChart.data.datasets[0].data.push(d.ram_percent);
    if (ramChart.data.labels.length > 20) ramChart.data.labels.shift();
    ramChart.update();

    // Disk gauge
    updateGauge(d.disk_percent);

    // Services
    let s = d.services;
    document.getElementById("servicesBox").innerHTML = `
        <span class="service ${s.nginx === 'running' ? 'ok':'down'}">Nginx: ${s.nginx}</span>
        <span class="service ${s.php_fpm === 'running' ? 'ok':'down'}">PHP-FPM: ${s.php_fpm}</span>
        <span class="service ${s.mariadb === 'running' ? 'ok':'down'}">MariaDB: ${s.mariadb}</span>
    `;

    // sys info
    document.getElementById("sysInfo").innerHTML = `
        <div><b>Uptime:</b> ${d.uptime}</div>
        <div><b>Load Average:</b> ${d.cpu_load['1min']} / ${d.cpu_load['5min']} / ${d.cpu_load['15min']}</div>
        <div><b>PHP Version:</b> ${d.php_version}</div>
        <div><b>Time:</b> ${d.timestamp}</div>
    `;
}

initCharts();
refresh();
setInterval(refresh, 5000);
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../template/layout.php';
?>
