<?php
// Script ini hanya untuk mengecek apakah Variable di Railway terbaca
echo "<h1>Pengecekan Lingkungan Railway</h1>";

$vars = ['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME', 'DB_PORT'];

foreach ($vars as $var) {
    $val = getenv($var);
    if ($val) {
        echo "<p style='color:green;'>$var: <b>TERBACA</b> (panjang: " . strlen($val) . " karakter)</p>";
    } else {
        echo "<p style='color:red;'>$var: <b>TIDAK DITEMUKAN</b></p>";
    }
}
?>