<?php
// Hapus session_start() dari sini karena sudah dipanggil di file utama
// session_start();

$host = "localhost";
$username = "root";
$password = "";
$database = "agrocure_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset to utf8
mysqli_set_charset($conn, "utf8");
?>