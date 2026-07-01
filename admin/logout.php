<?php
// logout.php - Halaman Konfirmasi Logout
session_start();

// Proses logout jika dikonfirmasi
if (isset($_GET['confirm']) && $_GET['confirm'] == 'true') {
    // Hapus semua data session
    $_SESSION = array();
    
    // Hapus session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], 
            $params["domain"], 
            $params["secure"], 
            $params["httponly"]
        );
    }
    
    // Hancurkan session
    session_destroy();
    
    // Hapus cookie remember me jika ada
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    // Redirect ke login
    header('Location: login.php');
    exit;
}

// Jika belum dikonfirmasi, tampilkan halaman konfirmasi
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfirmasi Logout - AGROCURE</title>
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

        /* Background Effects */
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
            opacity: 0.08;
            animation: float 15s ease-in-out infinite;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            top: -100px;
            right: -100px;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            bottom: -50px;
            left: -50px;
            background: linear-gradient(45deg, #ff6f00, #e65100);
            animation-delay: -5s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            top: 50%;
            left: 20%;
            background: linear-gradient(45deg, #2196f3, #0d47a1);
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        /* Modal Container */
        .logout-container {
            max-width: 450px;
            width: 100%;
            z-index: 1;
            animation: fadeInUp 0.4s ease-out;
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

        /* Modal Card */
        .logout-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .logout-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4caf50, #2e7d32, #ff6f00);
        }

        /* Icon */
        .icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
        }

        .icon-wrapper i {
            font-size: 2.5rem;
            color: #ff6f00;
        }

        /* Title */
        .logout-card h2 {
            font-size: 1.6rem;
            color: #2c3e50;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }

        /* Message */
        .logout-card p {
            color: #7f8c8d;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Info Box */
        .info-box {
            background: linear-gradient(135deg, rgba(46, 125, 50, 0.08), rgba(76, 175, 80, 0.05));
            padding: 0.9rem;
            border-radius: 16px;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            border: 1px solid rgba(46, 125, 50, 0.15);
        }

        .info-box i {
            font-size: 1rem;
            color: #2e7d32;
        }

        .info-box span {
            font-size: 0.85rem;
            color: #2c3e50;
            text-align: left;
        }

        /* Button Group */
        .button-group {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
            padding: 0.85rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        /* Button Logout - Iya */
        .btn-logout {
            background: linear-gradient(135deg, #ff6f00, #e65100);
            color: white;
            box-shadow: 0 4px 15px rgba(255, 111, 0, 0.3);
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 111, 0, 0.4);
        }

        /* Button Batal */
        .btn-cancel {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .btn-cancel:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(108, 117, 125, 0.3);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .logout-card {
                padding: 1.8rem;
            }

            .logout-card h2 {
                font-size: 1.3rem;
            }

            .btn {
                padding: 0.7rem 1.5rem;
                font-size: 0.85rem;
            }

            .button-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }

            .icon-wrapper {
                width: 65px;
                height: 65px;
            }

            .icon-wrapper i {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="logout-container">
        <div class="logout-card">
            <div class="icon-wrapper">
                <i class="fas fa-sign-out-alt"></i>
            </div>
            <h2>Konfirmasi Logout</h2>
            <p>Apakah Anda yakin ingin keluar dari sistem?</p>
            
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <span>Setelah logout, Anda harus login kembali untuk mengakses panel admin.</span>
            </div>

            <div class="button-group">
                <a href="logout.php?confirm=true" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Ya, Logout
                </a>
                <a href="dashboard.php" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
exit;
?>