<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Data dummy/default sesuai permintaan jika tabel di database belum terisi
$users_default = [
    ["id" => 1, "username" => "admin01", "nama_lengkap" => "Affan Admin", "role" => "ADMIN"],
    ["id" => 2, "username" => "petugas01", "nama_lengkap" => "Deswa Kasir", "role" => "PETUGAS"],
    ["id" => 3, "username" => "owner01", "nama_lengkap" => "Budi Santoso", "role" => "OWNER"],
];

// Proses Tambah User Baru
$pesan_sukses = "";
$pesan_error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_user'])) {
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $password     = md5($_POST['password']);
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $role         = mysqli_real_escape_string($conn, $_POST['role']);

    $query_insert = "INSERT INTO tb_user (username, password, nama_lengkap, role) 
                    VALUES ('$username', '$password', '$nama_lengkap', '$role')";
    
    if (mysqli_query($conn, $query_insert)) {
        $pesan_sukses = "User berhasil ditambahkan!";
    } else {
        $pesan_error = "Gagal menambahkan user!";
    }
}

// Ambil data user dari Database
$query_users = mysqli_query($conn, "SELECT * FROM tb_user ORDER BY id_user ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Data User - Terminal Giwangan</title>
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
        
        /* --- TOP HEADER --- */
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

        .btn-kembali { 
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

        .btn-kembali:hover {
            background: rgba(236, 72, 153, 0.25);
            border-color: rgba(236, 72, 153, 0.7);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(236, 72, 153, 0.4);
            transform: translateX(-3px);
        }

        /* --- LAYOUT CONTAINER --- */
        .container { 
            display: grid; 
            grid-template-columns: 340px 1fr; 
            gap: 24px; 
            max-width: 1100px; 
            margin: 0 auto; 
            position: relative;
            z-index: 10;
        } me

        /* --- GLASS CARDS --- */
        .card { 
            background: rgba(23, 11, 35, 0.65); 
            border: 1.5px solid rgba(244, 114, 182, 0.25); 
            border-radius: 22px; 
            padding: 28px; 
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6);
            transition: all 0.3s ease;
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

        /* --- FORM ELEMENTS --- */
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

        .form-group input, .form-group select { 
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

        .form-group input:focus, .form-group select:focus { 
            border-color: #ec4899; 
            background: rgba(13, 6, 20, 0.95);
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.35);
        }

        .form-group select option { 
            background: #170b23; 
            color: #fff; 
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

        /* --- NOTIFICATION MESSAGES --- */
        .alert-success {
            background: rgba(74, 222, 128, 0.15);
            border: 1px solid rgba(74, 222, 128, 0.4);
            color: #86efac;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 15px;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.2);
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.15);
            border: 1px solid rgba(244, 63, 94, 0.4);
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 15px;
            box-shadow: 0 0 15px rgba(244, 63, 94, 0.2);
        }

        /* --- TABLE STYLING --- */
        table { width: 100%; border-collapse: collapse; text-align: left; font-size: 13px; }
        
        th { 
            padding: 14px 12px; 
            color: #fbcfe8; 
            border-bottom: 1px solid rgba(244, 114, 182, 0.25); 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 11px;
        }

        td { 
            padding: 14px 12px; 
            border-bottom: 1px solid rgba(244, 114, 182, 0.1); 
            color: #fce7f3;
        }

        tr:hover td {
            background: rgba(236, 72, 153, 0.05);
        }

        /* --- ROLE BADGES --- */
        .badge { 
            padding: 4px 10px; 
            border-radius: 50px; 
            font-size: 10px; 
            font-weight: 800; 
            display: inline-block; 
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .badge-admin { 
            background: linear-gradient(135deg, #ec4899, #a855f7); 
            color: white; 
            box-shadow: 0 0 12px rgba(236, 72, 153, 0.4);
        }

        .badge-petugas { 
            background: linear-gradient(135deg, #f59e0b, #d97706); 
            color: white; 
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.4);
        }

        .badge-owner { 
            background: linear-gradient(135deg, #8b5cf6, #6366f1); 
            color: white; 
            box-shadow: 0 0 12px rgba(139, 92, 246, 0.4);
        }

        .btn-hapus { 
            color: #f43f5e; 
            text-decoration: none; 
            font-size: 12px; 
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 8px;
            background: rgba(244, 63, 94, 0.12);
            border: 1px solid rgba(244, 63, 94, 0.3);
            transition: all 0.3s ease;
        }

        .btn-hapus:hover {
            background: #f43f5e;
            color: #ffffff;
            box-shadow: 0 0 12px rgba(244, 63, 94, 0.5);
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

    <div class="top-header">
        <div>
            <h2>Kelola Data User</h2>
            <p>Login Sebagai: <strong><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? $_SESSION['username']); ?></strong></p>
        </div>
        <a href="dashboard.php" class="btn-kembali">◄ Kembali ke Dashboard</a>
    </div>

    <div class="container">
        <!-- FORM TAMBAH USER -->
        <div class="card">
            <div class="card-title">✨ Tambah User Baru</div>
            
            <?php if (!empty($pesan_sukses)): ?>
                <div class="alert-success">✓ <?= $pesan_sukses; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($pesan_error)): ?>
                <div class="alert-error">✕ <?= $pesan_error; ?></div>
            <?php endif; ?>

            <form action="" method="POST">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Contoh: petugas02" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Masukkan password" required>
                </div>
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" placeholder="Nama asli user" required autocomplete="off">
                </div>
                <div class="form-group">
                    <label>Role / Hak Akses</label>
                    <select name="role" required>
                        <option value="Admin">Admin</option>
                        <option value="Petugas">Petugas / Kasir</option>
                        <option value="Owner">Owner</option>
                    </select>
                </div>
                <button type="submit" name="tambah_user" class="btn-submit">Simpan User</button>
            </form>
        </div>

        <!-- TABEL DAFTAR USER -->
        <div class="card">
            <div class="card-title" style="color: #ffffff;">📋 Daftar User Saat Ini</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Username</th>
                        <th>Nama Lengkap</th>
                        <th>Role</th>
                        <th style="text-align: center;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if ($query_users && mysqli_num_rows($query_users) > 0):
                        while ($u = mysqli_fetch_assoc($query_users)): 
                            $role_class = 'badge-petugas';
                            $role = strtoupper($u['role']);
                            if ($role == 'ADMIN') $role_class = 'badge-admin';
                            if ($role == 'OWNER') $role_class = 'badge-owner';
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong style="color: #f472b6;"><?= htmlspecialchars($u['username']); ?></strong></td>
                            <td><?= htmlspecialchars($u['nama_lengkap']); ?></td>
                            <td><span class="badge <?= $role_class; ?>"><?= $role; ?></span></td>
                            <td style="text-align: center;"><a href="#" class="btn-hapus">Hapus</a></td>
                        </tr>
                    <?php 
                        endwhile; 
                    else:
                        // Menampilkan data bawaan (Affan, Deswa, Budi) jika database kosong
                        foreach ($users_default as $u):
                            $role_class = 'badge-petugas';
                            if ($u['role'] == 'ADMIN') $role_class = 'badge-admin';
                            if ($u['role'] == 'OWNER') $role_class = 'badge-owner';
                    ?>
                        <tr>
                            <td><?= $no++; ?></td>
                            <td><strong style="color: #f472b6;"><?= $u['username']; ?></strong></td>
                            <td><?= $u['nama_lengkap']; ?></td>
                            <td><span class="badge <?= $role_class; ?>"><?= $u['role']; ?></span></td>
                            <td style="text-align: center;"><a href="#" class="btn-hapus">Hapus</a></td>
                        </tr>
                    <?php 
                        endforeach;
                    endif; 
                    ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>