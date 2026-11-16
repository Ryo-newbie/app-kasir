<?php
session_start();

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $query = $conn->prepare(
        "SELECT * FROM users WHERE Username = ?"
    );

    $query->execute([$username]);
    $user = $query->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['Password'])) {
        session_regenerate_id(true);

        $_SESSION['UserID'] = $user['UserID'];
        $_SESSION['Nama'] = $user['Nama'];
        $_SESSION['Role'] = $user['Role'];

        if ($user['Role'] === 'administrator') {
            header("Location: admin/dashboard.php");
        } else {
            header("Location: petugas/dashboard.php");
        }

        exit;
    } else {
        $error = "Username atau password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Aplikasi Kasir</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="login-container">
    <h2>Login Aplikasi Kasir</h2>

    <?php if ($error): ?>
        <p style="color: red;">
            <?= htmlspecialchars($error) ?>
        </p>
    <?php endif; ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
