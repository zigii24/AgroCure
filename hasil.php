<?php
session_start();
include('config/database.php');

// Cek apakah ada data hasil diagnosa
if (!isset($_SESSION['hasil_diagnosis']) || empty($_SESSION['hasil_diagnosis'])) {
    header('Location: diagnosis.php');
    exit;
}

// Ambil data diagnosis
$hasil_diagnosis_data = $_SESSION['hasil_diagnosis'];
$gejala_aktif = $_SESSION['gejala_aktif'] ?? [];
$gejala_terpilih = $_SESSION['gejala_dipilih'] ?? [];

// Format data diagnosis
if (isset($hasil_diagnosis_data['penyakit'])) {
    $semua_hasil_diagnosis = [$hasil_diagnosis_data];
} else {
    $semua_hasil_diagnosis = $hasil_diagnosis_data;
}

// Validasi data
$semua_hasil_diagnosis = array_filter($semua_hasil_diagnosis, function($item) {
    return is_array($item) && isset($item['penyakit']) && isset($item['cf']);
});

if (empty($semua_hasil_diagnosis)) {
    header('Location: diagnosis.php');
    exit;
}

// Ambil semua gejala yang dipilih user dengan CF User
$semua_gejala_user = [];
if (!empty($gejala_terpilih)) {
    $ids_gejala = array_keys($gejala_terpilih);
    if (!empty($ids_gejala)) {
        $placeholders = implode(',', array_fill(0, count($ids_gejala), '?'));
        
        $query_gejala_user = "SELECT * FROM gejala WHERE id IN ($placeholders) ORDER BY kode";
        $stmt_gejala_user = mysqli_prepare($conn, $query_gejala_user);
        
        if ($stmt_gejala_user) {
            $types = str_repeat('i', count($ids_gejala));
            mysqli_stmt_bind_param($stmt_gejala_user, $types, ...$ids_gejala);
            mysqli_stmt_execute($stmt_gejala_user);
            $result_gejala_user = mysqli_stmt_get_result($stmt_gejala_user);
            
            while ($gejala = mysqli_fetch_assoc($result_gejala_user)) {
                $cf_user = isset($gejala_aktif[$gejala['id']]) ? floatval($gejala_aktif[$gejala['id']]) : 0;
                $semua_gejala_user[] = [
                    'id' => $gejala['id'],
                    'kode' => $gejala['kode'],
                    'nama' => $gejala['nama'],
                    'cf_user' => $cf_user
                ];
            }
        }
    }
}

// Urutkan diagnosis berdasarkan CF tertinggi
usort($semua_hasil_diagnosis, function($a, $b) {
    return $b['cf'] <=> $a['cf'];
});

// Diagnosis utama (peringkat 1)
$diagnosis_utama = $semua_hasil_diagnosis[0];
$penyakit_utama = $diagnosis_utama['penyakit'];
$id_penyakit_utama = $penyakit_utama['id'];

// Ambil data penyakit utama
$query_penyakit = "SELECT p.*, t.deskripsi as penanganan 
                   FROM penyakit p 
                   LEFT JOIN penanganan t ON p.id = t.id_penyakit 
                   WHERE p.id = ?";
$stmt_penyakit = mysqli_prepare($conn, $query_penyakit);
mysqli_stmt_bind_param($stmt_penyakit, 'i', $id_penyakit_utama);
mysqli_stmt_execute($stmt_penyakit);
$result_penyakit = mysqli_stmt_get_result($stmt_penyakit);
$penyakit_data = mysqli_fetch_assoc($result_penyakit);

// Ambil semua penyakit beserta penanganannya
$query_all_penyakit = "SELECT p.*, t.deskripsi as penanganan 
                       FROM penyakit p 
                       LEFT JOIN penanganan t ON p.id = t.id_penyakit 
                       ORDER BY p.kode";
$result_all_penyakit = mysqli_query($conn, $query_all_penyakit);
$all_penyakit = [];
while ($row = mysqli_fetch_assoc($result_all_penyakit)) {
    $all_penyakit[$row['id']] = $row;
}

// Fungsi untuk mendapatkan perhitungan CF untuk semua penyakit
function getPerhitunganCF($all_penyakit, $gejala_aktif, $conn) {
    $hasil_perhitungan = [];
    
    foreach ($all_penyakit as $id_penyakit => $penyakit) {
        $query_aturan = "SELECT a.*, g.kode, g.nama as nama_gejala 
                        FROM aturan a 
                        JOIN gejala g ON a.id_gejala = g.id 
                        WHERE a.id_penyakit = ? 
                        ORDER BY g.kode";
        $stmt_aturan = mysqli_prepare($conn, $query_aturan);
        mysqli_stmt_bind_param($stmt_aturan, 'i', $id_penyakit);
        mysqli_stmt_execute($stmt_aturan);
        $result_aturan = mysqli_stmt_get_result($stmt_aturan);
        
        $cf_combined = 0;
        $gejala_cocok = [];
        $total_cf_user = 0;
        $total_cf_pakar = 0;
        $total_cf_hasil = 0;
        
        while ($aturan = mysqli_fetch_assoc($result_aturan)) {
            $id_gejala = $aturan['id_gejala'];
            $cf_pakar = floatval($aturan['cf_pakar']);
            
            if (isset($gejala_aktif[$id_gejala])) {
                $cf_user = floatval($gejala_aktif[$id_gejala]);
                $cf_gejala = $cf_user * $cf_pakar;
                
                $gejala_cocok[] = [
                    'kode' => $aturan['kode'],
                    'nama' => $aturan['nama_gejala'],
                    'cf_user' => $cf_user,
                    'cf_pakar' => $cf_pakar,
                    'cf_hasil' => $cf_gejala
                ];
                
                $total_cf_user += $cf_user;
                $total_cf_pakar += $cf_pakar;
                $total_cf_hasil += $cf_gejala;
                
                if ($cf_combined == 0) {
                    $cf_combined = $cf_gejala;
                } else {
                    $cf_combined = $cf_combined + $cf_gejala * (1 - $cf_combined);
                }
            }
        }
        
        if ($cf_combined > 0) {
            $hasil_perhitungan[$id_penyakit] = [
                'penyakit' => $penyakit,
                'cf_final' => $cf_combined,
                'gejala_cocok' => $gejala_cocok,
                'total_cf_user' => $total_cf_user,
                'total_cf_pakar' => $total_cf_pakar,
                'total_cf_hasil' => $total_cf_hasil,
                'jumlah_gejala' => count($gejala_cocok),
                'penanganan' => $penyakit['penanganan'] ?? 'Penanganan belum tersedia'
            ];
        }
    }
    
    // Urutkan berdasarkan CF tertinggi
    uasort($hasil_perhitungan, function($a, $b) {
        return $b['cf_final'] <=> $a['cf_final'];
    });
    
    return $hasil_perhitungan;
}

