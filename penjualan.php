
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

// PROSES TRANSAKSI
if (isset($_POST['simpan'])) {
    $pelangganID = (int) $_POST['pelanggan'];
    $produkID = $_POST['produk'] ?? [];
    $jumlah = $_POST['jumlah'] ?? [];

    try {
        if ($pelangganID <= 0 || empty($produkID)) {
            throw new Exception("Pilih pelanggan dan minimal satu produk.");
        }

        $conn->beginTransaction();

        // CEK PELANGGAN
        $cekPelanggan = $conn->prepare(
            "SELECT PelangganID FROM pelanggan WHERE PelangganID = ?"
        );
        $cekPelanggan->execute([$pelangganID]);

        if (!$cekPelanggan->fetch()) {
            throw new Exception("Pelanggan tidak ditemukan.");
        }

        $items = [];
        $totalHarga = 0;

        // CEK PRODUK DAN STOK
        foreach ($produkID as $index => $id) {
            $id = (int) $id;
            $qty = (int) ($jumlah[$index] ?? 0);

            if ($id <= 0 || $qty <= 0) {
                continue;
            }

            $query = $conn->prepare(
                "SELECT ProdukID, Harga, Stok
                 FROM produk
                 WHERE ProdukID = ?
                 FOR UPDATE"
            );
            $query->execute([$id]);

            $produk = $query->fetch(PDO::FETCH_ASSOC);

            if (!$produk) {
                throw new Exception("Produk tidak ditemukan.");
            }

            if ($qty > $produk['Stok']) {
                throw new Exception("Stok produk tidak mencukupi.");
            }

            $subtotal = $produk['Harga'] * $qty;
            $totalHarga += $subtotal;

            $items[] = [
                'id' => $id,
                'jumlah' => $qty,
                'subtotal' => $subtotal
            ];
        }

        if (empty($items)) {
            throw new Exception("Jumlah pembelian tidak valid.");
        }

        // SIMPAN PENJUALAN
        $query = $conn->prepare(
            "INSERT INTO penjualan
             (TanggalPenjualan, TotalHarga, PelangganID)
             VALUES (NOW(), ?, ?)"
        );

        $query->execute([$totalHarga, $pelangganID]);

        $penjualanID = $conn->lastInsertId();

        // SIMPAN DETAIL DAN KURANGI STOK
        foreach ($items as $item) {
            $query = $conn->prepare(
                "INSERT INTO detailpenjualan
                 (PenjualanID, ProdukID, JumlahProduk, Subtotal)
                 VALUES (?, ?, ?, ?)"
            );

            $query->execute([
                $penjualanID,
                $item['id'],
                $item['jumlah'],
                $item['subtotal']
            ]);

            $query = $conn->prepare(
                "UPDATE produk
                 SET Stok = Stok - ?
                 WHERE ProdukID = ?"
            );

            $query->execute([
                $item['jumlah'],
                $item['id']
            ]);
        }

        $conn->commit();

        header("Location: penjualan.php?status=berhasil");
        exit;

    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $pesan = $e->getMessage();
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        $pesan = "Transaksi gagal disimpan.";
    }
}

// AMBIL DATA PELANGGAN
$pelanggan = $conn->query(
    "SELECT * FROM pelanggan ORDER BY NamaPelanggan ASC"
)->fetchAll(PDO::FETCH_ASSOC);

