
<?php
session_start();
require_once 'config/database.php';

// CEK LOGIN DAN ROLE
if (
    !isset($_SESSION['UserID']) ||
    !in_array($_SESSION['Role'], ['administrator', 'petugas'])
) {
    header("Location: login.php");
    exit;
}

// KONEKSI DATABASE
$db = new Database();
$conn = $db->connect();

$pesan = "";

// TAMBAH PRODUK
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama']);
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    if (
        $nama !== "" &&
        is_numeric($harga) &&
        $harga >= 0 &&
        filter_var($stok, FILTER_VALIDATE_INT) !== false &&
        $stok >= 0
    ) {
        $query = $conn->prepare(
            "INSERT INTO produk (NamaProduk, Harga, Stok)
             VALUES (?, ?, ?)"
        );

        $query->execute([$nama, $harga, $stok]);
        header("Location: produk.php?status=tambah");
        exit;
    } else {
        $pesan = "Data produk tidak valid!";
    }
}

// EDIT PRODUK
if (isset($_POST['edit'])) {
    $id = (int) $_POST['id'];
    $nama = trim($_POST['nama']);
    $harga = $_POST['harga'];
    $stok = $_POST['stok'];

    if (
        $id > 0 &&
        $nama !== "" &&
        is_numeric($harga) &&
        $harga >= 0 &&
        filter_var($stok, FILTER_VALIDATE_INT) !== false &&
        $stok >= 0
    ) {
        $query = $conn->prepare(
            "UPDATE produk
             SET NamaProduk = ?, Harga = ?, Stok = ?
             WHERE ProdukID = ?"
        );

        $query->execute([$nama, $harga, $stok, $id]);
        header("Location: produk.php?status=edit");
        exit;
    } else {
        $pesan = "Data produk tidak valid!";
    }
}

// HAPUS PRODUK
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    try {
        $query = $conn->prepare(
            "DELETE FROM produk WHERE ProdukID = ?"
        );

        $query->execute([$id]);
        header("Location: produk.php?status=hapus");
        exit;

    } catch (PDOException $e) {
        $pesan = "Produk tidak bisa dihapus karena sudah digunakan dalam transaksi.";
    }
}

// PESAN BERHASIL
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'tambah') {
        $pesan = "Produk berhasil ditambahkan!";
    } elseif ($_GET['status'] === 'edit') {
        $pesan = "Produk berhasil diperbarui!";
    } elseif ($_GET['status'] === 'hapus') {
        $pesan = "Produk berhasil dihapus!";
    }
}

// AMBIL DATA EDIT
$dataEdit = null;

if (isset($_GET['edit'])) {
    $id = (int) $_GET['edit'];

    if ($id > 0) {
        $queryEdit = $conn->prepare(
            "SELECT ProdukID, NamaProduk, Harga, Stok
             FROM produk
             WHERE ProdukID = :id"
        );

        $queryEdit->execute(['id' => $id]);

        $dataEdit = $queryEdit->fetch(PDO::FETCH_ASSOC);
    }
}

// AMBIL SEMUA PRODUK
$query = $conn->query(
    "SELECT * FROM produk ORDER BY ProdukID DESC"
);

$produk = $query->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Pendataan Barang</title>

    <link rel="stylesheet" href="assets/style.css">

    <style>
        body {
            display: block;
            padding: 30px 15px;
        }

        .produk-container {
            background: white;
            max-width: 900px;
            margin: auto;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,.1);
        }

        .produk-container h2 {
            margin-bottom: 15px;
            color: #1e293b;
        }

        .produk-container h3 {
            margin: 20px 0 12px;
            color: #334155;
        }

        .produk-container form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .produk-container input {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .produk-container button {
            padding: 11px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .produk-container button:hover {
            background: #1d4ed8;
        }

        .tabel-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 11px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background: #2563eb;
            color: white;
        }

        .aksi a {
            text-decoration: none;
            margin-right: 8px;
        }

        .pesan {
            margin: 15px 0;
            padding: 10px;
            background: #e0f2fe;
            border-radius: 6px;
        }

        .navigasi {
            margin-top: 25px;
        }
    </style>
</head>

<body>

<div class="produk-container">

    <h2>Pendataan Barang</h2>

    <p>
        Selamat datang,
        <b><?= htmlspecialchars($_SESSION['Nama']) ?></b>
    </p>

    <?php if ($pesan): ?>
        <div class="pesan">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <h3>
        <?= $dataEdit ? 'Edit Produk' : 'Tambah Produk' ?>
    </h3>

    <form method="POST">

        <?php if ($dataEdit): ?>
            <input
                type="hidden"
                name="id"
                value="<?= $dataEdit['ProdukID'] ?>"
            >
        <?php endif; ?>

        <label>Nama Produk</label>

        <input
            type="text"
            name="nama"
            placeholder="Masukkan nama produk"
            value="<?= htmlspecialchars($dataEdit['NamaProduk'] ?? '') ?>"
            required
        >

        <label>Harga</label>

        <input
            type="number"
            name="harga"
            min="0"
            step="0.01"
            placeholder="Masukkan harga"
            value="<?= htmlspecialchars($dataEdit['Harga'] ?? '') ?>"
            required
        >

        <label>Stok</label>

        <input
            type="number"
            name="stok"
            min="0"
            placeholder="Masukkan stok"
            value="<?= htmlspecialchars($dataEdit['Stok'] ?? '') ?>"
            required
        >

        <?php if ($dataEdit): ?>

            <button type="submit" name="edit">
                Simpan Perubahan
            </button>

            <a href="produk.php">Batal Edit</a>

        <?php else: ?>

            <button type="submit" name="tambah">
                Tambah Produk
            </button>

        <?php endif; ?>

    </form>

    <h3>Daftar Produk</h3>

    <div class="tabel-wrapper">

        <table>
            <tr>
                <th>ID</th>
                <th>Nama Produk</th>
                <th>Harga</th>
                <th>Stok</th>
                <th>Aksi</th>
            </tr>

            <?php if (count($produk) > 0): ?>

                <?php foreach ($produk as $p): ?>

                    <tr>
                        <td><?= $p['ProdukID'] ?></td>

                        <td>
                            <?= htmlspecialchars($p['NamaProduk']) ?>
                        </td>

                        <td>
                            Rp <?= number_format($p['Harga'], 0, ',', '.') ?>
                        </td>

                        <td><?= $p['Stok'] ?></td>

                        <td class="aksi">

                            <a href="produk.php?edit=<?= (int) $p['ProdukID'] ?>">
                                Edit
                            </a>

                            <a
                                href="produk.php?hapus=<?= $p['ProdukID'] ?>"
                                onclick="return confirm('Yakin ingin menghapus produk ini?')"
                            >
                                Hapus
                            </a>

                        </td>
                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="5" style="text-align:center;">
                        Belum ada produk.
                    </td>
                </tr>

            <?php endif; ?>

        </table>

    </div>

    <div class="navigasi">
        <a href="javascript:history.back()">Kembali</a>
        |
        <a href="logout.php">Logout</a>
    </div>

</div>

</body>
</html>
