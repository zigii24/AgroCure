<?php
// Mengambil data dari Variables yang Anda set di Railway
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$db   = getenv('DB_NAME');
$port = getenv('DB_PORT');

// Pastikan variabel terbaca
if (!$host || !$user || !$pass || !$db) {
    die("ERROR: Konfigurasi Database tidak ditemukan di Environment Variables.");
}

$conn = mysqli_connect($host, $user, $pass, $db, $port);

if (!$conn) {
    // Menampilkan error detail agar Anda tahu apa masalahnya
    die("Koneksi gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8");
?>