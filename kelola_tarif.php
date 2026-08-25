<?php
session_start();
include 'koneksi.php';

// Pastikan hanya admin yang bisa akses halaman ini
if (!isset($_SESSION['username']) || strtolower($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit;
}

// Variabel untuk mode edit
$edit_mode = false;
$id_edit = '';
$jenis_kendaraan_edit = '';
$tarif_per_jam_edit = '';
$tarif_maksimal_edit = '';

// Jika tombol Edit diklik (Ambil data untuk diisi ke form)
if (isset($_GET['edit'])) {
    $id_edit = mysqli_real_escape_string($conn, $_GET['edit']);
    $query_get_edit = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE id_tarif = '$id_edit'");
    
    // Fallback jika primary key di database hanya 'id'
    if (!$query_get_edit || mysqli_num_rows($query_get_edit) == 0) {
        $query_get_edit = mysqli_query($conn, "SELECT * FROM tb_tarif WHERE id = '$id_edit'");
    }

    if ($query_get_edit && mysqli_num_rows($query_get_edit) > 0) {
        $data_edit = mysqli_fetch_assoc($query_get_edit);
        $edit_mode = true;
        $id_edit = $data_edit['id_tarif'] ?? $data_edit['id'] ?? $id_edit;
        $jenis_kendaraan_edit = $data_edit['jenis_kendaraan'] ?? '';
        $tarif_per_jam_edit   = $data_edit['tarif_per_jam'] ?? 0;
        $tarif_maksimal_edit  = $data_edit['tarif_maksimal'] ?? 0;
    }
}

// Proses Tambah / Update Tarif
if (isset($_POST['simpan_tarif'])) {
    $jenis_kendaraan = mysqli_real_escape_string($conn, $_POST['jenis_kendaraan']);
    $tarif_per_jam   = intval($_POST['tarif_per_jam']);
    $tarif_maksimal  = intval($_POST['tarif_maksimal']);

    if (isset($_POST['id_tarif']) && !empty($_POST['id_tarif'])) {
        // PROSES UPDATE
        $id_tarif = mysqli_real_escape_string($conn, $_POST['id_tarif']);
        $query_update = "UPDATE tb_tarif SET 
                            jenis_kendaraan = '$jenis_kendaraan', 
                            tarif_per_jam = '$tarif_per_jam', 
                            tarif_maksimal = '$tarif_maksimal' 
                         WHERE id_tarif = '$id_tarif'";

        if (mysqli_query($conn, $query_update)) {
            header("Location: kelola_tarif.php?status=edit_sukses");
            exit;
        } else {
            $error = "Gagal memperbarui data tarif: " . mysqli_error($conn);
        }
    } else {
        // PROSES TAMBAH BARU
        $query_tambah = "INSERT INTO tb_tarif (jenis_kendaraan, tarif_per_jam, tarif_maksimal) VALUES ('$jenis_kendaraan', '$tarif_per_jam', '$tarif_maksimal')";

        if (mysqli_query($conn, $query_tambah)) {
            header("Location: kelola_tarif.php?status=sukses");
            exit;
        } else {
            $error = "Gagal menambah data tarif baru: " . mysqli_error($conn);
        }
    }
}

// Proses Hapus Tarif
if (isset($_GET['hapus'])) {
    $id_tarif = mysqli_real_escape_string($conn, $_GET['hapus']);
    
    $query_hapus = "DELETE FROM tb_tarif WHERE id_tarif = '$id_tarif'";
    if (mysqli_query($conn, $query_hapus)) {
        header("Location: kelola_tarif.php?status=hapus_sukses");
        exit;
    } else {
        $error = "Gagal menghapus data tarif.";
    }
}

// Ambil semua daftar tarif dari database
$list_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif ORDER BY tarif_per_jam DESC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Tarif Kendaraan - Terminal Giwangan</title>
    <!-- Font Modern: Plus Jakarta Sans & Space Grotesk -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        body { 
            background: #0d0614; 
            color: #ffffff; 
            padding: 40px 20px; 
            min-height: 100vh; 
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
            background: rgba(236, 72, 153, 0.35); /* Hot Pink Glow */
            top: -10%;
            left: -5%;
        }

        .orb-2 {
            width: 450px;
            height: 450px;
            background: rgba(168, 85, 247, 0.3); /* Neon Purple Glow */
            bottom: -10%;
            right: -5%;
            animation-delay: -5s;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1) translate(0, 0); opacity: 0.6; }
            100% { transform: scale(1.15) translate(20px, -20px); opacity: 0.85; }
        }
        
        /* --- HEADER PANEL --- */
        .top-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            max-width: 1100px; 
            margin: 0 auto 25px auto; 
            position: relative;
            z-index: 10;
        }

        .top-header h2 { 
            font-family: 'Space Grotesk', sans-serif;
            font-size: 26px; 
            font-weight: 800; 
            background: linear-gradient(135deg, #FFFFFF 30%, #F472B6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .top-header p { 
            font-size: 13px; 
            color: #fbcfe8; 
            margin-top: 4px; 
            opacity: 0.8;
        }

        .nav-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .nav-item { 
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
            display: inline-flex;
            align-items: center;
        }

        .nav-item:hover {
            background: rgba(236, 72, 153, 0.25);
            border-color: rgba(236, 72, 153, 0.7);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(236, 72, 153, 0.4);
            transform: translateY(-2px);
        }

        .btn-back { 
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

        .btn-back:hover {
            background: rgba(236, 72, 153, 0.25);
            border-color: rgba(236, 72, 153, 0.7);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(236, 72, 153, 0.4);
            transform: translateX(-3px);
        }

        /* --- NOTIFICATION MESSAGES --- */
        .alert-container {
            max-width: 1100px;
            margin: 0 auto 20px auto;
            position: relative;
            z-index: 10;
        }

        .alert {
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .alert-success {
            background: rgba(74, 222, 128, 0.15);
            border: 1px solid rgba(74, 222, 128, 0.4);
            color: #86efac;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.2);
        }

        .alert-danger {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.4);
            color: #fca5a5;
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.2);
        }

        /* --- LAYOUT GRID DUA KOLOM --- */
        .container { 
            display: grid; 
            grid-template-columns: 340px 1fr; 
            gap: 24px; 
            max-width: 1100px; 
            margin: 0 auto; 
            position: relative;
            z-index: 10;
        }

        /* --- GLASS CARDS --- */
        .card-panel { 
            background: rgba(23, 11, 35, 0.65); 
            border: 1.5px solid rgba(244, 114, 182, 0.25); 
            border-radius: 22px; 
            padding: 28px; 
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease;
            height: fit-content;
        }

        .card-title { 
            font-family: 'Space Grotesk', sans-serif;
            font-size: 17px; 
            font-weight: 700; 
            color: #f472b6; 
            margin-bottom: 22px; 
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* --- FORM STYLING --- */
        .form-group { margin-bottom: 18px; }
        
        .form-group label { 
            display: block; 
            font-size: 12px; 
            color: #fbcfe8; 
            margin-bottom: 8px; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input { 
            width: 100%; 
            padding: 12px 14px; 
            background: rgba(13, 6, 20, 0.7); 
            border: 1px solid rgba(244, 114, 182, 0.25); 
            border-radius: 12px; 
            color: #fff; 
            font-size: 13px; 
            outline: none; 
            transition: all 0.3s ease;
        }

        .form-group input::placeholder { color: #831843; }

        .form-group input:focus { 
            border-color: #ec4899; 
            background: rgba(13, 6, 20, 0.95);
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.35);
        }

        .btn-submit { 
            width: 100%; 
            padding: 14px; 
            background: linear-gradient(135deg, #fb7185 0%, #ec4899 50%, #a855f7 100%); 
            color: white; 
            border: none; 
            border-radius: 12px; 
            font-weight: 800; 
            font-size: 14px; 
            cursor: pointer; 
            margin-top: 10px; 
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.4);
            transition: all 0.3s ease;
        }

        .btn-submit:hover { 
            box-shadow: 0 12px 35px rgba(236, 72, 153, 0.7);
            transform: translateY(-2px);
        }

        .btn-cancel {
            display: block;
            text-align: center;
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            background: rgba(255, 255, 255, 0.08);
            color: #fbcfe8;
            border-radius: 12px;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
        }

        /* --- TABLE STYLING --- */
        .table-responsive { overflow-x: auto; }

        .custom-table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        
        .custom-table th { 
            padding: 14px 12px; 
            color: #fbcfe8; 
            border-bottom: 1px solid rgba(244, 114, 182, 0.25); 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 11px;
        }

        .custom-table td { 
            padding: 14px 12px; 
            border-bottom: 1px solid rgba(244, 114, 182, 0.1); 
            color: #fce7f3;
        }

        .custom-table tr:hover td {
            background: rgba(236, 72, 153, 0.05);
        }

        /* HIGHLIGHT BARIS YANG SEDANG DI-EDIT */
        .row-editing td {
            background: rgba(236, 72, 153, 0.2) !important;
            border-bottom: 1px solid rgba(236, 72, 153, 0.6) !important;
        }

        .price-tag { 
            color: #4ade80; 
            font-weight: 700; 
            text-shadow: 0 0 10px rgba(74, 222, 128, 0.3);
        }

        /* --- STYLING AKSI BUTTONS --- */
        .action-btns {
            display: flex;
            gap: 6px;
            justify-content: center;
        }

        .btn-edit {
            color: #38bdf8;
            text-decoration: none;
            font-size: 12px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 8px;
            background: rgba(56, 189, 248, 0.12);
            border: 1px solid rgba(56, 189, 248, 0.3);
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
            position: relative;
            z-index: 11;
        }

        .btn-edit:hover {
            background: #38bdf8;
            color: #ffffff;
            box-shadow: 0 0 15px rgba(56, 189, 248, 0.5);
            transform: scale(1.05);
        }

        .btn-delete { 
            color: #f43f5e; 
            text-decoration: none; 
            font-size: 12px; 
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 8px;
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.3);
            transition: all 0.3s ease;
            display: inline-block;
            cursor: pointer;
            position: relative;
            z-index: 11;
        }

        .btn-delete:hover {
            background: #f43f5e;
            color: #ffffff;
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.5);
            transform: scale(1.05);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 850px) {
            .container { grid-template-columns: 1fr; }
            .top-header { flex-direction: column; align-items: flex-start; gap: 15px; }
        }
    </style>
</head>
<body>

    <!-- NEON PINK & PURPLE ORBS -->
    <div class="neon-orb orb-1"></div>
    <div class="neon-orb orb-2"></div>

    <!-- HEADER PANEL -->
    <div class="top-header">
        <div>
            <h2>Kelola Data Tarif Kendaraan</h2>
            <p>Login Sebagai: <strong><?= htmlspecialchars($_SESSION['nama'] ?? $_SESSION['nama_lengkap'] ?? $_SESSION['username']); ?></strong></p>
        </div>
        <div class="nav-actions">
            <a href="kelola_tarif.php" class="nav-item">
                <span>💰 Kelola Tarif Kendaraan</span>
            </a>
            <a href="dashboard.php" class="btn-back">◄ Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- NOTIFIKASI STATUS -->
    <div class="alert-container">
        <?php if(isset($_GET['status']) && $_GET['status'] == 'sukses'): ?>
            <div class="alert alert-success">✅ Tarif baru berhasil ditambahkan!</div>
        <?php endif; ?>
        <?php if(isset($_GET['status']) && $_GET['status'] == 'edit_sukses'): ?>
            <div class="alert alert-success">✏️ Data tarif berhasil diperbarui!</div>
        <?php endif; ?>
        <?php if(isset($_GET['status']) && $_GET['status'] == 'hapus_sukses'): ?>
            <div class="alert alert-success">🗑️ Data tarif berhasil dihapus dari sistem!</div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div class="alert alert-danger">⚠️ <?= $error; ?></div>
        <?php endif; ?>
    </div>

    <!-- LAYOUT UTAMA -->
    <div class="container">
        
        <!-- KOLOM KIRI: FORM INPUT / EDIT -->
        <div class="card-panel">
            <div class="card-title">
                <?= $edit_mode ? '✏️ Edit Tarif Kendaraan' : '✨ Tambah Tarif Baru'; ?>
            </div>
            <form action="kelola_tarif.php" method="POST">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id_tarif" value="<?= htmlspecialchars($id_edit); ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="jenis_kendaraan">Jenis Kendaraan</label>
                    <input type="text" id="jenis_kendaraan" name="jenis_kendaraan" value="<?= htmlspecialchars($jenis_kendaraan_edit); ?>" placeholder="Contoh: Mobil, Motor, Truk" required autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="tarif_per_jam">Tarif per Jam (Rp)</label>
                    <input type="number" id="tarif_per_jam" name="tarif_per_jam" value="<?= htmlspecialchars($tarif_per_jam_edit); ?>" placeholder="Contoh: 3000" required>
                </div>

                <div class="form-group">
                    <label for="tarif_maksimal">Tarif Maksimal Per Hari (Rp)</label>
                    <input type="number" id="tarif_maksimal" name="tarif_maksimal" value="<?= htmlspecialchars($tarif_maksimal_edit); ?>" placeholder="Contoh: 20000" required>
                </div>

                <button type="submit" name="simpan_tarif" class="btn-submit">
                    <?= $edit_mode ? 'Update Tarif' : 'Simpan Tarif'; ?>
                </button>
                
                <?php if ($edit_mode): ?>
                    <a href="kelola_tarif.php" class="btn-cancel">Batal Edit</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- KOLOM KANAN: TABEL DAFTAR TARIF -->
        <div class="card-panel">
            <div class="card-title" style="color: #ffffff;">📋 Daftar Tarif Saat Ini</div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Jenis Kendaraan</th>
                            <th>Tarif / Jam</th>
                            <th>Tarif Maksimal</th>
                            <th style="text-align: center; width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        if ($list_tarif && mysqli_num_rows($list_tarif) > 0):
                            while($t = mysqli_fetch_assoc($list_tarif)): 
                                $id_row = $t['id_tarif'] ?? $t['id'] ?? '';
                                $max_tarif = isset($t['tarif_maksimal']) ? $t['tarif_maksimal'] : 0;
                                $is_currently_edited = ($edit_mode && $id_edit == $id_row);
                        ?>
                        <tr class="<?= $is_currently_edited ? 'row-editing' : ''; ?>">
                            <td><?= $no++; ?></td>
                            <td><strong style="color: #f472b6;"><?= htmlspecialchars($t['jenis_kendaraan']); ?></strong></td>
                            <td class="price-tag">Rp <?= number_format($t['tarif_per_jam'], 0, ',', '.'); ?></td>
                            <td>Rp <?= number_format($max_tarif, 0, ',', '.'); ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="kelola_tarif.php?edit=<?= $id_row; ?>" class="btn-edit">Edit</a>
                                    <a href="kelola_tarif.php?hapus=<?= $id_row; ?>" class="btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus tarif ini?')">Hapus</a>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #fbcfe8; opacity: 0.6; padding: 20px;">
                                Belum ada data tarif yang tersimpan.
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