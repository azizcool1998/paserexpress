<?php
$title = "Admin Dashboard — PaserExpress";

// DATA CONTOH (gantikan dari controller nanti)
$total_users   = $stats['total_users'] ?? 0;
$total_orders  = $stats['total_orders'] ?? 0;
$total_drivers = $stats['total_drivers'] ?? 0;
$total_income  = $stats['total_income'] ?? 0;

ob_start();
?>

<div class="god-fade">

    <!-- PAGE HEADER -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        <a href="?page=admin_monitoring" class="btn btn-primary">
            <i class="bi bi-activity"></i> Monitoring PRO
        </a>
    </div>

    <!-- STAT CARDS -->
    <div class="row g-4">

        <div class="col-md-3">
            <div class="god-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="god-card-title">Total Users</h5>
                        <div class="god-card-value"><?= $total_users ?></div>
                    </div>
                    <div class="god-card-icon bg-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="god-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="god-card-title">Active Drivers</h5>
                        <div class="god-card-value"><?= $total_drivers ?></div>
                    </div>
                    <div class="god-card-icon bg-success">
                        <i class="bi bi-truck"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="god-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="god-card-title">Total Orders</h5>
                        <div class="god-card-value"><?= $total_orders ?></div>
                    </div>
                    <div class="god-card-icon bg-warning text-dark">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="god-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <h5 class="god-card-title">Total Income</h5>
                        <div class="god-card-value">Rp <?= number_format($total_income, 0, ',', '.') ?></div>
                    </div>
                    <div class="god-card-icon bg-danger">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
            </div>
        </div>

    </div>


    <!-- RECENT USER LIST -->
    <div class="god-box mt-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold"><i class="bi bi-people"></i> Latest Users</h4>
            <a href="?page=admin_users" class="btn btn-outline-primary btn-sm">
                View All <i class="bi bi-arrow-right-circle"></i>
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle rounded overflow-hidden">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($latest_users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= sanitize($u['username']) ?></td>
                        <td><?= sanitize($u['email']) ?></td>
                        <td><span class="badge bg-info"><?= sanitize($u['role']) ?></span></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>


    <!-- SYSTEM MONITOR PREVIEW -->
    <div class="god-box mt-5">
        <h4 class="fw-bold"><i class="bi bi-cpu"></i> System Monitor (Preview)</h4>

        <div id="monitor-mini" class="mt-3">
            <p class="text-muted">Loading...</p>
        </div>
    </div>

</div>


<script>
async function loadMiniMonitor() {
    try {
        const res = await fetch("?page=api_monitoring");
        const json = await res.json();

        if (!json.success) {
            document.getElementById("monitor-mini").innerHTML =
                "<span class='text-danger'>Cannot load system stats</span>";
            return;
        }

        const d = json.data;

        document.getElementById("monitor-mini").innerHTML = `
            <div class="row text-center g-4">

                <div class="col-md-4">
                    <div class="god-mini-card">
                        <h6>CPU Load</h6>
                        <p>${d.cpu_load["1min"]}</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="god-mini-card">
                        <h6>RAM</h6>
                        <p>${d.ram.used_mb} / ${d.ram.total_mb} MB</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="god-mini-card">
                        <h6>Disk</h6>
                        <p>${d.disk.used_gb} / ${d.disk.total_gb} GB</p>
                    </div>
                </div>

            </div>
        `;

    } catch (e) {
        document.getElementById("monitor-mini").innerHTML =
            "<span class='text-danger'>Monitor error</span>";
    }
}

loadMiniMonitor();
setInterval(loadMiniMonitor, 15000);
</script>

<?php
$content = ob_get_clean();
require __DIR__ . '/../template/layout.php';
?>
