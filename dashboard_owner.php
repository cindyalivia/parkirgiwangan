<?php
session_start();

// Cek apakah user sudah login dan role-nya owner
if (!isset($_SESSION['login']) || $_SESSION['role'] !== 'owner') {
    header("Location: login.php");
    exit;
}

$nama = $_SESSION['nama'] ?? 'Owner';
$role = $_SESSION['role'] ?? 'owner';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Owner - Karcis Parkir</title>
    <!-- Kamu bisa sesuaikan CSS / Bootstrap sesuai dashboard lain -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

    <!-- Navbar / Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-link navbar-brand fw-bold" href="#">Sistem Karcis Parkir</a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Halo, <strong><?= htmlspecialchars($nama); ?></strong> (Owner)</span>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Konten Utama Dashboard -->
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card shadow-sm p-4 mb-4">
                    <h3>Selamat Datang di Dashboard Owner</h3>
                    <p class="text-muted">Kelola dan pantau laporan pendapatan serta aktivitas parkir di sini.</p>
                </div>
            </div>
        </div>

        <!-- Menu/Card Khusus Owner (Contoh Laporan) -->
        <div class="row">
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Pendapatan</h5>
                        <p class="card-text fs-4 fw-bold">Rp 0</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title">Total Transaksi</h5>
                        <p class="card-text fs-4 fw-bold">0 Transaksi</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Script Suara Selamat Datang -->
    <?php if (isset($_SESSION['login_success'])): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                const nama = "<?= addslashes($nama); ?>";
                const role = "<?= addslashes($role); ?>";
                let teksPesan = `Selamat datang, Bapak ${nama}. Anda login sebagai Owner. Silakan tinjau laporan pendapatan dan analisis kepadatan.`;

                function putarSuara() {
                    if ('speechSynthesis' in window) {
                        window.speechSynthesis.cancel();
                        const utterance = new SpeechSynthesisUtterance(teksPesan);
                        utterance.lang = 'id-ID';
                        utterance.rate = 0.95;
                        utterance.pitch = 1.0;

                        const voices = window.speechSynthesis.getVoices();
                        const idVoice = voices.find(v => v.lang.includes('id') || v.lang.includes('ID'));
                        if (idVoice) {
                            utterance.voice = idVoice;
                        }

                        utterance.onerror = function(event) {
                            console.warn("Gagal memutar suara:", event.error);
                        };

                        window.speechSynthesis.speak(utterance);
                    }
                }

                if ('speechSynthesis' in window) {
                    window.speechSynthesis.onvoiceschanged = putarSuara;
                    putarSuara();
                }

                document.addEventListener('click', function mainkanSekali() {
                    if (window.speechSynthesis && !window.speechSynthesis.speaking) {
                        putarSuara();
                    }
                    document.removeEventListener('click', mainkanSekali);
                }, { once: true });
            });
        </script>
        <?php unset($_SESSION['login_success']); ?>
    <?php endif; ?>

</body>
</html>