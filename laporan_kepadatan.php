<?php
session_start();
include 'koneksi.php';

// Pastikan user sudah login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Ambil data area parkir untuk memantau kepadatan slot
$query_kepadatan = mysqli_query($conn, "SELECT * FROM tb_area_parkir ORDER BY nama_area ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman Laporan Kepadatan Parkir - Neon Pink Purple</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #090514;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(255, 0, 127, 0.12) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(155, 0, 250, 0.15) 0%, transparent 40%);
            color: #f1f5f9;
            min-height: 100vh;
            padding: 40px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* --- HEADER STYLE (NEON PINK-PURPLE) --- */
        .header-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(20, 10, 35, 0.6);
            padding: 22px 30px;
            border-radius: 16px;
            border: 1px solid rgba(255, 0, 127, 0.4);
            box-shadow: 0 0 20px rgba(255, 0, 127, 0.2), inset 0 0 15px rgba(155, 0, 250, 0.15);
            backdrop-filter: blur(12px);
            margin-bottom: 30px;
        }

        .header-panel h2 {
            font-size: 24px;
            font-weight: 700;
            background: linear-gradient(135deg, #ff007f, #b537ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 12px rgba(255, 0, 127, 0.4);
        }

        .header-panel p {
            font-size: 14px;
            color: #d1b3ff;
            margin-top: 4px;
        }

        .btn-back {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, rgba(255, 0, 127, 0.2), rgba(155, 0, 250, 0.2));
            color: #ff99dd;
            text-decoration: none;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 0, 127, 0.5);
            box-shadow: 0 0 10px rgba(255, 0, 127, 0.2);
        }

        .btn-back:hover {
            background: linear-gradient(135deg, #ff007f, #9b00fa);
            color: #ffffff;
            box-shadow: 0 0 20px rgba(255, 0, 127, 0.8), 0 0 10px rgba(155, 0, 250, 0.8);
            transform: translateY(-2px);
        }

        /* --- CARD PANEL (GLOW CONTAINER) --- */
        .card-panel {
            background: rgba(18, 9, 32, 0.7);
            border: 1px solid rgba(155, 0, 250, 0.3);
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5), 0 0 25px rgba(155, 0, 250, 0.15);
            backdrop-filter: blur(10px);
        }

        .card-panel h3 {
            font-size: 17px;
            font-weight: 600;
            color: #ff66cc;
            text-shadow: 0 0 8px rgba(255, 102, 204, 0.6);
            border-bottom: 1px solid rgba(255, 0, 127, 0.2);
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        /* --- TABLE STYLING --- */
        .table-responsive {
            overflow-x: auto;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        .custom-table th {
            background: rgba(40, 15, 65, 0.6);
            color: #ffb3ec;
            font-weight: 600;
            padding: 16px;
            border-bottom: 2px solid rgba(255, 0, 127, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .custom-table td {
            padding: 16px;
            border-bottom: 1px solid rgba(155, 0, 250, 0.15);
            color: #e2d9f3;
            vertical-align: middle;
        }

        .custom-table tr:hover td {
            background: rgba(255, 0, 127, 0.05);
        }

        /* --- NEON STATUS BADGES --- */
        .badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.5px;
        }

        .badge-safe {
            background: rgba(0, 255, 204, 0.15);
            color: #00ffcc;
            border: 1px solid rgba(0, 255, 204, 0.5);
            box-shadow: 0 0 10px rgba(0, 255, 204, 0.3);
        }

        .badge-warning {
            background: rgba(255, 204, 0, 0.15);
            color: #ffcc00;
            border: 1px solid rgba(255, 204, 0, 0.5);
            box-shadow: 0 0 10px rgba(255, 204, 0, 0.3);
        }

        .badge-danger {
            background: rgba(255, 0, 127, 0.2);
            color: #ff3399;
            border: 1px solid rgba(255, 0, 127, 0.6);
            box-shadow: 0 0 12px rgba(255, 0, 127, 0.4);
        }

        /* --- PROGRESS BAR COMPONENT --- */
        .progress-container {
            width: 100%;
            max-width: 130px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 10px;
            overflow: hidden;
            height: 8px;
            margin-top: 6px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.4s ease;
        }
    </style>
</head>
<body>

    <div class="container">
        
        <!-- HEADER PANEL -->
        <div class="header-panel">
            <div>
                <h2>Halaman Laporan Kepadatan Parkir Terminal</h2>
                <p>Selamat Datang, Pemilik Sistem (<b><?= htmlspecialchars($_SESSION['nama'] ?? 'Bapak Bos'); ?></b>)</p>
            </div>
            <a href="dashboard.php" class="btn-back">◄ Kembali ke Dashboard</a>
        </div>

        <!-- LAYOUT UTAMA TABEL MAP -->
        <div class="card-panel">
            <h3>Status Real-Time Ketersediaan Slot Area</h3>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">No</th>
                            <th>Nama Area / Jalur</th>
                            <th>Total Kapasitas Slot</th>
                            <th>Kendaraan Terisi</th>
                            <th>Sisa Slot Kosong</th>
                            <th>Status Kepadatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($query_kepadatan && mysqli_num_rows($query_kepadatan) > 0):
                            while($row = mysqli_fetch_assoc($query_kepadatan)): 
                                $total = $row['kapasitas_total'] ?? 0;
                                $terisi = $row['kapasitas_terisi'] ?? 0;
                                $sisa = $total - $terisi;
                                
                                // Hitung persentase keterisian area
                                $persentase = ($total > 0) ? ($terisi / $total) * 100 : 0;

                                // Penentuan Badge Status & Warna Bar Neon
                                if ($persentase >= 90) {
                                    $status_txt = "🔴 PENUH";
                                    $badge_class = "badge-danger";
                                    $bar_color = "#ff007f"; // Neon Pink
                                } elseif ($persentase >= 70) {
                                    $status_txt = "🟡 RAMAI";
                                    $badge_class = "badge-warning";
                                    $bar_color = "#ffcc00"; // Neon Yellow
                                } else {
                                    $status_txt = "🟢 LONGGAR";
                                    $badge_class = "badge-safe";
                                    $bar_color = "#00ffcc"; // Neon Cyan/Green
                                }
                        ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td>
                                <b style="color: #ffffff; text-shadow: 0 0 5px rgba(255,255,255,0.3);"><?= htmlspecialchars($row['nama_area']); ?></b>
                                <div class="progress-container">
                                    <div class="progress-bar" style="width: <?= $persentase; ?>%; background-color: <?= $bar_color; ?>; box-shadow: 0 0 8px <?= $bar_color; ?>;"></div>
                                </div>
                            </td>
                            <td><?= $total; ?> Kapasitas</td>
                            <td style="color: #d1b3ff;"><?= $terisi; ?> Slot</td>
                            <td style="font-weight: 600; color: #ff66cc; text-shadow: 0 0 5px rgba(255,102,204,0.4);"><?= $sisa; ?> Slot Tersisa</td>
                            <td>
                                <span class="badge <?= $badge_class; ?>">
                                    <?= $status_txt; ?> (<?= round($persentase); ?>%)
                                </span>
                            </td>
                        </tr>
                        <?php 
                            endwhile; 
                        else: 
                        ?>
                        <tr>
                            <!-- Tampilan fallback jika tabel database belum diintegrasikan -->
                            <td colspan="6" style="text-align: center; color: #b599d6; padding: 40px;">
                                📊 Data kepadatan slot parkir belum diintegrasikan atau masih kosong.
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>