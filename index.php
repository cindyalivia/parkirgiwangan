<?php
// 1. Koneksi Database
$conn = new mysqli("localhost", "root", "", "karcisparkirrr");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// SIMPAN ULASAN JIKA ADA FORM DIKIRIM
if (isset($_POST['kirim_ulasan'])) {
    $nama = $conn->real_escape_string($_POST['nama_pelanggan']);
    $kategori = $conn->real_escape_string($_POST['kategori']);
    $rating = (int)$_POST['rating'];
    $komentar = $conn->real_escape_string($_POST['komentar']);

    // Cek/Buat tabel ulasan jika belum ada
    $conn->query("CREATE TABLE IF NOT EXISTS tb_ulasan (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nama VARCHAR(100),
        kategori VARCHAR(100),
        rating INT,
        komentar TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $conn->query("INSERT INTO tb_ulasan (nama, kategori, rating, komentar) VALUES ('$nama', '$kategori', $rating, '$komentar')");
    header("Location: index.php#section-ulasan");
    exit();
}

// 2. Ambil data area parkir dari database
$query_area = "SELECT * FROM tb_area_parkir";
$result_area = $conn->query($query_area);

// 3. Ambil data aktivitas log berdasarkan role untuk Grafik/Chart
$count_admin = 0; $count_petugas = 0; $count_owner = 0;
$query_chart = $conn->query("SELECT LOWER(role) as role_clean, COUNT(*) as total FROM tb_log_aktivitas LEFT JOIN tb_user ON tb_log_aktivitas.id_user = tb_user.id_user GROUP BY role");

if ($query_chart) {
    while ($r = $query_chart->fetch_assoc()) {
        $role_name = strtolower($r['role_clean'] ?? '');
        if (strpos($role_name, 'admin') !== false) $count_admin = (int)$r['total'];
        elseif (strpos($role_name, 'petugas') !== false) $count_petugas = (int)$r['total'];
        elseif (strpos($role_name, 'owner') !== false) $count_owner = (int)$r['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-PARKIR - TERMINAL GIWANGAN</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js CDN (Grafik) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f051d;
            scroll-behavior: smooth;
        }

        /* ================= ANIMASI KERETA API BERJALAN BERURUTAN ================= */
        @keyframes jalanKereta {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-120%); }
        }

        @keyframes rodaBerputar {
            from { transform: rotate(0deg); }
            to { transform: rotate(-360deg); }
        }

        @keyframes asapKereta {
            0% { opacity: 0.8; transform: scale(0.6) translateY(0); }
            100% { opacity: 0; transform: scale(1.8) translateY(-15px); }
        }

        .train-track {
            position: relative;
            background: rgba(28, 11, 54, 0.8);
            border-top: 2px solid #2e1254;
            border-bottom: 2px solid #ff2a85;
            overflow: hidden;
            height: 65px;
        }

        .train-track::before {
            content: '';
            position: absolute;
            bottom: 8px;
            left: 0;
            width: 100%;
            height: 3px;
            background: repeating-linear-gradient(90deg, #ff2a85 0px, #ff2a85 15px, transparent 15px, transparent 25px);
            opacity: 0.6;
        }

        .train-container {
            display: flex;
            align-items: flex-end;
            position: absolute;
            bottom: 10px;
            animation: jalanKereta 14s linear infinite;
            white-space: nowrap;
        }

        .wheel-spin { animation: rodaBerputar 0.5s linear infinite; }
        .smoke-puff { animation: asapKereta 1s ease-out infinite; }

        /* ================= ANIMASI PURE CSS KARTU ================= */
        @keyframes floatLoop {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        @keyframes pulseGlow {
            0%, 100% {
                box-shadow: 0 0 15px rgba(255, 42, 133, 0.2);
                border-color: rgba(46, 18, 84, 1);
            }
            50% {
                box-shadow: 0 0 25px rgba(255, 42, 133, 0.6);
                border-color: rgba(255, 42, 133, 0.8);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-card-loop {
            animation: floatLoop 4s ease-in-out infinite, pulseGlow 3s ease-in-out infinite;
        }

        .anim-entry {
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .card-area {
            transition: all 0.3s ease;
        }
        .card-area:hover {
            transform: translateY(-12px) scale(1.03) !important;
            box-shadow: 0 15px 35px rgba(255, 42, 133, 0.4) !important;
            border-color: #ff2a85 !important;
            z-index: 10;
        }

        /* ================= STYLING SECTION RATING & FORM ULASAN ================= */
        .rating-section {
            background: rgba(20, 10, 25, 0.6);
            border: 1px solid rgba(255, 51, 153, 0.2);
            border-radius: 16px;
            padding: 30px;
            margin-top: 40px;
            backdrop-filter: blur(10px);
        }
        .rating-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
            border-bottom: 1px solid rgba(255, 51, 153, 0.15);
            padding-bottom: 20px;
        }
        .score-number { font-size: 36px; font-weight: 800; color: #ff3399; }
        .stars-outer { color: #ffaa00; font-size: 18px; }
        
        .form-ulasan {
            background: rgba(25, 10, 30, 0.8);
            border: 1px dashed rgba(255, 51, 153, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; color: #a090a5; font-weight: 600; margin-bottom: 6px; text-transform: uppercase; }
        .form-control {
            width: 100%;
            background: rgba(10, 5, 15, 0.7);
            border: 1px solid rgba(255, 51, 153, 0.2);
            border-radius: 8px;
            padding: 10px 14px;
            color: #fff;
            font-size: 13px;
            outline: none;
            transition: all 0.2s;
        }
        .form-control:focus { border-color: #ff3399; box-shadow: 0 0 10px rgba(255, 51, 153, 0.3); }
        
        .star-rating-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 8px; }
        .star-rating-input input { display: none; }
        .star-rating-input label { font-size: 22px; color: #443045; cursor: pointer; transition: color 0.2s; }
        .star-rating-input input:checked ~ label,
        .star-rating-input label:hover,
        .star-rating-input label:hover ~ label { color: #ffaa00; }

        .btn-submit-review {
            background: linear-gradient(90deg, #ff3399, #9933ff);
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit-review:hover { opacity: 0.9; }

        .rating-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .rating-card {
            background: rgba(25, 10, 30, 0.6);
            border: 1px solid rgba(255, 51, 153, 0.15);
            border-radius: 12px;
            padding: 20px;
            transition: transform 0.2s;
        }
        .rating-card:hover { transform: translateY(-3px); border-color: rgba(255, 51, 153, 0.4); }
        .user-info { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
        .avatar {
            width: 40px; height: 40px; border-radius: 50%;
            background: linear-gradient(135deg, #ff3399, #9933ff);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; color: #fff;
        }
        .user-name { font-size: 14px; font-weight: 700; color: #ffffff; }
        .user-role { font-size: 11px; color: #a090a5; }
        .comment-text { font-size: 13px; color: #e0d0e5; line-height: 1.5; }

        /* ================= STYLING PUSAT BANTUAN (FAQ) ================= */
        .faq-item {
            background: rgba(25, 10, 30, 0.6);
            border: 1px solid rgba(255, 51, 153, 0.15);
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .faq-item:hover {
            border-color: rgba(255, 51, 153, 0.4);
        }
        .faq-question {
            width: 100%;
            padding: 16px 20px;
            text-align: left;
            font-weight: 700;
            font-size: 14px;
            color: #ffffff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: transparent;
            border: none;
            cursor: pointer;
        }
        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease, padding 0.3s ease;
            padding: 0 20px;
            color: #a090a5;
            font-size: 13px;
            line-height: 1.6;
            background: rgba(15, 5, 25, 0.4);
        }
        .faq-item.active .faq-answer {
            max-height: 200px;
            padding: 15px 20px;
            border-top: 1px solid rgba(255, 51, 153, 0.1);
        }
        .faq-item.active .faq-icon {
            transform: rotate(180deg);
            color: #ff3399;
        }
        .faq-icon {
            transition: transform 0.3s ease;
            color: #9933ff;
        }
    </style>
</head>
<body class="bg-[#0f051d] text-white min-h-screen flex flex-col justify-between overflow-x-hidden relative">

    <!-- DEKORASI BACKGROUND GLOW MENGAMBANG -->
    <div class="fixed -top-40 -left-40 w-96 h-96 bg-[#ff2a85]/20 rounded-full blur-3xl pointer-events-none animate-pulse"></div>
    <div class="fixed -bottom-40 -right-40 w-96 h-96 bg-[#9d4edd]/20 rounded-full blur-3xl pointer-events-none animate-pulse"></div>

    <!-- ================= NAVBAR / HEADER ATAS ================= -->
    <nav class="bg-[#130826]/80 backdrop-blur-md border-b border-[#2e1254] px-6 py-4 flex justify-between items-center shadow-lg sticky top-0 z-50">
        <div class="flex items-center gap-2 font-extrabold text-sm md:text-base tracking-wide">
            <span class="text-xl animate-bounce">🚗</span>
            <span>E-PARKIR | <span class="text-[#ff2a85]">SELAMAT DATANG DI TERMINAL GIWANGAN</span></span>
        </div>
        <div class="flex items-center gap-3">
            <a href="#section-bantuan" class="hidden md:inline-block text-xs font-semibold text-slate-300 hover:text-[#ff2a85] px-3 py-2 transition-colors">
                <i class="fa-solid fa-[#ff2a85] fa-circle-question mr-1"></i> Bantuan
            </a>
            <button onclick="playAnimation()" class="bg-[#2e1254] hover:bg-[#3d186e] text-xs px-3 py-2 rounded-lg border border-[#ff2a85]/30 text-[#ff2a85] font-semibold transition-all">
                🔄 Putar Ulang Animasi
            </button>
            <a href="login.php" class="border border-[#ff2a85]/50 text-[#ff2a85] hover:bg-[#ff2a85] hover:text-white text-xs md:text-sm font-bold px-5 py-2 rounded-xl transition-all duration-300 shadow-[0_0_10px_rgba(255,42,133,0.2)]">
                MASUK / LOGIN
            </a>
        </div>
    </nav>

    <!-- ================= ANIMASI KERETA API BERJALAN BERURUTAN ================= -->
    <div class="train-track shadow-lg">
        <div class="train-container flex items-center gap-1">
            
            <!-- LOKOMOTIF (Kepala Kereta) -->
            <div class="relative bg-gradient-to-r from-[#ff2a85] to-[#b30059] text-white px-4 py-2 rounded-l-2xl rounded-r-md border border-[#ff2a85] flex items-center gap-2 shadow-[0_0_15px_rgba(255,42,133,0.8)]">
                <div class="absolute -top-3 left-2 smoke-puff text-gray-300 text-xs">💨</div>
                <div class="w-2 h-2 bg-yellow-300 rounded-full shadow-[0_0_8px_#ffeb3b] animate-pulse"></div>
                <i class="fa-solid fa-train-subway text-lg"></i>
                <span class="font-black text-xs tracking-wider">E-PARKIR EXPRESS</span>
                <div class="absolute -bottom-2.5 left-3 flex gap-3 text-[10px] text-gray-300">
                    <i class="fa-solid fa-gear wheel-spin"></i>
                    <i class="fa-solid fa-gear wheel-spin"></i>
                </div>
            </div>

            <div class="w-2 h-1 bg-gray-500"></div>

            <!-- GERBONG 1: MOTOR -->
            <div class="bg-[#130826] border border-[#7a1cb3] px-3 py-1.5 rounded-md flex items-center gap-2 relative shadow-md">
                <i class="fa-solid fa-motorcycle text-[#00e676] text-xs"></i>
                <span class="text-[11px] font-bold text-slate-200">GERBONG A: MOTOR</span>
                <div class="absolute -bottom-2.5 left-2 flex gap-4 text-[9px] text-purple-400">
                    <i class="fa-solid fa-gear wheel-spin"></i>
                    <i class="fa-solid fa-gear wheel-spin"></i>
                </div>
            </div>

            <div class="w-2 h-1 bg-gray-500"></div>

            <!-- GERBONG 2: MOBIL -->
            <div class="bg-[#130826] border border-[#7a1cb3] px-3 py-1.5 rounded-md flex items-center gap-2 relative shadow-md">
                <i class="fa-solid fa-car text-[#ff2a85] text-xs"></i>
                <span class="text-[11px] font-bold text-slate-200">GERBONG B: MOBIL</span>
                <div class="absolute -bottom-2.5 left-2 flex gap-4 text-[9px] text-purple-400">
                    <i class="fa-solid fa-gear wheel-spin"></i>
                    <i class="fa-solid fa-gear wheel-spin"></i>
                </div>
            </div>

            <div class="w-2 h-1 bg-gray-500"></div>

            <!-- GERBONG 3: BUS & TRUK -->
            <div class="bg-[#130826] border border-[#7a1cb3] px-3 py-1.5 rounded-md flex items-center gap-2 relative shadow-md">
                <i class="fa-solid fa-bus text-yellow-400 text-xs"></i>
                <span class="text-[11px] font-bold text-slate-200">GERBONG C: BUS / TRUK</span>
                <div class="absolute -bottom-2.5 left-2 flex gap-4 text-[9px] text-purple-400">
                    <i class="fa-solid fa-gear wheel-spin"></i>
                    <i class="fa-solid fa-gear wheel-spin"></i>
                </div>
            </div>

            <div class="w-2 h-1 bg-gray-500"></div>

            <!-- GERBONG BELAKANG -->
            <div class="bg-[#2e1254] border border-[#9d4edd] px-3 py-1.5 rounded-r-xl flex items-center gap-1.5 relative shadow-md">
                <i class="fa-solid fa-shield-halved text-[#00e676] text-xs"></i>
                <span class="text-[10px] font-extrabold text-[#9d4edd]">GIWANGAN 24/7</span>
                <div class="absolute -bottom-2.5 left-2 flex gap-3 text-[9px] text-purple-400">
                    <i class="fa-solid fa-gear wheel-spin"></i>
                    <i class="fa-solid fa-gear wheel-spin"></i>
                </div>
            </div>

        </div>
    </div>

    <!-- ================= KONTEN UTAMA ================= -->
    <main class="max-w-6xl mx-auto px-4 py-8 w-full flex-1 space-y-12">
        
        <!-- HEADER JUDUL -->
        <div class="text-center space-y-2 anim-entry">
            <h1 class="text-2xl md:text-4xl font-extrabold tracking-wide uppercase bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-200 to-gray-400">
                SELAMAT DATANG DI TERMINAL GIWANGAN
            </h1>
            <p class="text-[#a093b5] text-xs md:text-sm tracking-wider uppercase font-medium">
                SILAKAN PILIH DAN LIHAT LOKASI LAHAN PARKIR YANG TERSEDIA SEBELUM MELAKUKAN PARKIR.
            </p>
        </div>

        <!-- GRID KARTU AREA PARKIR -->
        <div id="cards-container" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <?php 
            if ($result_area && $result_area->num_rows > 0):
                while($area = $result_area->fetch_assoc()): 
                    $kapasitas = isset($area['kapasitas_total']) ? intval($area['kapasitas_total']) : 100;
                    $terisi = isset($area['slot_terisi']) ? intval($area['slot_terisi']) : 0;
                    $sisa_slot = $kapasitas - $terisi;
                    if ($sisa_slot < 0) $sisa_slot = 0;

                    $tarif = 0;
                    if (isset($area['harga'])) {
                        $tarif = $area['harga'];
                    } elseif (isset($area['tarif'])) {
                        $tarif = $area['tarif'];
                    } else {
                        if (stripos($area['nama_area'], 'motor') !== false) $tarif = 3000;
                        elseif (stripos($area['nama_area'], 'mobil') !== false) $tarif = 7000;
                        else $tarif = 15000;
                    }

                    $icon = "fa-car";
                    if (stripos($area['nama_area'], 'motor') !== false) $icon = "fa-motorcycle";
                    elseif (stripos($area['nama_area'], 'bus') !== false || stripos($area['nama_area'], 'truk') !== false) $icon = "fa-bus";
            ?>
                <!-- Kartu Area Parkir -->
                <div class="card-area anim-entry animate-card-loop bg-[#1c0b36] border border-[#2e1254] rounded-2xl p-6 flex flex-col justify-between shadow-lg group">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 rounded-xl bg-[#ff2a85]/15 text-[#ff2a85] flex items-center justify-center text-xl font-bold group-hover:rotate-12 transition-transform duration-300">
                                <i class="fa-solid <?php echo $icon; ?> animate-bounce"></i>
                            </div>
                            <div>
                                <h2 class="text-lg font-extrabold uppercase tracking-wide">
                                    <?php echo htmlspecialchars($area['nama_area']); ?>
                                </h2>
                                <p class="text-xs text-[#ff2a85] font-semibold flex items-center gap-1 mt-0.5">
                                    <i class="fa-solid fa-location-dot animate-pulse"></i> TERMINAL GIWANGAN
                                </p>
                            </div>
                        </div>

                        <div class="bg-[#130826] border border-[#00e676]/30 rounded-xl p-3.5 flex justify-between items-center">
                            <span class="text-xs text-[#a093b5] font-medium">Sisa Slot Kosong</span>
                            <span class="border border-[#00e676]/50 bg-[#00e676]/10 text-[#00e676] px-3 py-1 rounded-full text-xs font-bold animate-pulse">
                                <?php echo $sisa_slot; ?> Slot
                            </span>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-white/5 mt-4 flex items-baseline justify-between">
                        <span class="text-xs text-[#a093b5] font-semibold">Tarif Parkir:</span>
                        <div>
                            <span class="text-2xl font-black text-[#00e676]">
                                RP <?php echo number_format($tarif, 0, ',', '.'); ?>
                            </span>
                            <span class="text-xs text-[#a093b5] font-bold">/ JAM</span>
                        </div>
                    </div>
                </div>
            <?php 
                endwhile;
            else:
            ?>
                <div class="col-span-3 text-center py-12 text-[#a093b5] bg-[#1c0b36] rounded-2xl border border-[#2e1254]">
                    Belum ada area parkir yang terdaftar di database.
                </div>
            <?php endif; ?>
        </div>

        <!-- ================= SECTION GRAFIK AKTIVITAS ================= -->
        <div id="chart-card" class="bg-[#1c0b36] border border-[#2e1254] rounded-2xl p-6 md:p-8 shadow-2xl anim-entry">
            <h3 class="text-sm md:text-base font-bold text-[#ff2a85] flex items-center gap-2 mb-6">
                <i class="fa-solid fa-chart-column text-lg animate-bounce"></i> Ringkasan Aktivitas Berdasarkan Role
            </h3>
            
            <div class="relative w-full h-72 md:h-80">
                <canvas id="roleActivityChart"></canvas>
            </div>
        </div>

        <!-- ================= SECTION RATING & ULASAN PELAYANAN ================= -->
        <div id="section-ulasan" class="rating-section anim-entry">
            <div class="rating-header">
                <div>
                    <h3 style="font-size: 20px; font-weight: 700; color: #fff;">⭐ Rating & Ulasan Pelayanan Parkir</h3>
                    <p style="font-size: 13px; color: #a090a5;">Penilaian langsung dari pelanggan terhadap kecepatan dan kualitas pelayanan fasilitas E-Parkir</p>
                </div>
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="score-number">4.9</span>
                    <div>
                        <div class="stars-outer">
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                            <i class="fa-solid fa-star"></i>
                        </div>
                        <span style="font-size: 12px; color: #a090a5;">Kepuasan Pelanggan</span>
                    </div>
                </div>
            </div>

            <!-- FORM INPUT ULASAN DARI PELANGGAN -->
            <div class="form-ulasan">
                <h4 style="font-size: 15px; font-weight: 700; color: #ff66b2; margin-bottom: 15px;">✍️ Berikan Ulasan Pelayanan Kami</h4>
                <form action="" method="POST">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div class="form-group">
                            <label>Nama Anda</label>
                            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Contoh: Ahmad" required>
                        </div>
                        <div class="form-group">
                            <label>Kategori Kendaraan / Layanan</label>
                            <select name="kategori" class="form-control" required>
                                <option value="Pengguna Motor (Area A)">Pengguna Motor (Area A)</option>
                                <option value="Pengguna Mobil (Area B)">Pengguna Mobil (Area B)</option>
                                <option value="Pengguna Bus/Truk (Area C)">Pengguna Bus/Truk (Area C)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Beri Rating Pelayanan</label>
                            <div class="star-rating-input">
                                <input type="radio" id="star5" name="rating" value="5" required /><label for="star5" title="Sangat Puas"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4" /><label for="star4" title="Puas"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3" /><label for="star3" title="Cukup"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2" /><label for="star2" title="Kurang"><i class="fa-solid fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1" /><label for="star1" title="Buruk"><i class="fa-solid fa-star"></i></label>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Komentar Pelayanan</label>
                        <textarea name="komentar" class="form-control" rows="2" placeholder="Tuliskan pengalaman atau saran pelayanan petugas..." required></textarea>
                    </div>
                    <button type="submit" name="kirim_ulasan" class="btn-submit-review">🚀 Kirim Ulasan Pelayanan</button>
                </form>
            </div>

            <!-- LIST ULASAN PELANGGAN -->
            <div class="rating-cards">
                <?php
                $get_ulasan = $conn->query("SELECT * FROM tb_ulasan ORDER BY id DESC LIMIT 6");
                if ($get_ulasan && $get_ulasan->num_rows > 0) {
                    while ($u = $get_ulasan->fetch_assoc()) {
                        $initial = strtoupper(substr($u['nama'], 0, 1));
                        ?>
                        <div class="rating-card">
                            <div class="user-info">
                                <div class="avatar"><?= $initial; ?></div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($u['nama']); ?></div>
                                    <div class="user-role"><?= htmlspecialchars($u['kategori']); ?></div>
                                </div>
                            </div>
                            <div class="stars-outer" style="font-size: 13px; margin-bottom: 8px;">
                                <?php for($i=1; $i<=5; $i++): ?>
                                    <i class="fa-solid fa-star" style="color: <?= ($i <= $u['rating']) ? '#ffaa00' : '#443045'; ?>;"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="comment-text">"<?= htmlspecialchars($u['komentar']); ?>"</p>
                        </div>
                        <?php
                    }
                } else {
                ?>
                    <div class="rating-card">
                        <div class="user-info">
                            <div class="avatar">R</div>
                            <div>
                                <div class="user-name">Bagas</div>
                                <div class="user-role">Pelanggan Motor (Area A)</div>
                            </div>
                        </div>
                        <div class="stars-outer" style="font-size: 13px; margin-bottom: 8px;">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="comment-text">"Pelayanan petugas lapangan sangat cepat dan ramah. Gate otomatisnya responsif sekali!"</p>
                    </div>

                    <div class="rating-card">
                        <div class="user-info">
                            <div class="avatar" style="background: linear-gradient(135deg, #ffaa00, #ff3399);">S</div>
                            <div>
                                <div class="user-name">Izzul</div>
                                <div class="user-role">Pelanggan Mobil (Area B)</div>
                            </div>
                        </div>
                        <div class="stars-outer" style="font-size: 13px; margin-bottom: 8px;">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="comment-text">"Sangat terbantu dengan indikator sisa slot parkir di layar depan, jadi tidak perlu keliling cari tempat kosong."</p>
                    </div>

                    <div class="rating-card">
                        <div class="user-info">
                            <div class="avatar" style="background: linear-gradient(135deg, #00cc88, #3399ff);">B</div>
                            <div>
                                <div class="user-name">Seno</div>
                                <div class="user-role">Sopir Bus (Area C)</div>
                            </div>
                        </div>
                        <div class="stars-outer" style="font-size: 13px; margin-bottom: 8px;">
                            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
                        </div>
                        <p class="comment-text">"Petugas mengarahkan bus masuk dengan sangat tertib. Tarif transparan dan tercetak jelas di karcis."</p>
                    </div>
                <?php } ?>
            </div>
        </div>

        <!-- ================= SECTION PUSAT BANTUAN & INFORMASI (FAQ) ================= -->
        <div id="section-bantuan" class="bg-[#1c0b36] border border-[#2e1254] rounded-2xl p-6 md:p-8 shadow-2xl anim-entry">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8 border-b border-[#2e1254] pb-6">
                <div>
                    <h3 class="text-xl font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-circle-question text-[#ff2a85] text-2xl"></i> Pusat Bantuan & Pertanyaan Umum
                    </h3>
                    <p class="text-xs text-[#a093b5] mt-1">Informasi penting seputar penggunaan sistem dan layanan E-Parkir Terminal Giwangan</p>
                </div>
                <a href="https://wa.me/6281234567890" target="_blank" class="inline-flex items-center gap-2 bg-[#00e676]/10 text-[#00e676] border border-[#00e676]/40 px-4 py-2 rounded-xl text-xs font-bold hover:bg-[#00e676] hover:text-black transition-all">
                    <i class="fa-brands fa-whatsapp text-sm"></i> Hubungi Customer Care 24/7
                </a>
            </div>

            <div class="space-y-4">
                <!-- FAQ Item 1 -->
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span><i class="fa-solid fa-ticket text-[#ff2a85] mr-2"></i> Bagaimana cara melakukan reservasi parkir online?</span>
                        <i class="fa-solid fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Pengguna dapat mendaftar akun atau langsung menanyakan ketersediaan slot. Saat melakukan reservasi, pilih area parkir yang sesuai, tentukan jam masuk, dan sistem akan memotong slot secara langsung serta memberikan Tiket Reservasi Virtual.
                    </div>
                </div>

                <!-- FAQ Item 2 -->
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span><i class="fa-solid fa-wallet text-[#ff2a85] mr-2"></i> Apa saja metode pembayaran yang diterima?</span>
                        <i class="fa-solid fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Sistem E-Parkir Terminal Giwangan mendukung pembayaran Tunai di pos petugas keluar, serta pembayaran Non-Tunai melalui scan QRIS (GoPay, OVO, ShopeePay, Dana, LinkAja) dan transfer bank.
                    </div>
                </div>

                <!-- FAQ Item 3 -->
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span><i class="fa-solid fa-triangle-exclamation text-[#ff2a85] mr-2"></i> Bagaimana jika karcis/tiket fisik parkir hilang?</span>
                        <i class="fa-solid fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Jika karcis fisik hilang, Anda wajib menunjukkan Surat Tanda Nomor Kendaraan (STNK) yang sah beserta Kartu Identitas (KTP/SIM) kepada Petugas Parkir untuk dicocokkan dengan data nomor plat kendaraan pada log sistem.
                    </div>
                </div>

                <!-- FAQ Item 4 -->
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFaq(this)">
                        <span><i class="fa-solid fa-clock text-[#ff2a85] mr-2"></i> Bagaimana hitungan tarif parkir jika menginap?</span>
                        <i class="fa-solid fa-chevron-down faq-icon"></i>
                    </button>
                    <div class="faq-answer">
                        Tarif dihitung per jam sesuai dengan tarif masing-masing area. Untuk kendaraan yang menginap (lebih dari 24 jam), sistem secara otomatis mengakumulasikan durasi jam dikalikan dengan tarif normal per jam tanpa ada denda tersembunyi.
                    </div>
                </div>
            </div>
        </div>

    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="text-center py-6 border-t border-[#2e1254] text-xs text-[#a093b5] mt-10">
        &copy; <?php echo date('Y'); ?> E-Parkir Terminal Giwangan. All rights reserved.
    </footer>

    <!-- SCRIPT CHART.JS & INTERAKSI FAQ -->
    <script>
        let roleActivityChart = null;

        function initChart() {
            const canvas = document.getElementById('roleActivityChart');
            if (!canvas) return;

            const ctx = canvas.getContext('2d');
            
            if (roleActivityChart) {
                roleActivityChart.destroy();
            }

            roleActivityChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['ADMIN', 'PETUGAS', 'OWNER'],
                    datasets: [{
                        label: 'Jumlah Aktivitas',
                        data: [<?= $count_admin; ?>, <?= $count_petugas; ?>, <?= $count_owner; ?>],
                        backgroundColor: ['#7a1cb3', '#00a86b', '#c22575'],
                        borderColor: ['#9933ff', '#00e676', '#ff2a85'],
                        borderWidth: 1,
                        borderRadius: 12,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 1800,
                        easing: 'easeOutQuart'
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#100522',
                            titleColor: '#ffffff',
                            bodyColor: '#ffffff',
                            borderColor: '#2e1254',
                            borderWidth: 1,
                            padding: 12,
                            boxPadding: 6,
                            usePointStyle: true,
                            callbacks: {
                                label: function(context) {
                                    return '  Jumlah Aktivitas: ' + context.raw;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: {
                                color: '#ffffff',
                                font: { family: "'Plus Jakarta Sans', sans-serif", weight: '800', size: 12 }
                            }
                        },
                        y: {
                            min: 0,
                            max: 30,
                            ticks: {
                                stepSize: 3,
                                color: '#a093b5',
                                font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
                            },
                            grid: { color: 'rgba(255, 255, 255, 0.05)', drawBorder: false }
                        }
                    }
                }
            });
        }

        function playAnimation() {
            const elements = document.querySelectorAll('.anim-entry');
            elements.forEach(el => {
                el.classList.remove('anim-entry');
                void el.offsetWidth;
                el.classList.add('anim-entry');
            });
            initChart();
        }

        function toggleFaq(button) {
            const faqItem = button.parentElement;
            const isOpen = faqItem.classList.contains('active');
            
            // Tutup FAQ lain yang terbuka
            document.querySelectorAll('.faq-item').forEach(item => {
                item.classList.remove('active');
            });

            // Toggle item yang diklik
            if (!isOpen) {
                faqItem.classList.add('active');
            }
        }

        window.onload = function() {
            initChart();
        };
    </script>
</body>
</html>