// AMBIL DATA PRODUK
$produk = $conn->query(
    "SELECT * FROM produk WHERE Stok > 0 ORDER BY NamaProduk ASC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Penjualan</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            padding: 30px;
        }

        .container {
            max-width: 850px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h2, h3 {
            color: #1e293b;
        }

        label {
            display: block;
            margin-top: 15px;
        }

        select, input {
            width: 100%;
            padding: 10px;
            margin-top: 7px;
            border: 1px solid #cbd5e1;
            border-radius: 5px;
            box-sizing: border-box;
        }

        button {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            background: #2563eb;
            color: white;
            cursor: pointer;
            margin-top: 15px;
        }

        .produk-item {
            border: 1px solid #ddd;
            padding: 15px;
            margin-top: 12px;
            border-radius: 7px;
        }

        .hapus {
            background: #dc2626;
        }

        .total {
            font-size: 20px;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<div class="container">

    <h2>Transaksi Penjualan</h2>

    <p>
        <a href="javascript:history.back()">Kembali</a> |
        <a href="logout.php">Logout</a>
    </p>

    <?php if (isset($_GET['status'])): ?>
        <p style="color:green;">
            Transaksi berhasil disimpan!
        </p>
    <?php endif; ?>

    <?php if ($pesan): ?>
        <p style="color:red;">
            <?= htmlspecialchars($pesan) ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <label>Pilih Pelanggan</label>

        <select name="pelanggan" required>
            <option value="">-- Pilih Pelanggan --</option>

            <?php foreach ($pelanggan as $p): ?>
                <option value="<?= $p['PelangganID'] ?>">
                    <?= htmlspecialchars($p['NamaPelanggan']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <h3>Daftar Produk</h3>

        <div id="daftar-produk">

            <div class="produk-item">

                <label>Produk</label>

                <select name="produk[]" class="produk" required>
                    <option value="">-- Pilih Produk --</option>

                    <?php foreach ($produk as $pr): ?>
                        <option
                            value="<?= $pr['ProdukID'] ?>"
                            data-harga="<?= $pr['Harga'] ?>"
                            data-stok="<?= $pr['Stok'] ?>"
                        >
                            <?= htmlspecialchars($pr['NamaProduk']) ?>
                            - Rp <?= number_format($pr['Harga'], 0, ',', '.') ?>
                            (Stok: <?= $pr['Stok'] ?>)
                        </option>
                    <?php endforeach; ?>

                </select>

                <label>Jumlah</label>

                <input
                    type="number"
                    name="jumlah[]"
                    class="jumlah"
                    min="1"
                    value="1"
                    required
                >

                <p>Subtotal: <span class="subtotal">Rp 0</span></p>

                <button type="button" class="hapus" onclick="hapusProduk(this)">
                    Hapus Produk
                </button>

            </div>

        </div>

        <button type="button" onclick="tambahProduk()">
            + Tambah Produk
        </button>

        <div class="total">
            Total: <span id="total">Rp 0</span>
        </div>

        <button type="submit" name="simpan">
            Simpan Transaksi
        </button>

    </form>

</div>

<script>
function hitungTotal() {
    let total = 0;

    document.querySelectorAll('.produk-item').forEach(item => {
        let select = item.querySelector('.produk');
        let jumlah = item.querySelector('.jumlah');

        let harga = Number(
            select.selectedOptions[0]?.dataset.harga || 0
        );

        let stok = Number(
            select.selectedOptions[0]?.dataset.stok || 0
        );

        let qty = Number(jumlah.value || 0);

        if (qty > stok) {
            jumlah.setCustomValidity('Jumlah melebihi stok tersedia.');
        } else {
            jumlah.setCustomValidity('');
        }

        let subtotal = harga * qty;

        item.querySelector('.subtotal').textContent =
            'Rp ' + subtotal.toLocaleString('id-ID');

        total += subtotal;
    });

    document.getElementById('total').textContent =
        'Rp ' + total.toLocaleString('id-ID');
}

function tambahProduk() {
    let daftar = document.getElementById('daftar-produk');
    let item = daftar.querySelector('.produk-item').cloneNode(true);

    item.querySelector('.produk').selectedIndex = 0;
    item.querySelector('.jumlah').value = 1;
    item.querySelector('.jumlah').setCustomValidity('');
    item.querySelector('.subtotal').textContent = 'Rp 0';

    daftar.appendChild(item);
    hitungTotal();
}

function hapusProduk(button) {
    let daftar = document.querySelectorAll('.produk-item');

    if (daftar.length > 1) {
        button.closest('.produk-item').remove();
        hitungTotal();
    }
}

document.addEventListener('input', hitungTotal);
document.addEventListener('change', hitungTotal);

document.querySelector('form').addEventListener('submit', function(e) {
    if (!this.checkValidity()) {
        e.preventDefault();
        this.reportValidity();
    }
});

hitungTotal();
</script>

</body>
</html>
