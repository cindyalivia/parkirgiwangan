<?php
session_start();
date_default_timezone_set('Asia/Jakarta');

include 'koneksi.php';

$db = $koneksi ?? $conn ?? null;

if (!$db) {
    die("Koneksi database gagal!");
}

// Ambil parameter ID dari GET (URL) atau POST (Form Submit)
$id = $_GET['id'] ?? $_POST['modal-id-reservasi'] ?? $_POST['id_reservasi'] ?? '';

if (empty($id)) {
    die("ID Transaksi/Reservasi tidak ditemukan.");
}

$id_clean = mysqli_real_escape_string($db, $id);

// 1. Coba cari di tabel Reservasi dulu
$query = mysqli_query($db, "SELECT * FROM tb_reservasi WHERE id_reservasi = '$id_clean'");
$data  = mysqli_fetch_assoc($query);
$is_reservasi = (bool) $data;

// 2. Jika tidak ada di reservasi, cari di tabel Transaksi Parkir
if (!$data) {
    $query_manual = mysqli_query($db, "SELECT * FROM tb_parkir WHERE id_parkir = '$id_clean' OR id_transaksi = '$id_clean'");
    if ($query_manual) {
        $data = mysqli_fetch_assoc($query_manual);
    }
}

// Jika data tetap tidak ada di kedua tabel
if (!$data) {
    die("Data karcis tidak ditemukan.");
}

// --- SIMPAN PEMBAYARAN KE DATABASE (jika ini submit dari modal "Check-In & Bayar") ---
// Hanya jalan kalau request-nya POST dan membawa total_biaya (artinya baru saja dibayar),
// dan reservasinya belum berstatus Parkir/Selesai (mencegah update berulang saat halaman di-reprint).
if ($is_reservasi && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['total_biaya'])) {
    $status_sekarang = strtolower(trim($data['status'] ?? ''));
    if ($status_sekarang !== 'parkir' && $status_sekarang !== 'selesai') {
        $total_biaya_post = (int) $_POST['total_biaya'];
        mysqli_query($db, "UPDATE tb_reservasi SET status = 'Parkir', total_biaya = '$total_biaya_post' WHERE id_reservasi = '$id_clean'");

        // Refresh data supaya tampilan struk pakai nilai terbaru
        $query = mysqli_query($db, "SELECT * FROM tb_reservasi WHERE id_reservasi = '$id_clean'");
        $data  = mysqli_fetch_assoc($query);
    }
}

// Penyesuaian variabel data
$no_karcis    = $data['no_karcis'] ?? $data['kode_parkir'] ?? ('PRK-' . date('YmdHis'));
$nama_pemohon = $data['nama_pemohon'] ?? $data['nama'] ?? $data['nama_pengendara'] ?? '-';
$no_plat      = $data['plat_nomor'] ?? $data['no_plat'] ?? '-';
$jenis        = $data['jenis_kendaraan'] ?? 'Motor';
$waktu_masuk   = $data['waktu_reservasi'] ?? $data['waktu_masuk'] ?? date('Y-m-d H:i:s');
$petugas       = $_SESSION['username'] ?? 'petugas01';

// Penentuan Area Parkir & Tarif Otomatis (3000, 7000, 15000)
$jenis_lower = strtolower($jenis);
if (strpos($jenis_lower, 'mobil') !== false) {
    $area_parkir   = 'Area B (Mobil)';
    $tarif_per_jam = 'Rp. 7.000';
    $tarif_angka   = 7000;
} elseif (strpos($jenis_lower, 'truk') !== false || strpos($jenis_lower, 'bus') !== false) {
    $area_parkir   = 'Area C (Truk/Bus)';
    $tarif_per_jam = 'Rp. 15.000';
    $tarif_angka   = 15000;
} else {
    $area_parkir   = 'Area A (Motor)';
    $tarif_per_jam = 'Rp. 3.000';
    $tarif_angka   = 3000;
}

// Ambil Nominal Uang Bayar & Total Biaya.
// PENTING: total_biaya diprioritaskan dari POST (nilai yang baru saja dibayar di modal),
// baru fallback ke database. Sebelumnya kode ini selalu pakai nilai lama di database,
// makanya kembalian bisa salah hitung meski uang bayar sudah pas.
$total_biaya = $_POST['total_biaya'] ?? $data['total_biaya'] ?? $data['biaya'] ?? $tarif_angka;
$total_biaya = (int) $total_biaya;

$bayar = $_POST['modal-bayar'] ?? $_POST['uang_bayar'] ?? $data['bayar'] ?? $total_biaya;
$bayar = (int) $bayar;

