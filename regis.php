<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'config/database.php';

if (!isset($_SESSION['UserID']) ||
    $_SESSION['Role'] !== 'administrator') {
    header("Location: login.php");
    exit;
}

$db = new Database();
$conn = $db->connect();

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if ($nama === "" || $username === "" || $password === "") {
        $error = "Semua kolom wajib diisi!";
    } elseif (!in_array($role, ['administrator', 'petugas'])) {
        $error = "Role tidak valid!";
    } else {
        $check = $conn->prepare(
            "SELECT UserID FROM users WHERE Username = ?"
        );
        $check->execute([$username]);

        if ($check->fetch()) {
            $error = "Username sudah digunakan!";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $query = $conn->prepare(
                "INSERT INTO users (Nama, Username, Password, Role)
                 VALUES (?, ?, ?, ?)"
            );

            $query->execute([
                $nama,
                $username,
                $hash,
                $role
            ]);

            $success = "Akun berhasil didaftarkan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi Akun</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-container">
    <h2>Registrasi Akun</h2>

    <?php if ($success): ?>
        <p style="color:green;">
            <?= htmlspecialchars($success) ?>
        </p>
    <?php endif; ?>

    <?php if ($error): ?>
        <p style="color:red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" required>

        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <label>Role</label>
        <select name="role" required>
            <option value="petugas">Petugas</option>
            <option value="administrator">Administrator</option>
        </select>

        <button type="submit">Daftarkan Akun</button>
    </form>

    <p style="margin-top:15px;">
        <a href="admin/dashboard.php">Kembali ke Dashboard</a>
    </p>
</div>

</body>
</html>
