<!DOCTYPE html>
<html>
<head>
<title>Login - PaserExpress</title>
</head>
<body>

<h2>Login</h2>

<?php if (!empty($error)): ?>
<p style="color:red"><?= sanitize($error) ?></p>
<?php endif; ?>

<form method="post">
    <label>Username</label><br>
    <input name="username" required><br><br>

    <label>Password</label><br>
    <input type="password" name="password" required><br><br>

    <button>Login</button>
</form>

</body>
</html>
