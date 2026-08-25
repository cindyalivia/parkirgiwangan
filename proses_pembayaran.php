<?php
session_start();
include 'koneksi.php';

// Wajib: Atur header agar PHP memberikan respon JSON untuk AJAX
header('Content-Type: application/json');

// Tangkap input dari AJAX (bisa berupa $_POST biasa atau JSON Body)
$input_raw = json_decode(file_get_contents('php://input'), true);

$id_reservasi = intval($_POST['id_reservasi'] ?? $input_raw['id_reservasi'] ?? 0);
$uang_bayar   = floatval($_POST['uang_bayar'] ?? $input_raw['uang_bayar'] ?? 0);
$total_biaya  = floatval($_POST['total_biaya'] ?? $input_raw['total_biaya'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $id_reservasi > 0) {
    
    // Cek apakah uang cukup
    if ($uang_bayar < $total_biaya) {
        echo json_encode([
            'success' => false,
            'message' => 'Uang pembayaran kurang!'
        ]);
        exit();
    }

    // Ubah status menjadi 'Masuk' atau 'Parkir' agar kendaraan terdeteksi ada di dalam area parkir
     $query = "UPDATE tb_reservasi SET status = 'Masuk' WHERE id_reservasi = '$id_reservasi'";
    if (mysqli_query($conn, $query)) {
        echo json_encode([
            'success' => true,
            'message' => 'Pembayaran Berhasil!'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Gagal memperbarui database: ' . mysqli_error($conn)
        ]);
    }

} else {
    echo json_encode([
        'success' => false,
        'message' => 'Data tidak valid atau metode salah!'
    ]);
}
?>