$kembali = max(0, $bayar - $total_biaya);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Karcis & Masuk Berhasil</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
        }

        body {
            background-color: #ffffff;
            color: #000000;
            display: flex;
            justify-content: center;
            padding-top: 20px;
        }

        .ticket-wrapper {
            width: 100%;
            max-width: 380px;
            text-align: center;
        }

        .header h2 {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 2px;
        }

        .header p {
            font-size: 13px;
            color: #333;
        }

        .divider {
            border-top: 1px dashed #000000;
            margin: 10px 0;
        }

        .info-table {
            width: 100%;
            font-size: 13px;
            text-align: left;
            border-collapse: collapse;
        }

        .info-table tr td {
            padding: 3px 0;
            vertical-align: top;
        }

        .info-table tr td:first-child {
            width: 40%;
            color: #000;
        }

        .info-table tr td:last-child {
            text-align: right;
            color: #000;
            font-weight: normal;
        }

        .info-table td.bold-val {
            font-weight: bold;
        }

        .qr-section {
            margin: 15px 0;
            display: flex;
            justify-content: center;
        }

        .qr-section img {
            width: 150px;
            height: 150px;
            object-fit: contain;
        }

        .footer p {
            font-size: 11px;
            color: #000;
            line-height: 1.4;
        }

        /* Styling Modal Pop-up Berhasil */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .modal-card {
            background-color: #1a0c20;
            border: 1px solid rgba(255, 51, 153, 0.35);
            border-radius: 24px;
            width: 92%;
            max-width: 440px;
            padding: 40px 35px;
            text-align: center;
            box-shadow: 0 0 35px rgba(255, 51, 153, 0.25), 0 10px 25px rgba(0, 0, 0, 0.5);
            color: #ffffff;
        }

        .icon-circle {
            width: 80px;
            height: 80px;
            border: 2px solid #22c55e;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px auto;
        }

        .icon-circle svg {
            width: 40px;
            height: 40px;
            stroke: #22c55e;
        }

        .modal-title {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #ffffff;
        }

        .modal-desc {
            font-size: 14px;
            color: #d1d5db;
            margin-bottom: 26px;
            line-height: 1.5;
        }

        .btn-modal {
            background-color: #e91e63;
            color: #ffffff;
            border: none;
            padding: 13px 42px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        .btn-modal:hover {
            background-color: #d81b60;
        }

        @page {
            size: auto;
            margin: 5mm;
        }

        @media print {
            body {
                padding-top: 0;
            }

            .modal-overlay {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    <div class="ticket-wrapper">
        <div class="header">
            <h2>TERMINAL PARKIR</h2>
            <p>Karcis Masuk Kendaraan</p>
        </div>

        <div class="divider"></div>

        <table class="info-table">
            <tr>
                <td>No. Karcis:</td>
                <td><?= htmlspecialchars($no_karcis); ?></td>
            </tr>
            <?php if ($nama_pemohon !== '-'): ?>
            <tr>
                <td>Pengendara:</td>
                <td><?= htmlspecialchars($nama_pemohon); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td>No. Plat:</td>
                <td class="bold-val"><?= htmlspecialchars($no_plat); ?></td>
            </tr>
            <tr>
                <td>Jenis:</td>
                <td><?= htmlspecialchars($jenis); ?></td>
            </tr>
            <tr>
                <td>Area Parkir:</td>
                <td><?= htmlspecialchars($area_parkir); ?></td>
            </tr>
            <tr>
                <td>Tarif/Jam:</td>
                <td><?= htmlspecialchars($tarif_per_jam); ?></td>
            </tr>
            <tr>
                <td>Waktu Masuk:</td>
                <td><?= htmlspecialchars($waktu_masuk); ?></td>
            </tr>
            <tr>
                <td>Petugas:</td>
                <td><?= htmlspecialchars($petugas); ?></td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="info-table">
            <tr>
                <td>Total Biaya:</td>
                <td>Rp <?= number_format($total_biaya, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Uang Bayar:</td>
                <td>Rp <?= number_format($bayar, 0, ',', '.'); ?></td>
            </tr>
            <tr>
                <td>Kembalian:</td>
                <td class="bold-val">Rp <?= number_format($kembali, 0, ',', '.'); ?></td>
            </tr>
        </table>

        <!-- QRIS IMAGE -->
        <div class="qr-section">
            <img src="qris.jpeg" alt="QRIS Code">
        </div>

        <div class="divider"></div>

        <div class="footer">
            <p>JANGAN HILANGKAN KARCIS INI</p>
            <p>Terima Kasih Atas Kunjungan Anda</p>
        </div>
    </div>

    <!-- Modal Pop-up Notifikasi Berhasil -->
    <div class="modal-overlay" id="successModal">
        <div class="modal-card">
            <div class="icon-circle">
                <svg fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <div class="modal-title">Cetak Karcis & Masuk Berhasil!</div>
            <div class="modal-desc">Data kendaraan telah berhasil disimpan ke sistem.</div>
            <button class="btn-modal" onclick="closeOrRedirect()">Selesai</button>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Tampilkan popup sukses SEGERA saat struk terbuka
            showSuccessModal();
            // Baru buka dialog print sedikit setelahnya, tanpa menunggu popup ditutup
            setTimeout(function () {
                window.print();
            }, 300);
        };

        function showSuccessModal() {
            document.getElementById('successModal').style.display = 'flex';
            playSimpleBeep();
        }

        function playSimpleBeep() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

                const osc1 = audioCtx.createOscillator();
                const osc2 = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc1.frequency.setValueAtTime(1046.50, audioCtx.currentTime);
                osc2.frequency.setValueAtTime(1318.51, audioCtx.currentTime);

                osc1.type = 'sine';
                osc2.type = 'sine';

                gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.2);

                osc1.connect(gain);
                osc2.connect(gain);
                gain.connect(audioCtx.destination);

                osc1.start();
                osc2.start();
                osc1.stop(audioCtx.currentTime + 0.2);
                osc2.stop(audioCtx.currentTime + 0.2);
            } catch (e) {
                console.log("Audio Context diblokir browser", e);
            }
        }

        function closeOrRedirect() {
            if (window.opener || window.history.length > 1) {
                window.close();
            } else {
                window.location.href = 'dashboard.php';
            }
        }
    </script>

</body>
</html>
