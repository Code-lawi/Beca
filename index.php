<?php
require_once 'config/database.php';

// Get settings
$settings = getSettings($conn);

// Get visible products
$products = getProducts($conn, null, 1);

// Get homepage videos
$videos = getVideos($conn, 6, 1, 1);
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
    <title><?php echo htmlspecialchars($settings['site_name']); ?> - Scientific Calculators</title>
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
                radial-gradient(circle at 20% 30%, rgba(108, 99, 255, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 107, 107, 0.04) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 201, 167, 0.03) 0%, transparent 60%);
            z-index: 0;
        }

        body.dark-mode .math-bg {
            background: #0D0D1A;
        }

        body.dark-mode .math-bg::before {
            background: 
                radial-gradient(circle at 20% 30%, rgba(108, 99, 255, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 107, 107, 0.06) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, rgba(0, 201, 167, 0.05) 0%, transparent 60%);
        }

        .math-formula {
            position: absolute;
            font-size: 24px;
            font-weight: 700;
            color: rgba(108, 99, 255, 0.08);
            font-family: 'Times New Roman', serif;
            white-space: nowrap;
            animation: floatFormula 35s linear infinite;
            opacity: 0.6;
            transition: all 0.5s ease;
            z-index: 1;
        }

        body:not(.dark-mode) .math-formula {
            color: rgba(108, 99, 255, 0.10);
            text-shadow: 0 1px 2px rgba(108, 99, 255, 0.05);
        }

        body.dark-mode .math-formula {
            color: rgba(108, 99, 255, 0.18);
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
            position: relative;
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
           HERO
        ============================================ */
        .hero {
            background: var(--gradient-soft);
            color: white;
            padding: 60px 15px;
            text-align: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }
        .hero::before {
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
        .hero::after {
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
        .hero .container { position: relative; z-index: 1; }

        .hero .floating-calc {
            font-size: 60px;
            animation: float 6s ease-in-out infinite;
            display: inline-block;
            margin-bottom: 10px;
            filter: drop-shadow(0 10px 30px rgba(255,255,255,0.2));
        }

        .hero h1 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 10px;
            animation: fadeInUp 0.8s ease;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        .hero h1 .highlight {
            background: linear-gradient(135deg, #FFD93D 0%, #FF6B6B 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero p {
            font-size: 16px;
            opacity: 0.95;
            max-width: 550px;
            margin: 0 auto 20px;
            line-height: 1.7;
            animation: fadeInUp 0.8s ease 0.2s both;
        }

        .hero .hero-buttons {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            animation: fadeInUp 0.8s ease 0.4s both;
        }
        .hero .hero-buttons a {
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
            min-height: 48px;
            -webkit-tap-highlight-color: transparent;
        }
        .hero .hero-buttons .btn-primary {
            background: white;
            color: var(--primary);
            box-shadow: 0 8px 25px rgba(0,0,0,0.12);
        }
        .hero .hero-buttons .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        .hero .hero-buttons .btn-primary:active { transform: scale(0.97); }
        .hero .hero-buttons .btn-secondary {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(10px);
        }
        .hero .hero-buttons .btn-secondary:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-3px) scale(1.02);
            border-color: white;
        }
        .hero .hero-buttons .btn-secondary:active { transform: scale(0.97); }

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
           SECTION
        ============================================ */
        .section {
            padding: 60px 0;
            position: relative;
            z-index: 1;
        }

        .section-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--dark);
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            opacity: 0;
            transform: translateY(30px);
            animation: fadeInUp 0.6s ease forwards;
        }
        body.dark-mode .section-title { color: #ffffff; }

        .section-title i {
            color: var(--primary);
            font-size: 32px;
        }

        .section-title .gradient-text {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            color: var(--gray);
            margin-bottom: 40px;
            font-size: 16px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s ease 0.2s forwards;
        }
        body.dark-mode .section-subtitle { color: #8899aa; }

        /* ============================================
           PRODUCTS GRID
        ============================================ */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }

        /* ============================================
           PRODUCT CARD - ENHANCED
        ============================================ */
        .product-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(30px) scale(0.95);
            animation: cardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            cursor: pointer;
            position: relative;
            border: 1px solid rgba(0,0,0,0.03);
            -webkit-tap-highlight-color: transparent;
            display: flex;
            flex-direction: column;
        }

        body.dark-mode .product-card {
            background: #2D2D44;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .product-card:nth-child(1) { animation-delay: 0.05s; }
        .product-card:nth-child(2) { animation-delay: 0.1s; }
        .product-card:nth-child(3) { animation-delay: 0.15s; }
        .product-card:nth-child(4) { animation-delay: 0.2s; }
        .product-card:nth-child(5) { animation-delay: 0.25s; }
        .product-card:nth-child(6) { animation-delay: 0.3s; }
        .product-card:nth-child(7) { animation-delay: 0.35s; }
        .product-card:nth-child(8) { animation-delay: 0.4s; }

        @keyframes cardAppear {
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .product-card:hover {
            transform: translateY(-8px) scale(1.01);
            box-shadow: var(--shadow-hover);
        }
        body.dark-mode .product-card:hover { background: #3D3D5C; }
        .product-card:active { transform: scale(0.98); }

        /* Product Image */
        .product-card .product-image-wrapper {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #F8F9FC 0%, #EEF1F8 100%);
            height: 220px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            transition: var(--transition);
            flex-shrink: 0;
        }

        body.dark-mode .product-card .product-image-wrapper {
            background: linear-gradient(135deg, #1A1A2E 0%, #252542 100%);
        }

        .product-card .product-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: var(--transition);
            filter: drop-shadow(0 8px 20px rgba(0,0,0,0.08));
        }

        .product-card:hover .product-image {
            transform: scale(1.1) rotate(-2deg);
        }

        .product-card .product-image-wrapper .zoom-icon {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: var(--gradient);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            opacity: 0;
            transition: var(--transition);
            z-index: 5;
            transform: scale(0.5);
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.4);
        }

        .product-card:hover .product-image-wrapper .zoom-icon {
            opacity: 1;
            transform: scale(1);
        }

        .product-card .product-image-wrapper .badge {
            position: absolute;
            top: 12px;
            left: 12px;
            padding: 5px 14px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 700;
            z-index: 2;
            animation: pulse 2s infinite;
            display: flex;
            align-items: center;
            gap: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .badge-new { background: var(--secondary); color: white; }
        .badge-sale { background: var(--warning); color: var(--dark); }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Product Body */
        .product-card .product-body {
            padding: 18px 18px 18px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-card .product-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 6px;
            transition: var(--transition);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 42px;
        }
        body.dark-mode .product-card .product-name { color: #ffffff; }
        .product-card:hover .product-name { color: var(--primary); }

        .product-card .product-price {
            font-size: 22px;
            font-weight: 900;
            color: var(--success);
            margin-bottom: 8px;
            transition: var(--transition);
            letter-spacing: -0.8px;
        }
        .product-card:hover .product-price {
            transform: scale(1.03);
        }
        .product-card .product-price span {
            font-size: 12px;
            font-weight: 400;
            color: var(--gray);
        }

        .product-card .product-desc {
            color: var(--gray);
            font-size: 12px;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
            flex: 1;
        }

        .product-card .stock-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 12px;
            transition: var(--transition);
            width: fit-content;
        }
        .stock-badge.in-stock {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }
        body.dark-mode .stock-badge.in-stock {
            background: rgba(0, 201, 167, 0.2);
            color: #00C9A7;
        }
        .stock-badge.out-of-stock {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }
        body.dark-mode .stock-badge.out-of-stock {
            background: rgba(255, 107, 107, 0.2);
            color: #FF6B6B;
        }

        /* Product Actions */
        .product-card .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }
        .product-card .product-actions a {
            flex: 1;
            padding: 10px 14px;
            text-align: center;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            position: relative;
            overflow: hidden;
            min-height: 42px;
            -webkit-tap-highlight-color: transparent;
            cursor: pointer;
        }

        .product-card .product-actions .btn-order {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.25);
        }
        .product-card .product-actions .btn-order:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.4);
        }
        .product-card .product-actions .btn-order:active { transform: scale(0.97); }

        .product-card .product-actions .btn-view {
            background: rgba(108, 99, 255, 0.08);
            color: var(--primary);
            border: 2px solid rgba(108, 99, 255, 0.15);
        }
        .product-card .product-actions .btn-view:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateY(-2px) scale(1.02);
        }
        .product-card .product-actions .btn-view:active { transform: scale(0.97); }

        .product-card .product-actions a i {
            font-size: 13px;
        }

        /* ============================================
           LOCATION SECTION
        ============================================ */
        .location-section {
            padding: 60px 0;
            background: var(--white);
            position: relative;
            z-index: 1;
        }
        body.dark-mode .location-section {
            background: #1A1A2E;
        }

        .location-section .section-title {
            margin-bottom: 8px;
        }

        .location-section .location-container {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 30px;
            margin-top: 30px;
        }

        .location-info {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .location-info-card {
            background: var(--light-bg);
            border-radius: var(--radius);
            padding: 24px;
            border: 2px solid transparent;
            transition: var(--transition);
            display: flex;
            align-items: flex-start;
            gap: 16px;
        }
        body.dark-mode .location-info-card {
            background: #2D2D44;
        }
        .location-info-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
        }

        .location-info-card .icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.25);
        }

        .location-info-card .info-content {
            flex: 1;
        }

        .location-info-card .info-content h4 {
            font-size: 14px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        body.dark-mode .location-info-card .info-content h4 { color: white; }

        .location-info-card .info-content p {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }

        .location-info-card .info-content a {
            color: var(--primary);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }
        .location-info-card .info-content a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .location-map-wrapper {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 3px solid var(--white);
            position: relative;
            min-height: 400px;
            background: var(--light-bg);
        }
        body.dark-mode .location-map-wrapper {
            border-color: #2D2D44;
        }

        .location-map-wrapper iframe {
            width: 100%;
            height: 100%;
            min-height: 400px;
            border: none;
            display: block;
        }

        .location-map-wrapper .map-overlay {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            pointer-events: none;
        }
        body.dark-mode .location-map-wrapper .map-overlay {
            background: rgba(45,45,68,0.95);
        }

        .location-map-wrapper .map-overlay .overlay-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .location-map-wrapper .map-overlay .overlay-text h5 {
            font-size: 13px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 2px;
        }
        body.dark-mode .location-map-wrapper .map-overlay .overlay-text h5 { color: white; }

        .location-map-wrapper .map-overlay .overlay-text p {
            font-size: 12px;
            color: var(--gray);
            margin: 0;
        }

        /* ============================================
           VIDEOS GRID
        ============================================ */
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
        }

        .video-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            cursor: pointer;
            opacity: 0;
            transform: translateY(30px);
            animation: cardAppear 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
            border: 1px solid rgba(0,0,0,0.03);
            -webkit-tap-highlight-color: transparent;
        }
        body.dark-mode .video-card {
            background: #2D2D44;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .video-card:nth-child(1) { animation-delay: 0.05s; }
        .video-card:nth-child(2) { animation-delay: 0.1s; }
        .video-card:nth-child(3) { animation-delay: 0.15s; }

        .video-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }
        body.dark-mode .video-card:hover { background: #3D3D5C; }
        .video-card:active { transform: scale(0.98); }

        .video-card .video-thumb {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            background: #1A1A2E;
            overflow: hidden;
        }
        .video-card .video-thumb img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        .video-card:hover .video-thumb img { transform: scale(1.08); }

        .video-card .video-thumb .play-icon {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 64px;
            height: 64px;
            background: rgba(108, 99, 255, 0.95);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            transition: var(--transition);
            box-shadow: 0 0 0 12px rgba(108, 99, 255, 0.15);
        }
        .video-card:hover .video-thumb .play-icon {
            transform: translate(-50%, -50%) scale(1.15);
            background: var(--primary);
            box-shadow: 0 0 0 20px rgba(108, 99, 255, 0.25);
        }

        .video-card .video-thumb .video-duration {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.75);
            color: white;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
            backdrop-filter: blur(4px);
        }

        .video-card .video-body {
            padding: 16px 18px;
        }
        .video-card .video-body h4 {
            font-size: 15px;
            color: var(--dark);
            margin-bottom: 4px;
            line-height: 1.4;
            font-weight: 700;
        }
        body.dark-mode .video-card .video-body h4 { color: #ffffff; }

        .video-card .video-body p {
            color: var(--gray);
            font-size: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
            margin-top: 3px;
        }

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
            border-left: 4px solid var(--success);
        }
        .toast.show {
            transform: translateY(0) scale(1);
            opacity: 1;
        }
        .toast .toast-icon {
            font-size: 20px;
            color: var(--success);
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
           MODAL 3D - ZOOM ONLY (No Rotate)
        ============================================ */
        .modal-3d {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.92);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.3s ease;
            padding: 15px;
        }
        .modal-3d.show { display: flex; }

        .modal-3d .modal-3d-content {
            position: relative;
            max-width: 700px;
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            background: linear-gradient(135deg, #1A1A2E 0%, #252542 100%);
            animation: modalSlide 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            max-height: 95vh;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,255,255,0.08);
        }

        @keyframes modalSlide {
            from { transform: scale(0.8) translateY(50px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .modal-3d .modal-3d-header {
            padding: 16px 20px;
            background: rgba(255,255,255,0.03);
            border-bottom: 1px solid rgba(255,255,255,0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .modal-3d .modal-3d-header h3 {
            color: white;
            font-size: 15px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .modal-3d .close-3d {
            color: white;
            font-size: 18px;
            cursor: pointer;
            background: rgba(255,255,255,0.08);
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            border: none;
            -webkit-tap-highlight-color: transparent;
        }
        .modal-3d .close-3d:hover {
            background: var(--danger);
            transform: rotate(90deg);
        }
        .modal-3d .close-3d:active { transform: rotate(90deg) scale(0.9); }

        .modal-3d .modal-3d-body {
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 320px;
            position: relative;
            flex: 1;
            overflow: hidden;
            background: radial-gradient(circle at center, rgba(108,99,255,0.08) 0%, transparent 70%);
        }

        .modal-3d .modal-3d-body .image-container {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-bottom: 60px;
        }

        .modal-3d .modal-3d-body .image-container img {
            max-width: 100%;
            max-height: 50vh;
            object-fit: contain;
            border-radius: 12px;
            transition: transform 0.3s ease;
            -webkit-user-select: none;
            user-select: none;
            filter: drop-shadow(0 20px 40px rgba(0,0,0,0.4));
            cursor: zoom-in;
        }
        .modal-3d .modal-3d-body .image-container img:active {
            cursor: zoom-out;
        }

        .modal-3d .controls-3d {
            position: absolute;
            bottom: 16px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            background: rgba(0,0,0,0.6);
            padding: 8px 16px;
            border-radius: 50px;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            flex-wrap: wrap;
            justify-content: center;
            z-index: 5;
        }
        .modal-3d .controls-3d button {
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            font-size: 14px;
            cursor: pointer;
            transition: var(--transition);
            -webkit-tap-highlight-color: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-3d .controls-3d button:hover {
            background: var(--primary);
            transform: scale(1.1);
        }
        .modal-3d .controls-3d button:active { transform: scale(0.95); }
        .modal-3d .controls-3d .zoom-level {
            color: white;
            font-size: 12px;
            display: flex;
            align-items: center;
            padding: 0 8px;
            font-weight: 700;
            min-width: 50px;
            justify-content: center;
        }
        .modal-3d .controls-3d .reset-btn {
            background: var(--primary);
            color: white;
        }
        .modal-3d .controls-3d .reset-btn:hover { background: var(--primary-dark); }

        .modal-3d .modal-3d-footer {
            padding: 16px 20px;
            background: rgba(255,255,255,0.03);
            border-top: 1px solid rgba(255,255,255,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .modal-3d .modal-3d-footer .product-info-3d {
            flex: 1;
            min-width: 150px;
        }
        .modal-3d .modal-3d-footer .product-info-3d .name {
            color: white;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 2px;
        }
        .modal-3d .modal-3d-footer .product-info-3d .price {
            color: var(--success);
            font-weight: 800;
            font-size: 18px;
            letter-spacing: -0.5px;
        }
        .modal-3d .modal-3d-footer .action-buttons-3d {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .modal-3d .modal-3d-footer .action-buttons-3d a {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
            -webkit-tap-highlight-color: transparent;
        }
        .modal-3d .modal-3d-footer .action-buttons-3d .btn-order-3d {
            background: var(--gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }
        .modal-3d .modal-3d-footer .action-buttons-3d .btn-order-3d:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.4);
        }
        .modal-3d .modal-3d-footer .action-buttons-3d .btn-order-3d:active {
            transform: scale(0.97);
        }
        .modal-3d .modal-3d-footer .action-buttons-3d .btn-whatsapp-3d {
            background: #25D366;
            color: white;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }
        .modal-3d .modal-3d-footer .action-buttons-3d .btn-whatsapp-3d:hover {
            transform: translateY(-2px);
            background: #1da851;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }
        .modal-3d .modal-3d-footer .action-buttons-3d .btn-whatsapp-3d:active {
            transform: scale(0.97);
        }

        /* ============================================
           VIDEO MODAL - FIX
        ============================================ */
        .modal-video {
            display: none;
            position: fixed;
            z-index: 10000;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            align-items: center;
            justify-content: center;
            padding: 15px;
            animation: fadeIn 0.3s ease;
        }

        .modal-video.show {
            display: flex;
        }

        .modal-video-content {
            position: relative;
            max-width: 900px;
            width: 100%;
            background: #000;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.6);
            animation: modalSlide 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .modal-video-content video {
            width: 100%;
            height: auto;
            max-height: 80vh;
            display: block;
            background: #000;
            border-radius: 16px;
        }

        .modal-close-video {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(255, 107, 107, 0.9);
            color: white;
            border: none;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            -webkit-tap-highlight-color: transparent;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .modal-close-video:hover {
            background: #FF6B6B;
            transform: rotate(90deg) scale(1.1);
        }

        .modal-close-video:active {
            transform: rotate(90deg) scale(0.95);
        }

        /* ============================================
           EMPTY STATE
        ============================================ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        .empty-state .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            animation: float 4s ease-in-out infinite;
            color: var(--primary);
        }
        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 20px;
        }
        body.dark-mode .empty-state h3 { color: white; }
        .empty-state p { font-size: 14px; }

        /* ============================================
           ANIMATIONS
        ============================================ */
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }

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
        @media (min-width: 1025px) {
            .products-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (min-width: 601px) and (max-width: 1024px) {
            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 18px;
            }
            .product-card .product-image-wrapper { height: 200px; }
            .product-card .product-body { padding: 16px; }
            .product-card .product-name { font-size: 15px; }
            .product-card .product-price { font-size: 20px; }
            .product-card .product-desc { font-size: 12px; }
            .product-card .product-actions a { font-size: 12px; padding: 10px 12px; }
            .footer .container { grid-template-columns: 1fr 1fr; }

            .location-section .location-container {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            .location-map-wrapper { min-height: 350px; }
        }

        /* ============================================
           RESPONSIVE - MOBILE
        ============================================ */
        @media (max-width: 600px) {
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

            .hero { padding: 45px 15px; }
            .hero h1 { font-size: 26px; letter-spacing: -0.3px; }
            .hero .floating-calc { font-size: 50px; }
            .hero p { font-size: 14px; line-height: 1.6; }
            .hero .hero-buttons { gap: 10px; }
            .hero .hero-buttons a {
                padding: 12px 24px;
                font-size: 14px;
                flex: 1;
                min-width: 140px;
                justify-content: center;
            }

            .section { padding: 40px 0; }
            .section-title { font-size: 24px; gap: 8px; }
            .section-title i { font-size: 24px; }
            .section-subtitle { font-size: 14px; margin-bottom: 28px; }

            .products-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            .product-card .product-image-wrapper { height: 160px; padding: 12px; }
            .product-card .product-body { padding: 12px 12px 14px; }
            .product-card .product-name { font-size: 13px; min-height: 36px; }
            .product-card .product-price { font-size: 17px; }
            .product-card .product-desc { font-size: 11px; margin-bottom: 8px; }
            .product-card .stock-badge { font-size: 9px; padding: 3px 8px; }
            .product-card .product-actions { gap: 6px; }
            .product-card .product-actions a {
                font-size: 11px;
                padding: 8px 6px;
                min-height: 40px;
                gap: 4px;
            }
            .product-card .product-actions a i { font-size: 12px; }

            .videos-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            .video-card .video-body { padding: 14px 16px; }
            .video-card .video-body h4 { font-size: 14px; }
            .video-card .video-body p { font-size: 12px; }
            .video-card .video-thumb .play-icon { width: 56px; height: 56px; font-size: 22px; }

            .location-section { padding: 40px 0; }
            .location-section .location-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            .location-info-card {
                padding: 18px;
                gap: 12px;
            }
            .location-info-card .icon-wrapper {
                width: 42px;
                height: 42px;
                font-size: 18px;
            }
            .location-info-card .info-content h4 { font-size: 13px; }
            .location-info-card .info-content p { font-size: 13px; }
            .location-map-wrapper { min-height: 300px; }
            .location-map-wrapper iframe { min-height: 300px; }
            .location-map-wrapper .map-overlay {
                bottom: 12px;
                left: 12px;
                right: 12px;
                padding: 10px 14px;
            }
            .location-map-wrapper .map-overlay .overlay-icon {
                width: 34px;
                height: 34px;
                font-size: 16px;
            }
            .location-map-wrapper .map-overlay .overlay-text h5 { font-size: 12px; }
            .location-map-wrapper .map-overlay .overlay-text p { font-size: 11px; }

            /* Modal 3D */
            .modal-3d .modal-3d-body { min-height: 250px; padding: 12px; }
            .modal-3d .modal-3d-body .image-container img { max-height: 40vh; }
            .modal-3d .controls-3d {
                bottom: 10px;
                padding: 6px 12px;
                gap: 6px;
            }
            .modal-3d .controls-3d button { width: 36px; height: 36px; font-size: 13px; }
            .modal-3d .controls-3d .zoom-level { font-size: 11px; min-width: 44px; }
            .modal-3d .modal-3d-footer {
                flex-direction: column;
                text-align: center;
                padding: 14px 16px;
                gap: 12px;
            }
            .modal-3d .modal-3d-footer .product-info-3d { width: 100%; }
            .modal-3d .modal-3d-footer .action-buttons-3d { width: 100%; }
            .modal-3d .modal-3d-footer .action-buttons-3d a {
                flex: 1;
                justify-content: center;
                padding: 11px;
                font-size: 12px;
            }

            /* Video modal */
            .modal-video-content video {
                max-height: 60vh;
            }
            .modal-close-video {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }

            .footer { padding: 30px 15px 20px; padding-bottom: calc(20px + var(--safe-area-bottom)); }
            .footer .container {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 24px;
            }
            .footer .social-links { justify-content: center; }
            .footer h4 { justify-content: center; }
            .footer a { justify-content: center; }

            .toast {
                bottom: 80px;
                right: 12px;
                left: 12px;
                max-width: calc(100% - 24px);
                padding: 12px 16px;
                border-radius: 10px;
            }

            .back-to-top {
                bottom: 16px;
                left: 16px;
                width: 44px;
                height: 44px;
                font-size: 16px;
            }

            .math-formula { font-size: 12px !important; }
            .math-bg { opacity: 0.5; }

            .modal-3d .close-3d {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }
        }

        /* ============================================
           RESPONSIVE - SMALL MOBILE
        ============================================ */
        @media (max-width: 400px) {
            .header .logo h1 { font-size: 15px; }
            .header .logo img { max-height: 24px; }
            .header .nav a { font-size: 13px; padding: 10px 0; }

            .hero { padding: 35px 15px; }
            .hero h1 { font-size: 22px; }
            .hero .floating-calc { font-size: 42px; }
            .hero p { font-size: 13px; }
            .hero .hero-buttons a {
                padding: 10px 18px;
                font-size: 13px;
                min-width: 120px;
            }

            .section-title { font-size: 20px; gap: 6px; }
            .section-title i { font-size: 20px; }
            .section-subtitle { font-size: 12px; }

            .products-grid { gap: 10px; }
            .product-card .product-image-wrapper { height: 140px; }
            .product-card .product-body { padding: 10px 10px 12px; }
            .product-card .product-name { font-size: 12px; min-height: 32px; }
            .product-card .product-price { font-size: 15px; }
            .product-card .product-price span { font-size: 10px; }
            .product-card .product-desc {
                font-size: 10px;
                margin-bottom: 6px;
            }
            .product-card .stock-badge { font-size: 8px; padding: 2px 6px; }
            .product-card .product-actions a {
                font-size: 10px;
                padding: 7px 4px;
                min-height: 38px;
            }

            .footer h4 { font-size: 14px; }
            .footer a { font-size: 12px; }
            .footer .social-links a { width: 40px; height: 40px; font-size: 16px; }

            .modal-3d .modal-3d-body .image-container img { max-height: 35vh; }
            .modal-3d .controls-3d button { width: 32px; height: 32px; font-size: 12px; }
        }

        /* ============================================
           LANDSCAPE MODE
        ============================================ */
        @media (max-width: 900px) and (orientation: landscape) {
            .hero { padding: 30px 15px; }
            .hero h1 { font-size: 22px; }
            .hero .floating-calc { font-size: 40px; }
            .modal-3d .modal-3d-body .image-container img { max-height: 50vh; }
            .modal-video-content video { max-height: 75vh; }
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
        <div class="toast-icon"><i class="fas fa-shopping-cart"></i></div>
        <div class="toast-content">
            <h4>Order Started!</h4>
            <p id="toastMessage">Product added successfully</p>
        </div>
    </div>

    <!-- ===== BACK TO TOP ===== -->
    <button class="back-to-top" id="backToTop" onclick="scrollToTop()" aria-label="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- ===== 3D MODAL (ZOOM ONLY) ===== -->
    <div class="modal-3d" id="modal3D">
        <div class="modal-3d-content">
            <div class="modal-3d-header">
                <h3 id="modal3DTitle">
                    <i class="fas fa-search"></i> Product View
                </h3>
                <button class="close-3d" onclick="close3DViewer()" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-3d-body">
                <div class="image-container">
                    <img id="modal3DImage" src="" alt="Product">
                </div>
                <div class="controls-3d">
                    <button onclick="zoom3D('out')" aria-label="Zoom out"><i class="fas fa-search-minus"></i></button>
                    <button onclick="zoom3D('in')" aria-label="Zoom in"><i class="fas fa-search-plus"></i></button>
                    <button class="reset-btn" onclick="reset3D()" aria-label="Reset"><i class="fas fa-sync-alt"></i></button>
                    <span class="zoom-level" id="zoomLevel">100%</span>
                </div>
            </div>
            <div class="modal-3d-footer">
                <div class="product-info-3d">
                    <div class="name" id="modal3DProductName">Product Name</div>
                    <div class="price" id="modal3DProductPrice">TSh 0/=</div>
                </div>
                <div class="action-buttons-3d">
                    <a href="order.php?product_id=" id="modal3DOrderBtn" class="btn-order-3d">
                        <i class="fas fa-shopping-cart"></i> Order Now
                    </a>
                    <a href="#" id="modal3DWhatsAppBtn" target="_blank" class="btn-whatsapp-3d">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>

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
                <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
                <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                <a href="contact.php"><i class="fas fa-envelope"></i> Contact</a>
                <?php if (!empty($settings['whatsapp_number'])): ?>
                    <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank" class="whatsapp-btn">
                        <i class="fab fa-whatsapp"></i> WhatsApp
                    </a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- ===== HERO ===== -->
    <section class="hero">
        <div class="container">
            <div class="floating-calc">🧮</div>
            <h1>
                <?php echo htmlspecialchars($settings['hero_text'] ?: 'Premium Scientific'); ?>
                <br><span class="highlight">Calculators</span>
            </h1>
            <p>Quality calculators for students, professionals, and enthusiasts. Order now with fast delivery across Tanzania!</p>
            <div class="hero-buttons">
                <a href="#products" class="btn-primary"><i class="fas fa-shopping-bag"></i> Shop Now</a>
                <?php if (!empty($settings['whatsapp_number'])): ?>
                    <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank" class="btn-secondary">
                        <i class="fab fa-whatsapp"></i> Contact Us
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===== PRODUCTS ===== -->
    <section class="section" id="products">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-box"></i> Our <span class="gradient-text">Products</span>
            </h2>
            <p class="section-subtitle">Tap on any product to view in detail</p>

            <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card" onclick="open3DViewer(
                    '<?php echo $product['id']; ?>',
                    '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>',
                    '<?php echo $product['image']; ?>',
                    '<?php echo number_format($product['price']); ?>',
                    '<?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?>'
                )">
                    <div class="product-image-wrapper">
                        <?php 
                        $imagePath = 'uploads/products/' . $product['image'];
                        if (!file_exists($imagePath) || empty($product['image'])) {
                            $imagePath = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22280%22 height=%22230%22%3E%3Crect width=%22280%22 height=%22230%22 fill=%22%23f8f9fc%22/%3E%3Ctext x=%22100%22 y=%22130%22 font-size=%2240%22%3E🧮%3C/text%3E%3C/svg%3E';
                        }
                        ?>
                        <img src="<?php echo $imagePath; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy">
                        <div class="zoom-icon"><i class="fas fa-search-plus"></i></div>
                        <?php if ($product['stock'] > 10): ?>
                            <span class="badge badge-new"><i class="fas fa-fire"></i> In Stock</span>
                        <?php elseif ($product['stock'] > 0 && $product['stock'] <= 5): ?>
                            <span class="badge badge-sale"><i class="fas fa-bolt"></i> Limited</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-body">
                        <span class="stock-badge <?php echo $product['stock'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php if ($product['stock'] > 0): ?>
                                <i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock']; ?>)
                            <?php else: ?>
                                <i class="fas fa-times-circle"></i> Out of Stock
                            <?php endif; ?>
                        </span>
                        <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-price">TSh <?php echo number_format($product['price']); ?> <span>/=</span></div>
                        <p class="product-desc"><?php echo htmlspecialchars(substr($product['description'], 0, 70)); ?>...</p>
                        <div class="product-actions">
                            <a href="#" class="btn-view" onclick="event.stopPropagation(); open3DViewer(
                                '<?php echo $product['id']; ?>',
                                '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>',
                                '<?php echo $product['image']; ?>',
                                '<?php echo number_format($product['price']); ?>',
                                '<?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?>'
                            )"><i class="fas fa-eye"></i> View</a>
                            <a href="order.php?product_id=<?php echo $product['id']; ?>" class="btn-order"
                               onclick="event.stopPropagation(); showToast('<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')">
                                <i class="fas fa-shopping-cart"></i> Order
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-box-open"></i></div>
                <h3>No products available</h3>
                <p>Check back later for new calculators!</p>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===== LOCATION SECTION ===== -->
    <?php if (!empty($settings['location_name']) || !empty($settings['location_address'])): ?>
    <section class="location-section" id="location">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-map-marker-alt"></i> Our <span class="gradient-text">Location</span>
            </h2>
            <p class="section-subtitle">Visit our physical store or find us on the map</p>

            <div class="location-container">
                <div class="location-info">
                    <?php if (!empty($settings['location_name'])): ?>
                    <div class="location-info-card">
                        <div class="icon-wrapper"><i class="fas fa-store"></i></div>
                        <div class="info-content">
                            <h4>Store Name</h4>
                            <p><?php echo htmlspecialchars($settings['location_name']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($settings['location_address'])): ?>
                    <div class="location-info-card">
                        <div class="icon-wrapper"><i class="fas fa-map-pin"></i></div>
                        <div class="info-content">
                            <h4>Address</h4>
                            <p><?php echo htmlspecialchars($settings['location_address']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($settings['working_hours'])): ?>
                    <div class="location-info-card">
                        <div class="icon-wrapper"><i class="fas fa-clock"></i></div>
                        <div class="info-content">
                            <h4>Working Hours</h4>
                            <p><?php echo htmlspecialchars($settings['working_hours']); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($settings['phone_number']) || !empty($settings['whatsapp_number'])): ?>
                    <div class="location-info-card">
                        <div class="icon-wrapper"><i class="fas fa-phone"></i></div>
                        <div class="info-content">
                            <h4>Contact</h4>
                            <?php if (!empty($settings['phone_number'])): ?>
                                <p><a href="tel:<?php echo $settings['phone_number']; ?>"><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($settings['phone_number']); ?></a></p>
                            <?php endif; ?>
                            <?php if (!empty($settings['whatsapp_number'])): ?>
                                <p><a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank"><i class="fab fa-whatsapp"></i> <?php echo htmlspecialchars($settings['whatsapp_number']); ?></a></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="location-map-wrapper">
                    <?php 
                    $lat = $settings['location_lat'] ?? '-6.792354';
                    $lng = $settings['location_lng'] ?? '39.208328';
                    $mapUrl = "https://maps.google.com/maps?q={$lat},{$lng}&t=&z=15&ie=UTF8&iwloc=&output=embed";
                    ?>
                    <iframe 
                        src="<?php echo htmlspecialchars($mapUrl); ?>" 
                        width="100%" 
                        height="100%" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                    <div class="map-overlay">
                        <div class="overlay-icon"><i class="fas fa-map-marked-alt"></i></div>
                        <div class="overlay-text">
                            <h5><?php echo htmlspecialchars($settings['location_name'] ?? 'SCI-CALC Store'); ?></h5>
                            <p><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($settings['location'] ?? 'Dar es Salaam, Tanzania'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- ===== VIDEOS ===== -->
    <?php if (count($videos) > 0): ?>
    <section class="section">
        <div class="container">
            <h2 class="section-title">
                <i class="fas fa-video"></i> <span class="gradient-text">Video Tutorials</span>
            </h2>
            <p class="section-subtitle">Learn mathematics with our expert video guides</p>

            <div class="videos-grid">
                <?php foreach ($videos as $video): ?>
                <div class="video-card" onclick="playVideo('<?php echo htmlspecialchars($video['video_url'] ?? $video['video_file'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($video['title'], ENT_QUOTES); ?>')">
                    <div class="video-thumb">
                        <?php 
                        $thumbPath = 'uploads/videos/' . $video['thumbnail'];
                        if (!file_exists($thumbPath) || empty($video['thumbnail'])) {
                            $thumbPath = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22320%22%3E%3Crect width=%22320%22 height=%22320%22 fill=%22%231a1a2e%22/%3E%3Ctext x=%22130%22 y=%22180%22 font-size=%2260%22%3E🎬%3C/text%3E%3C/svg%3E';
                        }
                        ?>
                        <img src="<?php echo $thumbPath; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>" loading="lazy">
                        <div class="play-icon"><i class="fas fa-play"></i></div>
                        <span class="video-duration"><i class="fas fa-play-circle"></i> Watch</span>
                    </div>
                    <div class="video-body">
                        <h4><?php echo htmlspecialchars($video['title']); ?></h4>
                        <p><?php echo htmlspecialchars(substr($video['description'], 0, 60)); ?>...</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

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
                    <a href="<?php echo !empty($settings['instagram_link']) ? htmlspecialchars($settings['instagram_link']) : '#'; ?>" target="_blank" class="instagram" title="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="<?php echo !empty($settings['facebook_link']) ? htmlspecialchars($settings['facebook_link']) : '#'; ?>" target="_blank" class="facebook" title="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="<?php echo !empty($settings['twitter_link']) ? htmlspecialchars($settings['twitter_link']) : '#'; ?>" target="_blank" class="twitter" title="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="<?php echo !empty($settings['linkedin_link']) ? htmlspecialchars($settings['linkedin_link']) : '#'; ?>" target="_blank" class="linkedin" title="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="<?php echo !empty($settings['whatsapp_group']) ? htmlspecialchars($settings['whatsapp_group']) : '#'; ?>" target="_blank" class="whatsapp-social" title="WhatsApp Group">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            <div class="copyright">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($settings['site_name']); ?>. Made with <i class="fas fa-heart"></i>
            </div>
        </div>
    </footer>

    <!-- ===== VIDEO MODAL ===== -->
    <div class="modal-video" id="videoModal">
        <div class="modal-video-content">
            <button class="modal-close-video" onclick="closeVideo()" aria-label="Close">
                <i class="fas fa-times"></i>
            </button>
            <video id="modalVideo" controls autoplay playsinline preload="metadata">
                <source src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>

    <!-- ===== JAVASCRIPT ===== -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            'use strict';

            // ========================================
            // 1. 3D VIEWER - ZOOM ONLY (No Rotate, No Drag)
            // ========================================
            let scale = 1;

            window.open3DViewer = function(id, name, image, price, desc) {
                const modal = document.getElementById('modal3D');
                document.getElementById('modal3DImage').src = 'uploads/products/' + image;
                document.getElementById('modal3DTitle').innerHTML = '<i class="fas fa-search"></i> ' + name;
                document.getElementById('modal3DProductName').textContent = name;
                document.getElementById('modal3DProductPrice').textContent = 'TSh ' + price + '/=';
                document.getElementById('modal3DOrderBtn').href = 'order.php?product_id=' + id;
                document.getElementById('modal3DWhatsAppBtn').href = 'https://wa.me/<?php echo $settings['whatsapp_number']; ?>?text=' + encodeURIComponent('Nataka kununua ' + name + ' - TSh ' + price + '/=');
                scale = 1;
                updateTransform3D();
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                document.getElementById('zoomLevel').textContent = '100%';
            };

            window.close3DViewer = function() {
                document.getElementById('modal3D').classList.remove('show');
                document.body.style.overflow = '';
            };

            function updateTransform3D() {
                const img = document.getElementById('modal3DImage');
                img.style.transform = `scale(${scale})`;
            }

            window.zoom3D = function(dir) {
                scale = dir === 'in' ? Math.min(scale + 0.2, 4) : Math.max(scale - 0.2, 0.5);
                updateTransform3D();
                document.getElementById('zoomLevel').textContent = Math.round(scale * 100) + '%';
            };

            window.reset3D = function() {
                scale = 1;
                updateTransform3D();
                document.getElementById('zoomLevel').textContent = '100%';
            };

            // Keyboard shortcuts for 3D viewer
            document.addEventListener('keydown', function(e) {
                if (!document.getElementById('modal3D').classList.contains('show')) return;
                if (e.key === '+' || e.key === '=') zoom3D('in');
                else if (e.key === '-') zoom3D('out');
                else if (e.key === 'r' || e.key === 'R') reset3D();
                else if (e.key === 'Escape') close3DViewer();
            });

            document.getElementById('modal3D').addEventListener('click', function(e) {
                if (e.target === this) close3DViewer();
            });

            // ========================================
            // 2. MOBILE MENU
            // ========================================
            window.toggleMenu = function() {
                const nav = document.getElementById('mainNav');
                const hamburger = document.querySelector('.hamburger');
                nav.classList.toggle('open');
                hamburger.innerHTML = nav.classList.contains('open')
                    ? '<i class="fas fa-times"></i>'
                    : '<i class="fas fa-bars"></i>';
            };
            document.querySelectorAll('#mainNav a').forEach(l => l.addEventListener('click', () => {
                document.getElementById('mainNav').classList.remove('open');
                document.querySelector('.hamburger').innerHTML = '<i class="fas fa-bars"></i>';
            }));

            // ========================================
            // 3. VIDEO PLAYER - FIXED
            // ========================================
            window.playVideo = function(file, title) {
                const modal = document.getElementById('videoModal');
                const video = document.getElementById('modalVideo');
                
                if (!file || file === '') {
                    alert('Video haipatikani kwa sasa.');
                    return;
                }
                
                // Determine correct path
                let videoSrc;
                if (file.startsWith('http://') || file.startsWith('https://')) {
                    videoSrc = file;
                } else if (file.startsWith('uploads/')) {
                    videoSrc = file;
                } else {
                    videoSrc = 'uploads/videos/' + file;
                }
                
                video.src = videoSrc;
                video.load();
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                
                setTimeout(() => {
                    video.play().catch(err => console.log('Autoplay blocked:', err));
                }, 150);
            };

            window.closeVideo = function() {
                const modal = document.getElementById('videoModal');
                const video = document.getElementById('modalVideo');
                video.pause();
                video.currentTime = 0;
                video.removeAttribute('src');
                video.load();
                modal.classList.remove('show');
                document.body.style.overflow = '';
            };

            document.getElementById('videoModal').addEventListener('click', function(e) {
                if (e.target === this) closeVideo();
            });
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') { closeVideo(); close3DViewer(); }
            });

            // ========================================
            // 4. BACK TO TOP
            // ========================================
            window.addEventListener('scroll', function() {
                document.getElementById('backToTop').classList.toggle('show', window.scrollY > 400);
            }, { passive: true });

            window.scrollToTop = function() {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            };

            // ========================================
            // 5. TOAST
            // ========================================
            let toastTimeout;
            window.showToast = function(name) {
                const toast = document.getElementById('toast');
                document.getElementById('toastMessage').textContent = 'Ordering: ' + name;
                toast.classList.add('show');
                clearTimeout(toastTimeout);
                toastTimeout = setTimeout(() => toast.classList.remove('show'), 3500);
            };

            // ========================================
            // 6. DARK MODE
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
            // 7. SMOOTH SCROLL
            // ========================================
            document.querySelectorAll('a[href^="#"]').forEach(a => {
                a.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const offset = 70;
                        window.scrollTo({
                            top: target.getBoundingClientRect().top + window.pageYOffset - offset,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // ========================================
            // 8. INTERSECTION OBSERVER
            // ========================================
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(e => {
                        if (e.isIntersecting) {
                            e.target.style.opacity = '1';
                            e.target.style.transform = 'translateY(0) scale(1)';
                        }
                    });
                }, { threshold: 0.1 });
                document.querySelectorAll('.product-card, .video-card').forEach(el => observer.observe(el));
            }

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
                    el.style.color = `hsla(${hue}, 70%, 60%, 0.10)`;
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
            // 11. CONSOLE WELCOME
            // ========================================
            console.log('%c🧮 SCI-CALC', 'font-size:30px;font-weight:bold;color:#6C63FF;');
            console.log('%c📞 WhatsApp: <?php echo $settings['whatsapp_number']; ?>', 'font-size:14px;color:#25D366;');
            console.log('%c📍 Location: <?php echo $settings['location_name'] ?? 'Not set'; ?>', 'font-size:14px;color:#6C63FF;');
            console.log('%c✅ Site ready!', 'font-size:14px;color:#00C9A7;');
        });
    </script>
</body>
</html>