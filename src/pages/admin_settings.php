<?php
$title = "Settings — PaserExpress";

ob_start();
?>

<style>
/* God UI Card */
.god-card {
    background: rgba(255,255,255,0.05);
    border-radius: 16px;
    padding: 25px;
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.08);
    margin-bottom: 25px;
    box-shadow: 0 0 20px rgba(0,0,0,0.25);
    animation: fadeIn 0.7s ease;
}

/* Titles */
.god-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 15px;
    color: #4cc9f0;
}

.god-subtitle {
    opacity: 0.75;
    font-size: 0.9rem;
    margin-bottom: 20px;
}

/* Toggle Switch */
.god-switch {
    width: 50px;
    height: 25px;
    background: #444;
    border-radius: 30px;
    position: relative;
    cursor: pointer;
    transition: 0.3s;
}

.god-switch.active {
    background: #4cc9f0;
}

.god-switch .knob {
    width: 21px;
    height: 21px;
    background: white;
    border-radius: 50%;
    position: absolute;
    top: 2px;
    left: 3px;
    transition: 0.3s;
}

.god-switch.active .knob {
    left: 26px;
}

/* Save Button */
.god-save-btn {
    background: linear-gradient(45deg, #4cc9f0, #4361ee);
    border: none;
    color: white;
    height: 48px;
    border-radius: 12px;
    font-weight: 600;
    transition: 0.2s;
}

.god-save-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(67, 97, 238, 0.4);
}

@keyframes fadeIn {
    from { opacity:0; transform: translateY(10px); }
    to   { opacity:1; transform: translateY(0px); }
}
</style>


<h2 class="mb-4">⚙ Settings (GOD UI)</h2>


<!-- =======================
 SITE SETTINGS
==========================-->
<div class="god-card">
    <div class="god-title">🌐 Site Settings</div>
    <div class="god-subtitle">Pengaturan utama aplikasi website.</div>

    <div class="mb-3">
        <label class="form-label">Website Name</label>
        <input type="text" class="form-control" value="PaserExpress">
    </div>

    <div class="mb-3">
        <label class="form-label">Base URL</label>
        <input type="text" class="form-control" value="https://example.com">
    </div>

    <button class="btn god-save-btn w-100">Save Settings</button>
</div>


<!-- =======================
 AUTO BACKUP
==========================-->
<div class="god-card">
    <div class="god-title">💾 Auto Backup</div>
    <div class="god-subtitle">Atur auto-backup database & source.</div>

    <label class="form-label fw-bold">Auto Backup:</label><br>

    <div id="toggleBackup" class="god-switch <?= true ? 'active' : '' ?>">
        <div class="knob"></div>
    </div>

    <label class="form-label mt-3">Backup Interval</label>
    <select class="form-select">
        <option>1 menit</option>
        <option>5 menit</option>
        <option selected>15 menit</option>
        <option>30 menit</option>
        <option>1 jam</option>
        <option>2 jam</option>
        <option>6 jam</option>
        <option>12 jam</option>
        <option>1 hari</option>
        <option>1 minggu</option>
        <option>1 bulan</option>
        <option>3 bulan</option>
        <option>6 bulan</option>
        <option>1 tahun</option>
    </select>

    <button class="btn god-save-btn w-100 mt-3">Save Backup Settings</button>
</div>


<!-- =======================
 SECURITY SETTINGS
==========================-->
<div class="god-card">
    <div class="god-title">🔐 Security Settings</div>
    <div class="god-subtitle">Pengaturan keamanan tingkat dasar.</div>

    <div class="mb-3">
        <label class="form-label">Admin Email Recovery</label>
        <input type="email" class="form-control" value="admin@example.com">
    </div>

    <div class="mb-3">
        <label class="form-label">Maintenance Mode</label>
        <select class="form-select">
            <option value="off" selected>OFF</option>
            <option value="on">ON</option>
        </select>
    </div>

    <button class="btn god-save-btn w-100">Save Security</button>
</div>


<!-- =======================
 SYSTEM INFO
==========================-->
<div class="god-card">
    <div class="god-title">🖥 System Status</div>
    <div class="god-subtitle">Informasi dan kondisi server saat ini.</div>

    <div class="row">
        <div class="col-6">
            <div class="fw-bold">PHP Version:</div>
            <?= phpversion() ?>
        </div>
        <div class="col-6">
            <div class="fw-bold">Server Time:</div>
            <?= date("Y-m-d H:i:s") ?>
        </div>
    </div>
</div>


<script>
// Toggle animation
document.getElementById("toggleBackup").onclick = function() {
    this.classList.toggle("active");
};
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../template/layout.php';
?>
