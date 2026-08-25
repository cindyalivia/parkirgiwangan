<?php
session_start();
include 'koneksi.php';

if ($_SESSION['role'] !== 'owner') {
    echo "Akses hanya untuk Owner."; exit;
}

// Menghitung akumulasi pendapatan
$query_total = mysqli_query($conn, "SELECT SUM(total_bayar) AS grand_total, COUNT(id_transaksi) AS total_kendaraan FROM tb_transaksi WHERE status='keluar'");
$summary = mysqli_fetch_assoc($query_total);

// Menampilkan list transaksi terakhir
$riwayat = mysqli_query($conn, "SELECT t.*, u.nama_lengkap AS nama_petugas, a.nama_area 
                                FROM tb_transaksi t 
                                JOIN tb_user u ON t.id_user_petugas = u.id_user 
                                JOIN tb_area_parkir a ON t.id_area = a.id_area 
                                WHERE t.status = 'keluar' 
                                ORDER BY t.waktu_keluar DESC");
?>

<!DOCTYPE html>
<html>
<head><title>Laporan Pendapatan Parkir</title></head>
<body>
    <h2>Laporan Pendapatan - Ringkasan Owner</h2>
    <a href="dashboard.php">Kembali ke Dashboard</a>
    <hr>
    <h3>Ringkasan:</h3>
    <p><strong>Total Omset Keuangan:</strong> Rp. <?= number_format($summary['grand_total']); ?></p>
    <p><strong>Total Kendaraan Selesai Parkir:</strong> <?= $summary['total_kendaraan']; ?> unit</p>

    <h3>Detail Log Transaksi:</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>No</th>
                <th>Kode Barcode</th>
                <th>Plat Nomor</th>
                <th>Area</th>
                <th>Durasi</th>
                <th>Total Bayar</th>
                <th>Petugas Kasir</th>
                <th>Waktu Keluar</th>
            </tr>
        </thead>
        <tbody>
            <?php $no=1; while($r = mysqli_fetch_assoc($riwayat)): ?>
            <tr>
                <td><?= $no++; ?></td>
                <td><?= $r['kode_barcode']; ?></td>
                <td><?= $r['no_plat']; ?></td>
                <td><?= $r['nama_area']; ?></td>
                <td><?= $r['total_durasi']; ?> Jam</td>
                <td>Rp. <?= number_format($r['total_bayar']); ?></td>
                <td><?= $r['nama_petugas']; ?></td>
                <td><?= $r['waktu_keluar']; ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>