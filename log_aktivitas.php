<?php
session_start();
include 'koneksi.php';

// Cek Login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$nama_admin = $_SESSION['nama_lengkap'] ?? $_SESSION['username'];
$role       = $_SESSION['role'] ?? 'admin';

// Fitur Kosongkan Log
if (isset($_POST['kosongkan_log'])) {
    mysqli_query($conn, "TRUNCATE TABLE tb_log_aktivitas");
    header("Location: log_aktivitas.php");
    exit();
}

// Ambil Data Log
$query_log = mysqli_query($conn, "SELECT tb_log_aktivitas.*, 
                                  IFNULL(tb_user.nama_lengkap, tb_user.username) AS nama_user, 
                                  IFNULL(tb_user.role, '-') AS role 
                                  FROM tb_log_aktivitas 
                                  LEFT JOIN tb_user ON tb_log_aktivitas.id_user = tb_user.id_user 
                                  ORDER BY tb_log_aktivitas.id_log DESC") or die(mysqli_error($conn));

// Hitung Aktivitas Per Role
$count_admin   = 0;
$count_petugas = 0;
$count_owner   = 0;

$data_rows = [];
if ($query_log && mysqli_num_rows($query_log) > 0) {
    while ($row = mysqli_fetch_assoc($query_log)) {
        $data_rows[] = $row;
        
        // Count Per Role
        $r = strtolower($row['role']);
        if ($r === 'admin') $count_admin++;
        elseif ($r === 'petugas') $count_petugas++;
        elseif ($r === 'owner') $count_owner++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Aktivitas & Audit Sistem Parkir</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #0d020d; color: #ffffff; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .header h2 { font-size: 22px; color: #ff3399; }
        .header p { font-size: 13px; color: #a090a5; }
        .btn-dash { background: rgba(255, 255, 255, 0.1); color: #fff; text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 12px; border: 1px solid rgba(255,255,255,0.2); }
        
        /* CONTAINER DIAGRAM FULL WIDTH */
        .charts-container { margin-bottom: 25px; }

        /* CARD DIAGRAM & TABEL */
        .card { background: rgba(20, 10, 25, 0.8); border: 1px solid rgba(255, 51, 153, 0.2); border-radius: 16px; padding: 25px; margin-bottom: 25px; }
        .card-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 15px; font-weight: 700; color: #ff66b2; }
        .btn-clear { background: rgba(255, 51, 102, 0.15); color: #ff4d4d; border: 1px solid #ff4d4d; padding: 6px 14px; border-radius: 8px; cursor: pointer; font-size: 11px; font-weight: 700; }
        
        /* STYLES DIAGRAM */
        .chart-container { position: relative; height: 280px; width: 100%; display: flex; justify-content: center; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { text-align: left; padding: 12px; font-size: 11px; color: #c5b0cc; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        td { padding: 12px; font-size: 13px; color: #e0e0e0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .empty { text-align: center; color: #888; padding: 30px; font-size: 13px; }
        
        /* BADGE ROLE */
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; color: #fff; display: inline-block; }
        .badge-admin { background: #9933ff; }
        .badge-petugas { background: #00cc88; }
        .badge-owner { background: #ff3399; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <h2>Log Aktivitas & Audit Sistem Parkir</h2>
            <p>Login Sebagai: <strong><?= htmlspecialchars($nama_admin); ?> (<?= ucfirst($role); ?>)</strong></p>
        </div>
        <a href="dashboard.php" class="btn-dash">◄ Kembali ke Dashboard</a>
    </div>

    <!-- AREA DIAGRAM (HANYA AKTIVITAS PER ROLE) -->
    <div class="charts-container">
        <div class="card" style="margin-bottom: 0;">
            <div class="card-title" style="margin-bottom: 15px;">📊 Aktivitas Berdasarkan Role</div>
            <div class="chart-container">
                <canvas id="logChart"></canvas>
            </div>
        </div>
    </div>

    <!-- KOTAK TABEL RIWAYAT -->
    <div class="card">
        <div class="card-top">
            <div class="card-title">📜 Riwayat Tindakan User</div>
            <form method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengosongkan seluruh log?');">
                <button type="submit" name="kosongkan_log" class="btn-clear">KOSONGKAN LOG</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>NO</th>
                    <th>WAKTU / TANGGAL</th>
                    <th>NAMA USER</th>
                    <th>ROLE</th>
                    <th>AKTIVITAS TERDAFTAR</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                if (!empty($data_rows)): 
                    $no = 1;
                    foreach ($data_rows as $row):
                        $badgeClass = 'badge-admin';
                        $r = strtolower($row['role']);
                        if ($r === 'petugas') $badgeClass = 'badge-petugas';
                        if ($r === 'owner') $badgeClass = 'badge-owner';
                ?>
                    <tr>
                        <td><?= $no++; ?></td>
                        <td><?= date('d/m/Y H:i:s', strtotime($row['waktu_log'])); ?></td>
                        <td><?= htmlspecialchars($row['nama_user']); ?></td>
                        <td><span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars($row['role']); ?></span></td>
                        <td><?= htmlspecialchars($row['aktivitas']); ?></td>
                    </tr>
                <?php 
                    endforeach; 
                else: 
                ?>
                    <tr>
                        <td colspan="5" class="empty">Tidak ada riwayat aktivitas sistem saat ini atau tabel kosong.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- SCRIPT DIAGRAM BATANG -->
    <script>
        const ctxRole = document.getElementById('logChart').getContext('2d');
        new Chart(ctxRole, {
            type: 'bar',
            data: {
                labels: ['ADMIN', 'PETUGAS', 'OWNER'],
                datasets: [{
                    label: 'Jumlah Aktivitas',
                    data: [<?= $count_admin; ?>, <?= $count_petugas; ?>, <?= $count_owner; ?>],
                    backgroundColor: [
                        'rgba(153, 51, 255, 0.75)',  // Ungu untuk Admin
                        'rgba(0, 204, 136, 0.75)',   // Hijau untuk Petugas
                        'rgba(255, 51, 153, 0.75)'   // Pink untuk Owner
                    ],
                    borderColor: ['#9933ff', '#00cc88', '#ff3399'],
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#a090a5', precision: 0 },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#ffffff', font: { weight: 'bold' } },
                        grid: { display: false }
                    }
                }
            }
        });
    </script>

</body>
</html>