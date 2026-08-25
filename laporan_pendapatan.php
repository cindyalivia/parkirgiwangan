<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// 1. Eksekusi Query Laporan Pendapatan Ringkasan
$query_summary = "SELECT 
                    COUNT(*) AS total_transaksi, 
                    SUM(total_bayar) AS total_pendapatan 
                  FROM tb_transaksi 
                  WHERE status = 'keluar'";

$result_summary = $conn->query($query_summary);
$data_summary   = $result_summary->fetch_assoc();

$total_transaksi  = $data_summary['total_transaksi'] ?? 0;
$total_pendapatan = $data_summary['total_pendapatan'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Pendapatan - Sistem Parkir</title>
    
    <!-- Font Modern: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">

    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { 
            background: #0d0614; 
            color: #ffffff; 
            min-height: 100vh; 
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 40px 20px; 
            position: relative;
            overflow-x: hidden;
        }

        /* --- AMBIENT NEON PINK & PURPLE ORBS --- */
        .neon-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(150px);
            z-index: 1;
            pointer-events: none;
            animation: pulseGlow 10s ease-in-out infinite alternate;
        }

        .orb-1 {
            width: 500px;
            height: 500px;
            background: rgba(236, 72, 153, 0.3);
            top: -10%;
            left: -5%;
        }

        .orb-2 {
            width: 450px;
            height: 450px;
            background: rgba(168, 85, 247, 0.25);
            bottom: -10%;
            right: -5%;
            animation-delay: -5s;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.15) translate(20px, -20px); opacity: 0.85; }
        }

        .container { 
            width: 100%; 
            max-width: 900px; 
            position: relative;
            z-index: 10;
        }

        /* --- TOP NAVIGATION --- */
        .top-navigation { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 24px; 
        }

        .btn-dashboard { 
            background: rgba(23, 11, 35, 0.7); 
            color: #fce7f3; 
            text-decoration: none; 
            padding: 10px 18px; 
            border-radius: 12px; 
            font-size: 13px; 
            font-weight: 700;
            border: 1px solid rgba(244, 114, 182, 0.3); 
            backdrop-filter: blur(20px);
            transition: all 0.3s ease;
        }

        .btn-dashboard:hover {
            background: rgba(236, 72, 153, 0.25);
            border-color: rgba(236, 72, 153, 0.7);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(236, 72, 153, 0.4);
            transform: translateX(-3px);
        }

        /* TOMBOL CETAK LAPORAN */
        .btn-cetak {
            background: linear-gradient(135deg, #ec4899, #a855f7);
            color: #ffffff;
            border: none;
            padding: 10px 20px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-cetak:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(236, 72, 153, 0.6);
        }

        .header-title {
            text-align: center;
            margin-bottom: 30px;
        }

        .header-title h2 {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #FFFFFF 30%, #F472B6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 6px;
        }

        .header-title p {
            font-size: 14px;
            color: #fbcfe8;
            opacity: 0.8;
        }

        /* --- CARDS SUMMARY --- */
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-stat {
            background: rgba(23, 11, 35, 0.65);
            border: 1.5px solid rgba(244, 114, 182, 0.25);
            border-radius: 20px;
            padding: 24px;
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            transition: transform 0.3s ease;
        }

        .card-stat:hover {
            transform: translateY(-4px);
            border-color: rgba(244, 114, 182, 0.5);
        }

        .card-stat h4 {
            font-size: 13px;
            font-weight: 700;
            color: #fbcfe8;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            opacity: 0.85;
        }

        .card-stat .value {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
        }

        .card-stat .value.highlight {
            color: #4ade80;
            text-shadow: 0 0 15px rgba(74, 222, 128, 0.3);
        }

        /* --- TABLE CONTAINER --- */
        .card-table {
            background: rgba(23, 11, 35, 0.65);
            border: 1.5px solid rgba(244, 114, 182, 0.25);
            border-radius: 22px;
            padding: 24px;
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            overflow: hidden;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        thead tr {
            border-bottom: 1.5px solid rgba(244, 114, 182, 0.3);
        }

        th {
            padding: 14px 16px;
            color: #fbcfe8;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        tbody tr {
            border-bottom: 1px solid rgba(244, 114, 182, 0.1);
            transition: background 0.2s ease;
        }

        tbody tr:hover {
            background: rgba(236, 72, 153, 0.08);
        }

        td {
            padding: 16px;
            color: #e2e8f0;
        }

        td.no-karcis {
            font-family: 'Space Grotesk', monospace;
            font-weight: 700;
            color: #f472b6;
        }

        .badge-lunas {
            display: inline-block;
            background: rgba(74, 222, 128, 0.15);
            color: #4ade80;
            border: 1px solid rgba(74, 222, 128, 0.4);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .empty-row {
            text-align: center;
            padding: 30px;
            color: #fbcfe8;
            opacity: 0.6;
            font-style: italic;
        }

        /* Custom SweetAlert Style */
        .swal2-popup-custom {
            background: rgba(22, 12, 35, 0.95) !important;
            border: 1px solid rgba(244, 114, 182, 0.4) !important;
            border-radius: 20px !important;
            color: #ffffff !important;
            backdrop-filter: blur(15px);
        }
        .swal2-title-custom { color: #ffffff !important; font-weight: 800 !important; }
        .swal2-html-custom { color: #fbcfe8 !important; }

        /* ==========================================
           FIX MUTLAK: MEDIA PRINT (CETAK DOKUMEN)
           ========================================== */
        @media print {
            @page {
                size: A4 portrait;
                margin: 15mm;
            }

            /* Sembunyikan elemen non-cetak & efek SweetAlert */
            .neon-orb, 
            .top-navigation, 
            .swal2-container, 
            .swal2-backdrop-show,
            script {
                display: none !important;
                visibility: hidden !important;
            }

            /* Reset Total Layar */
            html, body {
                background: #ffffff !important;
                color: #000000 !important;
                margin: 0 !important;
                padding: 0 !important;
                display: block !important;
                min-height: auto !important;
                height: auto !important;
                overflow: visible !important;
            }

            /* Paksa Semua Teks Menjadi Hitam */
            *, *::before, *::after {
                background: transparent !important;
                color: #000000 !important;
                text-shadow: none !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
                -webkit-text-fill-color: #000000 !important;
            }

            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                position: static !important;
            }

            .header-title {
                margin-bottom: 25px !important;
                text-align: center !important;
            }

            .header-title h2 {
                font-size: 20pt !important;
                font-weight: bold !important;
                margin-bottom: 5px !important;
            }

            .header-title p {
                font-size: 10pt !important;
            }

            .summary-grid {
                display: flex !important;
                justify-content: space-between !important;
                gap: 15px !important;
                margin-bottom: 20px !important;
            }

            .card-stat {
                flex: 1 !important;
                border: 1px solid #000000 !important;
                border-radius: 6px !important;
                padding: 12px !important;
            }

            .card-stat h4 {
                font-size: 9pt !important;
                margin-bottom: 5px !important;
            }

            .card-stat .value {
                font-size: 16pt !important;
                font-weight: bold !important;
            }

            .card-table {
                border: none !important;
                padding: 0 !important;
                overflow: visible !important;
            }

            table {
                width: 100% !important;
                border-collapse: collapse !important;
                font-size: 10pt !important;
            }

            th, td {
                border: 1px solid #000000 !important;
                padding: 8px 10px !important;
                text-align: left !important;
            }

            th {
                background-color: #f2f2f2 !important;
                font-weight: bold !important;
            }

            .badge-lunas {
                border: none !important;
                padding: 0 !important;
                font-weight: bold !important;
            }
        }
    </style>
</head>
<body>

    <div class="neon-orb orb-1"></div>
    <div class="neon-orb orb-2"></div>

    <div class="container">
        <div class="top-navigation">
            <a href="dashboard.php" class="btn-dashboard">◄ Kembali ke Dashboard</a>
            <button onclick="cetakLaporan()" class="btn-cetak">🖨️ Cetak Laporan</button>
        </div>

        <div class="header-title">
            <h2>Laporan Pendapatan Parkir</h2>
            <p>Rekapitulasi Transaksi Pembayaran dan Kendaraan Keluar</p>
        </div>

        <!-- STATISTIK RINGKASAN -->
        <div class="summary-grid">
            <div class="card-stat">
                <h4>Total Kendaraan Keluar (Lunas)</h4>
                <div class="value"><?= number_format($total_transaksi, 0, ',', '.'); ?> <span style="font-size: 14px; font-weight: 500;">Kendaraan</span></div>
            </div>

            <div class="card-stat">
                <h4>Total Pendapatan Uang</h4>
                <div class="value highlight">Rp <?= number_format($total_pendapatan, 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- TABEL RINCIAN TRANSAKSI -->
        <div class="card-table">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>No Karcis</th>
                            <th>Waktu Masuk</th>
                            <th>Waktu Keluar</th>
                            <th>Total Biaya</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $detail_query = "SELECT * FROM tb_transaksi WHERE status = 'keluar' ORDER BY waktu_keluar DESC";
                        $detail_result = $conn->query($detail_query);

                        if ($detail_result && $detail_result->num_rows > 0) {
                            while ($row = $detail_result->fetch_assoc()) {
                                echo "<tr>
                                        <td>{$no}</td>
                                        <td class='no-karcis'>{$row['kode_barcode']}</td>
                                        <td>{$row['waktu_masuk']}</td>
                                        <td>{$row['waktu_keluar']}</td>
                                        <td style='font-weight:700;'>Rp " . number_format($row['total_bayar'], 0, ',', '.') . "</td>
                                        <td><span class='badge-lunas'>" . strtoupper($row['status']) . "</span></td>
                                      </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='6' class='empty-row'>Belum ada data transaksi lunas.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<script>
function cetakLaporan() {
    Swal.fire({
        icon: 'success',
        title: 'Cetak Laporan',
        text: 'Menyiapkan dokumen laporan pendapatan...',
        confirmButtonText: 'Lanjutkan Cetak',
        confirmButtonColor: '#ec4899',
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom',
            htmlContainer: 'swal2-html-custom'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // 1. Tutup SweetAlert secara total lebih dahulu
            Swal.close();

            // 2. Beri jeda sebentar agar browser selesai menghilangkan efek modal/blur SweetAlert
            setTimeout(() => {
                window.print();
            }, 300);
        }
    });
}
</script>

</body> 
</html>