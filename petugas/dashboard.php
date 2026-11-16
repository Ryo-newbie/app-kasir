
<?php
session_start();

if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Petugas</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="login-container">
    <h2>Dashboard Petugas</h2>

    <p>Selamat datang,
        <b><?= htmlspecialchars($_SESSION['Nama']) ?></b>
    </p>

    <br>

    <a href="../produk.php">Pendataan Barang</a>
    <br><br>

    <a href="../logout.php">Logout</a>
</div>

</body>
</html>
