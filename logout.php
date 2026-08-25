<?php
session_start();
include 'koneksi.php';

// Opsional: Catat log kalau user melakukan logout sebelum session dihancurkan
if (isset($_SESSION['id_user'])) {
    $id_user = $_SESSION['id_user'];
    mysqli_query($conn, "INSERT INTO tb_log_aktivitas (id_user, aktivitas) VALUES ('$id_user', 'User logout dari sistem')");
}

// Hancurkan semua session login
$_SESSION = [];
session_unset();
session_destroy();

// Pindahkan user kembali ke halaman login
header("Location: login.php");
exit;
?>