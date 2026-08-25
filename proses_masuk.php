<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login telah berakhir. Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_plat  = isset($_POST['no_plat']) ? strtoupper(trim($_POST['no_plat'])) : '';
    $id_tarif = isset($_POST['id_tarif']) ? intval($_POST['id_tarif']) : 0;
    $id_area  = isset($_POST['id_area']) ? intval($_POST['id_area']) : 0;
    $username = $_SESSION['username'];
    $waktu_masuk = date('Y-m-d H:i:s');

    if (empty($no_plat) || empty($id_tarif) || empty($id_area)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua data input wajib diisi!']);
        exit;
    }

    // 1. Ambil id_user dari tb_user
    $q_user = mysqli_query($conn, "SELECT id_user FROM tb_user WHERE username = '$username'");
    $d_user = mysqli_fetch_assoc($q_user);
    $id_user_petugas = isset($d_user['id_user']) ? $d_user['id_user'] : 'NULL';

    // 2. Generate kode barcode / nomor karcis unik
    $kode_barcode = "PRK-" . date('YmdHis') . rand(10, 99);

    // 3. Simpan ke tb_transaksi
    $query_insert = "INSERT INTO tb_transaksi (kode_barcode, no_plat, id_tarif, id_area, waktu_masuk, status, id_user_petugas) 
                     VALUES ('$kode_barcode', '$no_plat', '$id_tarif', '$id_area', '$waktu_masuk', 'masuk', $id_user_petugas)";

    if (mysqli_query($conn, $query_insert)) {
        
        // 4. SIMPAN JUGA KE tb_reservasi (Supaya terbaca di halaman Parkir Keluar!)
        @mysqli_query($conn, "INSERT INTO tb_reservasi (kode_reservasi, no_karcis, plat_nomor, id_tarif, id_area, waktu_masuk, status) 
                             VALUES ('$kode_barcode', '$kode_barcode', '$no_plat', '$id_tarif', '$id_area', '$waktu_masuk', 'MASUK')");

        // 5. Update kapasitas terisi pada area parkir
        mysqli_query($conn, "UPDATE tb_area_parkir SET kapasitas_terisi = kapasitas_terisi + 1 WHERE id_area = '$id_area'");

        echo json_encode([
            'status' => 'success',
            'no_karcis' => $kode_barcode,
            'waktu_masuk' => $waktu_masuk
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Query Error: ' . mysqli_error($conn)]);
    }
}
?>