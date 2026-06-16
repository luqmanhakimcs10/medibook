<?php
// 404.php — Place in medibook/ root folder
// Also add this to .htaccess: ErrorDocument 404 /medibook/404.php
require_once 'config/db.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — MediBook</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dark-mode.css">
    <style>
        .error-page {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
            background: var(--bg);
        }
        .error-code {
            font-family: 'Sora', sans-serif;
            font-size: clamp(6rem, 15vw, 10rem);
            font-weight: 800;
            line-height: 1;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
        }
        .error-illustration {
            font-size: 5rem;
            margin-bottom: 24px;
            animation: float 3s ease-in-out infinite;
        }
        .error-title {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: var(--text-dark);
        }
        .error-desc {
            color: var(--text-mid);
            font-size: 1rem;
            max-width: 440px;
            line-height: 1.7;
            margin-bottom: 36px;
        }
        .error-actions {
            display: flex;
            gap: 14px;
            justify-content: center;
            margin-bottom: 48px;
        }
        .quick-links {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .quick-link {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            background: white;
            border: 1px solid var(--border);
            border-radius: 50px;
            font-size: .85rem;
            font-weight: 600;
            color: var(--text-mid);
            transition: all .2s;
            text-decoration: none;
        }
        .quick-link:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: var(--primary-light);
            transform: translateY(-2px);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-12px); }
        }
    </style>
</head>
<body>

<!-- Minimal navbar -->
<nav style="position:fixed;top:0;left:0;right:0;height:64px;background:rgba(255,255,255,.95);
            backdrop-filter:blur(20px);border-bottom:1px solid var(--border);
            display:flex;align-items:center;justify-content:space-between;
            padding:0 5%;z-index:100">
    <a href="index.php" style="display:flex;align-items:center;gap:10px;
                               font-family:'Sora',sans-serif;font-size:1.35rem;
                               font-weight:800;color:var(--primary)">
        <div style="width:36px;height:36px;background:var(--primary);border-radius:9px;
                    display:flex;align-items:center;justify-content:center;
                    color:white;font-size:1rem">🏥</div>
        Medi<span style="color:var(--accent)">Book</span>
    </a>
    <button class="theme-toggle">🌙</button>
</nav>

<div class="error-page" style="padding-top:80px">
    <div class="error-illustration">🏥</div>
    <div class="error-code">404</div>
    <h1 class="error-title">Oops! Page Not Found</h1>
    <p class="error-desc">
        The page you're looking for seems to have gone on a medical leave.
        It may have been moved, deleted, or the URL might be incorrect.
    </p>

    <div class="error-actions">
        <a href="index.php" class="btn btn-primary btn-lg">🏠 Go to Home</a>
        <a href="javascript:history.back()" class="btn btn-outline btn-lg">← Go Back</a>
    </div>

    <p style="font-size:.85rem;color:var(--text-light);margin-bottom:16px;font-weight:600">
        Or go directly to:
    </p>
    <div class="quick-links">
        <?php if (isLoggedIn()): ?>
        <a href="<?= $_SESSION['role'] ?>/dashboard.php" class="quick-link">📊 My Dashboard</a>
        <?php endif; ?>
        <a href="auth/login.php"       class="quick-link">🔑 Login</a>
        <a href="auth/register.php"    class="quick-link">🧑 Patient Register</a>
        <a href="auth/register_doctor.php" class="quick-link">👨‍⚕️ Doctor Apply</a>
        <a href="index.php#doctors"    class="quick-link">🩺 Find Doctors</a>
    </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
