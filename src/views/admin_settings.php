<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Load settings (ambil dari .env atau table settings)
$settings = load_settings(); // kamu sudah punya fungsi ini sebelumnya

$title = "Settings — PaserExpress";
ob_start();
?>

<div class="god-card p-4 rounded-4 shadow-lg mb-4">
    <h2 class="mb-3 text-god-primary">
        <i class="bi bi-gear-fill"></i> System Settings
    </h2>
    <p class="god-muted mb-4">Atur konfigurasi utama PaserExpress di sini.</p>

    <form method="POST" action="?page=admin_settings_save">

        <!-- APP NAME -->
        <div class="mb-3">
            <label class="form-label fw-bold">App Name</label>
            <input type="text" name="app_name" class="form-control god-input"
                   value="<?= sanitize($settings['APP_NAME'] ?? 'Paser Express') ?>" required>
        </div>

        <!-- WEBSITE DOMAIN -->
        <div class="mb-3">
            <label class="form-label fw-bold">Website Domain</label>
            <input type="text" name="app_domain" class="form-control god-input"
                   value="<?= sanitize($settings['APP_BASE_URL'] ?? '') ?>" required>
        </div>

        <!-- NODE DOMAIN -->
        <div class="mb-3">
            <label class="form-label fw-bold">Node Domain</label>
            <input type="text" name="node_domain" class="form-control god-input"
                   value="<?= sanitize($settings['NODE_DOMAIN'] ?? '') ?>" required>
        </div>

        <!-- BACKUP SYSTEM -->
        <hr class="my-4">

        <h4 class="text-god-primary">
            <i class="bi bi-cloud-arrow-up-fill"></i> Backup Settings
        </h4>

        <!-- Toggle Backup -->
        <div class="form-check form-switch mt-2 mb-3">
            <input class="form-check-input" type="checkbox" name="backup_enabled" id="backup_enabled"
                   <?= ($settings['BACKUP_ENABLED'] ?? 'no') === 'yes' ? 'checked' : '' ?>>
            <label class="form-check-label" for="backup_enabled">Enable Auto Backup</label>
        </div>

        <!-- Backup Interval -->
        <div class="mb-3">
            <label class="form-label fw-bold">Backup Interval</label>
            <select name="backup_interval" class="form-control god-input" required>
                <?php
                $options = [
                    "1m" => "1 Menit",
                    "5m" => "5 Menit",
                    "15m" => "15 Menit",
                    "30m" => "30 Menit",
                    "1h" => "1 Jam",
                    "2h" => "2 Jam",
                    "3h" => "3 Jam",
                    "6h" => "6 Jam",
                    "9h" => "9 Jam",
                    "12h" => "12 Jam",
                    "18h" => "18 Jam",
                    "1d" => "1 Hari",
                    "3d" => "3 Hari",
                    "1w" => "1 Minggu",
                    "2w" => "2 Minggu",
                    "3w" => "3 Minggu",
                    "1mo" => "1 Bulan",
                    "2mo" => "2 Bulan",
                    "3mo" => "3 Bulan",
                    "6mo" => "6 Bulan",
                    "9mo" => "9 Bulan",
                    "1y" => "1 Tahun",
                ];

                $selected = $settings['BACKUP_INTERVAL'] ?? '1d';

                foreach ($options as $key => $label) {
                    echo "<option value='$key' " . ($selected === $key ? 'selected' : '') . ">$label</option>";
                }
                ?>
            </select>
        </div>

        <!-- SAVE BUTTON -->
        <div class="mt-4 text-end">
            <button type="submit" class="god-btn px-4">
                <i class="bi bi-check-circle-fill"></i> Save Settings
            </button>
        </div>
    </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layout.php';
