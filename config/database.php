<?php
// Ambil data koneksi dari Environment Variables (Agar aman dan lolos GitHub)
$host     = getenv('DB_HOST') ?: "mysql-5a5e2b3-zigiarizona8-0ea9.k.aivencloud.com";
$username = getenv('DB_USER') ?: "avnadmin";
// Ganti baris $password di config/database.php dengan ini:
$password = getenv('DB_PASSWORD');

if (empty($password)) {
    die("Error: DB_PASSWORD tidak terdeteksi di Environment Variables Railway!");
}
$database = getenv('DB_NAME') ?: "defaultdb";
$port     = getenv('DB_PORT'); // PORT SANGAT PENTING UNTUK AIVEN

try {
    // Parameter ke-5 adalah $port. Ini Wajib untuk Aiven!
    $conn = mysqli_connect($host, $username, $password, $database, $port);

    if (!$conn) {
        die("Koneksi database gagal: " . mysqli_connect_error());
    }

    // Set charset to utf8
    mysqli_set_charset($conn, "utf8");

} catch (mysqli_sql_exception $e) {
    // Jika koneksi gagal, tangkap errornya dan tampilkan di layar agar tidak Error 500
    die("Error Koneksi Database (Aiven): " . $e->getMessage());
}
?>