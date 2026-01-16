<h2>Auto Backup System</h2>

<h3>Status Backup</h3>
<select id="backup-status">
    <option value="on" <?= $status === "on" ? "selected" : "" ?>>Aktif</option>
    <option value="off" <?= $status === "off" ? "selected" : "" ?>>Nonaktif</option>
</select>
<button onclick="saveStatus()">Simpan Status</button>

<hr>

<h3>Interval Backup</h3>
<select id="backup-interval">
    <option value="1_min">1 Menit</option>
    <option value="5_min">5 Menit</option>
    <option value="15_min">15 Menit</option>
    <option value="30_min">30 Menit</option>
    <option value="1_hour">1 Jam</option>
    <option value="2_hour">2 Jam</option>
    <option value="3_hour">3 Jam</option>
    <option value="6_hour">6 Jam</option>
    <option value="9_hour">9 Jam</option>
    <option value="12_hour">12 Jam</option>
    <option value="18_hour">18 Jam</option>
    <option value="1_day">1 Hari</option>
    <option value="3_day">3 Hari</option>
    <option value="1_week">1 Minggu</option>
    <option value="2_week">2 Minggu</option>
    <option value="3_week">3 Minggu</option>
    <option value="1_month">1 Bulan</option>
    <option value="2_month">2 Bulan</option>
    <option value="3_month">3 Bulan</option>
    <option value="6_month">6 Bulan</option>
    <option value="9_month">9 Bulan</option>
    <option value="1_year">1 Tahun</option>
</select>

<button onclick="saveInterval()">Simpan Interval</button>

<hr>

<h3>Backup Files</h3>
<ul>
<?php foreach ($backups as $b): ?>
    <li><a href="/storage/backups/<?= $b ?>" download><?= $b ?></a></li>
<?php endforeach; ?>
</ul>

<script>
function saveStatus() {
    const s = document.getElementById("backup-status").value;
    fetch("?page=backup_set_status", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "status=" + s
    }).then(r => r.json()).then(j => alert(j.message));
}

function saveInterval() {
    const i = document.getElementById("backup-interval").value;
    fetch("?page=backup_set_interval", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "interval=" + i
    }).then(r => r.json()).then(j => alert(j.message));
}
</script>
