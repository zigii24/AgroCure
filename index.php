<?php
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGROCURE - Sistem Pakar Penyakit Padi</title>
    <link rel="shortcut icon" href="images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2e7d32;
            --primary-light: #4caf50;
            --primary-dark: #1b5e20;
            --secondary: #ff9800;
            --accent: #8bc34a;
            --text: #2d3748;
            --text-light: #718096;
            --background: #f7fafc;
            --white: #ffffff;
            --card-bg: rgba(255, 255, 255, 0.95);
            --shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 25px 45px rgba(0, 0, 0, 0.12);
            --radius: 24px;
            --gradient: linear-gradient(135deg, #2e7d32 0%, #4caf50 100%);
            --gradient-dark: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.7;
            color: var(--text);
            background: var(--background);
            overflow-x: hidden;
            position: relative;
        }

        /* Background Modern */
        .background-design {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.4;
        }

        .bg-shape-1 {
            position: absolute;
            top: -10%;
            right: -5%;
            width: 500px;
            height: 500px;
            background: linear-gradient(135deg, rgba(139, 195, 74, 0.2) 0%, rgba(76, 175, 80, 0.15) 100%);
            border-radius: 30% 70% 70% 30% / 30% 30% 70% 70%;
            animation: float 8s ease-in-out infinite;
        }

        .bg-shape-2 {
            position: absolute;
            bottom: -10%;
            left: -5%;
            width: 400px;
            height: 400px;
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.15) 0%, rgba(139, 195, 74, 0.1) 100%);
            border-radius: 60% 40% 30% 70% / 60% 30% 70% 40%;
            animation: float 12s ease-in-out infinite;
        }

        .bg-shape-3 {
            position: absolute;
            top: 40%;
            left: 20%;
            width: 300px;
            height: 300px;
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.15) 0%, rgba(139, 195, 74, 0.08) 100%);
            border-radius: 50% 20% 80% 40% / 40% 80% 20% 60%;
            animation: float 10s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* Header & Navigation - Modern */
        header {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid rgba(46, 125, 50, 0.1);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .logo-img {
            height: 45px;
            width: auto;
            object-fit: contain;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.05);
        }

        .logo h1 {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text);
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            transition: all 0.3s ease;
            position: relative;
            font-size: 0.95rem;
        }

        .nav-links a:hover {
            color: var(--primary);
            transform: translateY(-2px);
            background: rgba(76, 175, 80, 0.08);
        }

        .nav-links a.active {
            color: var(--primary);
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.1), rgba(76, 175, 80, 0.08));
            font-weight: 700;
        }

        .hamburger {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .hamburger span {
            width: 25px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--primary-light));
            margin: 3px 0;
            transition: 0.3s;
            border-radius: 3px;
        }

        /* Hero Section - Modern & Elegant (Only Logo) */
        .hero {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 60vh;
            padding: 4rem 5%;
            position: relative;
            max-width: 1400px;
            margin: 0 auto;
            text-align: center;
        }

        .hero-content {
            max-width: 850px;
            z-index: 2;
        }

        /* Modern Logo Container */
        .logo-container {
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .main-logo {
            display: inline-block;
            position: relative;
            margin-bottom: 1rem;
        }

        .logo-text {
            font-size: 4.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 25%, #4caf50 50%, #8bc34a 75%, #cddc39 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: 3px;
            position: relative;
            display: inline-block;
            text-transform: uppercase;
            text-shadow: 0 4px 20px rgba(46, 125, 50, 0.2);
            animation: fadeInDown 0.8s ease-out;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-subtitle {
            font-size: 1.1rem;
            color: var(--primary-dark);
            letter-spacing: 5px;
            margin-top: 0.5rem;
            font-weight: 600;
            position: relative;
            display: inline-block;
            padding: 0.6rem 1.8rem;
            background: rgba(46, 125, 50, 0.1);
            border-radius: 50px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(46, 125, 50, 0.2);
            animation: fadeInUp 0.8s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Sections - Modern */
        section {
            padding: 6rem 5%;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 25%, #4caf50 50%, #8bc34a 75%, #cddc39 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }

        .section-title h2::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--gradient);
            border-radius: 3px;
        }

        .section-title p {
            font-size: 1.2rem;
            color: var(--text-light);
            max-width: 700px;
            margin: 1.5rem auto 0;
            font-weight: 400;
            line-height: 1.6;
        }

        /* Features Grid - Modern Cards */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .feature-card {
            background: var(--card-bg);
            padding: 2.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.03), rgba(76, 175, 80, 0.02));
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .feature-card:hover::before {
            opacity: 1;
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.8rem;
            color: white;
            font-size: 2rem;
            box-shadow: 0 8px 20px rgba(46, 125, 50, 0.25);
            transition: all 0.4s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
            border-radius: 28px;
        }

        .feature-card h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 1rem;
        }

        /* Steps Container - Modern */
        .steps-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
            position: relative;
        }

        .step-card {
            background: var(--card-bg);
            padding: 2.5rem 2rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            transition: all 0.4s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .step-number {
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 50px;
            background: var(--gradient);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.4rem;
            box-shadow: 0 6px 15px rgba(46, 125, 50, 0.3);
            transition: all 0.3s ease;
        }

        .step-card:hover .step-number {
            transform: translateX(-50%) scale(1.1);
        }

        .step-card h3 {
            font-size: 1.3rem;
            margin: 1.5rem 0 0.8rem;
            color: var(--primary-dark);
            font-weight: 700;
        }

        .step-card p {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 0.95rem;
        }

        /* About Section - Modern */
        #about {
            background: linear-gradient(135deg, rgba(139, 195, 74, 0.05) 0%, rgba(76, 175, 80, 0.03) 100%);
            border-radius: 40px;
            margin: 2rem auto;
            position: relative;
            overflow: hidden;
        }

        /* How to Use Section - Modern */
        #how-to-use {
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.03) 0%, rgba(139, 195, 74, 0.05) 100%);
            border-radius: 40px;
            margin: 2rem auto;
            position: relative;
            overflow: hidden;
        }

        /* CTA Section - Tombol Diagnosis di Bawah */
        .cta-section {
            text-align: center;
            padding: 4rem 5%;
            max-width: 1400px;
            margin: 0 auto;
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.05), rgba(76, 175, 80, 0.03));
            border-radius: 40px;
            margin-bottom: 2rem;
        }

        .cta-title {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 25%, #4caf50 50%, #8bc34a 75%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-description {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        .btn-main {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            background: var(--gradient);
            color: white;
            padding: 1.2rem 3rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.35);
            font-size: 1.2rem;
        }

        .btn-main:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(46, 125, 50, 0.45);
        }

        .btn-main i {
            transition: transform 0.3s ease;
        }

        .btn-main:hover i {
            transform: translateX(5px);
        }

        /* Footer - Modern */
        footer {
            background: var(--gradient-dark);
            color: white;
            padding: 3.5rem 5%;
            text-align: center;
            border-radius: 30px 30px 0 0;
            margin-top: 4rem;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 1.5rem;
        }

        .footer-logo img {
            height: 40px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .footer-logo h2 {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
        }

        footer p {
            margin-bottom: 1rem;
            opacity: 0.9;
            font-size: 0.95rem;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            .logo-text {
                font-size: 3.5rem;
            }
            
            .section-title h2 {
                font-size: 2.3rem;
            }
            
            .cta-title {
                font-size: 1.8rem;
            }
            
            .bg-shape-1, .bg-shape-2, .bg-shape-3 {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .hamburger {
                display: flex;
            }
            
            .nav-links {
                position: fixed;
                top: 70px;
                right: -100%;
                flex-direction: column;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(20px);
                width: 80%;
                max-width: 280px;
                text-align: center;
                box-shadow: var(--shadow);
                border-radius: 20px 0 0 20px;
                transition: 0.4s;
                padding: 1.5rem 0;
                gap: 0;
                border-left: 1px solid rgba(46, 125, 50, 0.1);
            }
            
            .nav-links.active {
                right: 0;
            }
            
            .nav-links li {
                margin: 0.5rem 0;
            }

            .nav-links a {
                display: block;
                padding: 0.8rem 1.5rem;
            }
            
            .logo-text {
                font-size: 2.8rem;
                letter-spacing: 2px;
            }

            .logo-subtitle {
                font-size: 0.85rem;
                letter-spacing: 3px;
                padding: 0.4rem 1.2rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }

            .section-title p {
                font-size: 1rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .steps-container {
                grid-template-columns: 1fr;
            }

            #about, #how-to-use, .cta-section {
                border-radius: 30px;
                margin: 1rem;
            }

            .btn-main {
                padding: 1rem 2rem;
                font-size: 1rem;
            }

            .cta-title {
                font-size: 1.5rem;
            }

            .cta-description {
                font-size: 0.95rem;
                padding: 0 1rem;
            }
        }

        @media (max-width: 576px) {
            .navbar {
                padding: 0.8rem 1rem;
            }
            
            section {
                padding: 3rem 1rem;
            }
            
            .hero {
                padding: 2rem 1rem;
                min-height: 50vh;
            }

            .logo-img {
                height: 35px;
            }

            .logo h1 {
                font-size: 1.4rem;
            }
            
            .feature-card, .step-card {
                padding: 2rem 1.2rem;
            }

            .logo-text {
                font-size: 2rem;
            }

            .logo-subtitle {
                font-size: 0.7rem;
                letter-spacing: 2px;
            }

            .feature-icon {
                width: 65px;
                height: 65px;
                font-size: 1.6rem;
            }

            .cta-section {
                padding: 2rem 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Background Modern -->
    <div class="background-design">
        <div class="bg-shape-1"></div>
        <div class="bg-shape-2"></div>
        <div class="bg-shape-3"></div>
    </div>
    
    <!-- Header -->
    <header>
        <nav class="navbar">
            <div class="logo">
                <img src="images/logo.png" alt="Logo AGROCURE" class="logo-img">
                <h1>AGROCURE</h1>
            </div>
            <ul class="nav-links">
                <li><a href="admin/login.php"><i class="fas fa-user-shield"></i> Admin Panel</a></li>
            </ul>
            <div class="hamburger">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </nav>
    </header>

    <!-- Hero Section - Hanya Logo dan Subtitle -->
    <section class="hero">
        <div class="hero-content">
            <div class="logo-container">
                <div class="main-logo">
                    <span class="logo-text">AGROCURE</span>
                </div>
                <div class="logo-subtitle">SISTEM PAKAR PENYAKIT PADI</div>
            </div>
        </div>
    </section>

    <!-- Informasi Tentang Sistem - Ditempatkan di Atas -->
    <section id="about">
        <div class="section-title">
            <h2>Tentang Agrocure</h2>
            <p>Solusi cerdas untuk diagnosis penyakit padi dengan pendekatan sistem pakar dan metode Certainty Factor</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-brain"></i>
                </div>
                <h3>Sistem Pakar Cerdas</h3>
                <p>AGROCURE menggunakan kecerdasan buatan untuk menganalisis gejala penyakit padi dengan akurasi tinggi, meniru kemampuan pakar pertanian dalam mendiagnosis masalah tanaman.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Metode Certainty Factor</h3>
                <p>Dengan algoritma Certainty Factor, sistem dapat menghitung tingkat keyakinan diagnosis berdasarkan gejala yang diamati, memberikan hasil yang lebih terukur dan dapat diandalkan.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-seedling"></i>
                </div>
                <h3>Fokus Tanaman Padi</h3>
                <p>Khusus dikembangkan untuk mengidentifikasi berbagai penyakit yang umum menyerang tanaman padi, dengan database gejala dan solusi yang komprehensif.</p>
            </div>
        </div>
    </section>

    <!-- Cara Menggunakan -->
    <section id="how-to-use">
        <div class="section-title">
            <h2>Cara Menggunakan Agrocure</h2>
            <p>Langkah-langkah mudah untuk mendapatkan diagnosis penyakit padi yang akurat</p>
        </div>
        
        <div class="steps-container">
            <div class="step-card">
                <div class="step-number">1</div>
                <h3>Observasi Gejala</h3>
                <p>Amati kondisi tanaman padi Anda dengan teliti dan identifikasi gejala-gejala yang tampak pada daun, batang, atau bagian tanaman lainnya.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">2</div>
                <h3>Pilih Tingkat Keyakinan</h3>
                <p>Untuk setiap gejala yang diamati, pilih tingkat keyakinan sesuai dengan kondisi aktual tanaman padi Anda.</p>
            </div>
            
            <div class="step-card">
                <div class="step-number">3</div>
                <h3>Dapatkan Hasil</h3>
                <p>Sistem akan menganalisis data dan memberikan diagnosis penyakit beserta rekomendasi penanganan yang tepat.</p>
            </div>
        </div>
    </section>

    <!-- CTA Section - Tombol Diagnosis di Bawah Setelah User Membaca Informasi -->
    <div class="cta-section">
        <h2 class="cta-title">Siap Mendiagnosis Tanaman Padi Anda?</h2>
        <p class="cta-description">Dapatkan diagnosis akurat dan rekomendasi penanganan yang tepat untuk tanaman padi Anda sekarang juga!</p>
        <a href="diagnosis.php" class="btn-main">
            Mulai Diagnosis Sekarang <i class="fas fa-arrow-right"></i>
        </a>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <img src="images/logo.png" alt="Logo AGROCURE">
                <h2>AGROCURE</h2>
            </div>
            <p>&copy; 2025 AGROCURE - Sistem Pakar Diagnosis Penyakit Padi</p>
            <p style="font-size: 0.85rem; opacity: 0.8;">by zigi arizona</p>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        const hamburger = document.querySelector('.hamburger');
        const navLinks = document.querySelector('.nav-links');
        
        if (hamburger) {
            hamburger.addEventListener('click', function() {
                this.classList.toggle('active');
                navLinks.classList.toggle('active');
            });
        }

        // Close mobile menu when clicking on a link
        document.querySelectorAll('.nav-links a').forEach(link => {
            link.addEventListener('click', () => {
                if (hamburger) hamburger.classList.remove('active');
                if (navLinks) navLinks.classList.remove('active');
            });
        });

        // Add scroll animation
        document.addEventListener('DOMContentLoaded', function() {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe elements to animate
            const elementsToAnimate = document.querySelectorAll('.feature-card, .step-card');
            elementsToAnimate.forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(30px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });
    </script>
</body>
</html>