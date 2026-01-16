<!DOCTYPE html>
<html>
<head>
<title>Login - PaserExpress</title>
</head>
<body>

<h2>Login</h2>

<?php $title = "Login — PaserExpress"; ob_start(); ?>

<div class="row justify-content-center mt-5">
    <div class="col-md-4">

        <div class="god-card p-4">
            <h3 class="text-center mb-4 fw-bold">
                <i class="bi bi-shield-lock-fill"></i> Login
            </h3>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <input type="text" name="username" class="form-control" required />
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required />
                </div>

                <button class="btn btn-god-primary w-100">
                    Login <i class="bi bi-box-arrow-in-right"></i>
                </button>

            </form>
        </div>

    </div>
</div>

<?php $content = ob_get_clean(); include __DIR__ . "/layout.php"; ?>
