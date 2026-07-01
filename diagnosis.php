<?php
session_start();
include('config/database.php');

// Error reporting - nonaktifkan untuk production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Inisialisasi variabel
$clear_localstorage = false;
$current_tab = 1;
$total_tabs = 2;
$gejala = [];
$error = '';

// Reset data diagnosis jika kembali dari hasil.php
if (isset($_GET['from_result']) && $_GET['from_result'] == 'true') {
    unset($_SESSION['hasil_diagnosis']);
    unset($_SESSION['gejala_dipilih']);
    unset($_SESSION['gejala_aktif']);
    $clear_localstorage = true;
}

// Ambil data gejala dari database
$query_gejala = "SELECT * FROM gejala ORDER BY id";
$result_gejala = mysqli_query($conn, $query_gejala);

if ($result_gejala && mysqli_num_rows($result_gejala) > 0) {
    while ($row = mysqli_fetch_assoc($result_gejala)) {
        $gejala[] = $row;
    }
}

// Nilai CF User dengan ikon dan warna
$cf_user_options = [
    '1' => ['value' => 1.0, 'label' => 'Pasti', 'color' => '#2e7d32', 'icon' => 'fa-check-circle'],
    '2' => ['value' => 0.8, 'label' => 'Hampir Pasti', 'color' => '#4caf50', 'icon' => 'fa-check'],
    '3' => ['value' => 0.6, 'label' => 'Kemungkinan Besar', 'color' => '#8bc34a', 'icon' => 'fa-thumbs-up'],
    '4' => ['value' => 0.4, 'label' => 'Mungkin', 'color' => '#cddc39', 'icon' => 'fa-question'],
    '5' => ['value' => 0.2, 'label' => 'Tidak Tahu', 'color' => '#ff9800', 'icon' => 'fa-eye-slash'],
    '6' => ['value' => 0.0, 'label' => 'Tidak Ada', 'color' => '#f44336', 'icon' => 'fa-times-circle']
];

