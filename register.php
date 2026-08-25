<?php
session_start();
include 'koneksi.php';

$pesan_error = '';
$pesan_sukses = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil data dari form & membersihkan input
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $nama_lengkap = mysqli_real_escape_string($conn, trim($_POST['nama_lengkap']));
    $no_hp = mysqli_real_escape_string($conn, trim($_POST['no_hp']));
    $password = $_POST['password'];
    $konfirmasi_password = $_POST['konfirmasi_password'];
    
    // Default role untuk pendaftaran reservasi adalah 'user'
    $role = isset($_POST['role']) ? mysqli_real_escape_string($conn, $_POST['role']) : 'user';

    // Validasi
    if (empty($username) || empty($nama_lengkap) || empty($no_hp) || empty($password)) {
        $pesan_error = "Semua kolom wajib diisi!";
    } elseif ($password !== $konfirmasi_password) {
        $pesan_error = "Konfirmasi password tidak cocok!";
    } else {
        // 1. Cek apakah username sudah pernah terdaftar di database
        $cek_user = mysqli_query($conn, "SELECT id_user FROM tb_user WHERE username = '$username'");
        
        if (mysqli_num_rows($cek_user) > 0) {
            $pesan_error = "Username '$username' sudah terdaftar, silakan pakai username lain!";
        } else {
            // 2. Hash/enkripsi password
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // 3. Simpan data langsung ke database MySQL
            $query_register = "INSERT INTO tb_user (username, password, nama_lengkap, no_hp, role) 
                               VALUES ('$username', '$password_hashed', '$nama_lengkap', '$no_hp', '$role')";
            
            if (mysqli_query($conn, $query_register)) {
                // Berhasil disimpan, alihkan ke login setelah 2 detik
                $pesan_sukses = "Pendaftaran berhasil! Mengalihkan ke halaman login...";
                header("refresh:2;url=login.php");
            } else {
                $pesan_error = "Gagal menyimpan data ke database: " . mysqli_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - Parkir System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }

        body { 
            background: #0b0512; 
            color: #ffffff; 
            min-height: 100vh; 
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px 20px; 
            position: relative;
            overflow-x: hidden;
        }

        .neon-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(140px);
            z-index: 1;
            pointer-events: none;
        }

        .orb-1 { width: 400px; height: 400px; background: rgba(236, 72, 153, 0.25); top: -5%; left: -5%; }
        .orb-2 { width: 380px; height: 380px; background: rgba(168, 85, 247, 0.25); bottom: -5%; right: -5%; }

        .card-register { 
            width: 100%;
            max-width: 440px;
            background: rgba(18, 10, 30, 0.85); 
            border: 1.5px solid rgba(244, 114, 182, 0.18); 
            border-radius: 28px; 
            padding: 36px 30px; 
            backdrop-filter: blur(25px);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.8);
            position: relative;
            z-index: 10;
        }

        .logo-box {
            width: 52px;
            height: 52px;
            background: rgba(236, 72, 153, 0.15);
            border: 1.5px solid #ec4899;
            border-radius: 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0 auto 16px auto;
            color: #ec4899;
            font-size: 22px;
            font-weight: 800;
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.3);
        }

        .card-register h2 { 
            font-family: 'Space Grotesk', sans-serif;
            font-size: 22px; 
            font-weight: 800; 
            text-align: center;
            letter-spacing: 1px;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .card-register p.subtitle { 
            font-size: 11px; 
            color: #d1d5db; 
            text-align: center; 
            margin-bottom: 24px; 
            letter-spacing: 1.5px;
            font-weight: 700;
            opacity: 0.8;
        }

        .form-group { margin-bottom: 16px; }

        .form-group label { 
            display: block; 
            font-size: 11px; 
            font-weight: 700;
            color: #fbcfe8; 
            margin-bottom: 6px; 
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            color: #9ca3af;
            font-size: 14px;
            pointer-events: none;
            z-index: 2;
        }

        .input-wrapper input,
        .input-wrapper select { 
            width: 100%; 
            padding: 12px 16px 12px 44px; 
            background: rgba(10, 5, 18, 0.7); 
            border: 1px solid rgba(255, 255, 255, 0.12); 
            border-radius: 12px; 
            color: #ffffff; 
            font-size: 13px; 
            outline: none; 
            transition: all 0.3s ease;
        }

        .input-wrapper select {
            cursor: pointer;
            appearance: none;
            -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        .input-wrapper select option {
            background-color: #120a1e;
            color: #ffffff;
        }

        .input-wrapper input:focus,
        .input-wrapper select:focus {
            border-color: #ec4899;
            box-shadow: 0 0 12px rgba(236, 72, 153, 0.3);
            background-color: rgba(18, 10, 30, 0.9);
        }

        .btn-register { 
            width: 100%; 
            padding: 13px; 
            background: linear-gradient(90deg, #ec4899 0%, #a855f7 100%); 
            color: #ffffff; 
            font-weight: 800; 
            border: none; 
            border-radius: 12px; 
            font-size: 13px; 
            cursor: pointer; 
            transition: all 0.3s ease; 
            margin-top: 10px; 
            letter-spacing: 1px;
            text-transform: uppercase;
            box-shadow: 0 0 20px rgba(236, 72, 153, 0.4);
        }

        .btn-register:hover { 
            transform: translateY(-2px);
            box-shadow: 0 0 30px rgba(236, 72, 153, 0.7);
        }

        .alert-box {
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 12px;
            margin-bottom: 16px;
            text-align: center;
        }
        .alert-error { background: rgba(239, 68, 68, 0.2); border: 1px solid #ef4444; color: #fca5a5; }
        .alert-success { background: rgba(34, 197, 94, 0.2); border: 1px solid #22c55e; color: #86efac; }

        .footer-links {
            margin-top: 22px;
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
        }

        .footer-links a {
            color: #ec4899;
            text-decoration: none;
            font-weight: 700;
        }

        .footer-links a:hover { text-decoration: underline; }

        .back-home {
            display: inline-block;
            margin-top: 14px;
            color: #9ca3af;
            font-size: 12px;
            text-decoration: none;
        }

        .back-home:hover { color: #ffffff; }
    </style>
</head>
<body>

    <div class="neon-orb orb-1"></div>
    <div class="neon-orb orb-2"></div>

    <div class="card-register">
        <div class="logo-box">P</div>
        <h2>DAFTAR AKUN</h2>
        <p class="subtitle">TERMINAL GIWANGAN PARKING CENTER</p>

        <?php if ($pesan_error): ?>
            <div class="alert-box alert-error">❌ <?= $pesan_error; ?></div>
        <?php endif; ?>

        <?php if ($pesan_sukses): ?>
            <div class="alert-box alert-success">✅ <?= $pesan_sukses; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="nama_lengkap">Nama Lengkap</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-id-card"></i>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan nama lengkap" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Masukkan username" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="no_hp">Nomor WhatsApp / HP</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-phone"></i>
                    <input type="text" id="no_hp" name="no_hp" placeholder="Contoh: 081234567890" required autocomplete="off">
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group">
                <label for="konfirmasi_password">Konfirmasi Password</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-key"></i>
                    <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group">
                <label for="role">Akses / Role</label>
                <div class="input-wrapper">
                    <i class="fa-solid fa-user-shield"></i>
                    <select id="role" name="role" required>
                        <option value="user" selected>Pengendara (Reservasi)</option>
                        <option value="petugas">Petugas Lapangan</option>
                        <option value="admin">Administrator</option>
                        <option value="owner">Owner / Eksekutif</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-register">DAFTAR SEKARANG</button>
        </form>

        <div class="footer-links">
            Sudah memiliki akun? <a href="login.php">Masuk Sekarang</a>
            <br>
            <a href="index.php" class="back-home"><i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Utama</a>
        </div>
    </div>

</body>
</html>