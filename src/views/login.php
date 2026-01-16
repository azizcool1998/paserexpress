<?php
if (!isset($title)) $title = "Login — PaserExpress";
?>

<div class="god-login-wrapper">

    <!-- LOGIN CARD -->
    <div class="god-login-card animate__animated animate__fadeInDown">

        <!-- Logo + Title -->
        <div class="text-center mb-4">
            <i class="bi bi-lightning-charge-fill god-logo-lg"></i>
            <h2 class="mt-2 fw-bold">PaserExpress</h2>
            <p class="god-muted">Masuk ke dashboard administrasi</p>
        </div>

        <!-- LOGIN FORM -->
        <form method="POST" action="?page=login">

            <div class="mb-3">
                <label class="form-label fw-semibold">Username</label>
                <input type="text" name="username" class="form-control god-input" required>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Password</label>
                <div class="input-group">
                    <input type="password" id="password" name="password" class="form-control god-input" required>
                    <button class="btn btn-outline-secondary" type="button" id="togglePw">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <button class="btn god-btn w-100 mt-2" type="submit">
                <i class="bi bi-box-arrow-in-right me-1"></i>
                Login
            </button>
        </form>

        <div class="text-center mt-4 small god-muted">
            © <?= date("Y") ?> PaserExpress
        </div>

    </div>
</div>

<!-- PASSWORD TOGGLE -->
<script>
document.getElementById("togglePw").onclick = function () {
    const pw = document.getElementById("password");
    const icon = this.querySelector("i");

    if (pw.type === "password") {
        pw.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
    } else {
        pw.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
    }
};
</script>
