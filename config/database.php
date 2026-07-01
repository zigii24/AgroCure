<?php
// Railway akan membaca variabel ini. 
// Jika tidak ada di Railway, ia akan menggunakan port 18862 sebagai cadangan.
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: 18862; // Gunakan port Anda di sini

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    die("Koneksi Database Gagal (Cek port/host di Railway): " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>