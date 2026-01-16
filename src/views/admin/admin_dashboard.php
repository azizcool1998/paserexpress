<?php
$title = "Admin Dashboard";

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="fw-bold">Admin Dashboard</h2>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="?page=admin_dashboard">Admin</a></li>
            <li class="breadcrumb-item active">Dashboard</li>
        </ol>
    </nav>
</div>


<!-- ========== STAT CARDS ========== -->
<div class="row g-3 mb-4">

    <!-- Total Users -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Total Users</h6>
                <h3 class="fw-bold"><?= count($users) ?></h3>
                <i class="bi bi-people-fill fs-2 text-primary"></i>
            </div>
        </div>
    </div>

    <!-- Pelanggan -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Pelanggan</h6>
                <h3 class="fw-bold"><?= count(array_filter($users, fn($u) => $u['role'] === 'pelanggan')) ?></h3>
                <i class="bi bi-person-badge fs-2 text-success"></i>
            </div>
        </div>
    </div>

    <!-- Driver -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Driver</h6>
                <h3 class="fw-bold"><?= count(array_filter($users, fn($u) => $u['role'] === 'driver')) ?></h3>
                <i class="bi bi-truck fs-2 text-warning"></i>
            </div>
        </div>
    </div>

    <!-- Admin -->
    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h6 class="text-muted">Admin</h6>
                <h3 class="fw-bold"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></h3>
                <i class="bi bi-shield-lock-fill fs-2 text-danger"></i>
            </div>
        </div>
    </div>

</div>



<!-- ========== CHART AREA ========== -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">User Role Distribution</h5>
        <canvas id="roleChart" height="120"></canvas>
    </div>
</div>


<!-- ========== MONITORING RINGKAS ========== -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <h5 class="fw-bold mb-3">System Health (Ringkas)</h5>

        <div id="monitor-simple" class="row text-center">
            <div class="col-md-4 border-end">
                <h6>CPU Load</h6>
                <p id="cpuLoad">Loading...</p>
            </div>

            <div class="col-md-4 border-end">
                <h6>RAM</h6>
                <p id="ramLoad">Loading...</p>
            </div>

            <div class="col-md-4">
                <h6>Disk</h6>
                <p id="diskLoad">Loading...</p>
            </div>
        </div>

        <div class="text-end mt-3">
            <a href="?page=admin_monitoring" class="btn btn-outline-primary btn-sm">
                Monitoring PRO →
            </a>
        </div>
    </div>
</div>



<!-- ========== TABLE USERS ========== -->
<div class="card shadow-sm border-0">
    <div class="card-body">
        <h5 class="fw-bold mb-3">Users Terbaru</h5>

        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= sanitize($u['username']) ?></td>
                    <td><?= sanitize($u['email']) ?></td>
                    <td>
                        <span class="badge bg-primary"><?= sanitize($u['role']) ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>

        </table>
    </div>
</div>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>


<!-- ChartJS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ======================== CHART DATA ========================
const ctx = document.getElementById('roleChart').getContext('2d');

new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Pelanggan', 'Driver', 'Admin'],
        datasets: [{
            data: [
                <?= count(array_filter($users, fn($u) => $u['role'] === 'pelanggan')) ?>,
                <?= count(array_filter($users, fn($u) => $u['role'] === 'driver')) ?>,
                <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?>
            ],
            backgroundColor: ['#0d6efd', '#ffc107', '#dc3545']
        }]
    }
});

// ======================== SIMPLE MONITORING ========================
async function loadSimpleMon() {
    const res = await fetch("?page=api_monitoring");
    const json = await res.json();

    if (!json.success) return;

    const d = json.data;

    document.getElementById("cpuLoad").innerText =
        `${d.cpu_load['1min']} / ${d.cpu_load['5min']} / ${d.cpu_load['15min']}`;

    document.getElementById("ramLoad").innerText =
        `${d.ram.used_mb}MB / ${d.ram.total_mb}MB`;

    document.getElementById("diskLoad").innerText =
        `${d.disk.used_gb}GB / ${d.disk.total_gb}GB`;
}

loadSimpleMon();
setInterval(loadSimpleMon, 10000);
</script>
