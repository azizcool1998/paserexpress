<!DOCTYPE html>
<html>
<head>
    <title>Tracking Resi</title>
</head>
<body>
    <h1>Tracking Pengiriman</h1>
    <form method="post" action="tracking.php">
        <label for="resi">Masukkan Nomor Resi:</label>
        <input type="text" id="resi" name="resi" required>
        <button type="submit">Cek Resi</button>
    </form>
    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $resi = htmlspecialchars($_POST["resi"]);
        // Di sini kamu bisa tambahkan logika untuk mengecek resi dari berbagai ekspedisi.
        echo "<p>Hasil pencarian untuk resi: $resi</p>";
    }
    ?>
</body>
</html>
