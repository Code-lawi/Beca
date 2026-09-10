<?php
require_once 'config/database.php';

// Get settings
$settings = getSettings($conn);
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#6C63FF">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
    <title>About - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           CSS VARIABLES
        ============================================ */
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-light: #8B83FF;
            --secondary: #FF6B6B;
            --success: #00C9A7;
            --success-dark: #00A889;
            --warning: #FFD93D;
            --danger: #FF6B6B;
            --dark: #2D3436;
            --gray: #636E72;
            --light-gray: #DFE6E9;
            --white: #FFFFFF;
            --light-bg: #F5F7FA;
            --gradient: linear-gradient(135deg, #6C63FF 0%, #FF6B6B 100%);
            --gradient-soft: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow: 0 4px 20px rgba(0,0,0,0.06);
            --shadow-hover: 0 12px 40px rgba(108, 99, 255, 0.15);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.12);
            --radius: 16px;
            --radius-sm: 10px;
            --radius-lg: 24px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --safe-area-top: env(safe-area-inset-top, 0px);
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        html {
            scroll-behavior: smooth;
            -webkit-text-size-adjust: 100%;
            text-size-adjust: 100%;
        }

        body {
            background: var(--light-bg);
            color: var(--dark);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
            padding-top: var(--safe-area-top);
            padding-bottom: var(--safe-area-bottom);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            line-height: 1.5;
        }

        /* ============================================
           MATHEMATICAL FORMULAE BACKGROUND
        ============================================ */
        .math-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            background: var(--light-bg);
        }

        .math-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(108, 99, 255, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 107, 107, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 201, 167, 0.04) 0%, transparent 60%);
            z-index: 0;
        }

        body.dark-mode .math-bg {
            background: #0D0D1A;
        }

        body.dark-mode .math-bg::before {
            background: 
                radial-gradient(circle at 20% 30%, rgba(108, 99, 255, 0.10) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 107, 107, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 201, 167, 0.06) 0%, transparent 60%);
        }

        .math-formula {
            position: absolute;
            font-size: 24px;
            font-weight: 700;
            color: rgba(108, 99, 255, 0.10);
            font-family: 'Times New Roman', serif;
            white-space: nowrap;
            animation: floatFormula 35s linear infinite;
            opacity: 0.6;
            transition: all 0.5s ease;
            z-index: 1;
        }

        body:not(.dark-mode) .math-formula {
            color: rgba(108, 99, 255, 0.12);
            text-shadow: 0 1px 2px rgba(108, 99, 255, 0.06);
        }

        body.dark-mode .math-formula {
            color: rgba(108, 99, 255, 0.20);
        }

        .math-formula:nth-child(1) { top: 5%; left: -10%; animation-duration: 28s; font-size: 32px; }
        .math-formula:nth-child(2) { top: 15%; left: 20%; animation-duration: 22s; font-size: 26px; animation-delay: -4s; }
        .math-formula:nth-child(3) { top: 25%; left: 50%; animation-duration: 32s; font-size: 38px; animation-delay: -7s; }
        .math-formula:nth-child(4) { top: 35%; left: 75%; animation-duration: 24s; font-size: 22px; animation-delay: -2s; }
        .math-formula:nth-child(5) { top: 45%; left: -5%; animation-duration: 36s; font-size: 42px; animation-delay: -10s; }
        .math-formula:nth-child(6) { top: 55%; left: 30%; animation-duration: 20s; font-size: 18px; animation-delay: -5s; }
        .math-formula:nth-child(7) { top: 65%; left: 60%; animation-duration: 30s; font-size: 28px; animation-delay: -8s; }
        .math-formula:nth-child(8) { top: 75%; left: 85%; animation-duration: 23s; font-size: 24px; animation-delay: -3s; }
        .math-formula:nth-child(9) { top: 85%; left: 10%; animation-duration: 38s; font-size: 34px; animation-delay: -12s; }
        .math-formula:nth-child(10) { top: 10%; left: 90%; animation-duration: 26s; font-size: 16px; animation-delay: -6s; }
        .math-formula:nth-child(11) { top: 40%; left: 45%; animation-duration: 34s; font-size: 40px; animation-delay: -9s; }
        .math-formula:nth-child(12) { top: 70%; left: 40%; animation-duration: 21s; font-size: 14px; animation-delay: -11s; }

        @keyframes floatFormula {
            0% { transform: translateX(0) rotate(0deg); opacity: 0.15; }
            25% { transform: translateX(100px) rotate(3deg); opacity: 0.5; }
            50% { transform: translateX(200px) rotate(-2deg); opacity: 0.25; }
            75% { transform: translateX(300px) rotate(4deg); opacity: 0.6; }
            100% { transform: translateX(calc(100vw + 100%)) rotate(0deg); opacity: 0.15; }
        }

        /* ============================================
           SCROLL BAR
        ============================================ */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: var(--light-bg); }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }

        /* ============================================
           HEADER
        ============================================ */
        .header {
            background: var(--white);
            color: var(--dark);
            padding: 10px 0;
            padding-top: calc(10px + var(--safe-area-top));
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            background: rgba(255,255,255,0.92);
            transition: var(--transition);
        }

        body.dark-mode .header {
            background: rgba(26,26,46,0.92);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }

        .header .logo {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
        }
        .header .logo:hover { transform: scale(1.02); }
        .header .logo:active { transform: scale(0.98); }
        body.dark-mode .header .logo { color: white; }

        .header .logo img {
            max-height: 32px;
            border-radius: 8px;
        }

        .header .logo h1 {
            font-size: 18px;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .header .logo h1 span {
            -webkit-text-fill-color: var(--dark);
        }
        body.dark-mode .header .logo h1 span {
            -webkit-text-fill-color: white;
        }

        .header .nav {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .header .nav a {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            font-size: 13px;
            padding: 8px 14px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            -webkit-tap-highlight-color: transparent;
        }
        .header .nav a i {
            font-size: 14px;
        }
        body.dark-mode .header .nav a { color: rgba(255,255,255,0.7); }

        .header .nav a:hover,
        .header .nav a.active {
            color: var(--dark);
            background: rgba(108, 99, 255, 0.06);
        }
        body.dark-mode .header .nav a:hover,
        body.dark-mode .header .nav a.active {
            color: white;
            background: rgba(108, 99, 255, 0.15);
        }
        .header .nav a:active { transform: scale(0.97); }

        .header .nav .whatsapp-btn {
            background: #25D366;
            color: white;
            padding: 8px 18px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            transition: var(--transition);
        }
        .header .nav .whatsapp-btn:hover {
            transform: scale(1.05) translateY(-2px);
            background: #1da851;
            color: white;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }
        .header .nav .whatsapp-btn:active { transform: scale(0.97); }

        .hamburger {
            display: none;
            font-size: 20px;
            cursor: pointer;
            color: var(--dark);
            padding: 6px;
            transition: var(--transition);
            background: none;
            border: none;
            border-radius: 8px;
            min-width: 44px;
            min-height: 44px;
            align-items: center;
            justify-content: center;
        }
        .hamburger:hover { transform: scale(1.1); background: rgba(108, 99, 255, 0.08); }
        .hamburger:active { transform: scale(0.95); }
        body.dark-mode .hamburger { color: white; }

        .theme-toggle {
            background: rgba(0,0,0,0.04);
            border: none;
            color: var(--dark);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        body.dark-mode .theme-toggle {
            background: rgba(255,255,255,0.08);
            color: white;
        }
        .theme-toggle:hover {
            background: rgba(108, 99, 255, 0.1);
            transform: rotate(30deg) scale(1.1);
        }
        .theme-toggle:active { transform: rotate(30deg) scale(0.95); }

        /* ============================================
           PAGE HEADER
        ============================================ */
        .page-header {
            background: var(--gradient-soft);
            color: white;
            padding: 60px 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.05);
            border-radius: 50%;
            animation: float 20s infinite;
        }

        .page-header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            animation: float 25s infinite reverse;
        }

        .page-header .container {
            position: relative;
            z-index: 1;
        }

        .page-header .header-icon {
            font-size: 60px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            animation: float 4s ease-in-out infinite;
            margin-bottom: 15px;
            width: 90px;
            height: 90px;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .page-header h1 {
            font-size: 40px;
            font-weight: 800;
            margin-bottom: 10px;
            animation: fadeInUp 0.8s ease;
            line-height: 1.2;
        }

        .page-header h1 .highlight {
            background: linear-gradient(135deg, #FFD93D 0%, #FF6B6B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-header p {
            font-size: 17px;
            opacity: 0.95;
            animation: fadeInUp 0.8s ease 0.2s both;
            max-width: 600px;
            margin: 0 auto;
        }

        /* ============================================
           CONTAINER
        ============================================ */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
            position: relative;
            z-index: 1;
        }

        /* ============================================
           ABOUT SECTION
        ============================================ */
        .about-section {
            padding: 60px 0;
            position: relative;
            z-index: 1;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .about-grid .about-text {
            animation: fadeInUp 0.8s ease;
        }

        .about-grid .about-text .badge-about {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 18px;
            background: var(--gradient);
            color: white;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }

        .about-grid .about-text h2 {
            font-size: 34px;
            color: var(--dark);
            margin-bottom: 18px;
            line-height: 1.2;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        body.dark-mode .about-grid .about-text h2 { color: white; }

        .about-grid .about-text h2 .gradient-text {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .about-grid .about-text p {
            color: var(--gray);
            line-height: 1.9;
            font-size: 15px;
            margin-bottom: 15px;
        }

        /* ===== MISSION & VISION ===== */
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid rgba(108, 99, 255, 0.1);
        }

        .mission-vision .mv-item {
            background: var(--white);
            padding: 25px 22px;
            border-radius: var(--radius);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
            box-shadow: var(--shadow);
        }

        body.dark-mode .mission-vision .mv-item {
            background: #2D2D44;
            box-shadow: none;
        }

        .mission-vision .mv-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .mission-vision .mv-item .mv-icon {
            width: 50px;
            height: 50px;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
            transition: var(--transition);
        }

        .mission-vision .mv-item:hover .mv-icon {
            background: var(--primary);
            color: white;
            transform: scale(1.05) rotate(-5deg);
        }

        .mission-vision .mv-item h4 {
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 8px;
            font-weight: 700;
        }
        body.dark-mode .mission-vision .mv-item h4 { color: white; }

        .mission-vision .mv-item p {
            color: var(--gray);
            font-size: 13px;
            line-height: 1.7;
        }

        /* ===== ABOUT IMAGE ===== */
        .about-grid .about-image {
            text-align: center;
            animation: fadeInUp 0.8s ease 0.3s both;
        }

        .about-grid .about-image .image-wrapper {
            position: relative;
            display: inline-block;
        }

        .about-grid .about-image .image-wrapper .floating-badge {
            position: absolute;
            bottom: -15px;
            right: -15px;
            background: var(--gradient);
            color: white;
            padding: 12px 22px;
            border-radius: 15px;
            font-weight: 700;
            font-size: 14px;
            box-shadow: var(--shadow-lg);
            animation: float 3s ease-in-out infinite;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .about-grid .about-image img {
            max-width: 100%;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .about-grid .about-image img:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-hover);
        }

        .about-grid .about-image .placeholder {
            font-size: 130px;
            color: var(--primary);
            background: var(--white);
            padding: 50px;
            border-radius: var(--radius);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow);
            border: 3px dashed rgba(108, 99, 255, 0.15);
            transition: var(--transition);
        }
        body.dark-mode .about-grid .about-image .placeholder {
            background: #1A1A2E;
        }
        .about-grid .about-image .placeholder:hover {
            transform: scale(1.02);
        }

        /* ============================================
           FEATURES
        ============================================ */
        .features-section {
            background: var(--white);
            padding: 60px 0;
            position: relative;
            z-index: 1;
        }
        body.dark-mode .features-section {
            background: #1A1A2E;
        }

        .features-section .section-header {
            text-align: center;
            margin-bottom: 45px;
        }

        .features-section .section-header .sub-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 18px;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
            border-radius: 50px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 12px;
        }

        .features-section .section-header h2 {
            font-size: 32px;
            color: var(--dark);
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        body.dark-mode .features-section .section-header h2 { color: white; }

        .features-section .section-header h2 .gradient-text {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .features-section .section-header p {
            color: var(--gray);
            font-size: 15px;
            margin-top: 10px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 25px;
        }

        .feature-card {
            text-align: center;
            padding: 35px 25px;
            border-radius: var(--radius);
            background: var(--light-bg);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(30px);
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        body.dark-mode .feature-card {
            background: #2D2D44;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gradient);
            opacity: 0;
            transition: var(--transition);
            z-index: 0;
        }

        .feature-card:hover::before {
            opacity: 0.04;
        }

        .feature-card:nth-child(1) { animation-delay: 0.1s; }
        .feature-card:nth-child(2) { animation-delay: 0.2s; }
        .feature-card:nth-child(3) { animation-delay: 0.3s; }
        .feature-card:nth-child(4) { animation-delay: 0.4s; }
        .feature-card:nth-child(5) { animation-delay: 0.5s; }
        .feature-card:nth-child(6) { animation-delay: 0.6s; }

        .feature-card.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .feature-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-hover);
            border-color: var(--primary);
        }

        .feature-card .feature-icon-wrapper {
            width: 75px;
            height: 75px;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 32px;
            transition: var(--transition);
            position: relative;
            z-index: 1;
        }

        .feature-card:hover .feature-icon-wrapper {
            background: var(--primary);
            color: white;
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.3);
        }

        .feature-card h4 {
            font-size: 17px;
            color: var(--dark);
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
            font-weight: 700;
        }
        body.dark-mode .feature-card h4 { color: white; }

        .feature-card p {
            color: var(--gray);
            font-size: 13px;
            line-height: 1.7;
            position: relative;
            z-index: 1;
        }

        /* ============================================
           STATS SECTION
        ============================================ */
        .stats-section {
            padding: 60px 0;
            background: var(--gradient-soft);
            color: white;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .stats-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -20%;
            width: 500px;
            height: 500px;
            background: rgba(255,255,255,0.03);
            border-radius: 50%;
            animation: float 18s infinite;
        }

        .stats-section .container {
            position: relative;
            z-index: 1;
        }

        .stats-section .stats-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .stats-section .stats-header h2 {
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .stats-section .stats-header p {
            opacity: 0.85;
            font-size: 15px;
            margin-top: 6px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            text-align: center;
        }

        .stats-grid .stat-item {
            background: rgba(255,255,255,0.08);
            padding: 30px 20px;
            border-radius: var(--radius);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.1);
            transition: var(--transition);
            opacity: 0;
            transform: scale(0.9);
        }

        .stats-grid .stat-item.visible {
            opacity: 1;
            transform: scale(1);
        }

        .stats-grid .stat-item:hover {
            transform: translateY(-5px) scale(1.02);
            background: rgba(255,255,255,0.12);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        }

        .stats-grid .stat-item .stat-icon {
            width: 60px;
            height: 60px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 26px;
            transition: var(--transition);
        }

        .stats-grid .stat-item:hover .stat-icon {
            background: rgba(255,255,255,0.25);
            transform: scale(1.1);
        }

        .stats-grid .stat-item .stat-number {
            font-size: 42px;
            font-weight: 800;
            color: white;
            line-height: 1.1;
        }

        .stats-grid .stat-item .stat-number .plus {
            font-size: 28px;
            color: rgba(255,255,255,0.7);
        }

        .stats-grid .stat-item .stat-number .count {
            display: inline-block;
        }

        .stats-grid .stat-item .stat-label {
            font-size: 15px;
            opacity: 0.85;
            margin-top: 8px;
            font-weight: 500;
        }

        /* ============================================
           CTA SECTION
        ============================================ */
        .cta-section {
            padding: 60px 0;
            background: var(--white);
            text-align: center;
            position: relative;
            z-index: 1;
        }
        body.dark-mode .cta-section {
            background: #1A1A2E;
        }

        .cta-section h3 {
            font-size: 28px;
            color: var(--dark);
            margin-bottom: 12px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        body.dark-mode .cta-section h3 { color: white; }

        .cta-section h3 .gradient-text {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cta-section p {
            color: var(--gray);
            margin-bottom: 25px;
            font-size: 15px;
        }

        .cta-section .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-section .cta-buttons a {
            padding: 14px 32px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            min-height: 48px;
            -webkit-tap-highlight-color: transparent;
        }

        .cta-section .cta-buttons .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.3);
        }

        .cta-section .cta-buttons .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(108, 99, 255, 0.4);
        }
        .cta-section .cta-buttons .btn-primary:active { transform: scale(0.97); }

        .cta-section .cta-buttons .btn-secondary {
            background: transparent;
            color: var(--dark);
            border: 2px solid var(--light-gray);
        }
        body.dark-mode .cta-section .cta-buttons .btn-secondary {
            color: white;
            border-color: rgba(255,255,255,0.2);
        }

        .cta-section .cta-buttons .btn-secondary:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-3px) scale(1.02);
            background: rgba(108, 99, 255, 0.05);
        }
        .cta-section .cta-buttons .btn-secondary:active { transform: scale(0.97); }

        /* ============================================
           TOAST
        ============================================ */
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--dark);
            color: white;
            padding: 14px 22px;
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            transform: translateY(100px) scale(0.8);
            opacity: 0;
            transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            max-width: 90%;
            border-left: 4px solid var(--primary);
        }
        .toast.show {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .toast .toast-icon {
            font-size: 20px;
            color: var(--primary);
        }
        .toast .toast-content h4 { font-size: 13px; font-weight: 700; }
        .toast .toast-content p { font-size: 12px; opacity: 0.8; }

        /* ============================================
           BACK TO TOP
        ============================================ */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 48px;
            height: 48px;
            background: var(--gradient);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            box-shadow: var(--shadow);
            transition: var(--transition);
            opacity: 0;
            transform: scale(0) rotate(0deg);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
        }
        .back-to-top.show {
            opacity: 1;
            transform: scale(1) rotate(0deg);
        }
        .back-to-top:hover {
            transform: translateY(-3px) scale(1.1);
            box-shadow: var(--shadow-hover);
        }
        .back-to-top:active { transform: scale(0.95); }

        /* ============================================
           FOOTER
        ============================================ */
        .footer {
            background: var(--white);
            color: var(--gray);
            padding: 40px 15px 20px;
            padding-bottom: calc(20px + var(--safe-area-bottom));
            margin-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.04);
            position: relative;
            z-index: 1;
        }
        body.dark-mode .footer {
            background: #0D0D1A;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .footer .container {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 30px;
        }

        .footer h4 {
            color: var(--dark);
            margin-bottom: 12px;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .footer h4 i {
            color: var(--primary);
        }
        body.dark-mode .footer h4 { color: white; }

        .footer a {
            color: var(--gray);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 6px;
            transition: var(--transition);
            font-size: 13px;
            padding: 4px 0;
        }
        .footer a i {
            width: 16px;
            text-align: center;
            font-size: 13px;
        }
        .footer a:hover {
            color: var(--dark);
            transform: translateX(4px);
        }
        body.dark-mode .footer a:hover { color: white; }

        .footer .social-links {
            display: flex;
            gap: 12px;
            margin-top: 8px;
            flex-wrap: wrap;
        }

        .footer .social-links a {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(0,0,0,0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s ease;
            margin-bottom: 0;
            border: 2px solid rgba(0,0,0,0.04);
            color: var(--gray);
            text-decoration: none;
        }
        body.dark-mode .footer .social-links a {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.6);
        }
        .footer .social-links a:hover {
            transform: translateY(-5px) scale(1.1);
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .footer .social-links a:active { transform: scale(0.95); }
        .footer .social-links a.instagram:hover {
            background: #E4405F;
            border-color: #E4405F;
        }
        .footer .social-links a.facebook:hover {
            background: #1877F2;
            border-color: #1877F2;
        }
        .footer .social-links a.twitter:hover {
            background: #000000;
            border-color: #1DA1F2;
            color: #1DA1F2;
        }
        .footer .social-links a.linkedin:hover {
            background: #0A66C2;
            border-color: #0A66C2;
        }
        .footer .social-links a.whatsapp-social:hover {
            background: #25D366;
            border-color: #25D366;
        }

        .footer .copyright {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid rgba(0,0,0,0.04);
            margin-top: 20px;
            font-size: 12px;
            color: var(--gray);
            grid-column: 1 / -1;
        }
        body.dark-mode .footer .copyright {
            border-top: 1px solid rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.35);
        }
        .footer .copyright i {
            color: #FF6B6B;
            animation: heartbeat 1.5s infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }

        /* ============================================
           ANIMATIONS
        ============================================ */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-12px); } }

        /* ============================================
           DARK MODE
        ============================================ */
        body.dark-mode {
            --light-bg: #0D0D1A;
            --dark: #FFFFFF;
            --gray: #8899AA;
        }

        /* ============================================
           RESPONSIVE - TABLET
        ============================================ */
        @media (max-width: 992px) {
            .about-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            .about-grid .about-image {
                order: -1;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        /* ============================================
           RESPONSIVE - MOBILE
        ============================================ */
        @media (max-width: 768px) {
            .header { padding: 8px 0; padding-top: calc(8px + var(--safe-area-top)); }
            .header .container { padding: 0 12px; }
            .header .logo h1 { font-size: 16px; }
            .header .logo img { max-height: 28px; }

            .hamburger { display: flex; }

            .header .nav {
                display: none;
                width: 100%;
                flex-direction: column;
                padding: 10px 8px;
                gap: 4px;
                background: var(--white);
                border-radius: 12px;
                margin-top: 8px;
                box-shadow: 0 8px 30px rgba(0,0,0,0.08);
                border: 1px solid rgba(0,0,0,0.04);
            }
            body.dark-mode .header .nav { background: #1A1A2E; border-color: rgba(255,255,255,0.05); }
            .header .nav.open { display: flex; }
            .header .nav a {
                width: 100%;
                text-align: center;
                padding: 12px 0;
                font-size: 14px;
                border-radius: 8px;
                justify-content: center;
            }
            .header .nav .whatsapp-btn {
                width: 100%;
                justify-content: center;
                padding: 12px;
                font-size: 14px;
                border-radius: 8px;
                margin-top: 4px;
            }

            .theme-toggle {
                width: 38px;
                height: 38px;
                font-size: 15px;
            }

            /* Page Header */
            .page-header { padding: 45px 15px; }
            .page-header .header-icon { width: 75px; height: 75px; font-size: 45px; }
            .page-header h1 { font-size: 28px; }
            .page-header p { font-size: 14px; }

            /* About */
            .about-section { padding: 40px 0; }
            .about-grid .about-text h2 { font-size: 26px; }
            .about-grid .about-text p { font-size: 14px; }
            .about-grid .about-image .placeholder { font-size: 80px; padding: 35px; }

            .mission-vision {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .mission-vision .mv-item { padding: 20px; }

            /* Features */
            .features-section { padding: 40px 0; }
            .features-section .section-header h2 { font-size: 24px; }
            .features-section .section-header p { font-size: 13px; }
            .features-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .feature-card { padding: 25px 20px; }
            .feature-card .feature-icon-wrapper { width: 65px; height: 65px; font-size: 26px; }
            .feature-card h4 { font-size: 15px; }
            .feature-card p { font-size: 12px; }

            /* Stats */
            .stats-section { padding: 45px 0; }
            .stats-section .stats-header h2 { font-size: 24px; }
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 15px;
            }
            .stats-grid .stat-item { padding: 22px 15px; }
            .stats-grid .stat-item .stat-icon { width: 50px; height: 50px; font-size: 22px; }
            .stats-grid .stat-item .stat-number { font-size: 28px; }
            .stats-grid .stat-item .stat-number .plus { font-size: 18px; }
            .stats-grid .stat-item .stat-label { font-size: 12px; }

            /* CTA */
            .cta-section { padding: 40px 0; }
            .cta-section h3 { font-size: 22px; }
            .cta-section p { font-size: 13px; }
            .cta-section .cta-buttons { gap: 10px; }
            .cta-section .cta-buttons a {
                padding: 12px 24px;
                font-size: 14px;
                flex: 1;
                min-width: 140px;
                justify-content: center;
            }

            /* Footer */
            .footer { padding: 30px 15px 20px; padding-bottom: calc(20px + var(--safe-area-bottom)); }
            .footer .container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 24px;
            }
            .footer .social-links { justify-content: center; }
            .footer h4 { justify-content: center; }
            .footer a { justify-content: center; }

            /* Toast */
            .toast {
                bottom: 80px;
                right: 12px;
                left: 12px;
                max-width: calc(100% - 24px);
                padding: 12px 16px;
            }

            /* Back to top */
            .back-to-top {
                bottom: 16px;
                left: 16px;
                width: 44px;
                height: 44px;
                font-size: 16px;
            }

            /* Math formulas */
            .math-formula { font-size: 12px !important; }
            .math-bg { opacity: 0.5; }
        }

        /* ============================================
           RESPONSIVE - SMALL MOBILE
        ============================================ */
        @media (max-width: 400px) {
            .header .logo h1 { font-size: 15px; }
            .header .logo img { max-height: 24px; }

            .page-header h1 { font-size: 24px; }
            .page-header .header-icon { width: 65px; height: 65px; font-size: 38px; }

            .about-grid .about-text h2 { font-size: 22px; }
            .about-grid .about-image .placeholder { font-size: 60px; padding: 25px; }

            .stats-grid { grid-template-columns: 1fr; }
            .stats-grid .stat-item .stat-number { font-size: 32px; }

            .feature-card { padding: 20px 16px; }
            .feature-card .feature-icon-wrapper { width: 55px; height: 55px; font-size: 22px; }

            .cta-section .cta-buttons a {
                padding: 10px 20px;
                font-size: 13px;
                min-width: 120px;
            }

            .footer .social-links a { width: 40px; height: 40px; font-size: 16px; }
        }

        /* ============================================
           LANDSCAPE MODE
        ============================================ */
        @media (max-width: 900px) and (orientation: landscape) {
            .page-header { padding: 30px 15px; }
            .page-header h1 { font-size: 24px; }
            .page-header .header-icon { font-size: 40px; width: 65px; height: 65px; }
        }

        /* ============================================
           DESKTOP NAV - FORCE SHOW
        ============================================ */
        @media (min-width: 769px) {
            .header .nav { display: flex !important; }
            .hamburger { display: none !important; }
        }

        /* ============================================
           REDUCED MOTION
        ============================================ */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
            .math-formula { animation: none !important; }
        }
    </style>
</head>
<body>

    <!-- ===== MATHEMATICAL FORMULAE BACKGROUND ===== -->
    <div class="math-bg" id="mathBg">
        <div class="math-formula">∫ f(x) dx</div>
        <div class="math-formula">Σ n² = n(n+1)(2n+1)/6</div>
        <div class="math-formula">E = mc²</div>
        <div class="math-formula">sin²θ + cos²θ = 1</div>
        <div class="math-formula">∇ × E = -∂B/∂t</div>
        <div class="math-formula">π ≈ 3.14159</div>
        <div class="math-formula">e^(iπ) + 1 = 0</div>
        <div class="math-formula">d/dx (xⁿ) = nxⁿ⁻¹</div>
        <div class="math-formula">F = ma</div>
        <div class="math-formula">√(a² + b²) = c</div>
        <div class="math-formula">P(E) = n(E)/n(S)</div>
        <div class="math-formula">sin(A+B) = sinA cosB + cosA sinB</div>
    </div>

    <!-- ===== TOAST ===== -->
    <div class="toast" id="toast">
        <div class="toast-icon"><i class="fas fa-info-circle"></i></div>
        <div class="toast-content">
            <h4>Welcome!</h4>
            <p id="toastMessage">Learn more about SCI-CALC</p>
        </div>
    </div>

    <!-- ===== BACK TO TOP ===== -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- ===== HEADER ===== -->
    <header class="header">
        <div class="container">
            <a href="index.php" class="logo">
                <?php if (!empty($settings['logo']) && file_exists('uploads/' . $settings['logo'])): ?>
                    <img src="uploads/<?php echo $settings['logo']; ?>" alt="Logo">
                <?php else: ?>
                    <img src="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2232%22 height=%2232%22%3E%3Crect width=%2232%22 height=%2232%22 rx=%228%22 fill=%22%236C63FF%22/%3E%3Ctext x=%2216%22 y=%2222%22 text-anchor=%22middle%22 font-size=%2218%22 fill=%22white%22%3E%E2%88%91%3C/text%3E%3C/svg%3E" alt="Logo">
                <?php endif; ?>
                <h1>SCI-<span>CALC</span></h1>
            </a>
            <div style="display:flex;align-items:center;gap:8px;">
                <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="hamburger" onclick="toggleMenu()" aria-label="Menu">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <nav class="nav" id="mainNav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="about.php" class="active"><i class="fas fa-info-circle"></i> About</a>
                <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
                <?php if (!empty($settings['whatsapp_number'])): ?>
                    <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank" class="whatsapp-btn">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- ===== PAGE HEADER ===== -->
    <section class="page-header">
        <div class="container">
            <div class="header-icon"><i class="fas fa-info-circle"></i></div>
            <h1>About <span class="highlight">Us</span></h1>
            <p>Learn more about <?php echo htmlspecialchars($settings['site_name']); ?> and our mission</p>
        </div>
    </section>

    <!-- ===== ABOUT SECTION ===== -->
    <section class="about-section">
        <div class="container">
            <div class="about-grid">
                <div class="about-text">
                    <span class="badge-about"><i class="fas fa-users"></i> Who We Are</span>
                    <h2>Welcome to <span class="gradient-text">SCI-CALC</span></h2>
                    <?php if (!empty($settings['about_info'])): ?>
                        <p><?php echo nl2br(htmlspecialchars($settings['about_info'])); ?></p>
                    <?php else: ?>
                        <p>
                            <?php echo htmlspecialchars($settings['site_name']); ?> is your trusted partner for 
                            high-quality scientific calculators in Tanzania. We provide authentic, reliable, 
                            and affordable calculators for students, teachers, engineers, and professionals.
                        </p>
                        <p>
                            Our mission is to make quality education tools accessible to everyone. We carefully 
                            select each product to ensure it meets the highest standards of quality and performance.
                        </p>
                        <p>
                            Whether you're a student preparing for exams or a professional needing accurate 
                            calculations, we have the right calculator for you.
                        </p>
                    <?php endif; ?>

                    <!-- ===== MISSION & VISION ===== -->
                    <div class="mission-vision">
                        <div class="mv-item">
                            <div class="mv-icon"><i class="fas fa-bullseye"></i></div>
                            <h4>Our Mission</h4>
                            <p>
                                To provide high-quality, authentic scientific calculators at affordable prices, 
                                making educational tools accessible to every student and professional in Tanzania.
                            </p>
                        </div>
                        <div class="mv-item">
                            <div class="mv-icon"><i class="fas fa-eye"></i></div>
                            <h4>Our Vision</h4>
                            <p>
                                To become the leading supplier of scientific calculators in East Africa, 
                                recognized for quality, reliability, and exceptional customer service.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <div class="image-wrapper">
                        <?php if (!empty($settings['logo']) && file_exists('uploads/' . $settings['logo'])): ?>
                            <img src="uploads/<?php echo $settings['logo']; ?>" alt="<?php echo $settings['site_name']; ?>" style="max-height:300px;">
                        <?php else: ?>
                            <div class="placeholder"><i class="fas fa-calculator"></i></div>
                        <?php endif; ?>
                        <div class="floating-badge"><i class="fas fa-star"></i> Since 2024</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== FEATURES ===== -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header">
                <span class="sub-badge"><i class="fas fa-star"></i> Why Choose Us</span>
                <h2>Our <span class="gradient-text">Features</span></h2>
                <p>We are committed to providing the best calculator shopping experience</p>
            </div>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon-wrapper"><i class="fas fa-check-circle"></i></div>
                    <h4>Authentic Products</h4>
                    <p>All our calculators are 100% genuine and sourced from trusted manufacturers</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper"><i class="fas fa-tags"></i></div>
                    <h4>Best Prices</h4>
                    <p>Competitive prices with the best value for money in the market</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper"><i class="fas fa-truck-fast"></i></div>
                    <h4>Fast Delivery</h4>
                    <p>Quick and reliable delivery across Tanzania</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper"><i class="fas fa-headset"></i></div>
                    <h4>24/7 Support</h4>
                    <p>Our team is always ready to help you with your questions</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper"><i class="fas fa-shield-halved"></i></div>
                    <h4>Warranty</h4>
                    <p>All products come with manufacturer warranty for peace of mind</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon-wrapper"><i class="fas fa-graduation-cap"></i></div>
                    <h4>Educational Focus</h4>
                    <p>We understand student needs and provide appropriate calculators</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== STATS SECTION ===== -->
    <section class="stats-section" id="stats">
        <div class="container">
            <div class="stats-header">
                <h2><i class="fas fa-chart-line"></i> Our Impact</h2>
                <p>Numbers that speak for themselves</p>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-smile"></i></div>
                    <div class="stat-number">
                        <span class="count" data-target="<?php echo $settings['happy_customers'] ?? 500; ?>">0</span><span class="plus">+</span>
                    </div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-shopping-cart"></i></div>
                    <div class="stat-number">
                        <span class="count" data-target="<?php echo $settings['products_sold'] ?? 50; ?>">0</span><span class="plus">+</span>
                    </div>
                    <div class="stat-label">Products Sold</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                    <div class="stat-number">
                        <span class="count" data-target="<?php echo $settings['calculator_models'] ?? 15; ?>">0</span><span class="plus">+</span>
                    </div>
                    <div class="stat-label">Calculator Models</div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fas fa-star"></i></div>
                    <div class="stat-number">
                        <span class="count" data-target="<?php echo $settings['rating'] ?? 4.8; ?>">0</span>
                    </div>
                    <div class="stat-label">Rating (out of 5)</div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CTA ===== -->
    <section class="cta-section">
        <div class="container">
            <h3>Ready to <span class="gradient-text">Shop</span>?</h3>
            <p>Browse our collection of high-quality calculators and place your order today!</p>
            <div class="cta-buttons">
                <a href="index.php#products" class="btn-primary">
                    <i class="fas fa-box"></i> View Products
                </a>
                <?php if (!empty($settings['whatsapp_number'])): ?>
                    <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank" class="btn-secondary">
                        <i class="fab fa-whatsapp"></i> Contact Us
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== FOOTER ===== -->
    <footer class="footer">
        <div class="container">
            <div>
                <h4><i class="fas fa-calculator"></i> <?php echo htmlspecialchars($settings['site_name']); ?></h4>
                <p style="color:var(--gray);font-size:12px;line-height:1.6;">
                    <?php echo htmlspecialchars($settings['footer_info'] ?: 'Your trusted source for scientific calculators.'); ?>
                </p>
            </div>
            <div>
                <h4><i class="fas fa-compass"></i> Quick Links</h4>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
            </div>
            <div>
                <h4><i class="fas fa-share-alt"></i> Follow Us</h4>
                <div class="social-links">
                    <!-- Instagram - Always visible -->
                    <a href="<?php echo !empty($settings['instagram_link']) ? htmlspecialchars($settings['instagram_link']) : '#'; ?>" 
                       target="_blank" rel="noopener noreferrer" class="instagram" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <!-- Facebook - Always visible -->
                    <a href="<?php echo !empty($settings['facebook_link']) ? htmlspecialchars($settings['facebook_link']) : '#'; ?>" 
                       target="_blank" rel="noopener noreferrer" class="facebook" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <!-- Twitter/X - Always visible -->
                    <a href="<?php echo !empty($settings['twitter_link']) ? htmlspecialchars($settings['twitter_link']) : '#'; ?>" 
                       target="_blank" rel="noopener noreferrer" class="twitter" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <!-- LinkedIn - Always visible -->
                    <a href="<?php echo !empty($settings['linkedin_link']) ? htmlspecialchars($settings['linkedin_link']) : '#'; ?>" 
                       target="_blank" rel="noopener noreferrer" class="linkedin" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <!-- WhatsApp Group - Always visible -->
                    <a href="<?php echo !empty($settings['whatsapp_group']) ? htmlspecialchars($settings['whatsapp_group']) : '#'; ?>" 
                       target="_blank" rel="noopener noreferrer" class="whatsapp-social" title="WhatsApp Group">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. Made with <i class="fas fa-heart"></i>
            </div>
        </div>
    </footer>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            'use strict';

            // ========================================
            // 1. MOBILE MENU
            // ========================================
            window.toggleMenu = function() {
                const nav = document.getElementById('mainNav');
                const hamburger = document.querySelector('.hamburger');
                nav.classList.toggle('open');
                hamburger.innerHTML = nav.classList.contains('open') 
                    ? '<i class="fas fa-times"></i>' 
                    : '<i class="fas fa-bars"></i>';
            };
            document.querySelectorAll('#mainNav a').forEach(link => {
                link.addEventListener('click', () => {
                    document.getElementById('mainNav').classList.remove('open');
                    document.querySelector('.hamburger').innerHTML = '<i class="fas fa-bars"></i>';
                });
            });

            // ========================================
            // 2. BACK TO TOP
            // ========================================
            window.addEventListener('scroll', function() {
                document.getElementById('backToTop').classList.toggle('show', window.scrollY > 400);
            }, { passive: true });

            window.scrollToTop = function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            // ========================================
            // 3. TOAST
            // ========================================
            let toastTimeout;
            window.showToast = function(message) {
                const toast = document.getElementById('toast');
                document.getElementById('toastMessage').textContent = message || 'Welcome to SCI-CALC!';
                toast.classList.add('show');
                clearTimeout(toastTimeout);
                toastTimeout = setTimeout(() => toast.classList.remove('show'), 4000);
            };

            setTimeout(() => {
                showToast('Welcome to our About page!');
            }, 1500);

            // ========================================
            // 4. DARK MODE
            // ========================================
            window.toggleTheme = function() {
                const body = document.body;
                const btn = document.querySelector('.theme-toggle');
                body.classList.toggle('dark-mode');
                btn.innerHTML = body.classList.contains('dark-mode') 
                    ? '<i class="fas fa-sun"></i>' 
                    : '<i class="fas fa-moon"></i>';
                localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
            };

            if (localStorage.getItem('theme') === 'dark') {
                document.body.classList.add('dark-mode');
                document.querySelector('.theme-toggle').innerHTML = '<i class="fas fa-sun"></i>';
            }

            // ========================================
            // 5. ANIMATED STATS COUNTER
            // ========================================
            const statItems = document.querySelectorAll('.stat-item');
            let statsAnimated = false;

            function animateNumbers() {
                if (statsAnimated) return;
                statsAnimated = true;

                statItems.forEach(item => {
                    item.classList.add('visible');
                    const countEl = item.querySelector('.count');
                    if (!countEl) return;

                    const target = parseFloat(countEl.getAttribute('data-target'));
                    const isDecimal = target % 1 !== 0;
                    const duration = 2500;
                    const startTime = Date.now();

                    function updateNumber() {
                        const elapsed = Date.now() - startTime;
                        const progress = Math.min(elapsed / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        const current = eased * target;

                        if (isDecimal) {
                            countEl.textContent = current.toFixed(1);
                        } else {
                            countEl.textContent = Math.floor(current);
                        }

                        if (progress < 1) {
                            requestAnimationFrame(updateNumber);
                        } else {
                            if (isDecimal) {
                                countEl.textContent = target.toFixed(1);
                            } else {
                                countEl.textContent = target;
                            }
                        }
                    }
                    updateNumber();
                });
            }

            const statsSection = document.querySelector('.stats-section');
            if (statsSection && 'IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            animateNumbers();
                            observer.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.3 });
                observer.observe(statsSection);
            }

            // ========================================
            // 6. FEATURE CARDS ANIMATION
            // ========================================
            const featureCards = document.querySelectorAll('.feature-card');
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                        }
                    });
                }, { threshold: 0.15 });
                featureCards.forEach(card => observer.observe(card));
            }

            // ========================================
            // 7. SMOOTH SCROLL
            // ========================================
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const offset = 80;
                        window.scrollTo({
                            top: target.getBoundingClientRect().top + window.pageYOffset - offset,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // ========================================
            // 8. KEYBOARD SHORTCUTS
            // ========================================
            document.addEventListener('keydown', function(e) {
                if ((e.key === 'm' || e.key === 'M') && window.innerWidth <= 768) {
                    toggleMenu();
                }
                if (e.key === 'd' || e.key === 'D') {
                    toggleTheme();
                }
                if (e.key === 't' || e.key === 'T') {
                    scrollToTop();
                }
                if (e.key === 'h' || e.key === 'H') {
                    window.location.href = 'index.php';
                }
            });

            // ========================================
            // 9. ROTATING MATH FORMULAE
            // ========================================
            const formulas = ['∫ f(x) dx', 'Σ n² = n(n+1)(2n+1)/6', 'E = mc²', 'sin²θ + cos²θ = 1', '∇ × E = -∂B/∂t', 'π ≈ 3.14159', 'e^(iπ) + 1 = 0', 'd/dx (xⁿ) = nxⁿ⁻¹', 'F = ma', '√(a² + b²) = c', 'P(E) = n(E)/n(S)', 'sin(A+B) = sinA cosB + cosA sinB'];
            const formulaEls = document.querySelectorAll('.math-formula');

            setInterval(() => {
                formulaEls.forEach(el => {
                    const idx = Math.floor(Math.random() * formulas.length);
                    el.textContent = formulas[idx];
                    const hue = Math.floor(Math.random() * 60) + 200;
                    el.style.color = `hsla(${hue}, 70%, 60%, 0.12)`;
                    const size = Math.floor(Math.random() * 20) + 18;
                    el.style.fontSize = size + 'px';
                });
            }, 4000);

            // ========================================
            // 10. NETWORK STATUS
            // ========================================
            window.addEventListener('online', () => showToast('Back online!'));
            window.addEventListener('offline', () => {
                const t = document.getElementById('toast');
                document.getElementById('toastMessage').textContent = 'You are offline!';
                t.classList.add('show');
                setTimeout(() => t.classList.remove('show'), 4000);
            });

            // ========================================
            // 11. CONSOLE
            // ========================================
            console.log('%c📄 SCI-CALC About Page', 'font-size:30px;font-weight:bold;color:#6C63FF;');
            console.log('%cLearn more about our mission and values!', 'font-size:16px;color:#2D3436;');
            console.log('%c📞 WhatsApp: <?php echo $settings['whatsapp_number']; ?>', 'font-size:14px;color:#25D366;');
            console.log('%c✅ About page loaded successfully!', 'font-size:14px;color:#00C9A7;');
        });
    </script>
</body>
</html>