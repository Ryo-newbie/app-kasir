
<?php
session_start();

if (!isset($_SESSION['UserID']) ||
    !in_array($_SESSION['Role'], ['administrator', 'petugas'])) {
    header("Location: login.php");
    exit;
}

require_once 'config/database.php';

$db = new Database();
$conn = $db->connect();

$pesan = "";

// TAMBAH PELANGGAN
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $alamat = trim($_POST['alamat']);
    $telepon = trim($_POST['telepon']);

    $query = $conn->prepare(
        "INSERT INTO pelanggan (NamaPelanggan, Alamat, NomorTelepon)
         VALUES (?, ?, ?)"
    );

    $query->execute([$nama, $alamat, $telepon]);

    header("Location: pelanggan.php?status=tambah");
    exit;
}

// EDIT PELANGGAN
if (isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama']);
    $alamat = trim($_POST['alamat']);
    $telepon = trim($_POST['telepon']);

    $query = $conn->prepare(
        "UPDATE pelanggan
         SET NamaPelanggan = ?, Alamat = ?, NomorTelepon = ?
         WHERE PelangganID = ?"
    );

    $query->execute([$nama, $alamat, $telepon, $id]);

    header("Location: pelanggan.php?status=edit");
    exit;
}

// HAPUS PELANGGAN
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    try {
        $query = $conn->prepare(
            "DELETE FROM pelanggan WHERE PelangganID = ?"
        );
        $query->execute([$id]);

        header("Location: pelanggan.php?status=hapus");
        exit;
    } catch (PDOException $e) {
        $pesan = "Pelanggan tidak bisa dihapus karena sudah memiliki transaksi.";
    }
}

// AMBIL DATA EDIT
$dataEdit = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    $query = $conn->prepare(
        "SELECT * FROM pelanggan WHERE PelangganID = ?"
    );
    $query->execute([$id]);

    $dataEdit = $query->fetch(PDO::FETCH_ASSOC);
}

// AMBIL SEMUA PELANGGAN
$query = $conn->query(
    "SELECT * FROM pelanggan ORDER BY PelangganID DESC"
);
$pelanggan = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
        }

        .container {
            max-width: 900px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2, h3 {
            color: #1e293b;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            margin: 8px 0 15px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 18px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #2563eb;
            color: white;
        }

        a {
            color: #2563eb;
            text-decoration: none;
        }

        .hapus {
            color: #dc2626;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Data Pelanggan</h2>

    <p>
        <a href="javascript:history.back()">Kembali</a> |
        <a href="logout.php">Logout</a>
    </p>

    <?php if (isset($_GET['status'])): ?>
        <p>Data pelanggan berhasil diproses.</p>
    <?php endif; ?>

    <?php if ($pesan): ?>
        <p style="color:red;">
            <?= htmlspecialchars($pesan) ?>
        </p>
    <?php endif; ?>

    <h3><?= $dataEdit ? 'Edit Pelanggan' : 'Tambah Pelanggan' ?></h3>

    <form method="POST">
        <?php if ($dataEdit): ?>
            <input type="hidden" name="id"
                value="<?= $dataEdit['PelangganID'] ?>">
        <?php endif; ?>

        <label>Nama Pelanggan</label>
        <input type="text" name="nama"
            value="<?= htmlspecialchars($dataEdit['NamaPelanggan'] ?? '') ?>"
            required>

        <label>Alamat</label>
        <textarea name="alamat" required><?= htmlspecialchars($dataEdit['Alamat'] ?? '') ?></textarea>

        <label>Nomor Telepon</label>
        <input type="text" name="telepon"
            value="<?= htmlspecialchars($dataEdit['NomorTelepon'] ?? '') ?>"
            required>

        <?php if ($dataEdit): ?>
            <button type="submit" name="edit">Simpan Perubahan</button>
            <a href="pelanggan.php">Batal</a>
        <?php else: ?>
            <button type="submit" name="tambah">Tambah Pelanggan</button>
        <?php endif; ?>
    </form>

    <h3>Daftar Pelanggan</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Nama</th>
            <th>Alamat</th>
            <th>Telepon</th>
            <th>Aksi</th>
        </tr>

        <?php foreach ($pelanggan as $p): ?>
        <tr>
            <td><?= $p['PelangganID'] ?></td>
            <td><?= htmlspecialchars($p['NamaPelanggan']) ?></td>
            <td><?= htmlspecialchars($p['Alamat']) ?></td>
            <td><?= htmlspecialchars($p['NomorTelepon']) ?></td>
            <td>
                <a href="pelanggan.php?edit=<?= $p['PelangganID'] ?>">Edit</a> |
                <a class="hapus"
                   href="pelanggan.php?hapus=<?= $p['PelangganID'] ?>"
                   onclick="return confirm('Yakin ingin menghapus pelanggan ini?')">
                    Hapus
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

</body>
</html>
