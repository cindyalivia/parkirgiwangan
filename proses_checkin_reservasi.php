<?php
include 'koneksi.php';

if (isset($_POST['submit_checkin'])) {
    $id_reservasi = $_POST['id_reservasi'];
    $biaya = $_POST['biaya'];
    $bayar = $_POST['bayar'];

    if ($bayar < $biaya) {
        echo "<script>alert('Uang pembayaran kurang!'); window.history.back();</script>";
        exit;
    }

    $kembalian = $bayar - $biaya;

    // Update status reservasi menjadi selesai/terparkir
    $query = "UPDATE reservasi SET status = 'selesai', bayar = '$bayar', kembalian = '$kembalian' WHERE id_reservasi = '$id_reservasi'";
    $result = mysqli_query($koneksi, $query);

    if ($result) {
        echo "<script>alert('Check-In & Pembayaran Berhasil!'); window.location.href='dashboard.php';</script>";
    } else {
        echo "<script>alert('Gagal memproses check-in: " . mysqli_error($koneksi) . "'); window.history.back();</script>";
    }
}
?>