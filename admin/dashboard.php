<?php
session_start();
include('../config/database.php');

// Cek login
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Ambil statistik
$query_penyakit = "SELECT COUNT(*) as total FROM penyakit";
$result_penyakit = mysqli_query($conn, $query_penyakit);
$total_penyakit = mysqli_fetch_assoc($result_penyakit)['total'];

$query_gejala = "SELECT COUNT(*) as total FROM gejala";
$result_gejala = mysqli_query($conn, $query_gejala);
$total_gejala = mysqli_fetch_assoc($result_gejala)['total'];

$query_aturan = "SELECT COUNT(*) as total FROM aturan";
$result_aturan = mysqli_query($conn, $query_aturan);
$total_aturan = mysqli_fetch_assoc($result_aturan)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - AGROCURE</title>
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
            padding: 3rem;
            border-radius: 24px;
            margin-bottom: 2.5rem;
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
            font-size: 2.5rem;
            margin-bottom: 0.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--text-dark), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .welcome-section p {
            font-size: 1.2rem;
            color: var(--text-light);
            opacity: 0.9;
        }

        /* Stats Grid - Enhanced */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2.5rem 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-light), var(--secondary), var(--accent));
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.05), rgba(255, 111, 0, 0.05));
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .stat-card:hover::after {
            opacity: 1;
        }

        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-hover), var(--glow);
        }

        .stat-card h3 {
            font-size: 3.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
            position: relative;
            z-index: 2;
        }

        .stat-card p {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-bottom: 2rem;
            font-weight: 500;
            position: relative;
            z-index: 2;
        }

        .stat-card .btn {
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
            position: relative;
            z-index: 2;
        }

        .stat-card .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.6);
        }

        /* Cards - Enhanced */
        .card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin-bottom: 2.5rem;
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

        /* Quick Actions - Enhanced */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
        }

        .action-btn {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            color: white;
            padding: 1.5rem;
            border-radius: 16px;
            text-decoration: none;
            text-align: center;
            font-weight: 600;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .action-btn:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: var(--shadow-hover), var(--glow);
        }

        /* Guide List - Enhanced */
        .guide-list {
            list-style: none;
        }

        .guide-list li {
            padding: 1.25rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            display: flex;
            align-items: flex-start;
            gap: 18px;
            transition: all 0.3s ease;
            position: relative;
        }

        .guide-list li::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 6px;
            background: var(--primary-light);
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .guide-list li:hover {
            transform: translateX(10px);
            padding-left: 10px;
        }

        .guide-list li:hover::before {
            opacity: 1;
        }

        .guide-list li:last-child {
            border-bottom: none;
        }

        .guide-list li i {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.3rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .guide-list li div {
            flex: 1;
        }

        .guide-list li strong {
            color: var(--text-dark);
            font-weight: 600;
            display: block;
            margin-bottom: 0.25rem;
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

        .stat-card, .card {
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
                font-size: 2rem;
            }

            .dashboard-stats {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .stat-card h3 {
                font-size: 2.5rem;
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

        /* Sembunyikan teks nama user */
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
                <li><a href="dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="penyakit.php"><i class="fas fa-disease"></i>Penyakit</a></li>
                <li><a href="gejala.php"><i class="fas fa-stethoscope"></i>Gejala</a></li>
                <li><a href="aturan.php"><i class="fas fa-link"></i>Aturan</a></li>
                <li><a href="penanganan.php"><i class="fas fa-hand-holding-medical"></i>Penanganan</a></li>
                <li><a href="logout.php" style="background: linear-gradient(135deg, var(--secondary), var(--secondary-light));"><i class="fas fa-sign-out-alt"></i>Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Dashboard Content -->
    <section class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2>Selamat Datang di Dashboard! 🌱</h2>
            <p>Halo, <?php echo $_SESSION['admin_name']; ?>! Mari kelola sistem pakar diagnosis penyakit padi.</p>
        </div>
        
        <!-- Statistik -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3><?php echo $total_penyakit; ?></h3>
                <p>Total Penyakit</p>
                <a href="penyakit.php" class="btn">
                    <i class="fas fa-cog"></i>Kelola Penyakit
                </a>
            </div>
            
            <div class="stat-card">
                <h3><?php echo $total_gejala; ?></h3>
                <p>Total Gejala</p>
                <a href="gejala.php" class="btn">
                    <i class="fas fa-cog"></i>Kelola Gejala
                </a>
            </div>
            
            <div class="stat-card">
                <h3><?php echo $total_aturan; ?></h3>
                <p>Total Aturan</p>
                <a href="aturan.php" class="btn">
                    <i class="fas fa-cog"></i>Kelola Aturan
                </a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <h3><i class="fas fa-bolt"></i>Quick Actions</h3>
            <div class="quick-actions">
                <a href="penyakit.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>Tambah Penyakit Baru
                </a>
                <a href="gejala.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>Tambah Gejala Baru
                </a>
                <a href="aturan.php?action=add" class="action-btn">
                    <i class="fas fa-plus-circle"></i>Tambah Aturan Baru
                </a>
            </div>
        </div>
        
        <!-- Panduan Penggunaan -->
        <div class="card">
            <h3><i class="fas fa-book-open"></i>Panduan Penggunaan</h3>
            <ul class="guide-list">
                <li>
                    <i class="fas fa-disease"></i>
                    <div>
                        <strong>Kelola Penyakit:</strong> Tambah, edit, atau hapus data penyakit padi dalam sistem
                    </div>
                </li>
                <li>
                    <i class="fas fa-stethoscope"></i>
                    <div>
                        <strong>Kelola Gejala:</strong> Kelola gejala-gejala yang terkait dengan penyakit padi
                    </div>
                </li>
                <li>
                    <i class="fas fa-link"></i>
                    <div>
                        <strong>Kelola Aturan:</strong> Atur hubungan antara penyakit dan gejala beserta CF Pakar
                    </div>
                </li>
                <li>
                    <i class="fas fa-hand-holding-medical"></i>
                    <div>
                        <strong>Kelola Penanganan:</strong> Kelola rekomendasi penanganan untuk setiap penyakit
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <script>
        // Enhanced animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Staggered animation for stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.15}s`;
                card.style.animationFillMode = 'both';
            });

            // Enhanced hover effects with parallax
            const cards = document.querySelectorAll('.card, .stat-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function(e) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    this.style.setProperty('--mouse-x', `${x}px`);
                    this.style.setProperty('--mouse-y', `${y}px`);
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

            // Add ripple effect to buttons
            const buttons = document.querySelectorAll('.btn, .action-btn');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.6);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        left: ${x}px;
                        top: ${y}px;
                    `;
                    
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add CSS for ripple animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>