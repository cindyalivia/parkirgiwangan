<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

// Pengecekan Keamanan: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

$id_user  = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Pengguna';
$nama     = $_SESSION['nama'] ?? $username;

// 1. PROSES PENGAJUAN RESERVASI PARKING BARU
$alert_msg = "";
$alert_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservasi'])) {
    $plat_nomor      = strtoupper(trim($_POST['plat_nomor']));
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $tgl_reservasi   = date('Y-m-d H:i:s');

    if (!empty($plat_nomor) && !empty($jenis_kendaraan)) {
        // Simpan ke database dengan status awal 'Menunggu'
        $query_insert = "INSERT INTO tb_reservasi (id_user, nama_pemohon, plat_nomor, jenis_kendaraan, status, tgl_reservasi) 
                        VALUES ('$id_user', '$nama', '$plat_nomor', '$jenis_kendaraan', 'Menunggu', '$tgl_reservasi')";
        
        if (mysqli_query($conn, $query_insert)) {
            $alert_msg  = "Pengajuan reservasi berhasil dikirim! Silakan tunggu konfirmasi petugas.";
            $alert_type = "success";
        } else {
            $alert_msg  = "Gagal mengajukan reservasi: " . mysqli_error($conn);
            $alert_type = "error";
        }
    } else {
        $alert_msg  = "Harap isi semua bidang formulir dengan benar!";
        $alert_type = "error";
    }
}

// 2. AMBIL DATA RIWAYAT RESERVASI USER
$q_reservasi = mysqli_query($conn, "SELECT * FROM tb_reservasi WHERE id_user = '$id_user' ORDER BY id_reservasi DESC");

// 3. HITUNG RINGKASAN
$q_count_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi WHERE id_user = '$id_user'");
$total_res = mysqli_fetch_assoc($q_count_total)['total'] ?? 0;

