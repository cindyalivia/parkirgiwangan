<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

// Pengecekan Keamanan: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

// FUNGSI HITUNG BIAYA RESERVASI
function hitungBiayaReservasi($jenisKendaraan, $durasiJam) {
    $durasiJam = max(1, ceil($durasiJam)); 
    $tarifPerJam = 0;
    
    switch (strtolower(trim($jenisKendaraan))) {
        case 'motor':
            $tarifPerJam = 3000;
            break;
        case 'mobil':
            $tarifPerJam = 7000;
            break;
        case 'bus':
        case 'truk':
        case 'bus/truk':
            $tarifPerJam = 15000;
            break;
        default:
            $tarifPerJam = 3000;
            break;
    }

    return $tarifPerJam * $durasiJam;
}

$id_user  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Pengguna';
$nama     = $_SESSION['nama'] ?? $_SESSION['nama_lengkap'] ?? $username;

$alert_msg  = "";
$alert_type = "";

// 1. PROSES PENGAJUAN RESERVASI PARKING BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservasi'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $tgl_kedatangan  = mysqli_real_escape_string($conn, $_POST['tgl_kedatangan']);
    $jam_kedatangan  = mysqli_real_escape_string($conn, $_POST['jam_kedatangan']);
    $durasi_jam      = isset($_POST['durasi_jam']) ? (int)$_POST['durasi_jam'] : 1;
    
    $total_biaya     = hitungBiayaReservasi($jenis_kendaraan, $durasi_jam);
    $tgl_reservasi   = $tgl_kedatangan . ' ' . $jam_kedatangan . ':00';
    $status_awal     = 'Menunggu';

    if (!empty($plat_nomor) && !empty($jenis_kendaraan) && !empty($tgl_kedatangan) && !empty($jam_kedatangan)) {
        $query_insert = "INSERT INTO tb_reservasi (id_user, nama_pemohon, plat_nomor, jenis_kendaraan, status, tgl_reservasi, durasi_jam, total_biaya) 
                        VALUES ('$id_user', '$nama', '$plat_nomor', '$jenis_kendaraan', '$status_awal', '$tgl_reservasi', '$durasi_jam', '$total_biaya')";
        
        if (!mysqli_query($conn, $query_insert)) {
            $query_insert = "INSERT INTO tb_reservasi (id_user, nama_pemohon, plat_nomor, jenis_kendaraan, status, tgl_reservasi) 
                            VALUES ('$id_user', '$nama', '$plat_nomor', '$jenis_kendaraan', '$status_awal', '$tgl_reservasi')";
            mysqli_query($conn, $query_insert);
        }

        header("Location: reservasi.php?status=success");
        exit();
    } else {
        $alert_msg  = "Harap isi semua bidang formulir dengan benar!";
        $alert_type = "error";
    }
}

if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $alert_msg  = "Pengajuan reservasi berhasil dikirim! Silakan tunggu konfirmasi.";
    $alert_type = "success";
}

