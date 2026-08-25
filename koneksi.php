<?php
// Set timezone untuk PHP (Asia/Jakarta = WIB)
date_default_timezone_set('Asia/Jakarta');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "karcisparkirrr";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi gagal! Pesan error: " . mysqli_connect_error());
} else {
    // Set timezone untuk MySQL Database (+07:00 = WIB)
    mysqli_query($conn, "SET time_zone = '+07:00'");
}
?>