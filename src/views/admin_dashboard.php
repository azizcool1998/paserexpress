<h2>Admin Dashboard</h2>

<li><a href="?page=admin_monitoring">Monitoring PRO</a></li>

<table border="1">
<tr>
    <th>ID</th>
    <th>Username</th>
    <th>Email</th>
    <th>Role</th>
</tr>

<?php foreach ($users as $u): ?>
<tr>
    <td><?= $u['id'] ?></td>
    <td><?= sanitize($u['username']) ?></td>
    <td><?= sanitize($u['email']) ?></td>
    <td><?= sanitize($u['role']) ?></td>
</tr>
<?php endforeach; ?>

</table> <!-- ✔️ TUTUP DI SINI -->

<h2>Server Monitoring</h2>
<div id="monitor-box" style="padding:15px;background:#1e1e1e;color:#fff;border-radius:10px;margin-bottom:20px">
    <p><strong>Loading monitoring data...</strong></p>
</div>

<div class="mt-3">
    <a href="?page=admin_update" class="btn btn-warning">🚀 Update PaserExpress</a>
</div>

<script>
async function loadMonitoring() {
    const res = await fetch("?page=api_monitoring");
    const json = await res.json();

    if (!json.success) {
        document.getElementById("monitor-box").innerHTML =
            "<b>Error loading monitoring.</b>";
        return;
    }

    const d = json.data;

    document.getElementById("monitor-box").innerHTML = `
        <h3>📌 System Status (${d.timestamp})</h3>

        <b>CPU Load:</b> ${d.cpu_load['1min']} (1m),
        ${d.cpu_load['5min']} (5m),
        ${d.cpu_load['15min']} (15m)<br><br>

        <b>RAM:</b> ${d.ram.used_mb} MB / ${d.ram.total_mb} MB
        (Free: ${d.ram.free_mb} MB)<br><br>

        <b>Disk:</b> ${d.disk.used_gb} GB / ${d.disk.total_gb} GB
        (Free: ${d.disk.free_gb} GB)<br><br>

        <b>Services:</b><br>
        Nginx: <span style="color:${d.services.nginx === 'running' ? 'lightgreen' : 'red'}">${d.services.nginx}</span><br>
        PHP-FPM: <span style="color:${d.services.php_fpm === 'running' ? 'lightgreen' : 'red'}">${d.services.php_fpm}</span><br>
        MariaDB: <span style="color:${d.services.mariadb === 'running' ? 'lightgreen' : 'red'}">${d.services.mariadb}</span><br><br>

        <b>System Uptime:</b> ${d.uptime}
    `;
}

// auto refresh tiap 15 detik
loadMonitoring();
setInterval(loadMonitoring, 15000);
</script>
