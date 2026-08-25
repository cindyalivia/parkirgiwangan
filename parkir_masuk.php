<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

// Mengambil data untuk pilihan dropdown
$query_tarif = mysqli_query($conn, "SELECT * FROM tb_tarif ORDER BY jenis_kendaraan ASC");
$query_area = mysqli_query($conn, "SELECT * FROM tb_area_parkir ORDER BY nama_area ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Kendaraan Masuk - Sistem Parkir</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@700;800&display=swap" rel="stylesheet">
    
    <!-- Pustaka SweetAlert2 untuk Pop-up Notifikasi -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #0d0614; color: #ffffff; min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 40px 20px; position: relative; overflow-x: hidden; }
        .neon-orb { position: fixed; border-radius: 50%; filter: blur(150px); z-index: 1; pointer-events: none; animation: pulseGlow 10s ease-in-out infinite alternate; }
        .orb-1 { width: 450px; height: 450px; background: rgba(236, 72, 153, 0.35); top: -10%; left: -5%; }
        .orb-2 { width: 400px; height: 400px; background: rgba(168, 85, 247, 0.3); bottom: -10%; right: -5%; animation-delay: -5s; }
        @keyframes pulseGlow { 0% { transform: scale(1) translate(0, 0); opacity: 0.6; } 100% { transform: scale(1.15) translate(20px, -20px); opacity: 0.85; } }
        .container { width: 100%; max-width: 520px; position: relative; z-index: 10; }
        .top-navigation { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 0 5px; }
        .btn-dashboard { background: rgba(23, 11, 35, 0.7); color: #fce7f3; text-decoration: none; padding: 8px 16px; border-radius: 12px; font-size: 13px; font-weight: 700; border: 1px solid rgba(244, 114, 182, 0.3); backdrop-filter: blur(20px); transition: all 0.3s ease; }
        .btn-dashboard:hover { background: rgba(236, 72, 153, 0.25); border-color: rgba(236, 72, 153, 0.7); color: #ffffff; box-shadow: 0 0 15px rgba(236, 72, 153, 0.4); transform: translateX(-3px); }
        .kasir-info { font-size: 13px; color: #fbcfe8; opacity: 0.85; }
        .card-form { background: rgba(23, 11, 35, 0.65); border: 1.5px solid rgba(244, 114, 182, 0.25); border-radius: 22px; padding: 32px; backdrop-filter: blur(30px); box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6); }
        .card-form h2 { font-family: 'Space Grotesk', sans-serif; font-size: 24px; font-weight: 800; text-align: center; margin-bottom: 6px; background: linear-gradient(135deg, #FFFFFF 30%, #F472B6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .card-form p.subtitle { font-size: 13px; color: #fbcfe8; text-align: center; margin-bottom: 28px; opacity: 0.8; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 12px; font-weight: 700; color: #fbcfe8; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-group input, .form-group select { width: 100%; padding: 13px 16px; background: rgba(13, 6, 20, 0.6); border: 1px solid rgba(244, 114, 182, 0.25); border-radius: 12px; color: #ffffff; font-size: 14px; outline: none; transition: all 0.3s ease; }
        .form-group input:focus, .form-group select:focus { border-color: #f472b6; box-shadow: 0 0 15px rgba(244, 114, 182, 0.3); background: rgba(23, 11, 35, 0.8); }
        .form-group select option { color: #ffffff !important; background-color: #170b23 !important; }
        .form-group input#no_plat { text-transform: uppercase; font-family: 'Space Grotesk', monospace; font-weight: 800; text-align: center; font-size: 20px; color: #f472b6; letter-spacing: 3px; background: rgba(236, 72, 153, 0.08); border: 1.5px solid rgba(236, 72, 153, 0.4); }
        .btn-submit { width: 100%; padding: 14px; background: linear-gradient(135deg, #ec4899 0%, #a855f7 100%); color: #ffffff; font-weight: 700; border: none; border-radius: 12px; font-size: 15px; cursor: pointer; transition: all 0.3s ease; margin-top: 10px; box-shadow: 0 0 20px rgba(236, 72, 153, 0.4); letter-spacing: 0.5px; }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 0 30px rgba(236, 72, 153, 0.7); }
        
        /* POPUP MODAL STRUK HIDDEN */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(13, 6, 20, 0.85); backdrop-filter: blur(10px); display: none; justify-content: center; align-items: center; z-index: 9999; }
        .modal-content { background: #ffffff; padding: 24px; border-radius: 16px; box-shadow: 0 20px 50px rgba(236, 72, 153, 0.3); text-align: center; }
        #struk-karcis { width: 76mm; background: #ffffff; color: #000000; padding: 10px; font-family: 'Courier New', Courier, monospace; font-size: 13px; text-align: left; margin: 0 auto; }
        .struk-header { text-align: center; margin-bottom: 12px; border-bottom: 1px dashed #000; padding-bottom: 8px; }
        .struk-header h3 { font-size: 16px; margin-bottom: 2px; color: #000; font-weight: bold; }
        .struk-header p { font-size: 12px; color: #000; }
        .struk-line { display: flex; justify-content: space-between; margin-bottom: 5px; color: #000; }
        .struk-barcode { text-align: center; margin: 15px 0 5px 0; }
        .struk-barcode img { max-width: 100%; height: auto; max-height: 80px; object-fit: contain; }
        .struk-footer { text-align: center; margin-top: 12px; border-top: 1px dashed #000; padding-top: 8px; font-size: 11px; color: #000; }

        /* DESAIN SWEETALERT TEMA DARK NEON */
        .swal2-popup-custom {
            background: rgba(23, 11, 35, 0.95) !important;
            border: 1px solid rgba(244, 114, 182, 0.4) !important;
            border-radius: 24px !important;
            color: #ffffff !important;
            backdrop-filter: blur(20px);
            box-shadow: 0 0 30px rgba(236, 72, 153, 0.3) !important;
        }
        .swal2-title-custom {
            color: #ffffff !important;
            font-weight: 800 !important;
            font-size: 22px !important;
        }
        .swal2-html-custom {
            color: #fbcfe8 !important;
            font-size: 14px !important;
        }

        /* TAMPILAN KHUSUS PRINT CETAK */
        @media print {
            body * { visibility: hidden !important; }
            #struk-karcis, #struk-karcis * { visibility: visible !important; }
            #modalStruk { position: absolute !important; left: 0 !important; top: 0 !important; width: 100% !important; background: none !important; display: block !important; }
            .modal-content { box-shadow: none !important; padding: 0 !important; }
            .struk-line { display: flex !important; }
        }
    </style>
</head>
<body>

    <div class="neon-orb orb-1"></div>
    <div class="neon-orb orb-2"></div>

    <div class="container">
        <div class="top-navigation">
            <a href="dashboard.php" class="btn-dashboard">◄ Kembali ke Dashboard</a>
            <span class="kasir-info">Kasir: <strong style="color: #f472b6;" id="namaKasir"><?= htmlspecialchars($_SESSION['username']); ?></strong></span>
        </div>

        <div class="card-form">
            <h2>Input Kendaraan Masuk</h2>
            <p class="subtitle">Sistem Manajemen Perparkiran Terminal</p>

            <form id="formParkirMurni">
                <div class="form-group">
                    <label for="no_plat">Nomor Plat Kendaraan</label>
                    <input type="text" id="no_plat" placeholder="B 1234 AB" required autocomplete="off" autofocus>
                </div>

                <div class="form-group">
                    <label for="id_tarif">Jenis Kendaraan / Tarif</label>
                    <select id="id_tarif" required>
                        <option value="" disabled selected>-- Pilih Jenis Kendaraan --</option>
                        <?php while($t = mysqli_fetch_assoc($query_tarif)): ?>
                            <option value="<?= $t['id_tarif']; ?>" data-nama="<?= $t['jenis_kendaraan']; ?>" data-tarif="<?= $t['tarif_per_jam']; ?>">
                                <?= $t['jenis_kendaraan']; ?> (Rp. <?= number_format($t['tarif_per_jam'], 0, ',', '.'); ?>/jam)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="id_area">Area / Lokasi Parkir</label>
                    <select id="id_area" required>
                        <option value="" disabled selected>-- Pilih Area Parkir --</option>
                        <?php while($a = mysqli_fetch_assoc($query_area)):
                            $sisa = $a['kapasitas_total'] - $a['kapasitas_terisi'];
                            $jenis_area = isset($a['jenis_kendaraan']) ? $a['jenis_kendaraan'] : '';
                        ?>
                            <option value="<?= $a['id_area']; ?>" data-nama="<?= $a['nama_area']; ?>" data-jenis="<?= $jenis_area; ?>" <?= ($sisa <= 0) ? 'disabled' : ''; ?>>
                                <?= $a['nama_area']; ?> (Sisa slot: <?= $sisa; ?>) <?= ($sisa <= 0) ? '- PENUH' : ''; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Barcode Digunakan:</label>
                    <div style="background: rgba(13, 6, 20, 0.6); padding: 12px; border-radius: 12px; text-align: center; border: 1px solid rgba(244, 114, 182, 0.25);">
                        <img src="qris.jpeg" alt="Barcode Default" style="max-height: 80px; border-radius: 6px;">
                        <p style="font-size: 11px; color: #fbcfe8; margin-top: 6px; opacity: 0.7;">(Otomatis terpakai Barcode Bawaan)</p>
                    </div>
                </div>

                <button type="submit" class="btn-submit" id="btnSubmit">🖨️ CETAK KARCIS & MASUK</button>
            </form>
        </div>
    </div>

    <!-- MODAL POPUP STRUK (Khusus Cetak Print) -->
    <div class="modal-overlay" id="modalStruk">
        <div class="modal-content">
            <div id="struk-karcis">
                <div class="struk-header">
                    <h3>TERMINAL PARKIR</h3>
                    <p>Karcis Masuk Kendaraan</p>
                </div>
                
                <div class="struk-line"><span>No. Karcis:</span> <span id="strKarcis"></span></div>
                <div class="struk-line"><span>No. Plat:</span> <span id="strPlat" style="font-weight: 700;"></span></div>
                <div class="struk-line"><span>Jenis:</span> <span id="strJenis"></span></div>
                <div class="struk-line"><span>Area Parkir:</span> <span id="strArea"></span></div>
                <div class="struk-line"><span>Tarif/Jam:</span> <span id="strTarif"></span></div>
                <div class="struk-line"><span>Waktu Masuk:</span> <span id="strWaktu"></span></div>
                <div class="struk-line"><span>Petugas:</span> <span id="strPetugas"></span></div>

                <div class="struk-barcode">
                    <img id="barcodeImageTarget" src="qris.jpeg" alt="Barcode Parkir">
                </div>

                <div class="struk-footer">
                    <p>JANGAN HILANGKAN KARCIS INI</p>
                    <p>Terima Kasih Atas Kunjungan Anda</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi Web Audio API untuk menghasilkan suara Beep
        function playSoundNotification(type) {
            try {
                const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                
                if (type === 'success') {
                    // Nada tinggi 2x untuk Berhasil
                    const osc1 = audioCtx.createOscillator();
                    const gain1 = audioCtx.createGain();
                    osc1.type = 'sine';
                    osc1.frequency.setValueAtTime(800, audioCtx.currentTime);
                    gain1.gain.setValueAtTime(0.1, audioCtx.currentTime);
                    osc1.connect(gain1);
                    gain1.connect(audioCtx.destination);
                    osc1.start();
                    osc1.stop(audioCtx.currentTime + 0.1);

                    setTimeout(() => {
                        const osc2 = audioCtx.createOscillator();
                        const gain2 = audioCtx.createGain();
                        osc2.type = 'sine';
                        osc2.frequency.setValueAtTime(1200, audioCtx.currentTime);
                        gain2.gain.setValueAtTime(0.1, audioCtx.currentTime);
                        osc2.connect(gain2);
                        gain2.connect(audioCtx.destination);
                        osc2.start();
                        osc2.stop(audioCtx.currentTime + 0.15);
                    }, 120);
                } else {
                    // Nada rendah untuk Error / Gagal
                    const osc = audioCtx.createOscillator();
                    const gain = audioCtx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(300, audioCtx.currentTime);
                    gain.gain.setValueAtTime(0.15, audioCtx.currentTime);
                    osc.connect(gain);
                    gain.connect(audioCtx.destination);
                    osc.start();
                    osc.stop(audioCtx.currentTime + 0.3);
                }
            } catch (e) {
                console.log("Audio Web API tidak didukung oleh browser.");
            }
        }

        document.getElementById('formParkirMurni').addEventListener('submit', function(e) {
            e.preventDefault();

            let selectTarif = document.getElementById('id_tarif');
            let idTarif = selectTarif.value;
            let namaKendaraan = selectTarif.options[selectTarif.selectedIndex].getAttribute('data-nama');
            let tarifHarga = selectTarif.options[selectTarif.selectedIndex].getAttribute('data-tarif');
            
            let selectArea = document.getElementById('id_area');
            let idArea = selectArea.value;
            let namaArea = selectArea.options[selectArea.selectedIndex].getAttribute('data-nama');

            let platInput = document.getElementById('no_plat').value.trim().toUpperCase();

            let formData = new FormData();
            formData.append('no_plat', platInput);
            formData.append('id_tarif', idTarif);
            formData.append('id_area', idArea);

            fetch('proses_masuk.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Putar Suara Berhasil
                    playSoundNotification('success');

                    let namaKasir = document.getElementById('namaKasir').innerText;

                    // 1. Isikan data ke struk cetak
                    document.getElementById('strKarcis').innerText = data.no_karcis;
                    document.getElementById('strPlat').innerText = platInput;
                    document.getElementById('strJenis').innerText = namaKendaraan;
                    document.getElementById('strArea').innerText = namaArea;
                    document.getElementById('strTarif').innerText = "Rp. " + parseInt(tarifHarga).toLocaleString('id-ID');
                    document.getElementById('strWaktu').innerText = data.waktu_masuk;
                    document.getElementById('strPetugas').innerText = namaKasir;

                    // 2. Jalankan perintah print
                    setTimeout(() => {
                        window.print();
                    }, 200);

                    // 3. Tampilkan Pop-Up Notifikasi Berhasil (SweetAlert2)
                    Swal.fire({
                        icon: 'success',
                        title: 'Cetak Karcis & Masuk Berhasil!',
                        text: 'Data kendaraan telah berhasil disimpan ke sistem.',
                        confirmButtonText: 'Selesai',
                        confirmButtonColor: '#ec4899',
                        customClass: {
                            popup: 'swal2-popup-custom',
                            title: 'swal2-title-custom',
                            htmlContainer: 'swal2-html-custom'
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            location.reload();
                        }
                    });

                    document.getElementById('formParkirMurni').reset();

                } else {
                    // Putar Suara Gagal
                    playSoundNotification('error');

                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal Menyimpan!',
                        text: data.message,
                        confirmButtonColor: '#ec4899',
                        customClass: {
                            popup: 'swal2-popup-custom',
                            title: 'swal2-title-custom',
                            htmlContainer: 'swal2-html-custom'
                        }
                    });
                }
            })
            .catch(err => {
                // Putar Suara Gagal
                playSoundNotification('error');

                Swal.fire({
                    icon: 'error',
                    title: 'Terjadi Kesalahan!',
                    text: 'Gagal terhubung ke server / jaringan error.',
                    confirmButtonColor: '#ec4899',
                    customClass: {
                        popup: 'swal2-popup-custom',
                        title: 'swal2-title-custom',
                        htmlContainer: 'swal2-html-custom'
                    }
                });
            });
        });
    </script>
</body>
</html>