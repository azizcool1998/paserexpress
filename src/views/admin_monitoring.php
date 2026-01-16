<h2>📊 Monitoring PRO (Realtime)</h2>

<canvas id="cpu-chart" height="80"></canvas>
<canvas id="ram-chart" height="80"></canvas>
<canvas id="disk-chart" height="80"></canvas>
<canvas id="net-chart" height="80"></canvas>

<div id="status-box" style="margin-top:20px;color:white;background:#222;padding:15px;border-radius:10px;">
    Loading...
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let cpuChart, ramChart, diskChart, netChart;

function newChart(id, label) {
    return new Chart(document.getElementById(id), {
        type: "line",
        data: {
            labels: [],
            datasets: [{
                label,
                data: [],
                borderWidth: 2
            }]
        },
        options: {
            scales: { y: { beginAtZero: true } }
        }
    });
}

function initCharts() {
    cpuChart  = newChart("cpu-chart", "CPU %");
    ramChart  = newChart("ram-chart", "RAM %");
    diskChart = newChart("disk-chart", "Disk %");
    netChart  = newChart("net-chart", "Network KB");
}

async function updateMonitoring() {
    const res = await fetch("?page=api_monitoring_pro");
    const j = await res.json();

    if (!j.success) return;

    const t = j.timestamp;

    cpuChart.data.labels.push(t);
    cpuChart.data.datasets[0].data.push(j.cpu["1m"]);
    cpuChart.update();

    ramChart.data.labels.push(t);
    ramChart.data.datasets[0].data.push(j.ram.percent);
    ramChart.update();

    diskChart.data.labels.push(t);
    diskChart.data.datasets[0].data.push(j.disk.percent);
    diskChart.update();

    netChart.data.labels.push(t);
    netChart.data.datasets[0].data.push(j.network.rx_kb);
    netChart.update();

    document.getElementById("status-box").innerHTML = `
        <b>Timestamp:</b> ${j.timestamp}<br>
        <b>Uptime:</b> ${j.uptime}<br><br>

        <b>Services:</b><br>
        Nginx: <span style="color:${j.services.nginx === 'running' ? 'lightgreen':'red'}">${j.services.nginx}</span><br>
        PHP-FPM: <span style="color:${j.services.php_fpm === 'running' ? 'lightgreen':'red'}">${j.services.php_fpm}</span><br>
        MariaDB: <span style="color:${j.services.mariadb === 'running' ? 'lightgreen':'red'}">${j.services.mariadb}</span><br>
    `;
}

initCharts();
updateMonitoring();
setInterval(updateMonitoring, 3000);
</script>
