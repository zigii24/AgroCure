<?php
session_start();

// 1. Tentukan path absolut yang pasti (Paling Aman)
// __DIR__ merujuk pada folder tempat file login.php berada
$db_path = __DIR__ . '/../config/database.php';

// 2. Debug: Cek apakah file benar-benar ada sebelum di-include
if (!file_exists($db_path)) {
    die("FATAL ERROR: File koneksi tidak ditemukan di path: " . $db_path);
}

// 3. Masukkan file koneksi
require_once($db_path);

// 4. CEK LOGIN TERLEBIH DAHULU (Jangan hapus session sebelum dicek!)
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}
// Variabel error
$error = '';
$success_redirect = false;
$redirect_url = '';

// PROSES LOGIN HANYA JIKA FORM DISUBMIT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Validasi input
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'Username dan password harus diisi!';
    } else {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        
        // Query untuk cek admin
        $query = "SELECT * FROM admin WHERE username = '$username'";
        $result = mysqli_query($conn, $query);
        
        if ($result && mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            
            // CEK PASSWORD DENGAN MULTIPLE METHODS
            $login_success = false;
            
            if ($password == $admin['password']) {
                $login_success = true;
            } elseif (function_exists('password_verify') && password_verify($password, $admin['password'])) {
                $login_success = true;
            } elseif (md5($password) == $admin['password']) {
                $login_success = true;
            }
            
            if ($login_success) {
                // SET SESSION
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nama'];
                $_SESSION['login_time'] = time();
                
                // Set flag untuk redirect dengan loading
                $success_redirect = true;
                $redirect_url = 'dashboard.php';
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Username tidak ditemukan!';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - AGROCURE</title>
    <link rel="shortcut icon" href="../images/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f9f0 0%, #e8f5e9 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        /* Background Abstrak */
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            background: linear-gradient(45deg, #4caf50, #2e7d32);
            opacity: 0.1;
            animation: float 15s ease-in-out infinite;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            left: -100px;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            right: -50px;
            background: linear-gradient(45deg, #ff6f00, #e65100);
            animation-delay: -5s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            right: 20%;
            background: linear-gradient(45deg, #2196f3, #0d47a1);
            animation-delay: -10s;
        }

        .shape-4 {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 10%;
            background: linear-gradient(45deg, #9c27b0, #6a1b9a);
            animation-delay: -3s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            z-index: 1;
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
        
        .login-form {
            background: rgba(255, 255, 255, 0.98);
            padding: 2.5rem;
            border-radius: 28px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .login-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #2e7d32, #4caf50, #8bc34a);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 1rem;
            border-radius: 20px;
            box-shadow: 0 8px 25px rgba(46, 125, 50, 0.2);
            transition: transform 0.3s ease;
        }

        .logo-img:hover {
            transform: scale(1.05);
        }
        
        .login-form h2 {
            text-align: center;
            background: linear-gradient(135deg, #1b5e20, #2e7d32);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .login-subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 2rem;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #2c3e50;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 14px 15px 14px 45px;
            border: 2px solid #e8f4e8;
            border-radius: 14px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: #f8fdf8;
            color: #2c3e50;
        }

        .form-control:focus {
            outline: none;
            border-color: #4caf50;
            background: white;
            box-shadow: 0 0 0 3px rgba(76, 175, 80, 0.1);
            transform: translateY(-2px);
        }

        .form-control::placeholder {
            color: #a0a0a0;
        }
        
        .btn {
            background: linear-gradient(135deg, #4caf50, #2e7d32);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 14px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(76, 175, 80, 0.35);
        }

        .btn:active {
            transform: translateY(0);
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        /* Loading Overlay - Hijau Putih */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #1b5e20, #2e7d32, #4caf50);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            animation: fadeIn 0.5s ease;
        }

        .loading-overlay.show {
            display: flex;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Logo di tengah loading */
        .loading-logo-wrapper {
            text-align: center;
            margin-bottom: 40px;
            animation: logoFloat 2s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        .loading-logo-img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            margin-bottom: 20px;
            filter: drop-shadow(0 0 20px rgba(255, 255, 255, 0.5));
        }

        .loading-logo-text {
            color: white;
            font-size: 1.8rem;
            font-weight: 800;
            letter-spacing: 3px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .loading-logo-sub {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        /* Loading Spinner Hijau Putih */
        .loading-spinner-green {
            width: 70px;
            height: 70px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-bottom: 25px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 1.2rem;
            font-weight: 500;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .loading-dots {
            display: inline-flex;
            gap: 5px;
            margin-left: 8px;
        }

        .loading-dots span {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: bounce 1.4s ease-in-out infinite;
        }

        .loading-dots span:nth-child(1) { animation-delay: 0s; }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }

        @keyframes bounce {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-12px); }
        }

        /* Progress Bar Hijau */
        .loading-progress {
            width: 280px;
            height: 6px;
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            margin-top: 20px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .loading-progress-bar {
            width: 0%;
            height: 100%;
            background: white;
            border-radius: 10px;
            transition: width 0.1s linear;
            box-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        }

        .loading-percentage {
            color: white;
            font-size: 0.9rem;
            margin-top: 10px;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .loading-subtext {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            margin-top: 15px;
            letter-spacing: 1px;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 14px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            animation: slideDown 0.3s ease-out;
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

        .alert i {
            margin-right: 10px;
            font-size: 1.1rem;
        }

        .alert-error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border-left: 4px solid #c0392b;
        }
        
        .back-link {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #ecf0f1;
        }
        
        .back-link a {
            color: #7f8c8d;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
        }

        .back-link a:hover {
            color: #4caf50;
        }

        .back-link a i {
            margin-right: 6px;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-form {
                padding: 1.8rem;
            }
            
            .login-form h2 {
                font-size: 1.6rem;
            }

            .logo-img {
                width: 60px;
                height: 60px;
            }

            .loading-logo-img {
                width: 80px;
                height: 80px;
            }

            .loading-logo-text {
                font-size: 1.3rem;
            }

            .loading-spinner-green {
                width: 50px;
                height: 50px;
            }

            .loading-text {
                font-size: 1rem;
            }

            .loading-progress {
                width: 220px;
            }
        }
    </style>
</head>
<body>
    <!-- Background Abstrak -->
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>
    </div>
    
    <!-- Loading Overlay - Hijau Putih dengan Logo -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-logo-wrapper">
            <img src="../images/logo.png" alt="AGROCURE Logo" class="loading-logo-img" onerror="this.src='logo.png'">
            <div class="loading-logo-text">AGROCURE</div>
            <div class="loading-logo-sub">SISTEM PAKAR PENYAKIT PADI</div>
        </div>
        <div class="loading-spinner-green"></div>
        <div class="loading-text">
            Mengalihkan ke Dashboard
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
        <div class="loading-progress">
            <div class="loading-progress-bar" id="progressBar"></div>
        </div>
        <div class="loading-percentage" id="loadingPercentage">0%</div>
        <div class="loading-subtext">Memuat data panel admin, mohon tunggu...</div>
    </div>
    
    <div class="login-container">
        <form method="POST" action="" class="login-form" id="loginForm">
            <div class="logo">
                <img src="../images/logo.png" alt="AGROCURE Logo" class="logo-img" onerror="this.src='logo.png'">
                <h2>AGROCURE</h2>
                <p class="login-subtitle">Admin Panel Login</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="username">
                    <i class="fas fa-user" style="margin-right: 5px;"></i>Username
                </label>
                <div class="input-with-icon">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="username" class="form-control" required 
                           placeholder="Masukkan username Anda" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">
                    <i class="fas fa-lock" style="margin-right: 5px;"></i>Password
                </label>
                <div class="input-with-icon">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="password" class="form-control" required 
                           placeholder="Masukkan password Anda">
                    <i class="fas fa-eye toggle-password" style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #7f8c8d; z-index: 10;"></i>
                </div>
            </div>
            
            <button type="submit" class="btn" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <div class="back-link">
                <a href="../index.php">
                    <i class="fas fa-arrow-left"></i> Kembali ke Website Utama
                </a>
            </div>
        </form>
    </div>

    <?php if ($success_redirect): ?>
    <script>
        // Tampilkan loading overlay
        const loadingOverlay = document.getElementById('loadingOverlay');
        const progressBar = document.getElementById('progressBar');
        const loadingPercentage = document.getElementById('loadingPercentage');
        
        loadingOverlay.classList.add('show');
        
        // Animasi progress bar 5 detik (5000ms)
        let progress = 0;
        const totalDuration = 5000; // 5 detik
        const intervalTime = 50; // update setiap 50ms
        const step = (intervalTime / totalDuration) * 100; // 1% per 50ms?
        // step = (50 / 5000) * 100 = 1% per 50ms
        // Maka total 100 update = 5000ms (5 detik)
        
        const interval = setInterval(function() {
            progress += 1; // tambah 1% setiap 50ms
            if (progress >= 100) {
                progress = 100;
                clearInterval(interval);
                // Redirect setelah progress selesai
                window.location.href = '<?php echo $redirect_url; ?>';
            }
            progressBar.style.width = progress + '%';
            if (loadingPercentage) {
                loadingPercentage.textContent = Math.floor(progress) + '%';
            }
        }, 50); // 50ms * 100 = 5000ms (5 detik)
    </script>
    <?php endif; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const usernameField = document.getElementById('username');
            const passwordField = document.getElementById('password');
            const loginBtn = document.getElementById('loginBtn');
            const loginForm = document.getElementById('loginForm');
            
            // Auto-focus on username field
            if (usernameField.value === '') {
                usernameField.focus();
            } else {
                passwordField.focus();
            }
            
            // Form validation before submit
            loginForm.addEventListener('submit', function(e) {
                const username = usernameField.value.trim();
                const password = passwordField.value.trim();
                
                if (username === '') {
                    e.preventDefault();
                    showError('Username harus diisi!');
                    usernameField.focus();
                    return false;
                }
                
                if (password === '') {
                    e.preventDefault();
                    showError('Password harus diisi!');
                    passwordField.focus();
                    return false;
                }
                
                // Disable button to prevent double submit
                loginBtn.disabled = true;
                loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
                
                return true;
            });
            
            // Show error message function
            function showError(message) {
                const existingAlert = document.querySelector('.alert-error');
                if (existingAlert) {
                    existingAlert.remove();
                }
                
                const alertDiv = document.createElement('div');
                alertDiv.className = 'alert alert-error';
                alertDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                
                const form = document.querySelector('.login-form');
                const logoElement = form.querySelector('.logo');
                if (logoElement) {
                    logoElement.insertAdjacentElement('afterend', alertDiv);
                } else {
                    form.insertBefore(alertDiv, form.firstChild);
                }
                
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 3000);
            }
            
            // Password visibility toggle
            const togglePassword = document.querySelector('.toggle-password');
            if (togglePassword) {
                togglePassword.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            }
        });
    </script>
</body>
</html>