$q_count_aktif = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi WHERE id_user = '$id_user' AND status IN ('Menunggu', 'Disetujui', 'Parkir')");
$total_aktif = mysqli_fetch_assoc($q_count_aktif)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengguna - Terminal Giwangan Parking Center</title>
    
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

        /* Neon Glow Utility */
        .neon-border {
            border: 1px solid rgba(255, 51, 153, 0.3);
            box-shadow: 0 0 15px rgba(255, 51, 153, 0.15);
        }
        .neon-card {
            background: rgba(20, 8, 25, 0.75);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 51, 153, 0.2);
        }
        .btn-pink-gradient {
            background: linear-gradient(90deg, #ff3399 0%, #a832a8 100%);
            box-shadow: 0 0 15px rgba(255, 51, 153, 0.4);
            transition: all 0.3s ease;
        }
        .btn-pink-gradient:hover {
            opacity: 0.9;
            box-shadow: 0 0 25px rgba(255, 51, 153, 0.6);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- HEADER / NAVBAR -->
    <nav class="bg-[#140819]/80 border-b border-[#ff3399]/20 backdrop-blur-md sticky top-0 z-40 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <!-- BRAND LOGO -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#ff3399]/15 border border-[#ff3399]/50 rounded-xl flex items-center justify-center text-[#ff3399] shadow-[0_0_10px_rgba(255,51,153,0.3)]">
                    <i class="fa-solid fa-bus text-lg"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-lg text-white tracking-wider leading-none">PARKIR SYSTEM</h1>
                    <span class="text-[10px] text-[#ff3399] font-bold uppercase tracking-widest"><i class="fa-solid fa-location-dot"></i> Terminal Giwangan</span>
                </div>
            </div>

            <!-- PROFILE & LOGOUT -->
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
                <span class="text-xs font-extrabold text-[#ff66b2] uppercase tracking-widest block mb-1">Area Layanan Pengguna</span>
                <h2 class="text-2xl md:text-3xl font-extrabold text-white">Selamat Datang, <?php echo htmlspecialchars($nama); ?>! 👋</h2>
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

            <!-- KIRI: FORM RESERVASI BARU -->
            <div class="lg:col-span-1">
                <div class="neon-card rounded-2xl p-6 sticky top-24">
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-[#ff3399]/20">
                        <div class="w-8 h-8 rounded-lg bg-[#ff3399]/20 text-[#ff3399] flex items-center justify-center font-bold">
                            <i class="fa-solid fa-calendar-plus"></i>
                        </div>
                        <h3 class="font-bold text-base text-white">Buat Reservasi Baru</h3>
                    </div>

                    <form action="" method="POST" class="space-y-5">
                        <!-- Plat Nomor -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#e0d0e5] mb-2">Plat Nomor Kendaraan</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#a090a5]">
                                    <i class="fa-solid fa-id-card"></i>
                                </span>
                                <input type="text" name="plat_nomor" required placeholder="Contoh: AB 1234 CD" 
                                       class="w-full bg-[#130826] border border-[#ff3399]/30 focus:border-[#ff3399] rounded-xl py-3 pl-10 pr-4 text-sm font-mono uppercase font-bold text-white outline-none transition duration-200 placeholder-gray-600">
                            </div>
                        </div>

                        <!-- Jenis Kendaraan -->
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-[#e0d0e5] mb-2">Jenis Kendaraan</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-[#a090a5]">
                                    <i class="fa-solid fa-car"></i>
                                </span>
                                <select name="jenis_kendaraan" required 
                                        class="w-full bg-[#130826] border border-[#ff3399]/30 focus:border-[#ff3399] rounded-xl py-3 pl-10 pr-4 text-sm font-semibold text-white outline-none transition duration-200 appearance-none cursor-pointer">
                                    <option value="" disabled selected class="bg-[#130826]">-- Pilih Jenis --</option>
                                    <option value="Motor" class="bg-[#130826]">🏍️ Motor (Rp 5.000)</option>
                                    <option value="Mobil" class="bg-[#130826]">🚗 Mobil (Rp 10.000)</option>
                                    <option value="Bus" class="bg-[#130826]">🚌 Bus / Truk (Rp 20.000)</option>
                                </select>
                                <span class="absolute inset-y-0 right-0 flex items-center pr-3.5 pointer-events-none text-[#a090a5]">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </span>
                            </div>
                        </div>

                        <!-- Tombol Submit -->
                        <button type="submit" name="submit_reservasi" 
                                class="w-full btn-pink-gradient font-extrabold py-3.5 px-4 rounded-xl text-white text-xs uppercase tracking-wider flex items-center justify-center gap-2 mt-4">
                            <i class="fa-solid fa-[#ff3399] fa-paper-plane"></i> Kirim Pengajuan
                        </button>
                    </form>
                </div>
            </div>

            <!-- KANAN: TABEL RIWAYAT RESERVASI -->
            <div class="lg:col-span-2">
                <div class="neon-card rounded-2xl p-6">
                    <div class="flex justify-between items-center mb-6 pb-4 border-b border-[#ff3399]/20">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-[#ff3399]/20 text-[#ff3399] flex items-center justify-center font-bold">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <h3 class="font-bold text-base text-white">Riwayat Reservasi Anda</h3>
                        </div>
                        <button onclick="location.reload()" class="text-xs text-[#ff66b2] hover:underline font-bold flex items-center gap-1">
                            <i class="fa-solid fa-rotate-right"></i> Refresh
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="border-b border-[#ff3399]/20 text-[#a090a5] text-[11px] uppercase tracking-wider">
                                    <th class="p-3">Waktu</th>
                                    <th class="p-3">Plat Nomor</th>
                                    <th class="p-3">Jenis</th>
                                    <th class="p-3">Status</th>
                                    <th class="p-3 text-center">Karcis</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[#ff3399]/10 text-xs">
                                <?php if (mysqli_num_rows($q_reservasi) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($q_reservasi)): 
                                        $st = strtolower(trim($row['status']));
                                        $tgl = $row['tgl_reservasi'] ?? date('Y-m-d H:i:s');
                                    ?>
                                        <tr class="hover:bg-[#ff3399]/5 transition">
                                            <td class="p-3 text-[#a090a5]"><?php echo date('d/m/Y H:i', strtotime($tgl)); ?></td>
                                            <td class="p-3 font-mono font-bold text-[#ff3399]"><?php echo htmlspecialchars($row['plat_nomor']); ?></td>
                                            <td class="p-3 font-semibold"><?php echo htmlspecialchars($row['jenis_kendaraan']); ?></td>
                                            <td class="p-3">
                                                <?php if ($st === 'disetujui'): ?>
                                                    <span class="bg-blue-500/20 text-blue-400 px-2.5 py-1 rounded-full text-[10px] font-bold border border-blue-500/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-check"></i> Disetujui
                                                    </span>
                                                <?php elseif ($st === 'parkir'): ?>
                                                    <span class="bg-[#00e676]/20 text-[#00e676] px-2.5 py-1 rounded-full text-[10px] font-bold border border-[#00e676]/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-car-side"></i> Parkir
                                                    </span>
                                                <?php elseif ($st === 'selesai'): ?>
                                                    <span class="bg-purple-500/20 text-purple-400 px-2.5 py-1 rounded-full text-[10px] font-bold border border-purple-500/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-flag-checkered"></i> Selesai
                                                    </span>
                                                <?php elseif ($st === 'ditolak'): ?>
                                                    <span class="bg-[#ff2a85]/20 text-[#ff2a85] px-2.5 py-1 rounded-full text-[10px] font-bold border border-[#ff2a85]/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-circle-xmark"></i> Ditolak
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-amber-500/20 text-amber-400 px-2.5 py-1 rounded-full text-[10px] font-bold border border-amber-500/40 inline-flex items-center gap-1">
                                                        <i class="fa-solid fa-hourglass-half"></i> Menunggu
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="p-3 text-center">
                                                <?php if ($st === 'disetujui' || $st === 'parkir' || $st === 'selesai'): ?>
                                                    <button onclick="cetakStruk(<?php echo $row['id_reservasi']; ?>)" 
                                                            class="bg-[#ff3399]/20 hover:bg-[#ff3399]/40 text-[#ff66b2] border border-[#ff3399]/50 font-bold px-2.5 py-1 rounded-lg transition inline-flex items-center gap-1 text-[11px]">
                                                        <i class="fa-solid fa-print"></i> Karcis
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-[#a090a5] italic">-</span>
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

    <!-- SCRIPT CETAK STRUK/KARCIS -->
    <script>
        function cetakStruk(id) {
            window.open('cetak_struk_reservasi.php?id=' + id, '_blank', 'width=400,height=600');
        }
    </script>
</body>
</html>