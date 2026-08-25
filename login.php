<?php
session_start();

// 1. Koneksi Database
$conn = new mysqli("localhost", "root", "", "karcisparkirrr");

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Koneksi database gagal: ' . $conn->connect_error]));
}

// 2. PROSES LOGIN VIA AJAX
if (isset($_POST['login_ajax'])) {
    header('Content-Type: application/json');

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Lengkapi username dan password!']);
        exit();
    }

    $stmt = $conn->prepare("SELECT * FROM tb_user WHERE username = ?");
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_role_db = strtolower(trim($user['role'] ?? 'user'));

            if (password_verify($password, $user['password']) || $password === $user['password']) {
                $id_user = $user['id_user'] ?? $user['id'] ?? 1;

                $_SESSION['user_id']       = $id_user;
                $_SESSION['username']      = $user['username'];
                $_SESSION['role']          = $user_role_db;
                $_SESSION['nama']          = $user['nama_lengkap'] ?? $user['nama'] ?? $user['username'];
                $_SESSION['login_success'] = true;

                // =========================================================================
                // CATAT LOG AKTIVITAS KE DATABASE SAAT LOGIN BERHASIL
                // =========================================================================
                $nama_user = $_SESSION['nama'];
                $aktivitas = "User " . $user['username'] . " (" . strtoupper($user_role_db) . ") berhasil login ke dalam sistem.";
                
                $log_stmt = $conn->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_log) VALUES (?, ?, NOW())");
                if ($log_stmt) {
                    $log_stmt->bind_param("is", $id_user, $aktivitas);
                    $log_stmt->execute();
                    $log_stmt->close();
                }
                // =========================================================================

                // Set Pesan Notifikasi Berdasarkan Role
                if ($user_role_db === 'admin') {
                    $pesan_modal = "Selamat datang Administrator $nama_user! Anda berhasil masuk ke sistem.";
                    $redirect    = 'dashboard.php';
                } elseif ($user_role_db === 'petugas') {
                    $pesan_modal = "Selamat bertugas $nama_user! Silakan kelola pintu masuk/keluar parkir.";
                    $redirect    = 'dashboard.php';
                } elseif ($user_role_db === 'owner') {
                    $pesan_modal = "Selamat datang Owner $nama_user! Laporan dan aktivitas sistem siap ditinjau.";
                    $redirect    = 'dashboard.php';
                } else {
                    $pesan_modal = "Selamat datang $nama_user, Anda berhasil masuk ke sistem.";
                    $redirect    = 'reservasi.php';
                }

                echo json_encode([
                    'status'    => 'success',
                    'message'   => 'Login Berhasil!',
                    'detail'    => $pesan_modal,
                    'role'      => $user_role_db,
                    'redirect'  => $redirect
                ]);
                exit();
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Password salah!']);
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Username tidak terdaftar!']);
            exit();
        }
        $stmt->close();
    }
}

// 3. PROSES REGISTRASI AKUN BARU
$message = "";
$message_type = "";