// 2. AMBIL DATA RIWAYAT
$q_reservasi = mysqli_query($conn, "SELECT * FROM tb_reservasi 
                                    WHERE id_user = '$id_user' 
                                       OR LOWER(TRIM(nama_pemohon)) = LOWER(TRIM('$nama')) 
                                       OR LOWER(TRIM(nama_pemohon)) = LOWER(TRIM('$username')) 
                                    ORDER BY id_reservasi DESC");

// 3. HITUNG RINGKASAN DATA
$q_count_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi WHERE id_user = '$id_user' OR nama_pemohon = '$nama'");
$res_total     = mysqli_fetch_assoc($q_count_total);
$total_res     = $res_total['total'] ?? 0;

$q_count_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi WHERE (id_user = '$id_user' OR nama_pemohon = '$nama') AND (LOWER(status) IN ('menunggu', 'disetujui', 'parkir') OR status = '' OR status IS NULL)");
$res_aktif     = mysqli_fetch_assoc($q_count_aktif);
$total_aktif   = $res_aktif['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservasi Parkir - Terminal Giwangan Parking Center</title>
    
    <!-- Tailwind CSS & FontAwesome -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { 
            background: radial-gradient(circle at top left, #2a0826 0%, #0d020d 60%, #050005 100%); 
            color: #ffffff; 
            min-height: 100vh;
        }

        .neon-card {
            background: rgba(20, 8, 25, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 51, 153, 0.2);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- NAVBAR -->
    <nav class="bg-[#140819]/80 border-b border-[#ff3399]/20 backdrop-blur-md sticky top-0 z-40 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#ff3399]/15 border border-[#ff3399]/50 rounded-xl flex items-center justify-center text-[#ff3399] shadow-[0_0_10px_rgba(255,51,153,0.3)]">
                    <i class="fa-solid fa-bus text-lg"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-lg text-white tracking-wider leading-none">PARKIR SYSTEM</h1>
                    <span class="text-[10px] text-[#ff3399] font-bold uppercase tracking-widest"><i class="fa-solid fa-location-dot"></i> Terminal Giwangan</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-sm font-bold text-white"><?php echo htmlspecialchars($nama); ?></span>
                    <span class="text-xs text-[#a090a5]">Pengendara / User</span>
                </div>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" 
                   class="bg-[#ff3366]/10 hover:bg-[#ff3366]/20 border border-[#ff3366]/40 text-[#ff4d4d] px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- CONTENT WRAPPER -->
    <main class="max-w-7xl mx-auto w-full p-4 md:p-8 flex-grow space-y-8">

        <!-- WELCOME BANNER -->
        <div class="neon-card rounded-2xl p-6 md:p-8 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="text-xs font-extrabold text-[#ff66b2] uppercase tracking-widest block mb-1">Area Reservasi Kendaraan</span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-white">Layanan Reservasi, <?php echo htmlspecialchars($nama); ?>! 👋</h2>
                <p class="text-xs md:text-sm text-[#a090a5] mt-1">Ajukan reservasi slot parkir Anda secara mandiri di Terminal Giwangan Parking Center.</p>
            </div>
            <div class="flex gap-4 w-full md:w-auto">
                <div class="bg-[#130826] border border-[#ff3399]/20 px-5 py-3 rounded-xl flex-1 md:flex-none text-center">
                    <span class="text-[11px] text-[#a090a5] font-semibold block">Total Reservasi</span>
                    <span class="text-xl font-extrabold text-[#ff3399]"><?php echo $total_res; ?></span>
                </div>
                <div class="bg-[#130826] border border-[#ff3399]/20 px-5 py-3 rounded-xl flex-1 md:flex-none text-center">
                    <span class="text-[11px] text-[#a090a5] font-semibold block">Reservasi Aktif</span>
                    <span class="text-xl font-extrabold text-[#00e676]"><?php echo $total_aktif; ?></span>
                </div>
            </div>
        </div>

        <!-- NOTIFIKASI ALERT -->
        <?php if (!empty($alert_msg)): ?>
            <div class="p-4 rounded-xl text-xs font-bold flex items-center justify-between border <?php echo $alert_type === 'success' ? 'bg-[#00e676]/10 border-[#00e676]/40 text-[#00e676] shadow-[0_0_15px_rgba(0,230,118,0.2)]' : 'bg-[#ff2a85]/10 border-[#ff2a85]/40 text-[#ff2a85] shadow-[0_0_15px_rgba(255,42,133,0.2)]'; ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check text-base' : 'fa-triangle-exclamation text-base'; ?>"></i>
                    <span><?php echo $alert_msg; ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="hover:opacity-70"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- MAIN GRID: FORM + HISTORI -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- FORM RESERVASI -->
            <div class="lg:col-span-1">
                <div class="neon-card rounded-2xl p-6 sticky top-24">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#ff3399]/20">
                        <div class="w-8 h-8 rounded-lg bg-[#ff3399]/20 text-[#ff3399] flex items-center justify-center font-bold">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <h3 class="font-bold text-base text-white">Buat Reservasi Baru</h3>
                    </div>

                    <form action="reservasi.php" method="POST" class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Plat Nomor Kendaraan</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                                    <i class="fa-solid fa-id-card"></i>
                                </span>
                                <input type="text" name="plat_nomor" required placeholder="CONTOH: AB 1234 CD" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none uppercase transition">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Jenis Kendaraan</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                                    <i class="fa-solid fa-car"></i>
                                </span>
                                <select name="jenis_kendaraan" id="jenis_kendaraan" required onchange="updateEstimasiBiaya()" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition appearance-none">
                                    <option value="" disabled selected>-- Pilih Jenis --</option>
                                    <option value="Motor">Motor (Rp 3.000/jam)</option>
                                    <option value="Mobil">Mobil (Rp 7.000/jam)</option>
                                    <option value="Bus">Bus (Rp 15.000/jam)</option>
                                    <option value="Truk">Truk (Rp 15.000/jam)</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Durasi Parkir (Jam)</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                </span>
                                <input type="number" name="durasi_jam" id="durasi_jam" min="1" value="1" required oninput="updateEstimasiBiaya()" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Tanggal Kedatangan</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                                        <i class="fa-solid fa-calendar-days"></i>
                                    </span>
                                    <input type="date" name="tgl_kedatangan" value="<?= date('Y-m-d'); ?>" required class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-3 py-3 outline-none transition [color-scheme:dark]">
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Jam Kedatangan</label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                                        <i class="fa-solid fa-clock"></i>
                                    </span>
                                    <input type="time" name="jam_kedatangan" value="<?= date('H:i'); ?>" required class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-3 py-3 outline-none transition [color-scheme:dark]">
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-[#ff3399]/10 border border-[#ff3399]/30 rounded-xl flex justify-between items-center text-xs">
                            <span class="text-[#a090a5] font-semibold">Estimasi Total Biaya:</span>
                            <span id="label_total_biaya" class="font-extrabold text-[#ff3399] text-sm">Rp 0</span>
                        </div>

                        <button type="submit" name="submit_reservasi" class="w-full bg-gradient-to-r from-[#ff2a85] to-[#9d4edd] hover:opacity-90 text-white font-bold text-sm py-3.5 rounded-xl transition shadow-[0_0_20px_rgba(255,42,133,0.4)] flex items-center justify-center gap-2 mt-2">
                            <i class="fa-solid fa-paper-plane"></i> KIRIM PENGAJUAN
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABEL RIWAYAT RESERVASI (TANPA KOLOM KARCIS) -->
            <div class="lg:col-span-2">
                <div class="neon-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-[#ff3399]/20">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#ff3399]/20 text-[#ff3399] flex items-center justify-center font-bold">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <h3 class="font-bold text-base text-white">Riwayat Reservasi Anda</h3>
                        </div>
                        <a href="reservasi.php" class="text-xs text-[#ff66b2] hover:underline font-bold flex items-center gap-1">
                            <i class="fa-solid fa-rotate-right"></i> Refresh
                        </a>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-[#ff3399]/20 text-[#a090a5] text-[11px] uppercase tracking-wider">
                                    <th class="p-3">Waktu Kedatangan</th>
                                    <th class="p-3">Plat Nomor</th>
                                    <th class="p-3">Jenis</th>
                                    <th class="p-3">Estimasi Biaya</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ff3399]/10 text-xs">
                                <?php if ($q_reservasi && mysqli_num_rows($q_reservasi) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($q_reservasi)): 
                                        $st = strtolower(trim($row['status'] ?? ''));
                                        if (empty($st)) { $st = 'menunggu'; } 
                                        $tgl_raw = $row['tgl_reservasi'] ?? date('Y-m-d H:i:s');
                                        $tgl_formatted = date('d/m/Y H:i', strtotime($tgl_raw));
                                        
                                        $durasi_jam_row = $row['durasi_jam'] ?? 1;
                                        $biaya_row = isset($row['total_biaya']) && $row['total_biaya'] > 0 
                                            ? $row['total_biaya'] 
                                            : hitungBiayaReservasi($row['jenis_kendaraan'], $durasi_jam_row);
                                    ?>
                                        <tr class="hover:bg-[#ff3399]/5 transition">
                                            <td class="p-3 text-[#a090a5] font-mono"><?php echo $tgl_formatted; ?></td>
                                            <td class="p-3 font-mono font-bold text-[#ff3399]"><?php echo htmlspecialchars($row['plat_nomor']); ?></td>
                                            <td class="p-3 font-semibold"><?php echo htmlspecialchars($row['jenis_kendaraan']); ?></td>
                                            <td class="p-3 font-bold text-[#00e676]">Rp <?php echo number_format($biaya_row, 0, ',', '.'); ?></td>
                                            <td class="p-3 text-center">
                                                <?php if ($st === 'disetujui' || $st === 'menunggu kedatangan'): ?>
                                                    <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-[10px] font-bold border border-blue-500/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-check"></i> Disetujui
                                                    </span>
                                                <?php elseif ($st === 'parkir'): ?>
                                                    <span class="bg-[#00e676]/20 text-[#00e676] px-3 py-1 rounded-full text-[10px] font-bold border border-[#00e676]/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-car-side"></i> Parkir
                                                    </span>
                                                <?php elseif ($st === 'selesai'): ?>
                                                    <span class="bg-purple-500/20 text-purple-400 px-3 py-1 rounded-full text-[10px] font-bold border border-purple-500/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-flag-checkered"></i> Selesai
                                                    </span>
                                                <?php elseif ($st === 'ditolak'): ?>
                                                    <span class="bg-[#ff2a85]/20 text-[#ff2a85] px-3 py-1 rounded-full text-[10px] font-bold border border-[#ff2a85]/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-amber-500/20 text-amber-400 px-3 py-1 rounded-full text-[10px] font-bold border border-amber-500/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-hourglass-half"></i> Menunggu
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="p-8 text-center text-[#a090a5]">
                                            <i class="fa-solid fa-inbox text-3xl mb-2 block opacity-40"></i>
                                            Belum ada riwayat reservasi parkir.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </main>

    <!-- FOOTER -->
    <footer class="bg-[#140819]/80 border-t border-[#ff3399]/20 p-4 text-center mt-auto">
        <p class="text-xs text-[#a090a5] font-medium">&copy; <?php echo date('Y'); ?> E–Parkir Terminal Giwangan. BY CINDY ALIVIA NINGRUM SMKN 1 SANDEN. All rights reserved.</p>
    </footer>

    <script>
        function updateEstimasiBiaya() {
            var jenis = document.getElementById('jenis_kendaraan').value.toLowerCase();
            var durasi = parseInt(document.getElementById('durasi_jam').value) || 1;
            var tarif = 0;

            if (jenis === 'motor') {
                tarif = 3000;
            } else if (jenis === 'mobil') {
                tarif = 7000;
            } else if (jenis === 'bus' || jenis === 'truk') {
                tarif = 15000;
            }

            var total = tarif * durasi;
            document.getElementById('label_total_biaya').innerText = 'Rp ' + total.toLocaleString('id-ID');
        }
    </script>
</body>
</html>