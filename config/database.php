<?php
// Ambil data dari Environment Variable
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: 18862;

// Menggunakan cara Object Oriented (lebih standar)
$conn = new mysqli($host, $user, $pass, $db, $port);

// Cek jika ada error koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}

// Set karakter
$conn->set_charset("utf8mb4");

// Jika Anda tetap butuh SSL (hanya jika Aiven mewajibkan secara ketat)
// $conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
// $conn->real_connect($host, $user, $pass, $db, $port, NULL, MYSQLI_CLIENT_SSL);
?>