if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $message_type = $_SESSION['flash_type'];
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
}

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama     = $_POST['nama_lengkap'];
    $role     = 'user';

    $check_user = $conn->query("SELECT * FROM tb_user WHERE username = '$username'");

    if ($check_user && $check_user->num_rows > 0) {
        $message = "Registrasi Gagal! Username sudah digunakan.";
        $message_type = "error";
    } else {
        $query = "INSERT INTO tb_user (username, password, nama_lengkap, role) VALUES ('$username', '$password', '$nama', '$role')";

        if ($conn->query($query)) {
            $message = "Registrasi Berhasil! Silakan masuk menggunakan akun Anda.";
            $message_type = "success";
        } else {
            $message = "Registrasi Gagal! Terjadi kesalahan pada sistem: " . $conn->error;
            $message_type = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Registrasi - Parkir System Terminal Giwangan</title>

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background-color: #0d041a;
        }

        .night-scene {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes moveRoad {
            0% { background-position-x: 0px; }
            100% { background-position-x: -800px; }
        }

        @keyframes busBounce {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-3px); }
        }

        @keyframes rotateWheel {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .city-silhouettes {
            position: absolute;
            bottom: 70px;
            width: 100%;
            height: 120px;
            background: repeating-linear-gradient(
                90deg,
                #18082e 0px, #18082e 40px,
                transparent 40px, transparent 50px,
                #1e0a3a 50px, #1e0a3a 110px,
                transparent 110px, transparent 125px
            );
            opacity: 0.6;
        }

        .road {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 75px;
            background: #080212;
            border-top: 2px solid #2e1254;
        }

        .road-line {
            position: absolute;
            top: 50%;
            width: 100%;
            height: 5px;
            background: repeating-linear-gradient(
                90deg,
                #ff2a85 0px, #ff2a85 40px,
                transparent 40px, transparent 80px
            );
            animation: moveRoad 1.2s linear infinite;
            box-shadow: 0 0 10px #ff2a85;
        }

        .bus-container {
            position: absolute;
            bottom: 35px;
            left: 12%;
            width: 200px;
            animation: busBounce 0.4s ease-in-out infinite;
            filter: drop-shadow(0 0 15px rgba(255, 42, 133, 0.4));
        }

        .headlight-glow {
            position: absolute;
            bottom: 38px;
            left: calc(12% + 180px);
            width: 150px;
            height: 35px;
            background: linear-gradient(90deg, rgba(0, 230, 118, 0.5) 0%, transparent 100%);
            clip-path: polygon(0 30%, 100% 0%, 100% 100%, 0 70%);
            pointer-events: none;
        }

        .wheel-spin {
            transform-origin: center;
            animation: rotateWheel 0.4s linear infinite;
        }

        /* Pop-up Modal Animation */
        @keyframes modalZoom {
            0% { transform: scale(0.8); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .animate-modal {
            animation: modalZoom 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-4 relative overflow-hidden">

    <!-- SCENE ANIMASI 2D BUS MALAM -->
    <div class="night-scene">
        <div class="absolute -top-30 -left-30 w-96 h-96 bg-[#ff2a85]/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-20 -right-20 w-96 h-96 bg-[#9d4edd]/25 rounded-full blur-3xl"></div>
        <div class="city-silhouettes"></div>
        <div class="road">
            <div class="road-line"></div>
        </div>
        <div class="headlight-glow"></div>
        <div class="bus-container">
            <svg viewBox="0 0 240 100" xmlns="http://www.w3.org/2000/svg">
                <rect x="10" y="20" width="190" height="55" rx="12" fill="#1c0b36" stroke="#ff2a85" stroke-width="3"/>
                <path d="M 20 20 L 190 20" stroke="#9d4edd" stroke-width="4" stroke-linecap="round"/>
                <rect x="25" y="30" width="35" height="20" rx="4" fill="#9d4edd" opacity="0.8"/>
                <rect x="68" y="30" width="35" height="20" rx="4" fill="#9d4edd" opacity="0.8"/>
                <rect x="111" y="30" width="35" height="20" rx="4" fill="#9d4edd" opacity="0.8"/>
                <path d="M 154 30 L 192 30 C 196 30 198 34 196 40 L 192 50 L 154 50 Z" fill="#00e676" opacity="0.85"/>
                <circle cx="196" cy="62" r="4" fill="#00e676" />
                <rect x="8" y="55" width="4" height="12" rx="2" fill="#ff2a85" />
                <line x1="15" y1="62" x2="185" y2="62" stroke="#ff2a85" stroke-width="2.5" />
                <g transform="translate(155, 75)">
                    <circle cx="0" cy="0" r="12" fill="#0d041a" stroke="#ff2a85" stroke-width="3"/>
                    <circle cx="0" cy="0" r="5" fill="#9d4edd" class="wheel-spin"/>
                    <line x1="-5" y1="0" x2="5" y2="0" stroke="#ffffff" stroke-width="1.5" class="wheel-spin"/>
                </g>
                <g transform="translate(50, 75)">
                    <circle cx="0" cy="0" r="12" fill="#0d041a" stroke="#ff2a85" stroke-width="3"/>
                    <circle cx="0" cy="0" r="5" fill="#9d4edd" class="wheel-spin"/>
                    <line x1="-5" y1="0" x2="5" y2="0" stroke="#ffffff" stroke-width="1.5" class="wheel-spin"/>
                </g>
            </svg>
        </div>
    </div>

    <!-- KARTU LOGIN UTAMA -->
    <div class="w-full max-w-md bg-[#1c0b36]/85 backdrop-blur-md border border-[#2e1254] rounded-3xl p-8 shadow-[0_0_50px_rgba(0,0,0,0.85)] z-10 relative">
        
        <div class="text-center mb-6">
            <div class="w-14 h-14 bg-[#ff2a85]/15 border border-[#ff2a85]/40 text-[#ff2a85] rounded-2xl flex items-center justify-center mx-auto mb-3 text-2xl font-black shadow-[0_0_20px_rgba(255,42,133,0.4)]">
                <i class="fa-solid fa-bus"></i>
            </div>
            <h1 class="text-2xl font-extrabold tracking-wide uppercase text-white">PARKIR SYSTEM</h1>
            <p class="text-xs text-[#a093b5] mt-1 font-semibold flex items-center justify-center gap-1.5">
                <i class="fa-solid fa-location-dot text-[#ff2a85]"></i> TERMINAL GIWANGAN PARKING CENTER
            </p>
        </div>

        <div id="alert-box" class="hidden mb-6 p-4 rounded-xl text-xs font-semibold flex items-center justify-between border transition-all duration-300">
            <div class="flex items-center gap-3">
                <i id="alert-icon" class="fa-solid text-base"></i>
                <span id="alert-text"></span>
            </div>
            <button type="button" onclick="document.getElementById('alert-box').classList.add('hidden')" class="hover:opacity-70 ml-2">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="mb-6 p-4 rounded-xl text-xs font-semibold flex items-center justify-between border <?php echo $message_type === 'success' ? 'bg-[#00e676]/10 border-[#00e676]/40 text-[#00e676] shadow-[0_0_15px_rgba(0,230,118,0.15)]' : 'bg-[#ff2a85]/10 border-[#ff2a85]/40 text-[#ff2a85] shadow-[0_0_15px_rgba(255,42,133,0.15)]'; ?>">
                <div class="flex items-center gap-3">
                    <i class="fa-solid <?php echo $message_type === 'success' ? 'fa-circle-check text-base' : 'fa-triangle-exclamation text-base'; ?>"></i>
                    <span><?php echo $message; ?></span>
                </div>
            </div>
        <?php endif; ?>

        <!-- FORM LOGIN -->
        <div id="login-section">
            <form id="form-login" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" id="login-username" name="username" required placeholder="Masukkan username" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition shadow-inner">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" id="login-password" name="password" required placeholder="••••••••" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition shadow-inner">
                    </div>
                </div>

                <button type="submit" id="btn-login" class="w-full bg-gradient-to-r from-[#ff2a85] to-[#9d4edd] hover:opacity-90 text-white font-bold text-sm py-3.5 rounded-xl transition-all duration-300 shadow-[0_0_20px_rgba(255,42,133,0.4)] mt-2 flex items-center justify-center gap-2">
                    <span id="btn-text">MASUK SEKARANG</span>
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/10 text-xs text-[#a093b5]">
                Belum memiliki akun? 
                <button type="button" onclick="toggleForm()" class="text-[#ff2a85] font-bold hover:underline ml-1">Daftar Akun Baru</button>
            </div>
        </div>

        <!-- FORM REGISTRASI -->
        <div id="register-section" class="hidden">
            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Nama Lengkap</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                            <i class="fa-solid fa-id-card"></i>
                        </span>
                        <input type="text" name="nama_lengkap" required placeholder="Nama Lengkap Anda" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition shadow-inner">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                            <i class="fa-solid fa-at"></i>
                        </span>
                        <input type="text" name="username" required placeholder="Buat username" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition shadow-inner">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-[#a093b5] uppercase mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-[#a093b5]">
                            <i class="fa-solid fa-key"></i>
                        </span>
                        <input type="password" name="password" required placeholder="Buat password" class="w-full bg-[#130826]/90 border border-[#2e1254] focus:border-[#ff2a85] text-white text-sm rounded-xl pl-11 pr-4 py-3 outline-none transition shadow-inner">
                    </div>
                </div>

                <button type="submit" name="register" class="w-full bg-gradient-to-r from-[#9d4edd] to-[#ff2a85] hover:opacity-90 text-white font-bold text-sm py-3.5 rounded-xl transition shadow-[0_0_20px_rgba(157,78,221,0.4)] mt-2">
                    DAFTAR AKUN
                </button>
            </form>

            <div class="text-center mt-6 pt-6 border-t border-white/10 text-xs text-[#a093b5]">
                Sudah punya akun? 
                <button type="button" onclick="toggleForm()" class="text-[#ff2a85] font-bold hover:underline ml-1">Masuk Kembali</button>
            </div>
        </div>

        <div class="text-center mt-6">
            <a href="index.php" class="text-xs text-[#a093b5] hover:text-white transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Halaman Utama
            </a>
        </div>
    </div>

    <!-- POP-UP MODAL NOTIFIKASI LOGIN BERHASIL -->
    <div id="modal-success" class="fixed inset-0 bg-black/75 backdrop-blur-md z-50 flex items-center justify-center p-4 hidden">
        <div class="bg-[#1c2430] border border-[#2e3e52] rounded-3xl p-8 max-w-sm w-full text-center shadow-[0_0_50px_rgba(0,0,0,0.9)] animate-modal">
            <!-- IKON CENTANG MELINGKAR -->
            <div class="w-20 h-20 rounded-full border-2 border-slate-300/60 flex items-center justify-center mx-auto mb-5 shadow-[0_0_15px_rgba(255,255,255,0.1)]">
                <i class="fa-solid fa-check text-4xl text-white"></i>
            </div>
            <!-- JUDUL POPUP -->
            <h3 id="modal-title" class="text-2xl font-bold text-white mb-2">Login Berhasil!</h3>
            <!-- PESAN DETAIL ROLE -->
            <p id="modal-desc" class="text-sm text-slate-300 font-medium leading-relaxed">Selamat datang, Anda berhasil masuk ke sistem.</p>
        </div>
    </div>

    <!-- JAVASCRIPT AJAX LOGIN & POPUP NOTIFIKASI -->
    <script>
        function toggleForm() {
            document.getElementById('login-section').classList.toggle('hidden');
            document.getElementById('register-section').classList.toggle('hidden');
        }

        document.getElementById('form-login').addEventListener('submit', function(e) {
            e.preventDefault();

            const alertBox  = document.getElementById('alert-box');
            const alertIcon = document.getElementById('alert-icon');
            const alertText = document.getElementById('alert-text');
            const btnLogin  = document.getElementById('btn-login');

            const modalSuccess = document.getElementById('modal-success');
            const modalTitle   = document.getElementById('modal-title');
            const modalDesc    = document.getElementById('modal-desc');

            const formData = new FormData();
            formData.append('login_ajax', '1');
            formData.append('username', document.getElementById('login-username').value);
            formData.append('password', document.getElementById('login-password').value);

            btnLogin.disabled = true;
            btnLogin.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-base"></i> <span>MEMPROSES...</span>';

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    // 1. UPDATE TOMBOL LOGIN
                    btnLogin.className = "w-full bg-[#00e676] text-black font-extrabold text-sm py-3.5 rounded-xl transition-all duration-300 shadow-[0_0_25px_rgba(0,230,118,0.6)] mt-2 flex items-center justify-center gap-2 transform scale-105";
                    btnLogin.innerHTML = '<i class="fa-solid fa-circle-check text-lg"></i> <span>BERHASIL MASUK!</span>';

                    alertBox.classList.add('hidden');

                    // 2. TAMPILKAN POP-UP MODAL SEPERTI GAMBAR
                    modalTitle.innerText = data.message || "Login Berhasil!";
                    modalDesc.innerText  = data.detail  || "Selamat datang, Anda berhasil masuk ke sistem.";
                    modalSuccess.classList.remove('hidden');

                    // 3. EFEK SUARA BEEP
                    playBeepSound();

                    // 4. REDIRECT OTOMATIS SETELAH 1.5 DETIK
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 1500);

                } else {
                    btnLogin.disabled = false;
                    btnLogin.className = "w-full bg-gradient-to-r from-[#ff2a85] to-[#9d4edd] hover:opacity-90 text-white font-bold text-sm py-3.5 rounded-xl transition-all duration-300 shadow-[0_0_20px_rgba(255,42,133,0.4)] mt-2 flex items-center justify-center gap-2";
                    btnLogin.innerHTML = '<span>MASUK SEKARANG</span>';

                    alertBox.classList.remove('hidden', 'bg-[#00e676]/10', 'border-[#00e676]/40', 'text-[#00e676]');
                    alertBox.classList.add('bg-[#ff2a85]/10', 'border-[#ff2a85]/40', 'text-[#ff2a85]', 'shadow-[0_0_15px_rgba(255,42,133,0.15)]');
                    alertIcon.className = 'fa-solid fa-triangle-exclamation text-base';
                    alertText.innerText = data.message;
                }
            })
            .catch(error => {
                btnLogin.disabled = false;
                btnLogin.className = "w-full bg-gradient-to-r from-[#ff2a85] to-[#9d4edd] hover:opacity-90 text-white font-bold text-sm py-3.5 rounded-xl transition-all duration-300 shadow-[0_0_20px_rgba(255,42,133,0.4)] mt-2 flex items-center justify-center gap-2";
                btnLogin.innerHTML = '<span>MASUK SEKARANG</span>';
            });
        });

        // FUNGSI SUARA BEEP SAAT LOGIN BERHASIL
        function playBeepSound() {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = audioCtx.createOscillator();
                const gain = audioCtx.createGain();

                osc.type = 'sine';
                osc.frequency.setValueAtTime(1046.50, audioCtx.currentTime); // C6 Note

                gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.3);

                osc.connect(gain);
                gain.connect(audioCtx.destination);

                osc.start();
                osc.stop(audioCtx.currentTime + 0.3);
            } catch (e) {
                console.log("Audio diblokir browser");
            }
        }
    </script>
</body>
</html>