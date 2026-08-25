<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM tb_user WHERE username='$username'");
    $user  = mysqli_fetch_assoc($query);

    // Cek keberadaan user & verifikasi password
    if ($user && password_verify($password, $user['password'])) { // atau jika tanpa hash: ($user['password'] == $password)
        
        // 1. Set Session Login
        $_SESSION['id_user']     = $user['id_user'];
        $_SESSION['username']    = $user['username'];
        $_SESSION['nama_lengkap']= $user['nama_lengkap'];
        $_SESSION['role']        = $user['role'];

        // 2. CATAT KE LOG AKTIVITAS (Bagian Utama agar Diagram Bertambah)
        $id_user   = $user['id_user'];
        $role_user = $user['role'];
        $aktivitas = "User " . $user['username'] . " (" . ucfirst($role_user) . ") berhasil login ke dalam sistem.";
        
        // Query Insert Log
        $sql_log = "INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_log) 
                    VALUES ('$id_user', '$aktivitas', NOW())";
        mysqli_query($conn, $sql_log);

        // 3. Redirect ke Dashboard
        header("Location: dashboard.php");
        exit();

    } else {
        $error = "Username atau password salah!";
    }
}
?>