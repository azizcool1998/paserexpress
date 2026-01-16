<?php
$title = "Login — PaserExpress";

ob_start();
?>

<style>
/* PAGE BACKGROUND */
.god-login-bg {
    min-height: 100vh;
    background: linear-gradient(145deg, #0e0e0e, #1a1f2b);
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 30px;
}

/* LOGIN CARD */
.god-login-card {
    width: 100%;
    max-width: 420px;
    background: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(25px);
    border-radius: 18px;
    padding: 35px;
    color: #fff;
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow:
        0 0 35px rgba(0,0,0,0.4),
        inset 0 0 25px rgba(255,255,255,0.03);
    animation: fadeIn 0.7s ease;
}

/* Header */
.god-login-title {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.god-login-subtitle {
    font-size: 0.95rem;
    opacity: 0.7;
    margin-bottom: 25px;
}

/* Inputs */
.god-input {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.2);
    height: 50px;
    color: #fff;
}

.god-input:focus {
    background: rgba(0,0,0,0.4);
    border-color: #4cc9f0;
    box-shadow: 0 0 8px rgba(76, 201, 240, 0.4);
    color: #fff;
}

/* Login Button */
.god-login-btn {
    height: 50px;
    background: linear-gradient(45deg, #4cc9f0, #4361ee);
    border: none;
    color: white;
    font-weight: 600;
    border-radius: 12px;
    transition: 0.2s;
}

.god-login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 18px rgba(67, 97, 238, 0.4);
}

/* Footer */
.god-login-footer {
    margin-top: 20px;
    text-align: center;
    opacity: 0.7;
}

/* Animations */
@keyframes fadeIn {
    from { opacity:0; transform: translateY(10px); }
    to   { opacity:1; transform: translateY(0px);  }
}
</style>


<div class="god-login-bg">

    <form method="POST" action="?page=login" class="god-login-card">

        <!-- ICON -->
        <div class="text-center mb-3">
            <i class="bi bi-lightning-charge-fill" style="font-size:55px;color:#4cc9f0;"></i>
        </div>

        <!-- TITLE -->
        <div class="god-login-title text-center">Welcome Back</div>
        <div class="god-login-subtitle text-center">Login to access your dashboard</div>

        <!-- USERNAME -->
        <label class="form-label fw-bold mt-2">Username</label>
        <input type="text" name="username" class="form-control god-input" required>

        <!-- PASSWORD -->
        <label class="form-label fw-bold mt-3">Password</label>
        <input type="password" name="password" class="form-control god-input" required>

        <!-- LOGIN BUTTON -->
        <button type="submit" class="btn god-login-btn w-100 mt-4">Sign In</button>

        <!-- FOOTER -->
        <div class="god-login-footer">
            PaserExpress Admin Panel<br>
            <small>© <?= date("Y") ?></small>
        </div>
    </form>

</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../template/layout_empty.php';
?>
