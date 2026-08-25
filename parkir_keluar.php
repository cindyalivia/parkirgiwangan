<?php
session_start();
// Zona waktu tidak lagi dipaksa ke Asia/Jakarta — memakai default timezone server.

include 'koneksi.php';
$db = $koneksi ?? $conn ?? null;

if (!$db) {
    die("Koneksi database gagal!");
}

$keyword     = isset($_POST['kode_karcis']) ? trim($_POST['kode_karcis']) : (isset($_GET['kode_karcis']) ? trim($_GET['kode_karcis']) : '');
$data        = null;
$sumber_tabel = ''; // 'transaksi' atau 'reservasi'
$error_msg   = '';
$success_msg = '';
$show_success_alert = false;
$alert_data  = [];

if (!empty($keyword)) {
    $keyword_clean = mysqli_real_escape_string($db, $keyword);
    $keyword_tanpa_spasi = str_replace(' ', '', $keyword_clean);

    // 1. CARI DI TABEL tb_transaksi (Parkir Manual / Masuk Langsung)
    $query_trx = mysqli_query($db, "SELECT t.*, tr.jenis_kendaraan, tr.tarif_per_jam 
        FROM tb_transaksi t
        LEFT JOIN tb_tarif tr ON t.id_tarif = tr.id_tarif
        WHERE (
            t.kode_barcode = '$keyword_clean' 
            OR LOWER(t.no_plat) = LOWER('$keyword_clean')
            OR REPLACE(LOWER(t.no_plat), ' ', '') = LOWER('$keyword_tanpa_spasi')
            OR t.id_transaksi = '$keyword_clean'
        )
        AND LOWER(t.status) = 'masuk'
        ORDER BY t.id_transaksi DESC LIMIT 1");

    if ($query_trx && mysqli_num_rows($query_trx) > 0) {
        $data = mysqli_fetch_assoc($query_trx);
        $sumber_tabel = 'transaksi';
        $success_msg = "✅ Data kendaraan ditemukan di Transaksi Parkir!";
    } else {
        // 2. CARI DI TABEL tb_reservasi (Reservasi Online - AMAN TANPA MENGUBAH LOGIC RESERVASI KANAN/KIRI)
        $query_res = mysqli_query($db, "SELECT * FROM tb_reservasi 
            WHERE (
                id_reservasi = '$keyword_clean' 
                OR LOWER(plat_nomor) = LOWER('$keyword_clean')
                OR REPLACE(LOWER(plat_nomor), ' ', '') = LOWER('$keyword_tanpa_spasi')
            )
            AND (status IS NULL OR LOWER(status) != 'keluar')
            ORDER BY id_reservasi DESC LIMIT 1");

        if ($query_res && mysqli_num_rows($query_res) > 0) {
            $data = mysqli_fetch_assoc($query_res);
            $sumber_tabel = 'reservasi';
            $success_msg = "✅ Data kendaraan ditemukan di Reservasi!";
        } else {
            $error_msg = "❌ Plat Nomor / Kode Karcis '<b>" . htmlspecialchars($keyword) . "</b>' tidak ditemukan atau kendaraan sudah keluar.";
        }
    }
}

// PROSES PEMBAYARAN & UPDATE STATUS KELUAR
if (isset($_POST['proses_keluar'])) {
    $id_proses        = mysqli_real_escape_string($db, $_POST['id_proses']);
    $tipe_sumber      = mysqli_real_escape_string($db, $_POST['sumber_tabel']);
    $total_bayar      = (float)($_POST['total_bayar'] ?? 0);
    $total_durasi     = mysqli_real_escape_string($db, $_POST['total_durasi']);
    $waktu_keluar_post = mysqli_real_escape_string($db, $_POST['waktu_keluar']);
    $metode_pembayaran = mysqli_real_escape_string($db, $_POST['metode_pembayaran'] ?? 'Cash');
    $nominal_diterima = (float)($_POST['nominal_diterima'] ?? $total_bayar);
    $kembalian        = max(0, $nominal_diterima - $total_bayar);

    if ($tipe_sumber === 'transaksi') {
        // Update untuk Parkir Manual (tb_transaksi)
        $update = mysqli_query($db, "UPDATE tb_transaksi SET 
            status = 'keluar',
            waktu_keluar = '$waktu_keluar_post',
            total_durasi = '$total_durasi',
            total_bayar = '$total_bayar'
            WHERE id_transaksi = '$id_proses'");
    } else {
        // Update untuk Reservasi Online (tb_reservasi)
        // PERBAIKAN: kolom yang dipakai konsisten dengan check-in (total_biaya, bukan biaya),
        // status jadi 'Selesai' karena kendaraan sudah keluar, dan slot parkir dikurangi.
        $update = mysqli_query($db, "UPDATE tb_reservasi SET 
            status = 'Selesai',
            total_biaya = '$total_bayar'
            WHERE id_reservasi = '$id_proses'");

        if ($update && !empty($_POST['jenis_kendaraan'])) {
            $jenis_slot = mysqli_real_escape_string($db, $_POST['jenis_kendaraan']);
            mysqli_query($db, "UPDATE tb_slot_parkir SET terisi = GREATEST(0, terisi - 1) WHERE jenis_kendaraan = '$jenis_slot'");
        }
    }

    if ($update) {
        $show_success_alert = true;
        $alert_data = [
            'total' => number_format($total_bayar, 0, ',', '.'),
            'metode' => $metode_pembayaran,
            'diterima' => number_format($nominal_diterima, 0, ',', '.'),
            'kembalian' => number_format($kembalian, 0, ',', '.')
        ];
    } else {
        $error_msg = "❌ Gagal memproses transaksi keluar: " . mysqli_error($db);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Parkir Keluar - Sistem Parkir</title>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: Arial, sans-serif; }
        body { background-color: #0d0614; color: #ffffff; padding: 20px; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { width: 100%; max-width: 550px; background: #160c23; border: 1px solid #2d1847; border-radius: 16px; padding: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .btn-back { display: inline-block; padding: 8px 16px; background: #26133d; color: #fff; text-decoration: none; border-radius: 8px; font-size: 13px; font-weight: bold; margin-bottom: 20px; border: 1px solid #3d1f63; }
        .title { text-align: center; margin-bottom: 20px; }
        .title h2 { color: #f43f5e; font-size: 22px; }
        .title p { color: #a1a1aa; font-size: 13px; margin-top: 4px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; text-transform: uppercase; color: #a1a1aa; margin-bottom: 6px; font-weight: bold; }
        .form-control { width: 100%; padding: 12px; background: #0d0614; border: 1px solid #3d1f63; border-radius: 8px; color: #fff; font-size: 15px; text-align: center; font-weight: bold; }
        .form-select { width: 100%; padding: 12px; background: #0d0614; border: 1px solid #3d1f63; border-radius: 8px; color: #38bdf8; font-size: 15px; font-weight: bold; text-align-last: center; cursor: pointer; }
        .btn-submit { width: 100%; padding: 12px; background: #f43f5e; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: bold; cursor: pointer; transition: 0.2s; }
        .btn-submit:hover { background: #e11d48; }
        .alert { padding: 12px; border-radius: 8px; font-size: 14px; text-align: center; margin-bottom: 16px; }
        .alert-error { background: rgba(244, 63, 94, 0.2); border: 1px solid #f43f5e; color: #f43f5e; }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #22c55e; }
        .detail-box { background: #0d0614; border: 1px solid #3d1f63; border-radius: 12px; padding: 16px; margin-top: 20px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #221038; font-size: 14px; }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #a1a1aa; }
        .detail-val { font-weight: bold; color: #fff; }
        .badge-reservasi { background: #8b5cf6; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
        .badge-manual { background: #06b6d4; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 11px; }
        .payment-section { background: #1a0f2e; border: 1px solid #3d1f63; border-radius: 10px; padding: 14px; margin-top: 14px; }
        .btn-process { width: 100%; padding: 14px; background: #22c55e; color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 16px; transition: 0.2s; }
        .btn-process:hover { background: #16a34a; }

        .swal2-popup-custom {
            background: rgba(22, 12, 35, 0.95) !important;
            border: 1px solid rgba(244, 63, 94, 0.4) !important;
            border-radius: 20px !important;
            color: #ffffff !important;
            backdrop-filter: blur(15px);
            box-shadow: 0 0 35px rgba(0, 0, 0, 0.8) !important;
        }
        .swal2-title-custom { color: #ffffff !important; font-weight: 800 !important; font-size: 22px !important; }
        .swal2-html-custom { color: #a1a1aa !important; font-size: 14px !important; }
    </style>
</head>
<body>

<div class="container">
    <a href="dashboard.php" class="btn-back">◄ Kembali ke Dashboard</a>

    <div class="title">
        <h2>Pembayaran Parkir Keluar</h2>
        <p>Cari berdasarkan Plat Nomor, Kode Barcode, atau ID Reservasi</p>
    </div>

    <!-- Alert Notifikasi -->
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-error"><?= $error_msg; ?></div>
    <?php endif; ?>

    <?php if (!empty($success_msg) && !$show_success_alert): ?>
        <div class="alert alert-success"><?= $success_msg; ?></div>
    <?php endif; ?>

    <!-- Form Pencarian -->
    <form method="POST" action="">
        <div class="form-group">
            <label>KODE BARCODE / ID RESERVASI / PLAT NOMOR</label>
            <input type="text" name="kode_karcis" class="form-control" placeholder="Contoh: AA 987 AD atau Kode Barcode" value="<?= htmlspecialchars($keyword); ?>" autofocus required>
        </div>
        <button type="submit" class="btn-submit">Cari Data Kendaraan</button>
    </form>

    <!-- Detail Hasil Pencarian -->
    <?php if ($data && !$show_success_alert): ?>
        <?php
        $id_proses   = $data['id_transaksi'] ?? $data['id_reservasi'];
        $plat_tampil = $data['no_plat'] ?? $data['plat_nomor'] ?? '-';
        
        $waktu_masuk_str  = date('Y-m-d H:i:s');
        $waktu_keluar_str = date('Y-m-d H:i:s');
        
        $is_reservasi = ($sumber_tabel === 'reservasi');

        // Hitung Durasi Jam
        $awal  = strtotime($waktu_masuk_str);
        $akhir = strtotime($waktu_keluar_str);
        $diff  = $akhir - $awal;
        $jam   = ceil($diff / 3600);
        if ($jam <= 0) $jam = 1;

        // Hitung Biaya
        if ($is_reservasi) {
            $jenis_lower_r = strtolower($data['jenis_kendaraan'] ?? 'motor');
            if (strpos($jenis_lower_r, 'mobil') !== false) {
                $tarif_per_jam_r = 7000;
            } elseif (strpos($jenis_lower_r, 'truk') !== false || strpos($jenis_lower_r, 'bus') !== false) {
                $tarif_per_jam_r = 15000;
            } else {
                $tarif_per_jam_r = 3000;
            }

            // Prioritaskan total_biaya yang sudah tersimpan saat Check-In & Bayar,
            // kalau belum ada (mis. langsung ke parkir_keluar tanpa check-in dulu),
            // hitung dari tarif per jenis kendaraan x durasi.
            $biaya_tersimpan = (float)($data['total_biaya'] ?? 0);
            $total_bayar = $biaya_tersimpan > 0 ? $biaya_tersimpan : ($jam * $tarif_per_jam_r);
        } else {
            $tarif_per_jam = isset($data['tarif_per_jam']) ? floatval($data['tarif_per_jam']) : 3000;
            $total_bayar   = $jam * $tarif_per_jam;
        }
        ?>

        <div class="detail-box">
            <div class="detail-row">
                <span class="detail-label">Tipe Transaksi:</span>
                <span class="detail-val">
                    <?php if ($is_reservasi): ?>
                        <span class="badge-reservasi">RESERVASI ONLINE</span>
                    <?php else: ?>
                        <span class="badge-manual">PARKIR MANUAL</span>
                    <?php endif; ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">ID Transaksi / Barcode:</span>
                <span class="detail-val">#<?= htmlspecialchars($data['kode_barcode'] ?? $id_proses); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Plat Nomor:</span>
                <span class="detail-val" style="color: #f43f5e; font-size: 16px;"><?= htmlspecialchars($plat_tampil); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Jenis Kendaraan:</span>
                <span class="detail-val"><?= htmlspecialchars($data['jenis_kendaraan'] ?? 'Motor'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Waktu Masuk:</span>
                <span class="detail-val" style="color: #38bdf8;"><?= $waktu_masuk_str; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Waktu Keluar:</span>
                <span class="detail-val" style="color: #f472b6;"><?= $waktu_keluar_str; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Durasi:</span>
                <span class="detail-val"><?= $jam; ?> Jam</span>
            </div>
            <div class="detail-row" style="margin-top: 10px; border-top: 1px dashed #3d1f63; padding-top: 10px;">
                <span class="detail-label" style="font-size: 16px; color: #fff;">TOTAL BIAYA:</span>
                <span class="detail-val" style="font-size: 20px; color: #22c55e;">Rp <?= number_format($total_bayar, 0, ',', '.'); ?></span>
            </div>

            <!-- Form Pilih Metode Pembayaran -->
            <form method="POST" action="">
                <input type="hidden" name="id_proses" value="<?= $id_proses; ?>">
                <input type="hidden" name="sumber_tabel" value="<?= $sumber_tabel; ?>">
                <input type="hidden" name="waktu_keluar" value="<?= $waktu_keluar_str; ?>">
                <input type="hidden" name="total_durasi" value="<?= $jam; ?>">
                <input type="hidden" name="jenis_kendaraan" value="<?= htmlspecialchars($data['jenis_kendaraan'] ?? 'Motor'); ?>">
                <input type="hidden" id="total_bayar_val" name="total_bayar" value="<?= $total_bayar; ?>">

                <div class="payment-section">
                    <div class="form-group">
                        <label style="color: #f43f5e;">METODE PEMBAYARAN</label>
                        <select name="metode_pembayaran" id="metode_pembayaran" class="form-select" onchange="toggleCashInput()">
                            <option value="Tunai (Cash)">💵 Tunai / Cash</option>
                            <option value="QRIS">📱 QRIS / E-Wallet</option>
                            <option value="Transfer Bank">🏦 Transfer Bank</option>
                            <option value="Kartu Debit/Kredit">💳 Kartu Debit / Kredit</option>
                        </select>
                    </div>

                    <div class="form-group" id="cash_group">
                        <div style="display: flex; gap: 10px;">
                            <div style="flex: 1;">
                                <label>UANG DITERIMA (RP)</label>
                                <input type="number" name="nominal_diterima" id="nominal_diterima" class="form-control" placeholder="0" value="<?= $total_bayar; ?>" oninput="hitungKembalian()">
                            </div>
                            <div style="flex: 1;">
                                <label>KEMBALIAN (RP)</label>
                                <input type="text" id="nominal_kembalian" class="form-control" value="Rp 0" readonly style="color: #22c55e;">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" name="proses_keluar" class="btn-process">
                    ✔ Bayar & Selesaikan Parkir
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<script>
function hitungKembalian() {
    const total = parseFloat(document.getElementById('total_bayar_val').value) || 0;
    const diterima = parseFloat(document.getElementById('nominal_diterima').value) || 0;
    const kembalian = diterima - total;

    const elKembalian = document.getElementById('nominal_kembalian');
    if (kembalian >= 0) {
        elKembalian.value = "Rp " + new Intl.NumberFormat('id-ID').format(kembalian);
        elKembalian.style.color = "#22c55e";
    } else {
        elKembalian.value = "Uang Kurang!";
        elKembalian.style.color = "#f43f5e";
    }
}

function toggleCashInput() {
    const metode = document.getElementById('metode_pembayaran').value;
    const cashGroup = document.getElementById('cash_group');
    if (metode === 'Tunai (Cash)') {
        cashGroup.style.display = 'block';
    } else {
        cashGroup.style.display = 'none';
        document.getElementById('nominal_diterima').value = document.getElementById('total_bayar_val').value;
        hitungKembalian();
    }
}
</script>

<?php if ($show_success_alert): ?>
<script>
    Swal.fire({
        icon: 'success',
        title: 'Pembayaran Berhasil!',
        html: `
            <div style="text-align: left; background: rgba(0,0,0,0.3); padding: 12px; border-radius: 8px; margin-top: 10px;">
                <p><b>Metode:</b> <?= $alert_data['metode']; ?></p>
                <p><b>Total Biaya:</b> Rp <?= $alert_data['total']; ?></p>
                <p><b>Uang Diterima:</b> Rp <?= $alert_data['diterima']; ?></p>
                <p style="color: #22c55e; font-size: 16px; margin-top: 5px;"><b>Kembalian:</b> Rp <?= $alert_data['kembalian']; ?></p>
            </div>
        `,
        confirmButtonText: 'Selesai',
        confirmButtonColor: '#22c55e',
        customClass: {
            popup: 'swal2-popup-custom',
            title: 'swal2-title-custom',
            htmlContainer: 'swal2-html-custom'
        }
    }).then((result) => {
        if (result.isConfirmed || result.isDismissed) {
            window.location.href = 'parkir_keluar.php';
        }
    });
</script>
<?php endif; ?>

</body>
</html>
