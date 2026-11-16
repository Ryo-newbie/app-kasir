
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

// FILTER TANGGAL
$tanggalAwal = $_GET['awal'] ?? '';
$tanggalAkhir = $_GET['akhir'] ?? '';

$sql = "
    SELECT
        p.PenjualanID,
        p.TanggalPenjualan,
        p.TotalHarga,
        pl.NamaPelanggan
    FROM penjualan p
    JOIN pelanggan pl ON p.PelangganID = pl.PelangganID
    WHERE 1=1
";

$params = [];

if ($tanggalAwal !== '') {
    $sql .= " AND DATE(p.TanggalPenjualan) >= ?";
    $params[] = $tanggalAwal;
}

if ($tanggalAkhir !== '') {
    $sql .= " AND DATE(p.TanggalPenjualan) <= ?";
    $params[] = $tanggalAkhir;
}

$sql .= " ORDER BY p.TanggalPenjualan DESC";

$query = $conn->prepare($sql);
$query->execute($params);

$laporan = $query->fetchAll(PDO::FETCH_ASSOC);

// TOTAL PENDAPATAN
$totalQuery = $conn->prepare("
    SELECT COALESCE(SUM(TotalHarga), 0)
    FROM penjualan
    WHERE 1=1
");

$totalParams = [];

if ($tanggalAwal !== '') {
    $totalQuery = $conn->prepare("
        SELECT COALESCE(SUM(TotalHarga), 0)
        FROM penjualan
        WHERE DATE(TanggalPenjualan) >= ?
    ");
    $totalParams[] = $tanggalAwal;
}

if ($tanggalAkhir !== '') {
    $sqlTotal = "
        SELECT COALESCE(SUM(TotalHarga), 0)
        FROM penjualan
        WHERE 1=1
    ";

    $totalParams = [];

    if ($tanggalAwal !== '') {
        $sqlTotal .= " AND DATE(TanggalPenjualan) >= ?";
        $totalParams[] = $tanggalAwal;
    }

    $sqlTotal .= " AND DATE(TanggalPenjualan) <= ?";
    $totalParams[] = $tanggalAkhir;

    $totalQuery = $conn->prepare($sqlTotal);
}

$totalQuery->execute($totalParams);
$totalPendapatan = $totalQuery->fetchColumn();

// DETAIL TRANSAKSI
$detailQuery = $conn->prepare("
    SELECT
        d.PenjualanID,
        pr.NamaProduk,
        d.JumlahProduk,
        d.Subtotal
    FROM detailpenjualan d
    JOIN produk pr ON d.ProdukID = pr.ProdukID
    ORDER BY d.PenjualanID DESC
");

$detailQuery->execute();
$detail = $detailQuery->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
        }

        .container {
            max-width: 1100px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2, h3 {
            color: #1e293b;
        }

        .filter {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px 0;
        }

        input, button {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
        }

        button {
            background: #2563eb;
            color: white;
            cursor: pointer;
            border: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
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

        .total {
            background: #eff6ff;
            padding: 18px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Laporan Penjualan</h2>

    <p>
        <a href="javascript:history.back()">Kembali</a> |
        <a href="logout.php">Logout</a>
    </p>

    <form method="GET" class="filter">

        <div>
            <label>Dari tanggal</label><br>
            <input type="date" name="awal"
                value="<?= htmlspecialchars($tanggalAwal) ?>">
        </div>

        <div>
            <label>Sampai tanggal</label><br>
            <input type="date" name="akhir"
                value="<?= htmlspecialchars($tanggalAkhir) ?>">
        </div>

        <div>
            <button type="submit">Filter</button>
            <a href="laporan.php">Reset</a>
        </div>

    </form>

    <div class="total">
        Total Pendapatan:
        Rp <?= number_format($totalPendapatan, 0, ',', '.') ?>
    </div>

    <h3>Riwayat Transaksi</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Tanggal</th>
            <th>Pelanggan</th>
            <th>Total Harga</th>
        </tr>

        <?php foreach ($laporan as $l): ?>
        <tr>
            <td><?= $l['PenjualanID'] ?></td>
            <td><?= htmlspecialchars($l['TanggalPenjualan']) ?></td>
            <td><?= htmlspecialchars($l['NamaPelanggan']) ?></td>
            <td>
                Rp <?= number_format($l['TotalHarga'], 0, ',', '.') ?>
            </td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($laporan)): ?>
        <tr>
            <td colspan="4">Tidak ada transaksi.</td>
        </tr>
        <?php endif; ?>

    </table>

    <h3>Detail Produk Terjual</h3>

    <table>
        <tr>
            <th>ID Transaksi</th>
            <th>Nama Produk</th>
            <th>Jumlah</th>
            <th>Subtotal</th>
        </tr>

        <?php foreach ($detail as $d): ?>
        <tr>
            <td><?= $d['PenjualanID'] ?></td>
            <td><?= htmlspecialchars($d['NamaProduk']) ?></td>
            <td><?= $d['JumlahProduk'] ?></td>
            <td>
                Rp <?= number_format($d['Subtotal'], 0, ',', '.') ?>
            </td>
        </tr>
        <?php endforeach; ?>

        <?php if (empty($detail)): ?>
        <tr>
            <td colspan="4">Belum ada detail penjualan.</td>
        </tr>
        <?php endif; ?>

    </table>

</div>

</body>
</html>
