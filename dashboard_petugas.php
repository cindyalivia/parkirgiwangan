<?php
date_default_timezone_set('Asia/Jakarta');
session_start();

// Pengecekan Keamanan: Wajib Login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'koneksi.php';

$nama_petugas = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Petugas';
$alert_msg    = "";
$alert_type   = "";

// 1. PROSES UPDATE STATUS RESERVASI (SETUJU / TOLAK / MASUK PARKIR)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id_reservasi = intval($_POST['id_reservasi']);
    $status_baru  = mysqli_real_escape_string($conn, $_POST['status_baru']);

    if ($id_reservasi > 0 && !empty($status_baru)) {
        $q_update = "UPDATE tb_reservasi SET status = '$status_baru' WHERE id_reservasi = '$id_reservasi'";
        
        if (mysqli_query($conn, $q_update)) {
            header("Location: dashboard_petugas.php?status=updated");
            exit();
        } else {
            $alert_msg  = "Gagal memperbarui status: " . mysqli_error($conn);
            $alert_type = "error";
        }
    }
}

// Tangkap Notifikasi
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'updated') {
        $alert_msg  = "Status reservasi berhasil diperbarui!";
        $alert_type = "success";
    } elseif ($_GET['status'] === 'success_bayar') {
        $alert_msg  = "Pembayaran berhasil diproses dan status telah Selesai!";
        $alert_type = "success";
    }
}

// 2. AMBIL SEMUA DATA RESERVASI UNTUK PETUGAS
$q_all_reservasi = mysqli_query($conn, "SELECT * FROM tb_reservasi ORDER BY id_reservasi DESC");

// 3. HITUNG RINGKASAN DATA
$q_pending = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi WHERE LOWER(status) = 'menunggu' OR status = '' OR status IS NULL");
$total_pending = mysqli_fetch_assoc($q_pending)['total'] ?? 0;

$q_approved = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi WHERE LOWER(status) = 'disetujui'");
$total_approved = mysqli_fetch_assoc($q_approved)['total'] ?? 0;

