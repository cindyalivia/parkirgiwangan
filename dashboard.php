<?php
include 'koneksi.php'; // Ganti 'koneksi.php' sesuai nama file koneksi milikmu (misal: config.php / db.php)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. ZONA WAKTU WIB & SESSION
date_default_timezone_set('Asia/Jakarta');
session_start();

// Pengecekan keamanan: Jika belum login, tendang balik ke halaman login
if (!isset($_SESSION['user_id']) && !isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

// --- TEMPEL KODE LOGIKA BAYAR DI SINI (BARIS 15) ---
if (isset($_POST['proses_bayar_langsung'])) {
    include 'koneksi.php'; // pastikan nama file koneksi sesuai (misal: koneksi.php / config.php)

    $id_reservasi = $_POST['id_reservasi'];
    $total_biaya  = $_POST['total_biaya'];
    $uang_bayar   = $_POST['uang_bayar'];
    $kembalian    = $uang_bayar - $total_biaya;

    // 1. Update status di database
    mysqli_query($conn, "UPDATE tb_reservasi SET status = 'Parkir', total_biaya = '$total_biaya' WHERE id_reservasi = '$id_reservasi'");

    // 2. Langsung redirect ke file cetak
    header("Location: cetak_karcis.php?id=" . $id_reservasi . "&bayar=" . $uang_bayar . "&kembali=" . $kembalian);
    exit();
}

// 3. AMBIL ROLE & USERNAME DARI SESSION
$raw_username = $_SESSION['username'] ?? 'User';
$raw_role     = $_SESSION['role'] ?? $_SESSION['level'] ?? 'admin';

$username     = strtolower(trim($raw_username));
$role_session = strtolower(trim($raw_role));

// 4. DETERMINASI ROLE AKTIF (SUPPORT: ADMIN, PETUGAS, OWNER)
if (strpos($role_session, 'petugas') !== false) {
    $role_active = 'petugas';
} elseif (strpos($role_session, 'owner') !== false) {
    $role_active = 'owner';
} else {
    $role_active = 'admin';
}

$role_display = strtoupper($role_active);

// SAPAAN NAMA DARI SESSION ATAU DATABASE
if (isset($_SESSION['nama']) && !empty($_SESSION['nama'])) {
    $nama_sapaan = $_SESSION['nama'];
} elseif ($role_active === 'petugas') {
    $nama_sapaan = 'Deswa';
} elseif ($role_active === 'owner') {
    $nama_sapaan = 'Budi Santoso';
} else {
    $nama_sapaan = 'Affan';
}

// 4.5 FLASH MESSAGE (biar tidak Undefined Variable)
$message      = $_SESSION['message'] ?? '';
$message_type = $_SESSION['message_type'] ?? 'success';
unset($_SESSION['message'], $_SESSION['message_type']);

// 5. PROSES AKSI PETUGAS (SETUJUI / CHECK-OUT / TOLAK)
if ($role_active === 'petugas' && isset($_GET['aksi']) && isset($_GET['id'])) {
    $id_reservasi = (int)$_GET['id'];
    $aksi = mysqli_real_escape_string($conn, $_GET['aksi']);

    $res_query = mysqli_query($conn, "SELECT * FROM tb_reservasi WHERE id_reservasi = $id_reservasi");
    $data_res  = mysqli_fetch_assoc($res_query);

    if ($data_res) {
        $jenis = mysqli_real_escape_string($conn, $data_res['jenis_kendaraan']);
        $status_curr = strtolower(trim($data_res['status']));

        if ($aksi === 'setujui' && $status_curr === 'menunggu') {
            mysqli_query($conn, "UPDATE tb_reservasi SET status = 'Disetujui' WHERE id_reservasi = $id_reservasi");
        } 
        elseif ($aksi === 'checkout' && $status_curr === 'parkir') {
            mysqli_query($conn, "UPDATE tb_reservasi SET status = 'Selesai' WHERE id_reservasi = $id_reservasi");
            mysqli_query($conn, "UPDATE tb_slot_parkir SET terisi = GREATEST(0, terisi - 1) WHERE jenis_kendaraan = '$jenis'");
        }
        elseif ($aksi === 'tolak') {
            if ($status_curr === 'parkir') {
                mysqli_query($conn, "UPDATE tb_slot_parkir SET terisi = GREATEST(0, terisi - 1) WHERE jenis_kendaraan = '$jenis'");
            }
            mysqli_query($conn, "UPDATE tb_reservasi SET status = 'Ditolak' WHERE id_reservasi = $id_reservasi");
        }
    }
    header("Location: dashboard.php");
    exit();
}

// 6. AMBIL DATA METRIK DARI DATABASE
$count_admin = 0; $count_petugas = 0; $count_owner = 0;
$query_chart = mysqli_query($conn, "SELECT LOWER(role) as role_clean, COUNT(*) as total FROM tb_log_aktivitas LEFT JOIN tb_user ON tb_log_aktivitas.id_user = tb_user.id_user GROUP BY role");
if ($query_chart) {
    while ($r = mysqli_fetch_assoc($query_chart)) {
        $role_name = strtolower($r['role_clean'] ?? '');
        if (strpos($role_name, 'admin') !== false) $count_admin = (int)$r['total'];
        elseif (strpos($role_name, 'petugas') !== false) $count_petugas = (int)$r['total'];
        elseif (strpos($role_name, 'owner') !== false) $count_owner = (int)$r['total'];
    }
}

// Total & Persentase Aktivitas
$total_aktivitas = $count_admin + $count_petugas + $count_owner;
$pct_admin   = ($total_aktivitas > 0) ? round(($count_admin / $total_aktivitas) * 100) : 0;
$pct_petugas = ($total_aktivitas > 0) ? round(($count_petugas / $total_aktivitas) * 100) : 0;
$pct_owner   = ($total_aktivitas > 0) ? round(($count_owner / $total_aktivitas) * 100) : 0;

// Data khusus Petugas
$slots = [];
$reservasi = null;
if ($role_active === 'petugas') {
    $q_slots = mysqli_query($conn, "SELECT * FROM tb_slot_parkir");
    if ($q_slots) {
        while ($s = mysqli_fetch_assoc($q_slots)) {
            $slots[] = $s;
        }
    }
    $reservasi = mysqli_query($conn, "SELECT * FROM tb_reservasi ORDER BY id_reservasi DESC");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistem Parkir</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: radial-gradient(circle at top left, #2a0826 0%, #0d020d 60%, #050005 100%); color: #ffffff; display: flex; min-height: 100vh; }

        .sidebar { width: 260px; min-width: 260px; background: rgba(20, 8, 25, 0.85); border-right: 1px solid rgba(255, 51, 153, 0.15); display: flex; flex-direction: column; justify-content: space-between; padding: 30px 20px; backdrop-filter: blur(10px); }
        .sidebar-brand h3 { font-size: 20px; font-weight: 800; color: #ff3399; margin-bottom: 40px; text-align: center; }
        .menu-heading { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: #a090a5; margin-bottom: 15px; padding-left: 10px; }
        .sidebar-menu { list-style: none; flex-grow: 1; }
        .sidebar-menu li { margin-bottom: 8px; }
        .sidebar-menu a { display: block; padding: 12px 15px; color: #e0d0e5; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 600; transition: all 0.2s ease; }
        .sidebar-menu a:hover { background: rgba(255, 51, 153, 0.15); color: #ff66b2; padding-left: 20px; }
        .btn-logout { display: block; padding: 12px 15px; color: #ff4d4d; text-decoration: none; border-radius: 8px; font-size: 14px; font-weight: 700; background: rgba(255, 51, 102, 0.1); text-align: center; border: 1px solid rgba(255, 51, 102, 0.3); }

        .main-content { flex-grow: 1; padding: 40px; display: flex; flex-direction: column; justify-content: space-between; min-width: 0; }

        .header-welcome { background: rgba(25, 10, 30, 0.6); padding: 25px 30px; border-radius: 16px; border: 1px solid rgba(255, 51, 153, 0.2); margin-bottom: 30px; }
        .header-welcome .terminal-title { font-size: 13px; font-weight: 700; color: #ff66b2; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 6px; display: block; }
        .header-welcome h1 { font-size: 26px; font-weight: 700; margin-bottom: 5px; }
        .badge-role { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 800; display: inline-block; text-transform: uppercase; }
        .badge-admin { background: #9933ff; color: white; }
        .badge-petugas { background: #ffaa00; color: #111; }
        .badge-owner { background: #ff3399; color: white; }

        .dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .card { background: rgba(20, 10, 25, 0.6); border: 1px solid rgba(255, 51, 153, 0.15); padding: 22px; border-radius: 14px; }
        .card h4 { font-size: 13px; color: #a090a5; margin-bottom: 8px; font-weight: 600; text-transform: uppercase; }
        .card p.value { font-size: 24px; font-weight: 800; }

        .role-section { background: rgba(20, 10, 25, 0.6); border: 1px solid rgba(255, 51, 153, 0.2); border-radius: 16px; padding: 30px; margin-bottom: 25px; }
        .role-section h3 { font-size: 18px; font-weight: 700; margin-bottom: 15px; }

        .progress-item { margin-bottom: 18px; }
        .progress-info { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; margin-bottom: 6px; }
        .progress-bar-bg { width: 100%; height: 10px; background: rgba(255, 255, 255, 0.08); border-radius: 10px; overflow: hidden; }
        .progress-bar-fill { height: 100%; border-radius: 10px; }
        .fill-admin { background: linear-gradient(90deg, #9933ff, #c266ff); }
        .fill-petugas { background: linear-gradient(90deg, #ffaa00, #ffd166); }
        .fill-owner { background: linear-gradient(90deg, #ff3399, #ff66b2); }

        .main-footer { text-align: center; padding-top: 25px; margin-top: auto; border-top: 1px solid rgba(255, 51, 153, 0.15); }
        .main-footer p { font-size: 12px; color: #a090a5; font-weight: 500; }
    </style>
</head>
<body>

    <!-- SIDEBAR NAVIGASI -->
    <div class="sidebar">
        <div>
            <div class="sidebar-brand">
                <h3>PARKIR SYSTEM</h3>
            </div>
            
            <p class="menu-heading">NAVIGASI <?= $role_display; ?></p>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" style="background: rgba(255, 51, 153, 0.2); color: #ff66b2;">🏠 Dashboard Utama</a></li>
                
                <?php if($role_active === 'petugas'): ?>
                    <li><a href="parkir_masuk.php">🚗 Transaksi Parkir Masuk</a></li>
                    <li><a href="parkir_keluar.php">🏍️ Transaksi Parkir Keluar</a></li>

                <?php elseif($role_active === 'owner'): ?>
                    <li><a href="laporan_pendapatan.php">📈 Laporan Pendapatan</a></li>
                    <li><a href="laporan_kepadatan.php">📊 Analisis Kepadatan</a></li>

                <?php else: ?>
                    <li><a href="kelola_user.php">👤 Kelola Data User</a></li>
                    <li><a href="kelola_tarif.php">💰 Kelola Tarif Kendaraan</a></li>
                    <li><a href="kelola_area.php">📍 Kelola Area Parkir</a></li>
                    <li><a href="log_aktivitas.php">📋 Log Aktivitas Sistem</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <a href="logout.php" class="btn-logout">Logout / Keluar</a>
    </div>

    <!-- MAIN KONTEN -->
    <div class="main-content">
        <div>
            <!-- ALERT FLASH MESSAGE -->
            <?php if (!empty($message)): ?>
                <div id="alert-box" class="mb-6 p-4 rounded-xl text-xs font-semibold flex items-center justify-between border transition-all duration-300 <?php echo $message_type === 'success' ? 'bg-[#00e676]/10 border-[#00e676]/40 text-[#00e676] shadow-[0_0_15px_rgba(0,230,118,0.15)]' : 'bg-[#ff2a85]/10 border-[#ff2a85]/40 text-[#ff2a85] shadow-[0_0_15px_rgba(255,42,133,0.15)]'; ?>">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check text-base' : 'fa-triangle-exclamation text-base'; ?>"></i>
                        <span><?php echo $message; ?></span>
                    </div>
                    <button type="button" onclick="document.getElementById('alert-box').remove()" class="hover:opacity-70 ml-2">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- HEADER WELCOME -->
            <div class="header-welcome">
                <span class="terminal-title">Terminal Giwangan Parking Center</span>
                <h1>Selamat Datang, <?= htmlspecialchars($nama_sapaan); ?>!</h1>
                <p>Akses Aktif: 
                    <span class="badge-role <?= ($role_active === 'petugas') ? 'badge-petugas' : (($role_active === 'owner') ? 'badge-owner' : 'badge-admin'); ?>">
                        <?= $role_display; ?>
                    </span>
                </p>
            </div>

            <!-- TOP METRICS -->
            <div class="dashboard-cards">
                <div class="card">
                    <h4>Status Server</h4>
                    <p class="value" style="color: #00cc88;">Online</p>
                </div>
                <div class="card" style="border-color: #ff3399;">
                    <h4>Waktu Real-Time</h4>
                    <p class="value" style="font-size: 20px; color: #ff66b2;">
                        <span id="live-clock"><?= date('H:i:s'); ?></span> WIB
                    </p>
                </div>
                <div class="card">
                    <h4>Total Log Masuk</h4>
                    <p class="value" style="color: #ffaa00;"><?= $total_aktivitas; ?> <span style="font-size:12px; color:#a090a5;">Aktivitas</span></p>
                </div>
            </div>

            <!-- TAMPILAN KHUSUS ROLE PETUGAS -->
            <?php if ($role_active === 'petugas'): ?>
                <!-- INFORMASI SLOT PARKIR -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <?php if (!empty($slots)): ?>
                        <?php foreach ($slots as $s): 
                            $sisa = $s['kapasitas_total'] - $s['terisi'];
                        ?>
                            <div class="bg-[#1c0b36]/80 border border-[#2e1254] p-5 rounded-2xl flex justify-between items-center">
                                <div>
                                    <p class="text-xs text-[#a093b5] font-bold uppercase">Slot <?php echo $s['jenis_kendaraan']; ?></p>
                                    <h3 class="text-xl font-extrabold text-white mt-1"><?php echo $s['terisi']; ?> / <?php echo $s['kapasitas_total']; ?> Terisi</h3>
                                    <p class="text-xs font-bold <?php echo $sisa > 0 ? 'text-[#00e676]' : 'text-[#ff2a85]'; ?> mt-1">
                                        Sisa: <?php echo $sisa; ?> Slot
                                    </p>
                                </div>
                                <div class="w-12 h-12 bg-[#ff2a85]/15 border border-[#ff2a85]/40 text-[#ff2a85] rounded-xl flex items-center justify-center text-xl">
                                    <i class="fa-solid <?php echo $s['jenis_kendaraan'] === 'Motor' ? 'fa-motorcycle' : ($s['jenis_kendaraan'] === 'Mobil' ? 'fa-car' : 'fa-bus'); ?>"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- TABEL DATA RESERVASI -->
                <div class="role-section">
                    <h3 class="flex items-center gap-2">
                        <i class="fa-solid fa-list-check text-[#ff3399]"></i> Daftar Ajuan Reservasi Parkir
                    </h3>

                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-[#ff3399]/20 text-[#a090a5] text-xs uppercase">
                                    <th class="p-3">Waktu</th>
                                    <th class="p-3">Nama Pemohon</th>
                                    <th class="p-3">Plat Nomor</th>
                                    <th class="p-3">Jenis</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-center">Aksi Petugas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ff3399]/10 text-sm">
                                <?php if ($reservasi && mysqli_num_rows($reservasi) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($reservasi)): 
                                        $st = strtolower(trim($row['status']));
                                        $tgl = $row['tgl_reservasi'] ?? $row['waktu'] ?? date('Y-m-d H:i:s');
                                        $nama = htmlspecialchars($row['nama_pemohon'] ?? $row['nama'] ?? 'Pemohon');
                                        $plat = htmlspecialchars($row['plat_nomor']);
                                        $jenis_k = $row['jenis_kendaraan'] ?? $row['jenis'] ?? 'Motor';
                                        
                                        // Default biaya berdasarkan jenis kendaraan
                                        $biaya_default = (strtolower($jenis_k) === 'mobil') ? 10000 : ((strtolower($jenis_k) === 'bus') ? 20000 : 5000);
                                    ?>
                                        <tr>
                                            <td class="p-3 text-xs text-[#a090a5]"><?php echo date('d/m/Y H:i', strtotime($tgl)); ?></td>
                                            <td class="p-3 font-semibold"><?php echo $nama; ?></td>
                                            <td class="p-3 font-mono font-bold text-[#ff3399]"><?php echo $plat; ?></td>
                                            <td class="p-3"><?php echo $jenis_k; ?></td>
                                            
                                            <!-- STATUS RESERVASI -->
                                            <td class="p-3">
                                                <?php if ($st === 'disetujui'): ?>
                                                    <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-xs font-bold border border-blue-500/40">Menunggu Kedatangan</span>
                                                <?php elseif ($st === 'parkir'): ?>
                                                    <span class="bg-[#00e676]/20 text-[#00e676] px-3 py-1 rounded-full text-xs font-bold border border-[#00e676]/40">Sedang Parkir</span>
                                                <?php elseif ($st === 'selesai'): ?>
                                                    <span class="bg-purple-500/20 text-purple-400 px-3 py-1 rounded-full text-xs font-bold border border-purple-500/40">Selesai</span>
                                                <?php elseif ($st === 'ditolak'): ?>
                                                    <span class="bg-[#ff2a85]/20 text-[#ff2a85] px-3 py-1 rounded-full text-xs font-bold border border-[#ff2a85]/40">Ditolak</span>
                                                <?php else: ?>
                                                    <span class="bg-yellow-500/20 text-yellow-400 px-3 py-1 rounded-full text-xs font-bold border border-yellow-500/40">Menunggu</span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- AKSI PETUGAS -->
                                            <td class="p-3 text-center">
                                                <?php if ($st === 'menunggu'): ?>
                                                    <a href="dashboard.php?aksi=setujui&id=<?php echo $row['id_reservasi']; ?>" class="bg-[#00e676] text-black font-bold px-3 py-1.5 rounded-lg text-xs hover:opacity-80 transition mr-1 inline-block">
                                                        <i class="fa-solid fa-check"></i> Setujui
                                                    </a>
                                                    <a href="dashboard.php?aksi=tolak&id=<?php echo $row['id_reservasi']; ?>" class="bg-[#ff2a85] text-white font-bold px-3 py-1.5 rounded-lg text-xs hover:opacity-80 transition inline-block">
                                                        <i class="fa-solid fa-xmark"></i> Tolak
                                                    </a>

                                                <?php elseif ($st === 'disetujui'): ?>
                                                <div class="flex items-center justify-center gap-2 relative z-10">
                                                <!-- Tombol Check-In & Bayar -->
                                                <button type="button" 
                                                  class="btn-checkin bg-cyan-500/20 hover:bg-cyan-500/30 text-cyan-400 border border-cyan-500/40 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-1 cursor-pointer"
                                                  data-id="<?= $row['id_reservasi']; ?>"
                                                  data-nama="<?= htmlspecialchars($row['nama_pemohon'], ENT_QUOTES); ?>"
                                                  data-plat="<?= htmlspecialchars($row['plat_nomor'], ENT_QUOTES); ?>"
                                                   data-jenis="<?= htmlspecialchars($row['jenis_kendaraan'] ?? 'Motor', ENT_QUOTES); ?>"
                                                  data-durasi="<?= $row['durasi_jam'] ?? 1; ?>"
                                                  data-biaya="<?= $row['total_biaya'] ?? 0; ?>">
                                                  <i class="fa-solid fa-right-to-bracket"></i> Check-In & Bayar
                                                </button>                      

                                                 <!-- Tombol Batal -->
                                                 <a href="dashboard.php?aksi=tolak&id=<?php echo $row['id_reservasi']; ?>" 
                                                 onclick="return confirm('Yakin ingin membatalkan reservasi ini?')"
                                                 class="bg-[#ff2a85] text-white font-bold px-3 py-1.5 rounded-lg text-xs hover:opacity-80 transition flex items-center gap-1 cursor-pointer">
                                                <i class="fa-solid fa-xmark"></i> Batal
                                                </a>
                                                </div>
           

                                                <?php elseif ($st === 'parkir'): ?>
                                                    <a href="dashboard.php?aksi=checkout&id=<?php echo $row['id_reservasi']; ?>" class="bg-amber-500 text-black font-bold px-3 py-1.5 rounded-lg text-xs hover:opacity-80 transition mr-1 inline-block">
                                                        <i class="fa-solid fa-right-from-bracket"></i> Check-Out
                                                    </a>
                                                    <button type="button" onclick="cetakStruk(<?php echo $row['id_reservasi']; ?>)" class="bg-purple-600 hover:bg-purple-500 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition inline-block">
                                                        <i class="fa-solid fa-print"></i> Struk
                                                    </button>

                                                <?php elseif ($st === 'selesai'): ?>
                                                    <button type="button" onclick="cetakStruk(<?php echo $row['id_reservasi']; ?>)" class="bg-gray-700 hover:bg-gray-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition inline-block">
                                                        <i class="fa-solid fa-print"></i> Cetak Struk
                                                    </button>

                                                <?php else: ?>
                                                    <span class="text-xs text-[#a090a5] italic">-</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="p-6 text-center text-[#a090a5]">Belum ada ajuan reservasi masuk.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- BAGIAN ANALISIS SISTEM (TETAP TAMPIL UNTUK ADMIN DAN OWNER) -->
            <div class="role-section">
                <h3>⚡ Distribusi Aktivitas Pengguna (Beban Sistem)</h3>

                <div class="progress-item">
                    <div class="progress-info"><span style="color: #c266ff;">👑 Administrator</span><span><?= $count_admin; ?> Log (<?= $pct_admin; ?>%)</span></div>
                    <div class="progress-bar-bg"><div class="progress-bar-fill fill-admin" style="width: <?= $pct_admin; ?>%;"></div></div>
                </div>

                <div class="progress-item">
                    <div class="progress-info"><span style="color: #ffd166;">👮 Petugas Lapangan</span><span><?= $count_petugas; ?> Log (<?= $pct_petugas; ?>%)</span></div>
                    <div class="progress-bar-bg"><div class="progress-bar-fill fill-petugas" style="width: <?= $pct_petugas; ?>%;"></div></div>
                </div>

                <div class="progress-item" style="margin-bottom: 0;">
                    <div class="progress-info"><span style="color: #ff66b2;">💼 Owner / Eksekutif</span><span><?= $count_owner; ?> Log (<?= $pct_owner; ?>%)</span></div>
                    <div class="progress-bar-bg"><div class="progress-bar-fill fill-owner" style="width: <?= $pct_owner; ?>%;"></div></div>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <footer class="main-footer">
            <p>&copy; <?= date('Y'); ?> E–Parkir Terminal Giwangan. BY CINDY ALIVIA NINGRUM SMKN 1 SANDEN. All rights reserved.</p>
        </footer>
    </div>

    <!-- MODAL PEMBAYARAN RESERVASI PARKIR -->
<div id="modalBayar" class="fixed inset-0 bg-black/70 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-[#180a22] border border-[#ff3399]/30 rounded-2xl p-6 w-full max-w-md shadow-[0_0_30px_rgba(255,51,153,0.3)] relative">
        
        <!-- Header Modal -->
        <div class="flex justify-between items-center mb-5 pb-3 border-b border-[#ff3399]/20">
            <h3 class="font-bold text-base text-white flex items-center gap-2">
                <i class="fa-solid fa-receipt text-[#ff3399]"></i> Pembayaran Reservasi Parkir
            </h3>
            <button type="button" id="btn-close-modal" class="text-[#a090a5] hover:text-white transition cursor-pointer">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Form Pembayaran -->
        <form id="form-bayar-reservasi" action="cetak_karcis.php" method="POST" target="_blank">
            <input type="hidden" name="id_reservasi" id="modal-id-reservasi">
            <input type="hidden" name="total_biaya" id="modal-biaya">
            <input type="hidden" name="uang_bayar" id="modal-uang-bayar-hidden">
            
            <div class="space-y-4 text-xs">
                <!-- Nama Pemohon -->
                <div class="flex justify-between items-center bg-[#100517] p-3 rounded-xl border border-[#ff3399]/10">
                    <span class="text-[#a090a5]">Nama Pemohon:</span>
                    <span id="modal-nama" class="font-bold text-white">-</span>
                </div>

                <!-- Plat Nomor -->
                <div class="flex justify-between items-center bg-[#100517] p-3 rounded-xl border border-[#ff3399]/10">
                    <span class="text-[#a090a5]">Plat Nomor:</span>
                    <span id="modal-plat" class="font-bold text-[#ff3399] font-mono">-</span>
                </div>

                <!-- Total Biaya -->
                <div class="flex justify-between items-center bg-[#100517] p-3 rounded-xl border border-[#ff3399]/10">
                    <span class="text-[#a090a5]">Total Biaya:</span>
                    <span id="modal-biaya-text" class="font-extrabold text-[#00e676] text-sm">Rp 0</span>
                </div>

                <!-- Input Uang Bayar -->
                <div>
                    <label class="block text-[#a090a5] font-bold uppercase mb-1 tracking-wider text-[10px]">Nominal Uang Bayar (Rp)</label>
                    <input type="number" id="modal-bayar" required placeholder="Contoh: 10000"
                           class="w-full bg-[#100517] border border-[#ff3399]/40 focus:border-[#ff3399] text-white text-sm font-bold rounded-xl px-4 py-3 outline-none transition">
                </div>

                <!-- Output Kembalian -->
                <div class="flex justify-between items-center bg-[#100517] p-3 rounded-xl border border-[#ff3399]/10">
                    <span class="text-[#a090a5]">Kembalian:</span>
                    <span id="modal-kembalian" class="font-extrabold text-white text-sm">Rp 0</span>
                </div>
            </div>

            <!-- Tombol Submit -->
            <button type="submit" id="btn-proses-bayar" class="w-full bg-gradient-to-r from-[#ff3399] to-[#9d4edd] hover:opacity-90 text-white font-bold text-xs py-3.5 rounded-xl transition mt-6 flex items-center justify-center gap-2 shadow-[0_0_15px_rgba(255,51,153,0.4)] cursor-pointer">
                <i class="fa-solid fa-print"></i> BAYAR & CETAK STRUK
            </button>
        </form>

    </div>
</div>

<!-- Pop-Up Notifikasi Berhasil (dipakai untuk Bayar & Cetak Struk) -->
<div id="modalNotifSukses" class="fixed inset-0 bg-black/75 backdrop-blur-sm hidden justify-center items-center z-50">
    <div class="bg-[#38424d] rounded-2xl p-6 text-center text-white w-11/12 max-w-sm shadow-2xl">
        <!-- Icon Centang -->
        <div class="w-16 h-16 border-2 border-white rounded-full flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 stroke-white" fill="none" stroke-width="3" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        
        <h3 id="notif-title" class="text-xl font-bold mb-2">Pembayaran Berhasil!</h3>
        <p id="notif-message" class="text-xs text-gray-300 mb-6 leading-relaxed">
            Selamat, pembayaran Anda berhasil dan struk sedang dicetak.
        </p>
        
        <button type="button" onclick="selesaiDanReload()" class="bg-[#ff3399] hover:bg-[#e02e85] text-white px-8 py-2 rounded-lg font-bold text-sm transition-all cursor-pointer">
            Selesai
        </button>
    </div>
</div>

    <!-- SCRIPT JAM WIB REALTIME -->
    <script>
        function updateClock() {
            const now = new Date();
            const options = { timeZone: 'Asia/Jakarta', hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            const timeString = new Intl.DateTimeFormat('id-ID', options).format(now);
            document.getElementById('live-clock').textContent = timeString.replace(/\./g, ':');
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <!-- SCRIPT MODAL PEMBAYARAN & CETAK STRUK (SUDAH DIPERBAIKI) -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let totalBiayaGlobal = 0;
            let reloadAfterNotif = true;

            const modalBayar   = document.getElementById("modalBayar");
            const inputBayar   = document.getElementById("modal-bayar");
            const elKembalian  = document.getElementById("modal-kembalian");
            const btnClose     = document.getElementById("btn-close-modal");
            const formBayar    = document.getElementById("form-bayar-reservasi");
            const modalNotif   = document.getElementById("modalNotifSukses");
            const notifTitle   = document.getElementById("notif-title");
            const notifMessage = document.getElementById("notif-message");

            // 1. BUKA MODAL SAAT TOMBOL "CHECK-IN & BAYAR" DIKLIK
            document.addEventListener("click", function (e) {
                const button = e.target.closest(".btn-checkin");
                if (!button) return;

                e.preventDefault();

                const id     = button.getAttribute("data-id");
                const nama   = button.getAttribute("data-nama");
                const plat   = button.getAttribute("data-plat");
                const jenis  = (button.getAttribute("data-jenis") || "").toLowerCase().trim();
                const durasi = parseInt(button.getAttribute("data-durasi")) || 1;
                const biayaExisting = parseInt(button.getAttribute("data-biaya")) || 0;

                // Tarif per jam berdasarkan jenis kendaraan
                let tarifPerJam = 3000;
                if (jenis === "mobil") tarifPerJam = 7000;
                else if (jenis === "bus" || jenis === "truk") tarifPerJam = 15000;

                const totalBiaya = biayaExisting > 0 ? biayaExisting : (tarifPerJam * durasi);
                totalBiayaGlobal = totalBiaya;

                document.getElementById("modal-id-reservasi").value = id;
                document.getElementById("modal-nama").innerText = nama;
                document.getElementById("modal-plat").innerText = plat;
                document.getElementById("modal-biaya").value = totalBiaya;
                document.getElementById("modal-biaya-text").innerText = "Rp " + totalBiaya.toLocaleString("id-ID");

                inputBayar.value = "";
                elKembalian.innerText = "Rp 0";
                elKembalian.className = "font-extrabold text-white text-sm";

                modalBayar.classList.remove("hidden");
                modalBayar.classList.add("flex");
            });

            // 2. HITUNG KEMBALIAN OTOMATIS
            if (inputBayar) {
                inputBayar.addEventListener("input", function () {
                    const bayar = parseFloat(this.value) || 0;
                    const kembalian = bayar - totalBiayaGlobal;
                    document.getElementById("modal-uang-bayar-hidden").value = bayar;

                    if (kembalian >= 0) {
                        elKembalian.innerText = "Rp " + kembalian.toLocaleString("id-ID");
                        elKembalian.className = "font-extrabold text-[#00e676] text-sm";
                    } else {
                        elKembalian.innerText = "Kurang Rp " + Math.abs(kembalian).toLocaleString("id-ID");
                        elKembalian.className = "font-extrabold text-red-400 text-sm";
                    }
                });
            }

            // 3. TUTUP MODAL BAYAR
            if (btnClose) {
                btnClose.addEventListener("click", function () {
                    modalBayar.classList.add("hidden");
                    modalBayar.classList.remove("flex");
                });
            }

            // 4. SAAT FORM BAYAR DI-SUBMIT: validasi, biarkan submit ke tab baru, lalu tampilkan notif sukses
            if (formBayar) {
                formBayar.addEventListener("submit", function (e) {
                    const bayar = parseFloat(inputBayar.value) || 0;
                    if (bayar < totalBiayaGlobal) {
                        e.preventDefault();
                        alert("Uang bayar tidak cukup!");
                        return;
                    }
                    document.getElementById("modal-uang-bayar-hidden").value = bayar;

                    // biarkan form tetap submit (target="_blank" -> buka tab struk baru)
                    modalBayar.classList.add("hidden");
                    modalBayar.classList.remove("flex");

                    reloadAfterNotif = true;
                    notifTitle.innerText = "Pembayaran Berhasil!";
                    notifMessage.innerText = "Selamat, pembayaran Anda berhasil dan struk sedang dicetak.";
                    modalNotif.classList.remove("hidden");
                    modalNotif.classList.add("flex");
                });
            }

            // 5. CETAK STRUK (untuk status Parkir / Selesai) + tampilkan notif
            window.cetakStruk = function (idReservasi) {
                if (!idReservasi) {
                    alert("ID Reservasi tidak ditemukan untuk dicetak!");
                    return;
                }
                window.open('cetak_karcis.php?id=' + idReservasi, '_blank', 'width=400,height=600');

                reloadAfterNotif = false;
                notifTitle.innerText = "Struk Sedang Dicetak!";
                notifMessage.innerText = "Struk parkir sedang diproses dan dibuka pada tab baru.";
                modalNotif.classList.remove("hidden");
                modalNotif.classList.add("flex");
            };

            // 6. TOMBOL "SELESAI" PADA NOTIFIKASI
            window.selesaiDanReload = function () {
                modalNotif.classList.add("hidden");
                modalNotif.classList.remove("flex");
                if (reloadAfterNotif) {
                    location.reload();
                }
            };
        });
    </script>

    <!-- SCRIPT NOTIFIKASI SUARA SAAT LOGIN -->
    <?php if (isset($_SESSION['login_success']) && $_SESSION['login_success'] === true): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const role = "<?= $role_active; ?>";
                const nama = "<?= htmlspecialchars($nama_sapaan, ENT_QUOTES, 'UTF-8'); ?>";

                let teksPesan = "";

                if (role === "admin") {
                    teksPesan = "Selamat datang Administrator " + nama + ". Akses kontrol sistem parkir aktif.";
                } else if (role === "petugas") {
                    teksPesan = "Selamat bertugas " + nama + ". Silakan kelola pintu masuk dan keluar parkir.";
                } else if (role === "owner") {
                    teksPesan = "Selamat datang Owner " + nama + ". Laporan keuangan dan aktivitas siap ditinjau.";
                } else {
                    teksPesan = "Berhasil masuk ke dalam sistem.";
                }

                speakText(teksPesan);
                playSimpleBeep();
            });

            function speakText(text) {
                if ('speechSynthesis' in window) {
                    const speech = new SpeechSynthesisUtterance(text);
                    speech.lang = 'id-ID';
                    speech.rate = 1.0;
                    speech.pitch = 1.0;
                    window.speechSynthesis.speak(speech);
                }
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
        </script>
        <?php unset($_SESSION['login_success']); ?>
    <?php endif; ?>

</body>
</html>