// Ambil perhitungan untuk semua penyakit
$semua_perhitungan = getPerhitunganCF($all_penyakit, $gejala_aktif, $conn);

// Perhitungan untuk penyakit utama
$perhitungan_utama = $semua_perhitungan[$id_penyakit_utama] ?? null;
$cf_final = $perhitungan_utama['cf_final'] ?? $diagnosis_utama['cf'];

// Set timezone dan format tanggal
date_default_timezone_set('Asia/Jakarta');
$tanggal_diagnosis = date('d F Y');
$waktu_diagnosis = date('H:i:s');
$tanggal_waktu_lengkap = date('d F Y H:i:s');

// FUNGSI GENERATE PDF (DITAMBAHKAN DI SINI)
function generatePDF($conn, $session_data) {
    // Cek apakah TCPDF sudah terinstall
    $tcpdf_path = __DIR__ . '/vendor/tecnickcom/tcpdf/tcpdf.php';
    
    if (!file_exists($tcpdf_path)) {
        // Fallback: buat halaman HTML untuk download
        $filename = 'Diagnosis_Padi_' . date('Ymd_His') . '.html';
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // Ambil data dari session
        $hasil_diagnosis_data = $session_data['hasil_diagnosis'];
        $gejala_aktif = $session_data['gejala_aktif'] ?? [];
        $gejala_terpilih = $session_data['gejala_dipilih'] ?? [];
        
        // Proses data sama seperti di halaman
        if (isset($hasil_diagnosis_data['penyakit'])) {
            $semua_hasil_diagnosis = [$hasil_diagnosis_data];
        } else {
            $semua_hasil_diagnosis = $hasil_diagnosis_data;
        }
        
        usort($semua_hasil_diagnosis, function($a, $b) {
            return $b['cf'] <=> $a['cf'];
        });
        
        $diagnosis_utama = $semua_hasil_diagnosis[0];
        $penyakit_utama = $diagnosis_utama['penyakit'];
        $id_penyakit_utama = $penyakit_utama['id'];
        
        // Query data penyakit utama
        $query_penyakit = "SELECT p.*, t.deskripsi as penanganan 
                          FROM penyakit p 
                          LEFT JOIN penanganan t ON p.id = t.id_penyakit 
                          WHERE p.id = ?";
        $stmt_penyakit = mysqli_prepare($conn, $query_penyakit);
        mysqli_stmt_bind_param($stmt_penyakit, 'i', $id_penyakit_utama);
        mysqli_stmt_execute($stmt_penyakit);
        $result_penyakit = mysqli_stmt_get_result($stmt_penyakit);
        $penyakit_data = mysqli_fetch_assoc($result_penyakit);
        
        // Generate HTML untuk download
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Hasil Diagnosis - AGROCURE</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #2e7d32; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #2e7d32; margin: 0; }
                .header h2 { color: #666; margin: 10px 0 0; }
                .section { margin-bottom: 30px; }
                .section-title { background: #2e7d32; color: white; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
                .diagnosis-box { background: #e8f5e9; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 5px solid #2e7d32; }
                .confidence { text-align: center; background: #e3f2fd; padding: 30px; border-radius: 10px; margin: 30px 0; }
                .cf-value { font-size: 48px; font-weight: bold; color: #2e7d32; }
                table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                th { background: #2e7d32; color: white; padding: 12px; text-align: left; }
                td { padding: 10px; border: 1px solid #ddd; }
                .penanganan { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 5px solid #ff9800; }
                .footer { text-align: center; margin-top: 50px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>AGROCURE - Sistem Pakar Diagnosis Penyakit Padi</h1>
                <h2>Laporan Hasil Diagnosis</h2>
                <p>Tanggal: ' . date('d F Y H:i:s') . '</p>
            </div>
            
            <div class="diagnosis-box">
                <h2>Diagnosis Utama</h2>
                <h3>' . htmlspecialchars($penyakit_data['nama']) . ' (' . htmlspecialchars($penyakit_data['kode']) . ')</h3>
                <p><strong>Deskripsi:</strong> ' . htmlspecialchars($penyakit_data['deskripsi']) . '</p>
            </div>
            
            <div class="confidence">
                <div class="cf-value">' . number_format($diagnosis_utama['cf'] * 100, 2) . '%</div>
                <p><strong>Tingkat Keyakinan Diagnosis</strong></p>
            </div>';
        
        // Tampilkan gejala yang dipilih
        if (!empty($gejala_terpilih)) {
            echo '<div class="section">
                <div class="section-title">Gejala yang Dipilih (' . count($gejala_terpilih) . ' gejala)</div>
                <table>
                    <tr>
                        <th>No</th>
                        <th>Kode Gejala</th>
                        <th>Nama Gejala</th>
                        <th>CF User</th>
                    </tr>';
            
            $no = 1;
            foreach ($gejala_terpilih as $id_gejala => $cf_user) {
                $query_gejala = "SELECT kode, nama FROM gejala WHERE id = ?";
                $stmt_gejala = mysqli_prepare($conn, $query_gejala);
                mysqli_stmt_bind_param($stmt_gejala, 'i', $id_gejala);
                mysqli_stmt_execute($stmt_gejala);
                $result_gejala = mysqli_stmt_get_result($stmt_gejala);
                $gejala = mysqli_fetch_assoc($result_gejala);
                
                if ($gejala) {
                    echo '<tr>
                        <td>' . $no . '</td>
                        <td>' . htmlspecialchars($gejala['kode']) . '</td>
                        <td>' . htmlspecialchars($gejala['nama']) . '</td>
                        <td>' . number_format($cf_user, 2) . '</td>
                    </tr>';
                    $no++;
                }
            }
            
            echo '</table></div>';
        }
        
        // Tampilkan penanganan
        if (!empty($penyakit_data['penanganan'])) {
            echo '<div class="section">
                <div class="section-title">Rekomendasi Penanganan</div>
                <div class="penanganan">' . nl2br(htmlspecialchars($penyakit_data['penanganan'])) . '</div>
            </div>';
        }
        
        echo '<div class="footer">
                <p>Dokumen ini dibuat otomatis oleh Sistem AGROCURE</p>
                <p>© ' . date('Y') . ' AGROCURE - Diagnosis Penyakit Padi</p>
            </div>
        </body>
        </html>';
        exit;
    }
    
    // Jika TCPDF tersedia, lanjutkan dengan PDF
    require_once($tcpdf_path);
    
    // Ambil data dari session
    $hasil_diagnosis_data = $session_data['hasil_diagnosis'];
    $gejala_aktif = $session_data['gejala_aktif'] ?? [];
    $gejala_terpilih = $session_data['gejala_dipilih'] ?? [];
    
    // Proses data sama seperti di halaman
    if (isset($hasil_diagnosis_data['penyakit'])) {
        $semua_hasil_diagnosis = [$hasil_diagnosis_data];
    } else {
        $semua_hasil_diagnosis = $hasil_diagnosis_data;
    }
    
    usort($semua_hasil_diagnosis, function($a, $b) {
        return $b['cf'] <=> $a['cf'];
    });
    
    $diagnosis_utama = $semua_hasil_diagnosis[0];
    $penyakit_utama = $diagnosis_utama['penyakit'];
    $id_penyakit_utama = $penyakit_utama['id'];
    
    // Query data penyakit utama
    $query_penyakit = "SELECT p.*, t.deskripsi as penanganan 
                      FROM penyakit p 
                      LEFT JOIN penanganan t ON p.id = t.id_penyakit 
                      WHERE p.id = ?";
    $stmt_penyakit = mysqli_prepare($conn, $query_penyakit);
    mysqli_stmt_bind_param($stmt_penyakit, 'i', $id_penyakit_utama);
    mysqli_stmt_execute($stmt_penyakit);
    $result_penyakit = mysqli_stmt_get_result($stmt_penyakit);
    $penyakit_data = mysqli_fetch_assoc($result_penyakit);
    
    // Buat PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('AGROCURE');
    $pdf->SetAuthor('AGROCURE System');
    $pdf->SetTitle('Hasil Diagnosis Penyakit Padi');
    $pdf->SetSubject('Laporan Diagnosis');
    
    // Remove default header/footer
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Add a page
    $pdf->AddPage();
    
    // Set font
    $pdf->SetFont('helvetica', '', 11);
    
    // Header
    $pdf->SetFillColor(46, 125, 50);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Rect(0, 0, 210, 25, 'F');
    
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->SetXY(10, 5);
    $pdf->Cell(0, 0, '🌾 AGROCURE', 0, 1, 'L');
    
    $pdf->SetFont('helvetica', 'I', 10);
    $pdf->SetXY(10, 12);
    $pdf->Cell(0, 0, 'Sistem Pakar Diagnosis Penyakit Padi', 0, 1, 'L');
    
    // Tanggal
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(10, 30);
    $pdf->Cell(0, 0, 'Tanggal: ' . date('d F Y H:i:s'), 0, 1, 'C');
    
    // Judul
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetXY(10, 40);
    $pdf->Cell(0, 0, 'HASIL DIAGNOSIS PENYAKIT PADI', 0, 1, 'C');
    
    $y = 50;
    
    // Diagnosis Utama
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetXY(10, $y);
    $pdf->Cell(0, 0, 'DIAGNOSIS UTAMA', 0, 1);
    $y += 8;
    
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->SetXY(10, $y);
    $pdf->Cell(0, 0, $penyakit_data['nama'], 0, 1);
    $y += 7;
    
    $pdf->SetFont('helvetica', '', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(10, $y);
    $pdf->Cell(0, 0, 'Kode: ' . $penyakit_data['kode'], 0, 1);
    $y += 7;
    
    // Tingkat Keyakinan
    $cf_percentage = number_format($diagnosis_utama['cf'] * 100, 2);
    $pdf->SetFillColor(230, 247, 255);
    $pdf->Rect(10, $y, 190, 25, 'F');
    $pdf->SetDrawColor(33, 150, 243);
    $pdf->Rect(10, $y, 190, 25, 'D');
    
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetXY(10, $y + 3);
    $pdf->Cell(0, 0, 'TINGKAT KEYAKINAN', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->SetTextColor(46, 125, 50);
    $pdf->SetXY(10, $y + 8);
    $pdf->Cell(0, 0, $cf_percentage . '%', 0, 1, 'C');
    
    $y += 30;
    
    // Deskripsi Penyakit
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetXY(10, $y);
    $pdf->Cell(0, 0, 'Deskripsi:', 0, 1);
    $y += 6;
    
    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(10, $y);
    $pdf->MultiCell(0, 5, $penyakit_data['deskripsi'], 0, 'L');
    $y += $pdf->getStringHeight(190, $penyakit_data['deskripsi']) + 10;
    
    // Penanganan
    if (!empty($penyakit_data['penanganan'])) {
        $pdf->SetFillColor(255, 248, 225);
        $pdf->Rect(10, $y, 190, 40, 'F');
        $pdf->SetDrawColor(255, 152, 0);
        $pdf->Rect(10, $y, 190, 40, 'D');
        
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetTextColor(255, 87, 34);
        $pdf->SetXY(15, $y + 5);
        $pdf->Cell(0, 0, '💡 REKOMENDASI PENANGANAN', 0, 1);
        
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(15, $y + 12);
        $pdf->MultiCell(180, 5, $penyakit_data['penanganan'], 0, 'L');
        
        $y += 45;
    }
    
    // Gejala yang Dipilih
    if (!empty($gejala_terpilih)) {
        // Check if new page needed
        if ($y > 220) {
            $pdf->AddPage();
            $y = 20;
        }
        
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetXY(10, $y);
        $pdf->Cell(0, 0, 'GEJALA YANG DIPILIH (' . count($gejala_terpilih) . ' gejala)', 0, 1);
        $y += 8;
        
        // Table header
        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(10, $y);
        $pdf->Cell(15, 7, 'No', 1, 0, 'C', true);
        $pdf->Cell(25, 7, 'Kode', 1, 0, 'C', true);
        $pdf->Cell(110, 7, 'Nama Gejala', 1, 0, 'C', true);
        $pdf->Cell(40, 7, 'CF User', 1, 1, 'C', true);
        $y += 7;
        
        $pdf->SetFont('helvetica', '', 9);
        $no = 1;
        
        foreach ($gejala_terpilih as $id_gejala => $cf_user) {
            // Check if new page needed for each row
            if ($y > 250) {
                $pdf->AddPage();
                $y = 20;
                // Add header again
                $pdf->SetFillColor(240, 240, 240);
                $pdf->SetFont('helvetica', 'B', 9);
                $pdf->SetXY(10, $y);
                $pdf->Cell(15, 7, 'No', 1, 0, 'C', true);
                $pdf->Cell(25, 7, 'Kode', 1, 0, 'C', true);
                $pdf->Cell(110, 7, 'Nama Gejala', 1, 0, 'C', true);
                $pdf->Cell(40, 7, 'CF User', 1, 1, 'C', true);
                $y += 7;
                $pdf->SetFont('helvetica', '', 9);
            }
            
            $query_gejala = "SELECT kode, nama FROM gejala WHERE id = ?";
            $stmt_gejala = mysqli_prepare($conn, $query_gejala);
            mysqli_stmt_bind_param($stmt_gejala, 'i', $id_gejala);
            mysqli_stmt_execute($stmt_gejala);
            $result_gejala = mysqli_stmt_get_result($stmt_gejala);
            $gejala = mysqli_fetch_assoc($result_gejala);
            
            if ($gejala) {
                $pdf->SetXY(10, $y);
                $pdf->Cell(15, 7, $no, 1, 0, 'C');
                $pdf->Cell(25, 7, $gejala['kode'], 1, 0, 'C');
                $pdf->Cell(110, 7, substr($gejala['nama'], 0, 60), 1, 0, 'L');
                $pdf->Cell(40, 7, number_format($cf_user, 2), 1, 1, 'C');
                $y += 7;
                $no++;
            }
        }
    }
    
    // Footer
    $pdf->SetY(260);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(128, 128, 128);
    $pdf->Cell(0, 0, 'Dokumen ini dibuat otomatis oleh Sistem AGROCURE - Metode Certainty Factor', 0, 1, 'C');
    $pdf->Cell(0, 0, 'Hasil diagnosis ini merupakan perkiraan berdasarkan gejala yang dimasukkan.', 0, 1, 'C');
    $pdf->Cell(0, 0, 'Disarankan untuk berkonsultasi dengan ahli pertanian untuk penanganan lebih lanjut.', 0, 1, 'C');
    
    // Output PDF
    $filename = 'Hasil_Diagnosis_AGROCURE_' . date('Ymd_His') . '.pdf';
    $pdf->Output($filename, 'D');
}

// Proses Download PDF
if (isset($_GET['download_pdf'])) {
    generatePDF($conn, $_SESSION);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Hasil Diagnosis - AGROCURE</title>
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* ============================================================
                   ROOT & RESET
                   ============================================================ */
        :root {
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary: #ff9800;
            --accent: #2196f3;
            --danger: #f44336;
            --success: #4caf50;
            --warning: #ff9800;
            --info: #17a2b8;
            --text: #2d3748;
            --text-light: #718096;
            --background: #f7fafc;
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            --radius: 15px;
            --gradient: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            --header-height: 64px;
            --max-width: 1400px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--background);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ============================================================
                   BACKGROUND DECORATION (scaled down on mobile)
                   ============================================================ */
        .background-design {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.45;
            pointer-events: none;
        }

        .bg-shape-1,
        .bg-shape-2 {
            position: absolute;
            border-radius: 50%;
            animation: float 12s ease-in-out infinite;
        }

        .bg-shape-1 {
            top: -10%;
            right: -5%;
            width: min(500px, 80vw);
            height: min(500px, 80vw);
            background: linear-gradient(135deg, rgba(139, 195, 74, 0.15) 0%, rgba(76, 175, 80, 0.10) 100%);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
        }

        .bg-shape-2 {
            bottom: -10%;
            left: -5%;
            width: min(400px, 70vw);
            height: min(400px, 70vw);
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.10) 0%, rgba(139, 195, 74, 0.06) 100%);
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation-delay: 2s;
        }

        @keyframes float {
            0%,
            100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-18px) rotate(4deg);
            }
        }

        /* ============================================================
                   HEADER / NAVBAR (konsisten dengan halaman diagnosis)
                   ============================================================ */
        header {
            background: rgba(255, 255, 255, 0.97);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 2px 16px rgba(0, 0, 0, 0.06);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(46, 125, 50, 0.10);
            height: var(--header-height);
            display: flex;
            align-items: center;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 clamp(0.8rem, 3vw, 2rem);
            max-width: var(--max-width);
            width: 100%;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        .logo-img {
            height: 38px;
            width: auto;
            object-fit: contain;
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.05);
        }

        .logo h1 {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: clamp(1.2rem, 4vw, 1.8rem);
            font-weight: 800;
            letter-spacing: -0.3px;
            white-space: nowrap;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 0.8rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            padding: 0.4rem 1rem;
            border-radius: 40px;
            transition: all 0.3s ease;
            font-size: clamp(0.8rem, 1.4vw, 0.95rem);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap;
        }

        .nav-links a:hover {
            color: var(--primary);
            background: rgba(76, 175, 80, 0.10);
            transform: translateY(-2px);
        }

        .nav-links a i {
            font-size: 0.9em;
        }

        /* ============================================================
                   MAIN SECTION
                   ============================================================ */
        .result-section {
            padding: clamp(1rem, 3vw, 2rem) clamp(0.8rem, 3vw, 5%);
            max-width: var(--max-width);
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        .result-header {
            text-align: center;
            margin-bottom: clamp(1.5rem, 4vw, 2.5rem);
        }

        .result-title {
            font-size: clamp(1.6rem, 5vw, 2.8rem);
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 30%, #4caf50 60%, #8bc34a 80%, #cddc39 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .result-subtitle {
            font-size: clamp(0.9rem, 1.6vw, 1.1rem);
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
            padding: 0 0.5rem;
        }

        .diagnosis-timestamp {
            display: inline-block;
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.10), rgba(30, 136, 229, 0.05));
            padding: 0.6rem clamp(1rem, 2vw, 1.5rem);
            border-radius: 50px;
            margin-top: 1rem;
            border: 1px solid rgba(33, 150, 243, 0.25);
            font-size: clamp(0.8rem, 1vw, 0.95rem);
            color: var(--text);
        }

        .diagnosis-timestamp i {
            color: #2196f3;
            margin-right: 0.4rem;
        }

        /* ============================================================
                   CARD UTAMA
                   ============================================================ */
        .main-card {
            background: var(--card-bg);
            padding: clamp(1rem, 3vw, 2rem);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: clamp(1.2rem, 2.5vw, 2rem);
            border: 1px solid #e2e8f0;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            width: 100%;
            overflow: hidden;
        }

        .main-card:hover {
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.10);
            transform: translateY(-4px);
        }

        /* ============================================================
                   DIAGNOSIS UTAMA
                   ============================================================ */
        .diagnosis-utama {
            text-align: center;
            margin-bottom: clamp(1.5rem, 3vw, 3rem);
            padding-bottom: clamp(1rem, 2vw, 2rem);
            border-bottom: 2px dashed #e2e8f0;
        }

        .diagnosis-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: clamp(0.8rem, 2vw, 1.5rem);
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .rank-badge {
            background: linear-gradient(135deg, #ffd700 0%, #ffa000 100%);
            color: #000;
            width: clamp(44px, 8vw, 56px);
            height: clamp(44px, 8vw, 56px);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: clamp(1.2rem, 2.5vw, 1.6rem);
            font-weight: 800;
            box-shadow: 0 4px 12px rgba(255, 215, 0, 0.30);
            flex-shrink: 0;
        }

        .penyakit-info {
            flex: 1;
            min-width: 180px;
        }

        .penyakit-nama {
            font-size: clamp(1.4rem, 4vw, 2.2rem);
            font-weight: 800;
            color: var(--primary-dark);
            margin-bottom: 0.3rem;
            line-height: 1.2;
        }

        .penyakit-kode {
            background: var(--gradient);
            color: white;
            padding: 0.3rem clamp(1rem, 2vw, 1.5rem);
            border-radius: 25px;
            display: inline-block;
            font-weight: 600;
            font-size: clamp(0.85rem, 1.2vw, 1.1rem);
            box-shadow: 0 4px 12px rgba(46, 125, 50, 0.20);
        }

        .penyakit-deskripsi {
            font-size: clamp(0.9rem, 1.2vw, 1.1rem);
            line-height: 1.7;
            color: var(--text);
            margin-top: 1.5rem;
            padding: clamp(0.8rem, 2vw, 1.5rem);
            background: linear-gradient(135deg, rgba(139, 195, 74, 0.10), rgba(76, 175, 80, 0.05));
            border-radius: 12px;
            border-left: 4px solid var(--primary);
            text-align: left;
        }

        /* ============================================================
                   PENANGANAN DETAIL
                   ============================================================ */
        .penanganan-detail {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: 10px;
            padding: clamp(0.8rem, 2vw, 1.5rem);
            margin: 1rem 0;
            border-left: 4px solid #2e7d32;
            text-align: left;
        }

        .penanganan-detail h4 {
            color: #1b5e20;
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: clamp(0.95rem, 1.2vw, 1.1rem);
        }

        .penanganan-content {
            line-height: 1.8;
            color: var(--text);
        }

        .penanganan-list {
            list-style-type: none;
            padding-left: 0;
        }

        .penanganan-list li {
            padding: 0.4rem 0;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: clamp(0.85rem, 1vw, 0.95rem);
        }

        .penanganan-list li i {
            color: #2e7d32;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        /* ============================================================
                   CONFIDENCE BOX
                   ============================================================ */
        .confidence-box {
            background: linear-gradient(135deg, rgba(33, 150, 243, 0.10), rgba(30, 136, 229, 0.05));
            padding: clamp(1.2rem, 3vw, 2rem);
            border-radius: 12px;
            margin: clamp(1.2rem, 2.5vw, 2rem) 0;
            border: 2px solid #2196f3;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cf-value {
            font-size: clamp(2.5rem, 8vw, 3.8rem);
            font-weight: 800;
            margin-bottom: 0.3rem;
            background: linear-gradient(135deg, #1b5e20, #2e7d32, #4caf50);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cf-label {
            font-size: clamp(0.95rem, 1.5vw, 1.2rem);
            color: var(--text);
            font-weight: 600;
        }

        .confidence-box p {
            color: var(--text-light);
            margin-top: 0.8rem;
            font-size: clamp(0.8rem, 1vw, 0.95rem);
        }

        /* ============================================================
                   STATISTIK GRID
                   ============================================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: clamp(0.8rem, 1.5vw, 1.5rem);
            margin: clamp(1.2rem, 2.5vw, 2rem) 0;
        }

        .stat-card {
            background: white;
            padding: clamp(0.8rem, 1.5vw, 1.5rem);
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border-top: 4px solid var(--primary);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }

        .stat-value {
            font-size: clamp(1.8rem, 4vw, 2.8rem);
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.2rem;
            line-height: 1.2;
        }

        .stat-label {
            font-size: clamp(0.7rem, 0.9vw, 0.95rem);
            color: var(--text-light);
        }

        .stat-label i {
            margin-right: 0.3rem;
        }

        /* ============================================================
                   SECTION TITLE
                   ============================================================ */
        .section-title {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--primary-dark);
            margin-bottom: clamp(0.8rem, 2vw, 1.5rem);
            font-size: clamp(1.1rem, 2vw, 1.4rem);
            padding-bottom: 0.4rem;
            border-bottom: 2px solid var(--primary-light);
            flex-wrap: wrap;
        }

        .section-title i {
            color: var(--primary);
            font-size: 1em;
        }

        /* ============================================================
                   TABEL
                   ============================================================ */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -0.5rem;
            padding: 0 0.5rem;
        }

        .calculation-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            min-width: 520px;
        }

        .calculation-table thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        }

        .calculation-table th {
            color: white;
            padding: clamp(0.6rem, 1vw, 1.2rem) clamp(0.4rem, 0.8vw, 1rem);
            text-align: center;
            font-weight: 600;
            font-size: clamp(0.75rem, 0.9vw, 1rem);
            border: none;
            white-space: nowrap;
        }

        .calculation-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        .calculation-table tbody tr:hover {
            background-color: #f1f5f9 !important;
        }

        .calculation-table td {
            padding: clamp(0.5rem, 0.8vw, 1.2rem) clamp(0.3rem, 0.6vw, 1rem);
            text-align: center;
            color: var(--text);
            border: none;
            font-size: clamp(0.75rem, 0.9vw, 0.95rem);
            vertical-align: middle;
        }

        .calculation-table td:first-child {
            font-weight: 600;
            color: var(--text-light);
        }

        .calculation-table td[style*="text-align: left"] {
            text-align: left !important;
        }

        /* ============================================================
                   SUB CARD PER PENYAKIT
                   ============================================================ */
        .sub-card {
            background: var(--card-bg);
            padding: clamp(0.8rem, 2vw, 1.5rem);
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.2rem;
            transition: border 0.3s ease;
        }

        .sub-card.highlight {
            border: 2px solid var(--primary);
        }

        .sub-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-bottom: 0.8rem;
        }

        .sub-card-header .left {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .badge-utama {
            background: var(--primary);
            color: white;
            padding: 0.2rem 0.7rem;
            border-radius: 4px;
            font-weight: 700;
            font-size: clamp(0.65rem, 0.8vw, 0.8rem);
            white-space: nowrap;
        }

        .badge-kode {
            background: #6c757d;
            color: white;
            padding: 0.2rem 0.7rem;
            border-radius: 4px;
            font-weight: 600;
            font-size: clamp(0.65rem, 0.8vw, 0.8rem);
            white-space: nowrap;
        }

        .sub-card-header h4 {
            color: var(--text);
            margin: 0;
            font-size: clamp(0.95rem, 1.4vw, 1.2rem);
        }

        .cf-percentage {
            font-size: clamp(1.2rem, 2.5vw, 1.8rem);
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        .cf-percentage small {
            font-size: 0.7em;
            font-weight: 400;
            color: var(--text-light);
        }

        /* mini stats */
        .mini-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 0.6rem;
            margin-bottom: 0.8rem;
        }

        .mini-stat {
            text-align: center;
            padding: 0.4rem 0.2rem;
            background: #f8f9fa;
            border-radius: 6px;
        }

        .mini-stat .num {
            font-size: clamp(1rem, 1.4vw, 1.2rem);
            font-weight: 700;
            color: var(--primary-dark);
        }

        .mini-stat .lbl {
            font-size: clamp(0.6rem, 0.8vw, 0.8rem);
            color: var(--text-light);
        }

        /* ============================================================
                   TOMBOL AKSI
                   ============================================================ */
        .action-buttons {
            display: flex;
            gap: clamp(0.6rem, 1.5vw, 1rem);
            justify-content: center;
            flex-wrap: wrap;
            margin: clamp(1.2rem, 2.5vw, 2rem) 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.7rem 1.4rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: clamp(0.8rem, 1.2vw, 0.95rem);
            white-space: nowrap;
            min-height: 44px;
            flex: 0 1 auto;
            min-width: 140px;
        }

        .btn i {
            font-size: 0.95em;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.30);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.40);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 117, 125, 0.30);
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.40);
        }

        .btn-pdf {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            box-shadow: 0 4px 15px rgba(244, 67, 54, 0.30);
        }

        .btn-pdf:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(244, 67, 54, 0.40);
        }

        .btn-print {
            background: linear-gradient(135deg, #2196f3, #1976d2);
            color: white;
            box-shadow: 0 4px 15px rgba(33, 150, 243, 0.30);
        }

        .btn-print:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(33, 150, 243, 0.40);
        }

        .btn:active {
            transform: scale(0.97);
        }

        /* ============================================================
                   FOOTER
                   ============================================================ */
        footer {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
            color: white;
            padding: clamp(1.5rem, 3vw, 2.5rem) clamp(0.8rem, 3vw, 5%);
            text-align: center;
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .footer-content {
            max-width: var(--max-width);
            margin: 0 auto;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 0.5rem;
        }

        .footer-logo i {
            background: white;
            color: var(--primary);
            padding: 0.6rem;
            border-radius: 50%;
            font-size: clamp(1rem, 1.5vw, 1.2rem);
        }

        .footer-logo h2 {
            color: white;
            font-size: clamp(1.2rem, 2.5vw, 1.6rem);
            font-weight: 700;
        }

        footer p {
            opacity: 0.85;
            font-size: clamp(0.75rem, 0.9vw, 0.9rem);
        }

        /* ============================================================
                   RESPONSIVE BREAKPOINTS
                   ============================================================ */

        /* --- Tablets & small laptops (max-width: 1024px) --- */
        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
            .mini-stats {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }
        }

        /* --- Mobile landscape & small tablets (max-width: 768px) --- */
        @media (max-width: 768px) {
            :root {
                --header-height: 58px;
            }

            .navbar .logo h1 {
                font-size: 1.2rem;
            }
            .logo-img {
                height: 32px;
            }

            .diagnosis-header {
                flex-direction: column;
                text-align: center;
            }

            .penyakit-info {
                min-width: unset;
            }

            .penyakit-nama {
                font-size: 1.6rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.8rem;
            }

            .stat-card {
                padding: 0.8rem;
            }

            .stat-value {
                font-size: 1.8rem;
            }

            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
                min-width: unset;
                justify-content: center;
            }

            .sub-card-header {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            .cf-percentage {
                text-align: center;
            }

            .sub-card-header .left {
                justify-content: center;
            }

            .table-wrapper {
                margin: 0 -0.8rem;
                padding: 0 0.8rem;
            }

            .calculation-table {
                min-width: 480px;
                font-size: 0.8rem;
            }

            .calculation-table th,
            .calculation-table td {
                padding: 0.4rem 0.3rem;
            }

            .mini-stats {
                grid-template-columns: 1fr 1fr;
                gap: 0.4rem;
            }

            .result-title {
                font-size: 1.8rem;
            }

            .diagnosis-timestamp {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }

        /* --- Small phones (max-width: 480px) --- */
        @media (max-width: 480px) {
            :root {
                --header-height: 52px;
            }

            .navbar .logo h1 {
                font-size: 1.0rem;
            }
            .logo-img {
                height: 28px;
            }

            .result-title {
                font-size: 1.4rem;
            }

            .result-subtitle {
                font-size: 0.8rem;
            }

            .penyakit-nama {
                font-size: 1.3rem;
            }

            .penyakit-kode {
                font-size: 0.75rem;
                padding: 0.2rem 0.8rem;
            }

            .cf-value {
                font-size: 2.2rem;
            }

            .cf-label {
                font-size: 0.85rem;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 0.5rem;
            }

            .stat-value {
                font-size: 1.4rem;
            }
            .stat-label {
                font-size: 0.65rem;
            }

            .calculation-table {
                min-width: 360px;
                font-size: 0.7rem;
            }

            .calculation-table th,
            .calculation-table td {
                padding: 0.3rem 0.2rem;
            }

            .section-title {
                font-size: 1rem;
            }

            .sub-card-header h4 {
                font-size: 0.9rem;
            }

            .cf-percentage {
                font-size: 1.2rem;
            }

            .btn {
                font-size: 0.75rem;
                padding: 0.5rem 0.8rem;
                min-height: 38px;
            }

            .diagnosis-timestamp {
                font-size: 0.7rem;
                padding: 0.3rem 0.6rem;
            }

            .penanganan-detail h4 {
                font-size: 0.9rem;
            }

            .penanganan-list li {
                font-size: 0.8rem;
            }

            .mini-stats {
                grid-template-columns: 1fr 1fr;
                gap: 0.3rem;
            }

            .mini-stat .num {
                font-size: 0.9rem;
            }
            .mini-stat .lbl {
                font-size: 0.6rem;
            }

            .main-card {
                padding: 0.8rem;
                border-radius: 12px;
            }
        }

        /* --- Very small phones (max-width: 360px) --- */
        @media (max-width: 360px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .mini-stats {
                grid-template-columns: 1fr 1fr;
            }
            .calculation-table {
                min-width: 300px;
                font-size: 0.65rem;
            }
            .btn {
                font-size: 0.7rem;
                padding: 0.4rem 0.6rem;
                min-height: 34px;
            }
        }

        /* ============================================================
                   PRINT STYLES
                   ============================================================ */
        @media print {
            .background-design,
            .action-buttons,
            footer,
            header {
                display: none !important;
            }

            body {
                font-size: 12pt !important;
                background: white !important;
                color: black !important;
            }

            .main-card {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
                page-break-inside: avoid;
                transform: none !important;
            }

            .calculation-table {
                page-break-inside: avoid;
                border: 1px solid #000;
            }

            .cf-value {
                -webkit-text-fill-color: black !important;
                color: black !important;
                background: none !important;
            }

            .result-title {
                -webkit-text-fill-color: black !important;
                color: black !important;
                background: none !important;
            }

            .sub-card {
                border: 1px solid #ccc !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- ====== BACKGROUND ====== -->
    <div class="background-design">
        <div class="bg-shape-1"></div>
        <div class="bg-shape-2"></div>
    </div>

    <!-- ====== HEADER ====== -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="images/logo.png" alt="Logo AGROCURE" class="logo-img" loading="lazy">
                <h1>AGROCURE</h1>
            </div>
            <ul class="nav-links">
                <li><a href="admin/login.php"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
        </nav>
    </header>

    <!-- ====== HASIL DIAGNOSIS ====== -->
    <section class="result-section">
        <div class="result-header">
            <h1 class="result-title">Hasil Diagnosis Penyakit Padi</h1>
            <p class="result-subtitle">Sistem Pakar AGROCURE – Metode Certainty Factor</p>
            <div class="diagnosis-timestamp">
                <i class="fas fa-calendar-alt"></i>
                <strong>Tanggal:</strong> <?php echo $tanggal_diagnosis; ?> &nbsp;|&nbsp;
                <i class="fas fa-clock"></i>
                <strong>Waktu:</strong> <?php echo $waktu_diagnosis; ?>
            </div>
        </div>

        <?php if (empty($penyakit_data)): ?>
            <div class="main-card" style="text-align: center; padding: 3rem;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #ffc107; margin-bottom: 1rem;"></i>
                <h3 style="color: var(--text); margin-bottom: 1rem;">Data penyakit tidak ditemukan</h3>
                <a href="diagnosis.php" class="btn btn-primary">
                    <i class="fas fa-redo"></i> Diagnosa Ulang
                </a>
            </div>
        <?php else: ?>

            <!-- ====== DIAGNOSIS UTAMA ====== -->
            <div class="main-card">
                <div class="diagnosis-utama">
                    <div class="diagnosis-header">
                        <div class="rank-badge">
                            <i class="fas fa-crown"></i>
                        </div>
                        <div class="penyakit-info">
                            <h2 class="penyakit-nama"><?php echo htmlspecialchars($penyakit_data['nama']); ?></h2>
                            <div class="penyakit-kode"><?php echo htmlspecialchars($penyakit_data['kode']); ?></div>
                        </div>
                    </div>

                    <div class="penyakit-deskripsi">
                        <i class="fas fa-info-circle" style="color: var(--primary); margin-right: 0.5rem;"></i>
                        <?php echo htmlspecialchars($penyakit_data['deskripsi']); ?>
                    </div>

                    <!-- Penanganan utama -->
                    <?php if (!empty($penyakit_data['penanganan'])): ?>
                        <div class="penanganan-detail">
                            <h4><i class="fas fa-stethoscope"></i> Penanganan yang Direkomendasikan:</h4>
                            <div class="penanganan-content">
                                <?php
                                $penanganan_formatted = nl2br(htmlspecialchars($penyakit_data['penanganan']));
                                echo $penanganan_formatted;
                                ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="penanganan-detail" style="background: linear-gradient(135deg, #fff3cd, #ffeaa7); border-left-color: #ff9800;">
                            <h4><i class="fas fa-exclamation-triangle"></i> Informasi Penanganan:</h4>
                            <p style="color: #856404; font-style: italic;">
                                Informasi penanganan untuk penyakit ini sedang dalam pengembangan.
                                Disarankan untuk berkonsultasi dengan ahli pertanian.
                            </p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Confidence Box -->
                <div class="confidence-box">
                    <div class="cf-value"><?php echo number_format($cf_final * 100, 2); ?>%</div>
                    <div class="cf-label">TINGKAT KEYAKINAN DIAGNOSIS</div>
                    <p>
                        Berdasarkan perhitungan Certainty Factor dari <?php echo count($semua_gejala_user); ?> gejala yang dipilih
                        pada <?php echo $tanggal_waktu_lengkap; ?>
                    </p>
                </div>

                <!-- Statistik -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($semua_gejala_user); ?></div>
                        <div class="stat-label"><i class="fas fa-list-check"></i> Total Gejala Dipilih</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo $perhitungan_utama ? $perhitungan_utama['jumlah_gejala'] : 0; ?></div>
                        <div class="stat-label"><i class="fas fa-check-circle"></i> Gejala yang Cocok</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo count($semua_perhitungan); ?></div>
                        <div class="stat-label"><i class="fas fa-virus"></i> Penyakit Terdeteksi</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value"><?php echo number_format($cf_final * 100, 2); ?>%</div>
                        <div class="stat-label"><i class="fas fa-chart-line"></i> Tingkat Keyakinan</div>
                    </div>
                </div>
            </div>

            <!-- ====== GEJALA YANG DIPILIH ====== -->
            <?php if (!empty($semua_gejala_user)): ?>
                <div class="main-card">
                    <h3 class="section-title">
                        <i class="fas fa-list-check"></i> Gejala yang Dipilih (<?php echo count($semua_gejala_user); ?> gejala)
                    </h3>
                    <div class="table-wrapper">
                        <table class="calculation-table">
                            <thead>
                                <tr>
                                    <th width="50">No</th>
                                    <th>Kode</th>
                                    <th style="text-align:left; padding-left:1rem;">Nama Gejala</th>
                                    <th width="100">CF User</th>
                                    <th width="90">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no_gejala = 1; ?>
                                <?php foreach ($semua_gejala_user as $gejala): ?>
                                    <tr>
                                        <td><?php echo $no_gejala++; ?></td>
                                        <td style="font-weight:600; color:var(--primary-dark);"><?php echo htmlspecialchars($gejala['kode']); ?></td>
                                        <td style="text-align:left; padding-left:1rem;"><?php echo htmlspecialchars($gejala['nama']); ?></td>
                                        <td style="color:#2196f3; font-weight:600;"><?php echo number_format($gejala['cf_user'], 2); ?></td>
                                        <td>
                                            <?php
                                            $status_class = $gejala['cf_user'] >= 0.8 ? 'success' : ($gejala['cf_user'] >= 0.5 ? 'warning' : 'danger');
                                            $status_text = $gejala['cf_user'] >= 0.8 ? 'Tinggi' : ($gejala['cf_user'] >= 0.5 ? 'Sedang' : 'Rendah');
                                            $bg_color = $status_class == 'success' ? '#d4edda' : ($status_class == 'warning' ? '#fff3cd' : '#f8d7da');
                                            $text_color = $status_class == 'success' ? '#155724' : ($status_class == 'warning' ? '#856404' : '#721c24');
                                            ?>
                                            <span style="display:inline-block; padding:0.2rem 0.6rem; border-radius:20px; background:<?php echo $bg_color; ?>; color:<?php echo $text_color; ?>; font-size:clamp(0.6rem,0.8vw,0.85rem); font-weight:600;">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ====== PERHITUNGAN CF SEMUA PENYAKIT ====== -->
            <?php if (count($semua_perhitungan) > 0): ?>
                <div class="main-card">
                    <h3 class="section-title">
                        <i class="fas fa-calculator"></i> Hasil Perhitungan CF Semua Penyakit
                    </h3>
                    <p style="color:var(--text-light); margin-bottom:1.2rem; text-align:center; font-size:clamp(0.8rem,1vw,0.95rem);">
                        Hasil perhitungan dan rekomendasi penanganan untuk semua penyakit yang terdeteksi
                    </p>

                    <?php foreach ($semua_perhitungan as $id_penyakit => $perhitungan): ?>
                        <div class="sub-card <?php echo ($id_penyakit == $id_penyakit_utama) ? 'highlight' : ''; ?>">
                            <div class="sub-card-header">
                                <div class="left">
                                    <?php if ($id_penyakit == $id_penyakit_utama): ?>
                                        <span class="badge-utama"><i class="fas fa-crown"></i> DIAGNOSIS UTAMA</span>
                                    <?php endif; ?>
                                    <span class="badge-kode"><?php echo htmlspecialchars($perhitungan['penyakit']['kode']); ?></span>
                                    <h4><?php echo htmlspecialchars($perhitungan['penyakit']['nama']); ?></h4>
                                </div>
                                <div class="cf-percentage">
                                    <?php echo number_format($perhitungan['cf_final'] * 100, 2); ?>%
                                    <small>CF Final: <?php echo number_format($perhitungan['cf_final'], 4); ?></small>
                                </div>
                            </div>

                            <!-- Deskripsi -->
                            <div style="margin-bottom:0.8rem; padding:0.6rem 0.8rem; background:#f8f9fa; border-radius:8px; font-size:clamp(0.8rem,1vw,0.95rem);">
                                <i class="fas fa-info-circle" style="color:#17a2b8; margin-right:0.4rem;"></i>
                                <?php echo htmlspecialchars($perhitungan['penyakit']['deskripsi']); ?>
                            </div>

                            <!-- Mini stats -->
                            <div class="mini-stats">
                                <div class="mini-stat">
                                    <div class="num"><?php echo $perhitungan['jumlah_gejala']; ?></div>
                                    <div class="lbl">Gejala Cocok</div>
                                </div>
                                <div class="mini-stat">
                                    <div class="num"><?php echo number_format($perhitungan['total_cf_user'], 2); ?></div>
                                    <div class="lbl">Total CF User</div>
                                </div>
                                <div class="mini-stat">
                                    <div class="num"><?php echo number_format($perhitungan['total_cf_pakar'], 2); ?></div>
                                    <div class="lbl">Total CF Pakar</div>
                                </div>
                                <div class="mini-stat">
                                    <div class="num"><?php echo number_format($perhitungan['total_cf_hasil'], 4); ?></div>
                                    <div class="lbl">Total CF Hasil</div>
                                </div>
                            </div>

                            <!-- Penanganan -->
                            <?php if (!empty($perhitungan['penanganan']) && $perhitungan['penanganan'] !== 'Penanganan belum tersedia'): ?>
                                <div class="penanganan-detail" style="margin-top:0.5rem;">
                                    <h4 style="font-size:clamp(0.9rem,1.1vw,1rem);"><i class="fas fa-stethoscope"></i> Rekomendasi Penanganan:</h4>
                                    <div class="penanganan-content">
                                        <?php
                                        $penanganan_items = preg_split('/\n|\r\n?/', $perhitungan['penanganan']);
                                        echo '<ul class="penanganan-list">';
                                        foreach ($penanganan_items as $item) {
                                            if (trim($item) !== '') {
                                                echo '<li><i class="fas fa-check-circle"></i> ' . htmlspecialchars(trim($item)) . '</li>';
                                            }
                                        }
                                        echo '</ul>';
                                        ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div style="padding:0.6rem; background:#fff3cd; border-radius:8px; text-align:center; margin-top:0.5rem; font-size:clamp(0.75rem,0.9vw,0.9rem);">
                                    <i class="fas fa-exclamation-triangle" style="color:#ff9800; margin-right:0.4rem;"></i>
                                    <span style="color:#856404; font-style:italic;">Informasi penanganan untuk penyakit ini sedang dalam pengembangan.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <!-- KETERANGAN -->
                    <div style="margin-top:1.5rem; padding:1rem 1.2rem; background:linear-gradient(135deg,#f8f9fa,#e9ecef); border-radius:8px; border-left:4px solid #6c757d;">
                        <div style="display:flex; align-items:flex-start; gap:0.8rem; flex-wrap:wrap;">
                            <i class="fas fa-info-circle" style="color:#6c757d; margin-top:0.2rem;"></i>
                            <div style="flex:1; font-size:clamp(0.8rem,0.9vw,0.95rem);">
                                <strong style="display:block; margin-bottom:0.3rem;">Interpretasi Hasil:</strong>
                                <div style="display:flex; flex-wrap:wrap; gap:0.8rem 1.5rem;">
                                    <span><span style="display:inline-block; width:12px; height:12px; background:#2e7d32; border-radius:2px; margin-right:5px;"></span> <strong>≥ 70%</strong> – Tinggi</span>
                                    <span><span style="display:inline-block; width:12px; height:12px; background:#ff9800; border-radius:2px; margin-right:5px;"></span> <strong>50–69%</strong> – Sedang</span>
                                    <span><span style="display:inline-block; width:12px; height:12px; background:#f44336; border-radius:2px; margin-right:5px;"></span> <strong>&lt; 50%</strong> – Rendah</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ====== TOMBOL AKSI ====== -->
            <div class="main-card">
                <div class="action-buttons">
                    <a href="diagnosis.php?from_result=true" class="btn btn-primary">
                        <i class="fas fa-redo"></i> Diagnosa Ulang
                    </a>
                    <button onclick="window.print()" class="btn btn-print">
                        <i class="fas fa-print"></i> Cetak Hasil
                    </button>
                    <a href="?download_pdf=1" class="btn btn-pdf" onclick="showDownloadNotification()">
                        <i class="fas fa-file-pdf"></i> Download PDF
                    </a>
                </div>

                <div style="text-align:center; margin-top:1.5rem; padding:1rem 1.2rem; background:linear-gradient(135deg,rgba(233,30,99,0.08),rgba(156,39,176,0.04)); border-radius:10px; border-left:4px solid #e91e63;">
                    <div style="display:flex; align-items:center; justify-content:center; gap:0.8rem; flex-wrap:wrap;">
                        <i class="fas fa-calendar-check" style="color:#e91e63; font-size:1.3rem;"></i>
                        <div style="text-align:center; font-size:clamp(0.8rem,1vw,0.95rem);">
                            <strong>Diagnosis dilakukan pada:</strong>
                            <span style="color:var(--text-light); display:inline-block; margin-left:0.3rem;">
                                <?php echo $tanggal_waktu_lengkap; ?> WIB
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fas fa-leaf"></i>
                <h2>AGROCURE</h2>
            </div>
            <p>&copy; 2025 AGROCURE – Sistem Pakar Diagnosis Penyakit Padi</p>
            <p style="margin-top:0.3rem; font-size:clamp(0.7rem,0.8vw,0.9rem); opacity:0.9;">
                <i class="fas fa-graduation-cap"></i> oleh zigi arizona
            </p>
        </div>
    </footer>

    <!-- ====== JAVASCRIPT ====== -->
    <script>
        (function() {
            'use strict';

            // Animasi kartu saat halaman dimuat
            document.addEventListener('DOMContentLoaded', function() {
                const cards = document.querySelectorAll('.main-card, .sub-card');
                cards.forEach((card, index) => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(18px)';
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 150 * index);
                });
                // Scroll halus ke atas
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            // Notifikasi download PDF
            window.showDownloadNotification = function() {
                alert('Silakan tunggu, file PDF sedang diproses...');
            };

        })();
    </script>

</body>
</html>