$q_total = mysqli_query($conn, "SELECT COUNT(*) as total FROM tb_reservasi");
$total_reservasi = mysqli_fetch_assoc($q_total)['total'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reservasi - Dashboard Petugas</title>
    
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
        
        .btn-pink-gradient {
            background: linear-gradient(135deg, #ff3399 0%, #a832a8 100%);
            box-shadow: 0 0 15px rgba(255, 51, 153, 0.4);
        }
        .btn-pink-gradient:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body class="flex flex-col min-h-screen">

    <!-- NAVBAR PETUGAS -->
    <nav class="bg-[#140819]/80 border-b border-[#ff3399]/20 backdrop-blur-md sticky top-0 z-40 px-6 py-4">
        <div class="max-w-7xl mx-auto flex justify-between items-center">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[#ff3399]/15 border border-[#ff3399]/50 rounded-xl flex items-center justify-center text-[#ff3399] shadow-[0_0_10px_rgba(255,51,153,0.3)]">
                    <i class="fa-solid fa-user-shield text-lg"></i>
                </div>
                <div>
                    <h1 class="font-extrabold text-lg text-white tracking-wider leading-none">PANEL PETUGAS</h1>
                    <span class="text-[10px] text-[#ff3399] font-bold uppercase tracking-widest"><i class="fa-solid fa-location-dot"></i> Terminal Giwangan</span>
                </div>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex flex-col text-right">
                    <span class="text-sm font-bold text-white"><?php echo htmlspecialchars($nama_petugas); ?></span>
                    <span class="text-xs text-[#a090a5]">Petugas Parkir</span>
                </div>
                <a href="logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')" 
                   class="bg-[#ff3366]/10 hover:bg-[#ff3366]/20 border border-[#ff3366]/40 text-[#ff4d4d] px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <span class="hidden sm:inline">Keluar</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="max-w-7xl mx-auto w-full p-4 md:p-8 flex-grow space-y-8">

        <!-- STATS BANNER -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="neon-card p-5 rounded-2xl flex items-center justify-between">
                <div>
                    <span class="text-xs text-[#a090a5] font-bold uppercase tracking-wider block">Menunggu Konfirmasi</span>
                    <span class="text-2xl font-extrabold text-amber-400 mt-1 block"><?php echo $total_pending; ?> Data</span>
                </div>
                <div class="w-12 h-12 bg-amber-500/20 text-amber-400 rounded-xl flex items-center justify-center text-xl border border-amber-500/40">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>

            <div class="neon-card p-5 rounded-2xl flex items-center justify-between">
                <div>
                    <span class="text-xs text-[#a090a5] font-bold uppercase tracking-wider block">Reservasi Disetujui</span>
                    <span class="text-2xl font-extrabold text-blue-400 mt-1 block"><?php echo $total_approved; ?> Data</span>
                </div>
                <div class="w-12 h-12 bg-blue-500/20 text-blue-400 rounded-xl flex items-center justify-center text-xl border border-blue-500/40">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
            </div>

            <div class="neon-card p-5 rounded-2xl flex items-center justify-between">
                <div>
                    <span class="text-xs text-[#a090a5] font-bold uppercase tracking-wider block">Total Pengajuan</span>
                    <span class="text-2xl font-extrabold text-[#ff3399] mt-1 block"><?php echo $total_reservasi; ?> Data</span>
                </div>
                <div class="w-12 h-12 bg-[#ff3399]/20 text-[#ff3399] rounded-xl flex items-center justify-center text-xl border border-[#ff3399]/40">
                    <i class="fa-solid fa-list-check"></i>
                </div>
            </div>
        </div>

        <!-- NOTIFIKASI ALERT -->
        <?php if (!empty($alert_msg)): ?>
            <div class="p-4 rounded-xl text-xs font-bold flex items-center justify-between border <?php echo $alert_type === 'success' ? 'bg-[#00e676]/10 border-[#00e676]/40 text-[#00e676]' : 'bg-[#ff2a85]/10 border-[#ff2a85]/40 text-[#ff2a85]'; ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid <?php echo $alert_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?> text-base"></i>
                    <span><?php echo $alert_msg; ?></span>
                </div>
                <button type="button" onclick="this.parentElement.remove()"><i class="fa-solid fa-xmark"></i></button>
            </div>
        <?php endif; ?>

        <!-- TABEL KELOLA RESERVASI -->
        <div class="neon-card rounded-2xl p-6">
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-[#ff3399]/20">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-[#ff3399]/20 text-[#ff3399] flex items-center justify-center font-bold">
                        <i class="fa-solid fa-list-check"></i>
                    </div>
                    <h3 class="font-bold text-base text-white">Daftar Pengajuan Reservasi Masuk</h3>
                </div>
                <a href="dashboard_petugas.php" class="text-xs text-[#ff66b2] hover:underline font-bold flex items-center gap-1">
                    <i class="fa-solid fa-rotate-right"></i> Refresh Data
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-[#ff3399]/20 text-[#a090a5] text-[11px] uppercase tracking-wider">
                            <th class="p-3">#ID</th>
                            <th class="p-3">Waktu</th>
                            <th class="p-3">Pemohon</th>
                            <th class="p-3">Plat Nomor</th>
                            <th class="p-3">Jenis</th>
                            <th class="p-3">Status Saat Ini</th>
                            <th class="p-3 text-center">Aksi Konfirmasi Petugas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#ff3399]/10 text-xs">
                        <?php if ($q_all_reservasi && mysqli_num_rows($q_all_reservasi) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($q_all_reservasi)): 
                                $st = strtolower(trim($row['status'] ?? ''));
                                if (empty($st)) { $st = 'menunggu'; }
                                $tgl_formatted = date('d/m/Y H:i', strtotime($row['tgl_reservasi'] ?? date('Y-m-d H:i:s')));
                                $biaya = $row['biaya'] ?? $row['total_biaya'] ?? 5000;
                            ?>
                                <tr class="hover:bg-[#ff3399]/5 transition">
                                    <td class="p-3 font-mono text-[#a090a5]">#<?php echo $row['id_reservasi']; ?></td>
                                    <td class="p-3 text-[#a090a5] font-mono"><?php echo $tgl_formatted; ?></td>
                                    <td class="p-3 font-bold text-white"><?php echo htmlspecialchars($row['nama_pemohon']); ?></td>
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
                                        <?php if ($st === 'menunggu'): ?>
                                            <!-- FORM DISETUJUI / DITOLAK -->
                                            <form action="" method="POST" class="flex justify-center gap-2">
                                                <input type="hidden" name="id_reservasi" value="<?php echo $row['id_reservasi']; ?>">
                                                <input type="hidden" name="update_status" value="1">

                                                <button type="submit" name="status_baru" value="Disetujui" onclick="return confirm('Setujui reservasi ini?')"
                                                        class="bg-blue-600/80 hover:bg-blue-600 text-white font-bold px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 text-[11px] shadow-[0_0_10px_rgba(37,99,235,0.4)]">
                                                    <i class="fa-solid fa-check"></i> Setujui
                                                </button>
                                                <button type="submit" name="status_baru" value="Ditolak" onclick="return confirm('Tolak reservasi ini?')"
                                                        class="bg-[#ff2a85]/80 hover:bg-[#ff2a85] text-white font-bold px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 text-[11px] shadow-[0_0_10px_rgba(255,42,133,0.4)]">
                                                    <i class="fa-solid fa-xmark"></i> Tolak
                                                </button>
                                            </form>

                                        <?php elseif ($st === 'disetujui'): ?>
                                            <!-- MASUK PARKIR -->
                                            <form action="" method="POST" class="flex justify-center">
                                                <input type="hidden" name="id_reservasi" value="<?php echo $row['id_reservasi']; ?>">
                                                <input type="hidden" name="update_status" value="1">
                                                <button type="submit" name="status_baru" value="Parkir" onclick="return confirm('Tandai kendaraan sudah masuk lokasi parkir?')"
                                                        class="bg-[#00e676]/20 hover:bg-[#00e676]/40 text-[#00e676] border border-[#00e676]/50 font-bold px-3 py-1 rounded-lg transition inline-flex items-center gap-1 text-[11px]">
                                                    <i class="fa-solid fa-car-side"></i> Masuk Parkir
                                                </button>
                                            </form>

                                        <?php elseif ($st === 'parkir'): ?>
                                            <!-- TOMBOL BAYAR (MEMBUKA MODAL PEMBAYARAN) -->
                                            <button type="button" 
                                                    onclick="openModalBayar('<?php echo $row['id_reservasi']; ?>', '<?php echo htmlspecialchars(addslashes($row['nama_pemohon'])); ?>', '<?php echo htmlspecialchars(addslashes($row['plat_nomor'])); ?>', '<?php echo $biaya; ?>')" 
                                                    class="bg-gradient-to-r from-[#ff3399] to-[#a832a8] text-white font-bold px-3 py-1.5 rounded-lg transition inline-flex items-center gap-1 text-[11px] shadow-[0_0_10px_rgba(255,51,153,0.3)]">
                                                <i class="fa-solid fa-wallet"></i> Bayar
                                            </button>

                                        <?php else: ?>
                                            <span class="text-[#a090a5] italic text-[11px]">Tidak ada aksi</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="p-8 text-center text-[#a090a5]">
                                    <i class="fa-solid fa-inbox text-3xl mb-2 block opacity-40"></i>
                                    Belum ada pengajuan reservasi dari pengguna.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <!-- MODAL PEMBAYARAN FORM (HTML MURNI) -->
    <div id="modalBayar" class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center hidden">
        <div class="neon-card rounded-2xl p-6 w-full max-w-md relative">
            <button type="button" onclick="closeModalBayar()" class="absolute top-4 right-4 text-gray-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>

            <div class="flex items-center gap-3 mb-5 border-b border-[#ff3399]/20 pb-3">
                <i class="fa-solid fa-receipt text-[#ff3399] text-xl"></i>
                <h3 class="font-bold text-base text-white">Pembayaran Reservasi Parkir</h3>
            </div>

            <!-- FORM FORMAL MURNI TANPA AJAX -->
            <form action="proses_pembayaran.php" method="POST" class="space-y-4">
                <input type="hidden" name="id_reservasi" id="pay_id_reservasi" value="">
                <input type="hidden" name="total_biaya" id="pay_total_biaya_input" value="5000">

                <div class="bg-[#130826] p-3 rounded-xl border border-[#ff3399]/20 space-y-2 text-xs">
                    <div class="flex justify-between">
                        <span class="text-[#a090a5]">Nama Pemohon:</span>
                        <span class="font-bold text-white" id="pay_nama">-</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#a090a5]">Plat Nomor:</span>
                        <span class="font-mono font-bold text-[#ff3399]" id="pay_plat">-</span>
                    </div>
                    <div class="flex justify-between border-t border-[#ff3399]/10 pt-2">
                        <span class="text-[#a090a5]">Total Biaya:</span>
                        <span class="font-extrabold text-[#00e676]" id="pay_total_text">Rp 5.000</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-[#e0d0e5] mb-2">
                        Nominal Uang Bayar (Rp)
                    </label>
                    <input type="number" name="uang_bayar" id="pay_uang_bayar" required min="0" placeholder="Masukkan nominal..." 
                           oninput="hitungKembalian()"
                           class="w-full bg-[#130826] border border-[#ff3399]/30 focus:border-[#ff3399] rounded-xl py-3 px-4 text-sm font-bold text-white outline-none">
                </div>

                <div class="bg-[#130826] p-3 rounded-xl border border-[#ff3399]/20 flex justify-between items-center text-xs">
                    <span class="text-[#a090a5]">Kembalian:</span>
                    <span class="font-extrabold text-[#00e676]" id="pay_kembalian_text">Rp 0</span>
                </div>

                <button type="submit" 
                        class="w-full btn-pink-gradient font-extrabold py-3.5 px-4 rounded-xl text-white text-xs uppercase tracking-wider flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-check-circle"></i> Bayar Sekarang
                </button>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="bg-[#140819]/80 border-t border-[#ff3399]/20 p-4 text-center mt-auto">
        <p class="text-xs text-[#a090a5] font-medium">&copy; <?php echo date('Y'); ?> E–Parkir Terminal Giwangan. Panel Petugas Control Center.</p>
    </footer>

    <!-- SCRIPT UTILS -->
    <script>
        function openModalBayar(id, nama, plat, biaya) {
            document.getElementById('pay_id_reservasi').value = id;
            document.getElementById('pay_nama').innerText = nama;
            document.getElementById('pay_plat').innerText = plat;
            
            const total = parseFloat(biaya) || 5000;
            document.getElementById('pay_total_biaya_input').value = total;
            document.getElementById('pay_total_text').innerText = 'Rp ' + total.toLocaleString('id-ID');
            
            document.getElementById('pay_uang_bayar').value = '';
            document.getElementById('pay_kembalian_text').innerText = 'Rp 0';
            
            document.getElementById('modalBayar').classList.remove('hidden');
        }

        function closeModalBayar() {
            document.getElementById('modalBayar').classList.add('hidden');
        }

        function hitungKembalian() {
            const total = parseFloat(document.getElementById('pay_total_biaya_input').value) || 0;
            const bayar = parseFloat(document.getElementById('pay_uang_bayar').value) || 0;
            const kembalian = bayar - total;

            if (kembalian >= 0) {
                document.getElementById('pay_kembalian_text').innerText = 'Rp ' + kembalian.toLocaleString('id-ID');
            } else {
                document.getElementById('pay_kembalian_text').innerText = 'Uang Kurang';
            }
        }
    </script>
</body>
</html>