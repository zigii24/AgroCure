<?php
// Paksa PHP menampilkan error ke layar
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: 18862;

// Inisialisasi koneksi
$conn = mysqli_init();

// SSL untuk Aiven
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);

// Mencoba koneksi
if (!mysqli_real_connect($conn, $host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL)) {
    // INI AKAN MENAMPILKAN PESAN ERROR ASLI DI BROWSER
    echo "<h1>Gagal Koneksi ke Aiven!</h1>";
    echo "Error: " . mysqli_connect_error();
    echo "<br>Host: $host, User: $user, Port: $port";
    exit;
}

mysqli_set_charset($conn, "utf8mb4");
?>