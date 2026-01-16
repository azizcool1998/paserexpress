<?php $title = "Settings"; ob_start(); ?>

<h2 class="fw-bold mb-4">
    <i class="bi bi-gear-fill"></i> System Settings
</h2>

<div class="row">

    <div class="col-md-6">
        <div class="god-card">
            <h5 class="fw-bold">Administrator Profile</h5>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Admin Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= sanitize($admin['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">WhatsApp</label>
                    <input type="text" name="wa" class="form-control"
                           value="<?= sanitize($admin['whatsapp']) ?>" required>
                </div>

                <button class="btn btn-god-primary">
                    Save Changes
                </button>

            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="god-card">
            <h5 class="fw-bold">Auto-Backup Settings</h5>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="backup_enabled" class="form-select">
                        <option value="1" <?= $backup_enabled ? "selected" : "" ?>>Enabled</option>
                        <option value="0" <?= !$backup_enabled ? "selected" : "" ?>>Disabled</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Backup Interval</label>
                    <select name="backup_interval" class="form-select">
                        <?php foreach ($intervals as $key => $label): ?>
                            <option value="<?= $key ?>" <?= $backup_interval == $key ? "selected" : "" ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button class="btn btn-god-primary">
                    Save Backup Settings
                </button>
            </form>
        </div>
    </div>

</div>

<?php $content = ob_get_clean(); include __DIR__ . "/layout.php"; ?>
