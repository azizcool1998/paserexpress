<?php $title = "Admin Dashboard"; ob_start(); ?>

<h2 class="fw-bold mb-4">
    <i class="bi bi-speedometer2"></i> Dashboard
</h2>

<div class="row">

    <div class="col-md-4">
        <div class="widget-card">
            <h5 class="fw-bold">Total Users</h5>
            <div class="fs-3 fw-bold text-primary"><?= $stats['users'] ?></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="widget-card">
            <h5 class="fw-bold">Active Drivers</h5>
            <div class="fs-3 fw-bold text-success"><?= $stats['drivers'] ?></div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="widget-card">
            <h5 class="fw-bold">Pending Topups</h5>
            <div class="fs-3 fw-bold text-warning"><?= $stats['pending_topups'] ?></div>
        </div>
    </div>

</div>

<hr class="my-4">

<h4>Recent Users</h4>

<div class="table-god mt-3">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= sanitize($u['username']) ?></td>
                <td><?= sanitize($u['email']) ?></td>
                <td><?= sanitize($u['role']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean(); include __DIR__ . "/layout.php"; ?>
