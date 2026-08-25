<?php
session_start();
include 'koneksi.php';

header('Content-Type: application/json');

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Sesi login habis.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $no_plat = strtoupper(mysqli_real_escape_string($conn, $_POST['no_plat']));
    $id_tarif = mysqli_real_escape_string($conn, $_POST['id_tarif']);
    $id_area = mysqli_real_escape_string($conn, $_POST['id_area']);
    $waktu_masuk = date('Y-m-d H:i:s');
    $petugas = $_SESSION['username'];
    
    $no_karcis = "PRK-" . date('YmdHis');

    // =========================================================================
    // BARIS 21: PANDUAN PENYESUAIAN NAMA KOLOM TABEL `tb_transaksi`
    // Jika nama kolom di phpMyAdmin berbeda, ubah kata yang ada di dalam kurung pertama.
    // Contoh: (no_karcis -> kode_karcis), (no_plat -> plat_nomor), dst.
    // =========================================================================
    $query_simpan = mysqli_query($conn, "INSERT INTO tb_transaksi (no_karcis, no_plat, id_tarif, id_area, waktu_masuk, petugas) VALUES ('$no_karcis', '$no_plat', '$id_tarif', '$id_area', '$waktu_masuk', '$petugas')");
    
    if ($query_simpan) {
        // Update kuota kapasitas terisi
        mysqli_query($conn, "UPDATE tb_area_parkir SET kapasitas_terisi = kapasitas_terisi + 1 WHERE id_area = '$id_area'");
        
        // Ambil detail tarif & area
        $ambil_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE id_tarif = '$id_tarif'");
        $data_t = mysqli_fetch_assoc($ambil_tarif);
        
        $ambil_area = mysqli_query($conn, "SELECT * FROM tb_area_parkir WHERE id_area = '$id_area'");
        $data_a = mysqli_fetch_assoc($ambil_area);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'no_karcis' => $no_karcis,
                'no_plat' => $no_plat,
                'jenis' => $data_t['jenis_kendaraan'] ?? 'Kendaraan',
                'tarif' => "Rp. " . number_format(($data_t['tarif_per_jam'] ?? 0), 0, ',', '.'),
                'area' => $data_a['nama_area'] ?? 'Area',
                'waktu' => $waktu_masuk,
                'petugas' => $petugas
            ]
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => mysqli_error($conn)]);
    }
}
?>