<?php
$title = "Backup Settings";
ob_start();

$intervals = [
    "1m" => "Setiap 1 menit",
    "5m" => "Setiap 5 menit",
    "15m" => "Setiap 15 menit",
    "30m" => "Setiap 30 menit",
    "1h" => "Setiap 1 jam",
    "2h" => "Setiap 2 jam",
    "3h" => "Setiap 3 jam",
    "6h" => "Setiap 6 jam",
    "9h" => "Setiap 9 jam",
    "12h" => "Setiap 12 jam",
    "18h" => "Setiap 18 jam",
    "1d" => "Setiap 1 hari",
    "3d" => "Setiap 3 hari",
    "1w" => "Setiap 1 minggu",
    "2w" => "Setiap 2 minggu",
    "3w" => "Setiap 3 minggu",
    "1mo" => "Setiap 1 bulan",
    "2mo" => "Setiap 2 bulan",
    "3mo" => "Setiap 3 bulan",
    "6mo" => "Setiap 6 bulan",
    "9mo" => "Setiap 9 bulan",
    "1y" => "Setiap 1 tahun"
];
?>

<div class="card shadow-sm">
    <div class="card-body">
        <h3 class="mb-3 fw-bold">Backup Settings</h3>

        <?php if (isset($_GET['saved'])): ?>
            <div class="alert alert-success">Backup settings saved.</div>
        <?php endif; ?>

        <?php if (isset($_GET['run'])): ?>
            <div class="alert alert-info">Backup executed successfully.</div>
        <?php endif; ?>

        <form method="POST" action="?page=admin_backup_save">
            <div class="mb-3">
                <label class="form-label fw-bold">Auto Backup</label>
                <select name="enabled" class="form-select">
                    <option value="on" <?= $settings['enabled']=="on"?"selected":"" ?>>Enabled</option>
                    <option value="off" <?= $settings['enabled']=="off"?"selected":"" ?>>Disabled</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold">Backup Interval</label>
                <select name="interval" class="form-select">
                    <?php foreach ($intervals as $code => $label): ?>
                        <option value="<?= $code ?>" <?= $settings['interval']==$code?"selected":"" ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button class="btn btn-primary">Save Settings</button>
            <a href="?page=admin_backup_run" class="btn btn-success">Run Backup Now</a>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
