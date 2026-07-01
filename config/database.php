<?php
$host = getenv('MYSQLHOST');
$user = getenv('MYSQLUSER');
$pass = getenv('MYSQLPASSWORD');
$db   = getenv('MYSQLDATABASE');
$port = getenv('MYSQLPORT') ?: 18862;

// Tambahkan ini buat debugging kalau masih error
if (empty($host)) {
    die("Error: Variable MYSQLHOST tidak ditemukan di Railway. Cek tab Variables di Railway!");
}

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $conn = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // Kalau error, dia bakal kasih tau host mana yang dicoba
    die("Koneksi Gagal ke $host: " . $e->getMessage());
}
?>