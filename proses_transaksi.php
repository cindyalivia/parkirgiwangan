<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil input keyword dari form pencarian
    $keyword_raw = $_POST['keyword'] ?? $_POST['no_karcis'] ?? '';
    
    // Hapus spasi dari input user (misal: "AA 987 AD" menjadi "AA987AD")
    $keyword = str_replace(' ', '', $keyword_raw);
    $keyword = mysqli_real_escape_string($koneksi, $keyword);

    if (empty($keyword)) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Keyword pencarian tidak boleh kosong!'
        ]);
        exit();
    }

    $data_ditemukan = null;
    $tipe_parkir = '';

    // ==========================================
    // 1. CEK DALAM TABEL PARKIR MANUAL (tb_parkir)
    // ==========================================
    $query_manual = "SELECT * FROM tb_parkir 
                     WHERE (REPLACE(plat_nomor, ' ', '') = '$keyword' 
                        OR REPLACE(no_karcis, ' ', '') = '$keyword')
                     AND (status = 'Masuk' OR status = 'Parkir')";
                     
    $res_manual = mysqli_query($koneksi, $query_manual);

    if ($res_manual && mysqli_num_rows($res_manual) > 0) {
        $data_ditemukan = mysqli_fetch_assoc($res_manual);
        $tipe_parkir = 'manual';
    } else {
        // ==========================================
        // 2. JIKA TIDAK ADA, CEK TABEL RESERVASI (tb_reservasi)
        // ==========================================
        $query_reservasi = "SELECT * FROM tb_reservasi 
                            WHERE (REPLACE(plat_nomor, ' ', '') = '$keyword' 
                               OR REPLACE(kode_karcis, ' ', '') = '$keyword' 
                               OR id_reservasi = '$keyword')
                            AND (status = 'Masuk' OR status = 'Selesai' OR status = 'Parkir')";
                            
        $res_reservasi = mysqli_query($koneksi, $query_reservasi);

        if ($res_reservasi && mysqli_num_rows($res_reservasi) > 0) {
            $data_ditemukan = mysqli_fetch_assoc($res_reservasi);
            $tipe_parkir = 'reservasi';
        }
    }

    // ==========================================
    // 3. RESPONS JSON
    // ==========================================
    header('Content-Type: application/json');
    
    if ($data_ditemukan) {
        echo json_encode([
            'success' => true,
            'tipe'    => $tipe_parkir, // Menandai apakah data dari manual atau reservasi
            'data'    => $data_ditemukan
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => "Plat Nomor / Kode Karcis '$keyword_raw' tidak ditemukan atau kendaraan sudah keluar."
        ]);
    }
}
?>