// Proses form diagnosis
function prosesDiagnosis($gejala_terpilih, $cf_user_options, $conn) {
    if (!is_array($gejala_terpilih) || empty($gejala_terpilih)) {
        return ['error' => "Data gejala tidak valid."];
    }
    
    $gejala_aktif = [];
    foreach ($gejala_terpilih as $id_gejala => $cf_user_key) {
        if ($cf_user_key != '0' && isset($cf_user_options[$cf_user_key])) {
            $gejala_aktif[$id_gejala] = $cf_user_options[$cf_user_key]['value'];
        }
    }
    
    if (empty($gejala_aktif)) {
        return ['error' => "Silakan pilih tingkat keyakinan untuk setidaknya beberapa gejala."];
    }
    
    $query_penyakit = "SELECT * FROM penyakit";
    $result_penyakit = mysqli_query($conn, $query_penyakit);
    $penyakit_list = [];
    
    if ($result_penyakit && mysqli_num_rows($result_penyakit) > 0) {
        while ($row = mysqli_fetch_assoc($result_penyakit)) {
            $penyakit_list[$row['id']] = $row;
        }
    }
    
    if (empty($penyakit_list)) {
        return ['error' => "Data penyakit tidak ditemukan."];
    }
    
    $cf_penyakit = [];
    
    foreach ($penyakit_list as $id_penyakit => $penyakit) {
        $query_aturan = "SELECT * FROM aturan WHERE id_penyakit = " . intval($id_penyakit);
        $result_aturan = mysqli_query($conn, $query_aturan);
        $cf_combined = 0;
        
        if ($result_aturan && mysqli_num_rows($result_aturan) > 0) {
            while ($aturan = mysqli_fetch_assoc($result_aturan)) {
                $id_gejala = $aturan['id_gejala'];
                $cf_pakar = floatval($aturan['cf_pakar']);
                
                if (isset($gejala_aktif[$id_gejala])) {
                    $cf_user = floatval($gejala_aktif[$id_gejala]);
                    $cf_gejala = $cf_user * $cf_pakar;
                    
                    if ($cf_combined == 0) {
                        $cf_combined = $cf_gejala;
                    } else {
                        $cf_combined = $cf_combined + $cf_gejala * (1 - $cf_combined);
                    }
                }
            }
        }
        
        if ($cf_combined > 0) {
            $cf_penyakit[$id_penyakit] = $cf_combined;
        }
    }
    
    arsort($cf_penyakit);
    
    if (!empty($cf_penyakit)) {
        $id_penyakit_tertinggi = key($cf_penyakit);
        $cf_tertinggi = current($cf_penyakit);
        
        $query_penanganan = "SELECT * FROM penanganan WHERE id_penyakit = " . intval($id_penyakit_tertinggi);
        $result_penanganan = mysqli_query($conn, $query_penanganan);
        $penanganan = ($result_penanganan && mysqli_num_rows($result_penanganan) > 0) 
            ? mysqli_fetch_assoc($result_penanganan) 
            : null;
        
        return [
            'success' => true,
            'hasil_diagnosis' => [
                'penyakit' => $penyakit_list[$id_penyakit_tertinggi],
                'cf' => $cf_tertinggi,
                'penanganan' => $penanganan
            ],
            'gejala_terpilih' => $gejala_terpilih,
            'gejala_aktif' => $gejala_aktif
        ];
    } else {
        return ['error' => "Tidak dapat mengidentifikasi penyakit berdasarkan gejala yang dipilih."];
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gejala'])) {
    $gejala_terpilih = $_POST['gejala'];
    $result = prosesDiagnosis($gejala_terpilih, $cf_user_options, $conn);
    
    if (isset($result['success']) && $result['success']) {
        $_SESSION['hasil_diagnosis'] = $result['hasil_diagnosis'];
        $_SESSION['gejala_dipilih'] = $result['gejala_terpilih'];
        $_SESSION['gejala_aktif'] = $result['gejala_aktif'];
        
        header('Location: hasil.php');
        exit;
    } else {
        $error = $result['error'];
    }
}

$gejala_per_tab = ceil(count($gejala) / 2);
$tab1_gejala = array_slice($gejala, 0, $gejala_per_tab);
$tab2_gejala = array_slice($gejala, $gejala_per_tab);

if (isset($_GET['tab'])) {
    $current_tab = max(1, min($total_tabs, intval($_GET['tab'])));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0" />
    <title>Diagnosis - AGROCURE</title>
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        /* ============================================================
                   ROOT & RESET
                   ============================================================ */
        :root {
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary: #ff9800;
            --accent: #8bc34a;
            --text: #1a202c;
            --text-light: #4a5568;
            --background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e9 100%);
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.98);
            --shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 25px 40px -12px rgba(46, 125, 50, 0.25);
            --radius: 20px;
            --gradient: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            --gradient-light: linear-gradient(135deg, rgba(46, 125, 50, 0.08) 0%, rgba(76, 175, 80, 0.05) 100%);
            --header-height: 64px;
            --max-width: 1400px;
            --card-padding: 2rem;
        }

        *,
        *::before,
        *::after {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--background);
            overflow-x: hidden;
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
            opacity: 0.35;
            pointer-events: none;
        }

        .bg-shape-1,
        .bg-shape-2,
        .bg-shape-3 {
            position: absolute;
            border-radius: 50%;
            animation: float 12s ease-in-out infinite;
        }

        .bg-shape-1 {
            top: -10%;
            right: -5%;
            width: min(500px, 80vw);
            height: min(500px, 80vw);
            background: linear-gradient(135deg, rgba(139, 195, 74, 0.2) 0%, rgba(76, 175, 80, 0.12) 100%);
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

        .bg-shape-3 {
            top: 40%;
            left: 20%;
            width: min(300px, 60vw);
            height: min(300px, 60vw);
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.10) 0%, rgba(139, 195, 74, 0.04) 100%);
            border-radius: 50% 20% 80% 40% / 40% 80% 20% 60%;
            animation-delay: 4s;
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
                   HEADER / NAVBAR
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

        /* Hamburger */
        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
            padding: 4px;
            border-radius: 8px;
            transition: background 0.2s;
            flex-shrink: 0;
        }

        .hamburger:hover {
            background: rgba(46, 125, 50, 0.06);
        }

        .hamburger span {
            width: 26px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            border-radius: 4px;
            transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: center;
            display: block;
        }

        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        .hamburger.active span:nth-child(2) {
            opacity: 0;
            transform: scaleX(0);
        }
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(5px, -5px);
        }

        /* ============================================================
                   MAIN SECTION
                   ============================================================ */
        .diagnosis-section {
            padding: clamp(1rem, 3vw, 3rem) clamp(0.8rem, 3vw, 5%);
            max-width: var(--max-width);
            width: 100%;
            margin: 0 auto;
            flex: 1;
        }

        .section-title {
            text-align: center;
            margin-bottom: clamp(1.5rem, 4vw, 2.5rem);
        }

        .section-title h2 {
            font-size: clamp(1.6rem, 5vw, 2.8rem);
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 30%, #4caf50 60%, #8bc34a 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .section-title p {
            font-size: clamp(0.9rem, 1.6vw, 1.1rem);
            color: var(--text-light);
            max-width: 700px;
            margin: 0 auto;
            padding: 0 0.5rem;
        }

        /* ============================================================
                   CARD
                   ============================================================ */
        .card {
            background: var(--card-bg);
            padding: clamp(1rem, 3vw, 2.5rem);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.4);
            transition: all 0.3s ease;
            width: 100%;
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--shadow-hover);
        }

        /* ============================================================
                   LEGEND
                   ============================================================ */
        .legend {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.05), rgba(76, 175, 80, 0.03));
            padding: clamp(0.8rem, 2vw, 1.2rem);
            border-radius: 16px;
            border: 1px solid rgba(46, 125, 50, 0.12);
            margin-bottom: clamp(1.2rem, 2.5vw, 2rem);
        }

        .legend-title {
            font-weight: 700;
            margin-bottom: 0.6rem;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.85rem, 1.2vw, 1rem);
        }

        .legend-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(90px, 1fr));
            gap: 0.4rem 0.6rem;
            font-size: clamp(0.7rem, 1.1vw, 0.85rem);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.25rem 0.3rem;
            border-radius: 8px;
            transition: all 0.2s ease;
            white-space: nowrap;
        }

        .legend-item:hover {
            background: rgba(46, 125, 50, 0.08);
            transform: translateY(-1px);
        }

        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
        }

        /* ============================================================
                   TABS
                   ============================================================ */
        .tabs {
            display: flex;
            gap: 0.6rem;
            margin-bottom: clamp(1.2rem, 2.5vw, 2rem);
            flex-wrap: wrap;
        }

        .tab-btn {
            padding: 0.6rem clamp(1rem, 2vw, 2rem);
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: clamp(0.8rem, 1.2vw, 0.95rem);
            flex: 0 1 auto;
            white-space: nowrap;
        }

        .tab-btn i {
            font-size: clamp(0.8rem, 1vw, 1rem);
        }

        .tab-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
            background: rgba(76, 175, 80, 0.04);
        }

        .tab-btn.active {
            background: var(--gradient);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(46, 125, 50, 0.25);
        }

        .tab-btn .badge {
            font-size: 0.7rem;
            background: rgba(0, 0, 0, 0.08);
            padding: 0.1rem 0.5rem;
            border-radius: 20px;
            font-weight: 700;
        }

        .tab-btn.active .badge {
            background: rgba(255, 255, 255, 0.20);
        }

        /* ============================================================
                   TAB CONTENT
                   ============================================================ */
        .tab-content {
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(8px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ============================================================
                   GEJALA GRID
                   ============================================================ */
        .gejala-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(100%, 340px), 1fr));
            gap: clamp(0.8rem, 1.5vw, 1.5rem);
            max-height: 520px;
            overflow-y: auto;
            padding: 0.25rem 0.25rem 0.25rem 0;
        }

        .gejala-grid::-webkit-scrollbar {
            width: 6px;
        }

        .gejala-grid::-webkit-scrollbar-track {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .gejala-grid::-webkit-scrollbar-thumb {
            background: var(--primary-light);
            border-radius: 10px;
        }

        .gejala-grid::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* ============================================================
                   GEJALA ITEM
                   ============================================================ */
        .gejala-item {
            background: white;
            padding: clamp(0.8rem, 1.5vw, 1.5rem);
            border-radius: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
            border: 1px solid #eef2f6;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .gejala-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 3px;
            height: 100%;
            background: var(--gradient);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .gejala-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(46, 125, 50, 0.10);
            border-color: var(--primary-light);
        }

        .gejala-item:hover::before {
            opacity: 1;
        }

        .gejala-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .gejala-kode {
            background: var(--gradient);
            color: white;
            padding: 0.2rem 0.8rem;
            border-radius: 20px;
            font-size: clamp(0.65rem, 0.9vw, 0.8rem);
            font-weight: 700;
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.18);
            letter-spacing: 0.3px;
            flex-shrink: 0;
        }

        .gejala-text {
            font-size: clamp(0.85rem, 1.2vw, 1rem);
            line-height: 1.5;
            color: var(--text);
            font-weight: 500;
            margin-bottom: 0.8rem;
            padding-left: 0.25rem;
        }

        /* ============================================================
                   CF OPTIONS
                   ============================================================ */
        .cf-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.4rem;
        }

        .cf-option {
            position: relative;
        }

        .cf-option input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
            pointer-events: none;
        }

        .cf-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.3rem;
            padding: 0.45rem 0.2rem;
            text-align: center;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: clamp(0.65rem, 0.9vw, 0.85rem);
            font-weight: 500;
            cursor: pointer;
            transition: all 0.25s ease;
            min-height: 38px;
            word-break: break-word;
            line-height: 1.2;
        }

        .cf-option label i {
            font-size: clamp(0.7rem, 0.9vw, 0.9rem);
            flex-shrink: 0;
        }

        .cf-option input:checked+label {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.14), rgba(76, 175, 80, 0.08));
            border-color: var(--primary);
            color: var(--primary-dark);
            font-weight: 600;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(46, 125, 50, 0.10);
        }

        .cf-option label:hover {
            border-color: var(--primary-light);
            background: rgba(76, 175, 80, 0.05);
            transform: translateY(-1px);
        }

        .cf-option label:active {
            transform: scale(0.97);
        }

        /* ============================================================
                   SELECTION INFO
                   ============================================================ */
        .selection-info {
            background: var(--gradient-light);
            padding: clamp(0.8rem, 1.8vw, 1.5rem);
            border-radius: 16px;
            margin: clamp(1rem, 2vw, 2rem) 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.8rem 1.5rem;
            border: 1px solid rgba(46, 125, 50, 0.10);
        }

        .selection-stats {
            display: flex;
            gap: clamp(0.8rem, 2vw, 2rem);
            align-items: center;
            flex-wrap: wrap;
        }

        .stat-box {
            text-align: center;
            background: white;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            min-width: 60px;
        }

        .stat-number {
            font-size: clamp(1.4rem, 2.8vw, 2rem);
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1.2;
        }

        .stat-label {
            font-size: clamp(0.6rem, 0.8vw, 0.85rem);
            color: var(--text-light);
            display: block;
            margin-top: -2px;
        }

        .progress-container {
            flex: 1;
            min-width: 120px;
            max-width: 350px;
            width: 100%;
        }

        .progress-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 0.3rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--gradient);
            border-radius: 20px;
            transition: width 0.6s ease;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.30), transparent);
            animation: shimmer 2.4s infinite;
        }

        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        .progress-label {
            text-align: center;
            font-size: clamp(0.7rem, 0.9vw, 0.85rem);
            color: var(--text-light);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        /* ============================================================
                   ACTION BUTTONS
                   ============================================================ */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: clamp(0.6rem, 1.5vw, 1rem);
            margin-top: clamp(1.2rem, 2.5vw, 2rem);
            flex-wrap: wrap;
        }

        .action-buttons .btn-group {
            display: flex;
            gap: clamp(0.4rem, 1vw, 1rem);
            flex-wrap: wrap;
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
            flex-shrink: 0;
        }

        .btn i {
            font-size: 0.95em;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a90e2, #357abd);
            color: white;
            box-shadow: 0 4px 14px rgba(74, 144, 226, 0.30);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(74, 144, 226, 0.40);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(108, 117, 125, 0.30);
        }

        .btn-diagnosis {
            background: linear-gradient(135deg, #e65100 0%, #ff9800 100%);
            color: white;
            box-shadow: 0 4px 14px rgba(255, 111, 0, 0.30);
        }

        .btn-diagnosis:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(255, 111, 0, 0.40);
        }

        .btn:active {
            transform: scale(0.97);
        }

        /* ============================================================
                   MESSAGES
                   ============================================================ */
        .error-message {
            background: linear-gradient(135deg, #ffeaea, #ffd6d6);
            color: #d32f2f;
            padding: 0.8rem 1.2rem;
            border-radius: 14px;
            margin-bottom: 1.2rem;
            border-left: 4px solid #d32f2f;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            font-size: clamp(0.85rem, 1vw, 0.95rem);
        }

        .saved-indicator {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.10), rgba(46, 125, 50, 0.05));
            color: var(--primary);
            padding: 0.6rem 1rem;
            border-radius: 12px;
            margin-top: 0.8rem;
            text-align: center;
            font-size: clamp(0.8rem, 0.9vw, 0.9rem);
            display: none;
            border: 1px solid rgba(76, 175, 80, 0.25);
            animation: slideDown 0.35s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .footer-logo img {
            height: 34px;
            width: auto;
            filter: brightness(0) invert(1);
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
            :root {
                --card-padding: 1.5rem;
            }
            .gejala-grid {
                grid-template-columns: repeat(auto-fill, minmax(min(100%, 300px), 1fr));
                max-height: 480px;
            }
            .legend-items {
                grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
            }
        }

        /* --- Mobile landscape & small tablets (max-width: 768px) --- */
        @media (max-width: 768px) {
            :root {
                --header-height: 58px;
                --card-padding: 1rem;
            }

            /* NAV */
            .hamburger {
                display: flex;
            }

            .nav-links {
                position: fixed;
                top: var(--header-height);
                right: -100%;
                flex-direction: column;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                width: 75%;
                max-width: 280px;
                height: calc(100vh - var(--header-height));
                text-align: center;
                box-shadow: -8px 0 30px rgba(0, 0, 0, 0.08);
                border-radius: 20px 0 0 20px;
                transition: right 0.35s cubic-bezier(0.4, 0, 0.2, 1);
                padding: 1.2rem 0;
                gap: 0.2rem;
                border-left: 1px solid rgba(46, 125, 50, 0.06);
                overflow-y: auto;
                justify-content: flex-start;
                align-items: stretch;
            }

            .nav-links.active {
                right: 0;
            }

            .nav-links li {
                margin: 0;
                list-style: none;
            }

            .nav-links a {
                display: flex;
                padding: 0.7rem 1.5rem;
                justify-content: center;
                border-radius: 0;
                font-size: 0.95rem;
                white-space: normal;
                border-bottom: 1px solid rgba(0, 0, 0, 0.04);
            }

            .nav-links a:hover {
                background: rgba(76, 175, 80, 0.06);
                transform: none;
            }

            /* GEJALA GRID */
            .gejala-grid {
                grid-template-columns: 1fr;
                max-height: 420px;
                gap: 0.8rem;
            }

            /* CF OPTIONS -> 2 columns on small screens */
            .cf-options {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.3rem;
            }

            .cf-option label {
                padding: 0.35rem 0.15rem;
                font-size: 0.7rem;
                min-height: 34px;
                border-radius: 8px;
            }

            .cf-option label i {
                font-size: 0.7rem;
            }

            /* LEGEND -> 3 columns */
            .legend-items {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.2rem 0.4rem;
                font-size: 0.7rem;
            }

            .legend-item {
                white-space: normal;
                padding: 0.15rem 0.2rem;
            }

            .legend-color {
                width: 10px;
                height: 10px;
            }

            /* TABS */
            .tabs {
                gap: 0.4rem;
            }

            .tab-btn {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
                flex: 1 0 auto;
                justify-content: center;
                white-space: nowrap;
            }

            .tab-btn i {
                font-size: 0.75rem;
            }

            .tab-btn .badge {
                font-size: 0.6rem;
                padding: 0.05rem 0.4rem;
            }

            /* SELECTION INFO */
            .selection-info {
                flex-direction: column;
                align-items: stretch;
                gap: 0.6rem;
                padding: 0.8rem 1rem;
            }

            .selection-stats {
                justify-content: center;
                gap: 0.8rem;
            }

            .stat-box {
                padding: 0.2rem 0.8rem;
                min-width: 50px;
            }

            .stat-number {
                font-size: 1.4rem;
            }

            .progress-container {
                max-width: 100%;
            }

            /* ACTION BUTTONS */
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
                gap: 0.6rem;
            }

            .action-buttons .btn-group {
                flex-direction: row;
                justify-content: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
                min-height: 40px;
                flex: 1 1 auto;
                min-width: 80px;
            }

            .btn-diagnosis {
                width: 100%;
                justify-content: center;
            }

            /* CARD */
            .card {
                padding: 1rem;
                border-radius: 16px;
            }

            /* SECTION TITLE */
            .section-title h2 {
                font-size: 1.6rem;
            }

            .section-title p {
                font-size: 0.85rem;
            }

            /* GEJALA ITEM */
            .gejala-item {
                padding: 0.8rem;
                border-radius: 14px;
            }

            .gejala-text {
                font-size: 0.85rem;
                margin-bottom: 0.6rem;
            }

            .gejala-kode {
                font-size: 0.65rem;
                padding: 0.15rem 0.6rem;
            }
        }

        /* --- Small phones (max-width: 480px) --- */
        @media (max-width: 480px) {
            :root {
                --header-height: 52px;
            }

            .logo-img {
                height: 30px;
            }

            .logo h1 {
                font-size: 1.1rem;
            }

            .section-title h2 {
                font-size: 1.3rem;
            }

            .section-title p {
                font-size: 0.8rem;
            }

            /* CF OPTIONS -> 3 columns still, but smaller */
            .cf-options {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.2rem;
            }

            .cf-option label {
                font-size: 0.6rem;
                padding: 0.25rem 0.1rem;
                min-height: 30px;
                border-width: 1.5px;
            }

            .cf-option label i {
                font-size: 0.6rem;
            }

            /* LEGEND -> 2 columns */
            .legend-items {
                grid-template-columns: repeat(2, 1fr);
                font-size: 0.65rem;
                gap: 0.15rem 0.3rem;
            }

            .legend-item {
                padding: 0.1rem 0.15rem;
            }

            .legend-color {
                width: 8px;
                height: 8px;
            }

            .gejala-grid {
                max-height: 360px;
                gap: 0.6rem;
            }

            .gejala-item {
                padding: 0.6rem;
                border-radius: 12px;
            }

            .gejala-text {
                font-size: 0.78rem;
                margin-bottom: 0.5rem;
            }

            .gejala-kode {
                font-size: 0.6rem;
                padding: 0.1rem 0.5rem;
            }

            .tab-btn {
                font-size: 0.68rem;
                padding: 0.3rem 0.6rem;
                gap: 0.3rem;
            }

            .tab-btn i {
                font-size: 0.68rem;
            }

            .btn {
                font-size: 0.72rem;
                padding: 0.5rem 0.8rem;
                min-height: 36px;
                min-width: 60px;
            }

            .stat-number {
                font-size: 1.2rem;
            }
            .stat-label {
                font-size: 0.6rem;
            }
            .stat-box {
                padding: 0.15rem 0.6rem;
                min-width: 40px;
            }

            .selection-info {
                padding: 0.6rem 0.8rem;
                gap: 0.4rem;
            }

            .card {
                padding: 0.8rem;
                border-radius: 14px;
            }

            .error-message {
                font-size: 0.78rem;
                padding: 0.6rem 0.8rem;
                gap: 0.5rem;
            }

            .saved-indicator {
                font-size: 0.75rem;
                padding: 0.4rem 0.8rem;
            }
        }

        /* --- Very small phones (max-width: 360px) --- */
        @media (max-width: 360px) {
            .cf-options {
                grid-template-columns: repeat(2, 1fr);
            }

            .legend-items {
                grid-template-columns: repeat(2, 1fr);
            }

            .tab-btn {
                font-size: 0.6rem;
                padding: 0.25rem 0.4rem;
                flex: 1 0 40%;
            }

            .btn {
                font-size: 0.65rem;
                padding: 0.4rem 0.6rem;
                min-height: 32px;
                min-width: 50px;
            }

            .gejala-grid {
                max-height: 300px;
            }
        }

        /* ============================================================
                   UTILITY HELPERS
                   ============================================================ */
        .text-center {
            text-align: center;
        }
        .mt-1 {
            margin-top: 0.5rem;
        }
        .mb-1 {
            margin-bottom: 0.5rem;
        }
        .w-full {
            width: 100%;
        }
        .flex-center {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .gap-1 {
            gap: 0.5rem;
        }
        .gap-2 {
            gap: 1rem;
        }
        .flex-wrap {
            flex-wrap: wrap;
        }

        /* ============================================================
                   PRINT STYLES (optional)
                   ============================================================ */
        @media print {
            .background-design,
            .hamburger,
            .nav-links {
                display: none !important;
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ddd;
            }
            .gejala-grid {
                max-height: none !important;
                overflow: visible !important;
            }
            .btn {
                display: none !important;
            }
            header {
                position: static !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <!-- ====== BACKGROUND ====== -->
    <div class="background-design">
        <div class="bg-shape-1"></div>
        <div class="bg-shape-2"></div>
        <div class="bg-shape-3"></div>
    </div>

    <!-- ====== HEADER ====== -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="images/logo.png" alt="Logo AGROCURE" class="logo-img" loading="lazy" />
                <h1>AGROCURE</h1>
            </div>
            <ul class="nav-links" id="navLinks">
                <li><a href="admin/login.php"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
            <div class="hamburger" id="hamburger" aria-label="Toggle navigation menu" role="button" tabindex="0">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- ====== DIAGNOSIS SECTION ====== -->
    <section class="diagnosis-section">
        <div class="section-title">
            <h2>Diagnosis Penyakit Padi</h2>
            <p>Pilih tingkat keyakinan untuk setiap gejala yang Anda temukan pada tanaman padi</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="diagnosis.php" id="diagnosisForm">
            <div class="card">

                <!-- ====== LEGEND ====== -->
                <div class="legend">
                    <div class="legend-title">
                        <i class="fas fa-info-circle"></i> Tingkat Keyakinan
                    </div>
                    <div class="legend-items">
                        <?php foreach ($cf_user_options as $key => $option): ?>
                            <div class="legend-item">
                                <div class="legend-color" style="background: <?php echo $option['color']; ?>;"></div>
                                <span><?php echo htmlspecialchars($option['label']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ====== TABS ====== -->
                <div class="tabs">
                    <button type="button" class="tab-btn <?php echo $current_tab == 1 ? 'active' : ''; ?>" data-tab="1">
                        <i class="fas fa-leaf"></i> Gejala 1–16
                        <span class="badge"><?php echo count($tab1_gejala); ?></span>
                    </button>
                    <button type="button" class="tab-btn <?php echo $current_tab == 2 ? 'active' : ''; ?>" data-tab="2">
                        <i class="fas fa-leaf"></i> Gejala 17–32
                        <span class="badge"><?php echo count($tab2_gejala); ?></span>
                    </button>
                </div>

                <!-- ====== SELECTION INFO ====== -->
                <div class="selection-info">
                    <div class="selection-stats">
                        <div class="stat-box">
                            <div class="stat-number" id="selectedCount">0</div>
                            <span class="stat-label">Terpilih</span>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo count($gejala); ?></div>
                            <span class="stat-label">Total</span>
                        </div>
                    </div>
                    <div class="progress-container">
                        <div class="progress-bar">
                            <div class="progress-fill" id="progressFill" style="width: 0%;"></div>
                        </div>
                        <div class="progress-label">
                            <i class="fas fa-chart-line"></i>
                            <span id="progressPercentage">0%</span> selesai
                        </div>
                    </div>
                </div>

                <!-- ====== TAB 1 ====== -->
                <div class="tab-content <?php echo $current_tab == 1 ? 'active' : ''; ?>" id="tab1">
                    <div class="gejala-grid">
                        <?php foreach ($tab1_gejala as $g): ?>
                            <div class="gejala-item">
                                <div class="gejala-header">
                                    <span class="gejala-kode"><?php echo htmlspecialchars($g['kode']); ?></span>
                                </div>
                                <div class="gejala-text">
                                    <?php echo htmlspecialchars($g['nama']); ?>
                                </div>
                                <div class="cf-options">
                                    <?php foreach ($cf_user_options as $key => $option): ?>
                                        <div class="cf-option">
                                            <input type="radio"
                                            name="gejala[<?php echo intval($g['id']); ?>]"
                                            value="<?php echo htmlspecialchars($key); ?>"
                                            id="gejala_<?php echo intval($g['id']); ?>_<?php echo $key; ?>"
                                            data-gejala-id="<?php echo intval($g['id']); ?>"
                                            data-cf-value="<?php echo $option['value']; ?>" />
                                            <label for="gejala_<?php echo intval($g['id']); ?>_<?php echo $key; ?>">
                                                <i class="fas <?php echo $option['icon']; ?>" style="color: <?php echo $option['color']; ?>;"></i>
                                                <?php echo htmlspecialchars($option['label']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ====== TAB 2 ====== -->
                <div class="tab-content <?php echo $current_tab == 2 ? 'active' : ''; ?>" id="tab2">
                    <div class="gejala-grid">
                        <?php foreach ($tab2_gejala as $g): ?>
                            <div class="gejala-item">
                                <div class="gejala-header">
                                    <span class="gejala-kode"><?php echo htmlspecialchars($g['kode']); ?></span>
                                </div>
                                <div class="gejala-text">
                                    <?php echo htmlspecialchars($g['nama']); ?>
                                </div>
                                <div class="cf-options">
                                    <?php foreach ($cf_user_options as $key => $option): ?>
                                        <div class="cf-option">
                                            <input type="radio"
                                            name="gejala[<?php echo intval($g['id']); ?>]"
                                            value="<?php echo htmlspecialchars($key); ?>"
                                            id="gejala_<?php echo intval($g['id']); ?>_<?php echo $key; ?>"
                                            data-gejala-id="<?php echo intval($g['id']); ?>"
                                            data-cf-value="<?php echo $option['value']; ?>" />
                                            <label for="gejala_<?php echo intval($g['id']); ?>_<?php echo $key; ?>">
                                                <i class="fas <?php echo $option['icon']; ?>" style="color: <?php echo $option['color']; ?>;"></i>
                                                <?php echo htmlspecialchars($option['label']); ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ====== SAVED INDICATOR ====== -->
                <div class="saved-indicator" id="savedIndicator">
                    <i class="fas fa-check-circle"></i> Jawaban tersimpan otomatis
                </div>

                <!-- ====== ACTION BUTTONS ====== -->
                <div class="action-buttons">
                    <button type="button" onclick="resetAllData()" class="btn btn-secondary">
                        <i class="fas fa-redo-alt"></i> Reset
                    </button>

                    <div class="btn-group">
                        <?php if ($current_tab == 2): ?>
                            <button type="button" class="btn btn-primary" onclick="switchTab(1)">
                                <i class="fas fa-arrow-left"></i> Sebelumnya
                            </button>
                        <?php endif; ?>

                        <?php if ($current_tab == 1): ?>
                            <button type="button" class="btn btn-primary" onclick="switchTab(2)">
                                Selanjutnya <i class="fas fa-arrow-right"></i>
                            </button>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-diagnosis" id="diagnosisBtn">
                        <i class="fas fa-stethoscope"></i> Diagnosis Sekarang
                    </button>
                </div>
            </div>
        </form>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="images/logo.png" alt="Logo AGROCURE" loading="lazy" />
                <h2>AGROCURE</h2>
            </div>
            <p>&copy; 2025 AGROCURE – Sistem Pakar Diagnosis Penyakit Padi</p>
        </div>
    </footer>

    <!-- ================================================================
    JAVASCRIPT
    ================================================================ -->
    <script>
        (function() {
            'use strict';

            // ---- DOM refs ----
            const diagnosisForm = document.getElementById('diagnosisForm');
            const diagnosisBtn = document.getElementById('diagnosisBtn');
            const selectedCountEl = document.getElementById('selectedCount');
            const progressFill = document.getElementById('progressFill');
            const progressPercent = document.getElementById('progressPercentage');
            const savedIndicator = document.getElementById('savedIndicator');
            const hamburger = document.getElementById('hamburger');
            const navLinks = document.getElementById('navLinks');

            const totalGejala = <?php echo count($gejala); ?>;
            const currentTab = <?php echo $current_tab; ?>;
            const totalTabs = <?php echo $total_tabs; ?>;

            // ---- save to localStorage ----
            function saveToLocalStorage() {
                const inputs = document.querySelectorAll('input[type="radio"]:checked');
                const data = {};
                inputs.forEach(input => {
                    const gejalaId = input.dataset.gejalaId;
                    if (gejalaId) {
                        data['gejala_' + gejalaId] = {
                            value: input.value,
                            cfValue: input.dataset.cfValue || ''
                        };
                    }
                });
                try {
                    localStorage.setItem('diagnosis_data', JSON.stringify(data));
                } catch (_) { /* ignore */ }
                updateSelectionCounter();
                showSavedIndicator();
            }

            // ---- load from localStorage ----
            function loadFromLocalStorage() {
                let saved;
                try {
                    saved = localStorage.getItem('diagnosis_data');
                } catch (_) { return; }
                if (!saved) return;
                let data;
                try {
                    data = JSON.parse(saved);
                } catch (_) { return; }
                Object.keys(data).forEach(key => {
                    const gejalaId = key.replace('gejala_', '');
                    const val = data[key]?.value;
                    if (!val) return;
                    const input = document.querySelector(
                        'input[data-gejala-id="' + gejalaId + '"][value="' + val + '"]'
                    );
                    if (input) input.checked = true;
                });
                updateSelectionCounter();
            }

            // ---- update counter & progress ----
            function updateSelectionCounter() {
                const inputs = document.querySelectorAll('input[type="radio"]:checked');
                const count = inputs.length;
                const pct = totalGejala > 0 ? Math.round((count / totalGejala) * 100) : 0;

                if (selectedCountEl) selectedCountEl.textContent = count;
                if (progressFill) progressFill.style.width = pct + '%';
                if (progressPercent) progressPercent.textContent = pct + '%';

                if (diagnosisBtn) {
                    if (count > 0) {
                        diagnosisBtn.innerHTML = '<i class="fas fa-stethoscope"></i> Diagnosis (' + count + ' gejala)';
                    } else {
                        diagnosisBtn.innerHTML = '<i class="fas fa-stethoscope"></i> Diagnosis Sekarang';
                    }
                }
            }

            // ---- show saved indicator (with auto-hide) ----
            let indicatorTimer = null;

            function showSavedIndicator() {
                if (!savedIndicator) return;
                savedIndicator.style.display = 'block';
                clearTimeout(indicatorTimer);
                indicatorTimer = setTimeout(() => {
                    savedIndicator.style.display = 'none';
                }, 2000);
            }

            // ---- switch tab (navigate) ----
            window.switchTab = function(tabNumber) {
                saveToLocalStorage();
                window.location.href = 'diagnosis.php?tab=' + tabNumber;
            };

            // ---- reset all ----
            window.resetAllData = function() {
                if (!confirm('Yakin ingin menghapus semua jawaban dan memulai ulang?')) return;
                const inputs = document.querySelectorAll('input[type="radio"]');
                inputs.forEach(inp => { inp.checked = false; });
                try {
                    localStorage.removeItem('diagnosis_data');
                } catch (_) { /* ignore */ }
                updateSelectionCounter();
                if (savedIndicator) {
                    savedIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Semua jawaban telah direset!';
                    savedIndicator.style.display = 'block';
                    clearTimeout(indicatorTimer);
                    indicatorTimer = setTimeout(() => {
                        savedIndicator.style.display = 'none';
                        savedIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Jawaban tersimpan otomatis';
                    }, 3000);
                }
            };

            // ---- tab button clicks (fallback) ----
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tab = this.dataset.tab;
                    if (tab) switchTab(parseInt(tab, 10));
                });
            });

            // ---- radio change: save + micro animation ----
            document.querySelectorAll('input[type="radio"]').forEach(inp => {
                inp.addEventListener('change', function() {
                    saveToLocalStorage();
                    const label = this.nextElementSibling;
                    if (label && label.tagName === 'LABEL') {
                        label.style.transform = 'scale(0.97)';
                        requestAnimationFrame(() => {
                            label.style.transform = '';
                        });
                    }
                });
            });

            // ---- form submit ----
            if (diagnosisForm) {
                diagnosisForm.addEventListener('submit', function(e) {
                    saveToLocalStorage();
                    const checked = document.querySelectorAll('input[type="radio"]:checked');
                    if (checked.length === 0) {
                        e.preventDefault();
                        alert('Silakan pilih setidaknya satu gejala sebelum melakukan diagnosis.');
                        return false;
                    }
                    if (diagnosisBtn) {
                        diagnosisBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                        diagnosisBtn.disabled = true;
                    }
                    return true;
                });
            }

            // ---- auto-save every 30s ----
            setInterval(saveToLocalStorage, 30000);

            // ---- save before unload ----
            window.addEventListener('beforeunload', function() {
                saveToLocalStorage();
            });

            // ---- load saved data on DOM ready ----
            loadFromLocalStorage();

            // ---- clear localStorage flag from PHP ----
            <?php if ($clear_localstorage): ?>
            try { localStorage.removeItem('diagnosis_data'); } catch (_) {}
            if (savedIndicator) {
                savedIndicator.innerHTML = '<i class="fas fa-info-circle"></i> Data diagnosis sebelumnya direset';
                savedIndicator.style.display = 'block';
                clearTimeout(indicatorTimer);
                indicatorTimer = setTimeout(() => {
                    savedIndicator.style.display = 'none';
                    savedIndicator.innerHTML = '<i class="fas fa-check-circle"></i> Jawaban tersimpan otomatis';
                }, 3000);
            }
            <?php endif; ?>

            // ---- keyboard shortcuts ----
            document.addEventListener('keydown', function(e) {
                // Ctrl+R -> reset
                if (e.ctrlKey && e.key === 'r') {
                    e.preventDefault();
                    resetAllData();
                }
                // Alt+Left / Alt+Right -> tab navigation
                if (e.altKey) {
                    if (e.key === 'ArrowLeft' && currentTab > 1) {
                        e.preventDefault();
                        switchTab(currentTab - 1);
                    } else if (e.key === 'ArrowRight' && currentTab < totalTabs) {
                        e.preventDefault();
                        switchTab(currentTab + 1);
                    }
                }
            });

            // ---- hamburger menu toggle ----
            if (hamburger && navLinks) {
                hamburger.addEventListener('click', function(e) {
                    e.stopPropagation();
                    this.classList.toggle('active');
                    navLinks.classList.toggle('active');
                });

                // close on link click
                navLinks.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        hamburger.classList.remove('active');
                        navLinks.classList.remove('active');
                    });
                });

                // close on outside click
                document.addEventListener('click', function(e) {
                    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
                        hamburger.classList.remove('active');
                        navLinks.classList.remove('active');
                    }
                });

                // close on Escape
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        hamburger.classList.remove('active');
                        navLinks.classList.remove('active');
                    }
                });
            }

            // ---- subtle entrance animation for gejala items ----
            const items = document.querySelectorAll('.gejala-item');
            items.forEach((el, idx) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(12px)';
                el.style.transition =
                    'opacity 0.4s ease ' + (idx * 0.04) + 's, transform 0.4s ease ' + (idx * 0.04) + 's';
                requestAnimationFrame(() => {
                    el.style.opacity = '1';
                    el.style.transform = 'translateY(0)';
                });
            });

        })();
    </script>
</body>
</html>