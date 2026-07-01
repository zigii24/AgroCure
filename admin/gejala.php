<?php
session_start();
include('../config/database.php');

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Ambil data gejala
$query = "SELECT * FROM gejala ORDER BY kode";
$result = mysqli_query($conn, $query);
$gejala_list = [];

while ($row = mysqli_fetch_assoc($result)) {
    $gejala_list[] = $row;
}

// Proses tambah gejala
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah'])) {
    $kode = mysqli_real_escape_string($conn, $_POST['kode']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    
    $query = "INSERT INTO gejala (kode, nama) VALUES ('$kode', '$nama')";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = 'Gejala berhasil ditambahkan!';
        header('Location: gejala.php');
        exit;
    } else {
        $error = 'Gagal menambahkan gejala: ' . mysqli_error($conn);
    }
}

// Proses edit gejala
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id'];
    $kode = mysqli_real_escape_string($conn, $_POST['kode']);
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    
    $query = "UPDATE gejala SET kode='$kode', nama='$nama' WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = 'Gejala berhasil diupdate!';
        header('Location: gejala.php');
        exit;
    } else {
        $error = 'Gagal mengupdate gejala: ' . mysqli_error($conn);
    }
}

// Proses hapus gejala
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Hapus aturan terkait dulu
    $query_aturan = "DELETE FROM aturan WHERE id_gejala=$id";
    mysqli_query($conn, $query_aturan);
    
    // Hapus gejala
    $query = "DELETE FROM gejala WHERE id=$id";
    
    if (mysqli_query($conn, $query)) {
        $_SESSION['success'] = 'Gejala berhasil dihapus!';
        header('Location: gejala.php');
        exit;
    } else {
        $error = 'Gagal menghapus gejala: ' . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Gejala - AGROCURE</title>
    <link rel="shortcut icon" href="../images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary: #ff6f00;
            --secondary-light: #ff9800;
            --accent: #2196f3;
            --accent-light: #64b5f6;
            --text-dark: #1a237e;
            --text-light: #5d5d5d;
            --background: linear-gradient(135deg, #f8fdf8 0%, #e8f5e9 50%, #e1f5fe 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow: 0 8px 32px rgba(31, 38, 135, 0.15);
            --shadow-hover: 0 15px 45px rgba(31, 38, 135, 0.25);
            --glow: 0 0 20px rgba(76, 175, 80, 0.3);
        }

        body {
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--background);
            color: var(--text-dark);
            line-height: 1.7;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }

        /* Advanced Background Effects */
        .background-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }

        .gradient-orbs {
            position: absolute;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(76, 175, 80, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 111, 0, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(33, 150, 243, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 60% 60%, rgba(156, 39, 176, 0.08) 0%, transparent 50%);
            filter: blur(40px);
        }

        .floating-particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            background: var(--primary-light);
            border-radius: 50%;
            opacity: 0.1;
            animation: floatParticle 20s infinite linear;
        }

        .particle:nth-child(1) { width: 120px; height: 120px; top: 10%; left: 5%; animation-delay: 0s; background: var(--primary-light); }
        .particle:nth-child(2) { width: 80px; height: 80px; top: 60%; right: 10%; animation-delay: -5s; background: var(--secondary); }
        .particle:nth-child(3) { width: 60px; height: 60px; bottom: 20%; left: 15%; animation-delay: -10s; background: var(--accent); }
        .particle:nth-child(4) { width: 100px; height: 100px; top: 20%; right: 20%; animation-delay: -15s; background: var(--primary); }
        .particle:nth-child(5) { width: 70px; height: 70px; bottom: 10%; right: 5%; animation-delay: -7s; background: var(--secondary-light); }

        .geometric-pattern {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0.03;
            background-image: 
                radial-gradient(circle at 25% 25%, var(--primary) 2px, transparent 2px),
                radial-gradient(circle at 75% 75%, var(--secondary) 2px, transparent 2px);
            background-size: 50px 50px;
        }

        /* Header & Navigation - Glass Morphism */
        .admin-header {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            color: var(--text-dark);
            padding: 0;
            box-shadow: var(--shadow);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }

        .admin-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 16px;
            border: 1px solid var(--glass-border);
        }

        .logo-icon {
            font-size: 2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: pulseGlow 3s ease-in-out infinite;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s;
        }

        .nav-links a:hover::before {
            left: 100%;
        }

        .nav-links a:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            background: rgba(255, 255, 255, 0.4);
        }

        .nav-links a.active {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            box-shadow: var(--glow);
        }

        .nav-links a i {
            font-size: 1.1rem;
        }

        /* Main Content */
        .main-content {
            margin-top: 100px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 1;
        }

        /* Welcome Section - Enhanced */
        .welcome-section {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            color: var(--text-dark);
            padding: 2.5rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .welcome-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--secondary), var(--accent));
        }

        .welcome-section h2 {
            font-size: 2.2rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-section p {
            font-size: 1.1rem;
            color: var(--text-light);
            opacity: 0.9;
        }

        /* Cards - Enhanced */
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            border: 1px solid var(--glass-border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--secondary));
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card h3 {
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card h3 i {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.4rem;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid rgba(76, 175, 80, 0.2);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
            color: var(--text-dark);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-light);
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            background: white;
        }

        /* Buttons */
        .btn {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.4);
            cursor: pointer;
            font-size: 1rem;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.6);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
        }

        .btn-secondary:hover {
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.6);
        }

        .btn-edit {
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(33, 150, 243, 0.4);
        }

        .btn-delete {
            background: linear-gradient(135deg, #f44336, #d32f2f);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        th {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.95rem;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            color: var(--text-dark);
        }

        tr:hover {
            background: rgba(76, 175, 80, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            backdrop-filter: blur(10px);
        }

        .alert-success {
            background: rgba(76, 175, 80, 0.15);
            color: var(--primary-dark);
            border-left: 4px solid var(--primary);
        }

        .alert-error {
            background: rgba(244, 67, 54, 0.15);
            color: #d32f2f;
            border-left: 4px solid #f44336;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: var(--text-light);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        /* Animations */
        @keyframes pulseGlow {
            0%, 100% { 
                transform: scale(1);
                filter: drop-shadow(0 0 10px rgba(76, 175, 80, 0.3));
            }
            50% { 
                transform: scale(1.1);
                filter: drop-shadow(0 0 20px rgba(76, 175, 80, 0.6));
            }
        }

        @keyframes floatParticle {
            0%, 100% {
                transform: translateY(0px) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-40px) rotate(90deg) scale(1.1);
            }
            50% {
                transform: translateY(20px) rotate(180deg) scale(0.9);
            }
            75% {
                transform: translateY(-20px) rotate(270deg) scale(1.05);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card {
            animation: fadeInUp 0.8s ease-out;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }

            .nav-links {
                gap: 0.5rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .nav-links a {
                padding: 0.6rem 1rem;
                font-size: 0.9rem;
            }

            .main-content {
                margin-top: 140px;
                padding: 1rem;
            }

            .welcome-section {
                padding: 2rem;
            }

            .welcome-section h2 {
                font-size: 1.8rem;
            }

            .card {
                padding: 1.5rem;
            }

            table {
                font-size: 0.9rem;
            }

            th, td {
                padding: 0.75rem 0.5rem;
            }
        }

        /* User Info - Hanya icon saja */
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            padding: 0.5rem;
            border-radius: 12px;
            border: 1px solid var(--glass-border);
        }

        .user-info i {
            font-size: 1.2rem;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .user-info span {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Advanced Background Effects -->
    <div class="background-wrapper">
        <div class="gradient-orbs"></div>
        <div class="floating-particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
        <div class="geometric-pattern"></div>
    </div>

    <!-- Header -->
    <header class="admin-header">
        <nav class="admin-nav">
            <div class="logo">
                <i class="fas fa-leaf logo-icon"></i>
                <h1>AGROCURE - Admin</h1>
            </div>
            
            <ul class="nav-links">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="penyakit.php"><i class="fas fa-disease"></i>Penyakit</a></li>
                <li><a href="gejala.php" class="active"><i class="fas fa-stethoscope"></i>Gejala</a></li>
                <li><a href="aturan.php"><i class="fas fa-link"></i>Aturan</a></li>
                <li><a href="penanganan.php"><i class="fas fa-hand-holding-medical"></i>Penanganan</a></li>
                <li><a href="logout.php" style="background: linear-gradient(135deg, var(--secondary), var(--secondary-light));"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Main Content -->
    <section class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Kelola Data Gejala 🔍</h2>
            <p>Manajemen data gejala penyakit padi dalam sistem AGROCURE</p>
        </div>
        
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Form Tambah/Edit Gejala -->
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i><?php echo isset($_GET['edit']) ? ' Edit Gejala' : ' Tambah Gejala Baru'; ?></h3>
            <form method="POST" action="gejala.php">
                <?php if (isset($_GET['edit'])): 
                    $edit_id = $_GET['edit'];
                    $query_edit = "SELECT * FROM gejala WHERE id=$edit_id";
                    $result_edit = mysqli_query($conn, $query_edit);
                    $gejala_edit = mysqli_fetch_assoc($result_edit);
                ?>
                    <input type="hidden" name="id" value="<?php echo $gejala_edit['id']; ?>">
                    <input type="hidden" name="edit" value="1">
                <?php else: ?>
                    <input type="hidden" name="tambah" value="1">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="kode">Kode Gejala</label>
                    <input type="text" id="kode" name="kode" class="form-control" 
                           value="<?php echo isset($gejala_edit) ? $gejala_edit['kode'] : ''; ?>" 
                           placeholder="Contoh: G01" required>
                </div>
                
                <div class="form-group">
                    <label for="nama">Nama Gejala</label>
                    <input type="text" id="nama" name="nama" class="form-control" 
                           value="<?php echo isset($gejala_edit) ? $gejala_edit['nama'] : ''; ?>" 
                           placeholder="Masukkan nama gejala" required>
                </div>
                
                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i><?php echo isset($_GET['edit']) ? ' Update Gejala' : ' Tambah Gejala'; ?>
                    </button>
                    
                    <?php if (isset($_GET['edit'])): ?>
                        <a href="gejala.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i>Batal
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        
        <!-- Daftar Gejala -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Daftar Gejala</h3>
            
            <?php if (empty($gejala_list)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Belum ada data gejala</h3>
                    <p>Mulai dengan menambahkan gejala baru menggunakan form di atas</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Nama Gejala</th>
                                <th style="width: 150px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gejala_list as $gejala): ?>
                                <tr>
                                    <td><strong><?php echo $gejala['kode']; ?></strong></td>
                                    <td><?php echo $gejala['nama']; ?></td>
                                    <td>
                                        <div style="display: flex; gap: 0.5rem;">
                                            <a href="gejala.php?edit=<?php echo $gejala['id']; ?>" class="btn-edit">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="gejala.php?delete=<?php echo $gejala['id']; ?>" class="btn-delete" 
                                               onclick="return confirm('Yakin ingin menghapus gejala <?php echo $gejala['nama']; ?>?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <script>
        // Enhanced animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to cards
            const cards = document.querySelectorAll('.card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Floating particles interaction
            document.addEventListener('mousemove', function(e) {
                const particles = document.querySelectorAll('.particle');
                const x = e.clientX / window.innerWidth;
                const y = e.clientY / window.innerHeight;
                
                particles.forEach((particle, index) => {
                    const speed = (index + 1) * 0.3;
                    const xMove = (x - 0.5) * speed * 30;
                    const yMove = (y - 0.5) * speed * 30;
                    
                    particle.style.transform = `translate(${xMove}px, ${yMove}px) rotate(${x * 180}deg) scale(${1 + y * 0.1})`;
                });
            });

            // Auto-focus on first input
            const firstInput = document.querySelector('.form-control');
            if (firstInput) {
                firstInput.focus();
            }

            // Add confirmation for delete actions
            const deleteButtons = document.querySelectorAll('.btn-delete');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    if (!confirm('Apakah Anda yakin ingin menghapus gejala ini?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>