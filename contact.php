<?php
require_once 'config/database.php';

// Get settings
$settings = getSettings($conn);

$success = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill all fields!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address!';
    } else {
        // In production, send email here
        $success = 'Thank you for your message! We will get back to you soon.';
        $name = $email = $subject = $message = '';
    }
}
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
    <title>Contact - <?php echo htmlspecialchars($settings['site_name']); ?></title>
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
           CONTACT SECTION
        ============================================ */
        .contact-section {
            padding: 60px 0;
            position: relative;
            z-index: 1;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        /* ============================================
           CONTACT INFO
        ============================================ */
        .contact-info h2 {
            font-size: 26px;
            color: var(--dark);
            margin-bottom: 12px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }
        .contact-info h2 i {
            color: var(--primary);
            font-size: 24px;
        }
        body.dark-mode .contact-info h2 { color: white; }

        .contact-info .intro-text {
            color: var(--gray);
            line-height: 1.8;
            margin-bottom: 25px;
            font-size: 15px;
        }

        .contact-info .info-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid rgba(0,0,0,0.04);
            transition: var(--transition);
        }
        body.dark-mode .contact-info .info-item {
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .contact-info .info-item:hover {
            transform: translateX(5px);
        }

        .contact-info .info-item .icon {
            font-size: 20px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
            border-radius: 12px;
            transition: var(--transition);
            flex-shrink: 0;
        }

        .contact-info .info-item:hover .icon {
            background: var(--primary);
            color: white;
            transform: scale(1.05) rotate(-5deg);
        }

        .contact-info .info-item .details {
            flex: 1;
        }

        .contact-info .info-item .details h4 {
            font-size: 14px;
            color: var(--gray);
            margin-bottom: 2px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        body.dark-mode .contact-info .info-item .details h4 { color: #8899AA; }

        .contact-info .info-item .details p {
            color: var(--dark);
            font-size: 15px;
            margin-bottom: 0;
            font-weight: 500;
        }
        body.dark-mode .contact-info .info-item .details p { color: white; }

        .contact-info .info-item .details a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
        }
        body.dark-mode .contact-info .info-item .details a { color: white; }
        .contact-info .info-item .details a:hover {
            color: var(--primary);
        }

        /* ============================================
           SOCIAL LINKS
        ============================================ */
        .contact-info .social-links {
            display: flex;
            gap: 12px;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .contact-info .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(0,0,0,0.04);
            font-size: 20px;
            text-decoration: none;
            transition: var(--transition);
            color: var(--gray);
            border: 2px solid rgba(0,0,0,0.04);
        }
        body.dark-mode .contact-info .social-links a {
            background: rgba(255,255,255,0.06);
            border-color: rgba(255,255,255,0.05);
            color: rgba(255,255,255,0.6);
        }

        .contact-info .social-links a:hover {
            transform: translateY(-5px) scale(1.1);
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .contact-info .social-links a:active { transform: scale(0.95); }

        .contact-info .social-links a.whatsapp:hover {
            background: #25D366;
            border-color: #25D366;
        }
        .contact-info .social-links a.instagram:hover {
            background: #E4405F;
            border-color: #E4405F;
        }
        .contact-info .social-links a.facebook:hover {
            background: #1877F2;
            border-color: #1877F2;
        }
        .contact-info .social-links a.phone:hover {
            background: var(--primary);
            border-color: var(--primary);
        }
        .contact-info .social-links a.email:hover {
            background: #EA4335;
            border-color: #EA4335;
        }
        .contact-info .social-links a.twitter:hover {
            background: #000000;
            border-color: #1DA1F2;
            color: #1DA1F2;
        }
        .contact-info .social-links a.linkedin:hover {
            background: #0A66C2;
            border-color: #0A66C2;
        }

        /* ============================================
           CONTACT FORM
        ============================================ */
        .contact-form {
            background: var(--white);
            padding: 35px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        body.dark-mode .contact-form {
            background: #2D2D44;
        }
        .contact-form:hover {
            box-shadow: var(--shadow-hover);
        }

        .contact-form h2 {
            font-size: 22px;
            color: var(--dark);
            margin-bottom: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }
        .contact-form h2 i {
            color: var(--primary);
            font-size: 22px;
        }
        body.dark-mode .contact-form h2 { color: white; }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
            font-size: 14px;
        }
        body.dark-mode .form-group label { color: #ddd; }

        .form-group label .required {
            color: var(--secondary);
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid rgba(0,0,0,0.06);
            border-radius: var(--radius-sm);
            font-size: 15px;
            transition: var(--transition);
            outline: none;
            font-family: 'Inter', sans-serif;
            background: var(--light-bg);
            color: var(--dark);
        }
        body.dark-mode .form-group input,
        body.dark-mode .form-group textarea {
            background: #1A1A2E;
            border-color: rgba(255,255,255,0.06);
            color: white;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
            background: var(--white);
        }
        body.dark-mode .form-group input:focus,
        body.dark-mode .form-group textarea:focus {
            background: #2D2D44;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--gray);
        }
        body.dark-mode .form-group input::placeholder,
        body.dark-mode .form-group textarea::placeholder {
            color: #667;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 130px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            min-height: 50px;
            -webkit-tap-highlight-color: transparent;
        }
        .btn-primary {
            background: var(--gradient);
            color: white;
            width: 100%;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.25);
        }
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(108, 99, 255, 0.35);
        }
        .btn-primary:active { transform: scale(0.98); }

        /* ============================================
           ALERTS
        ============================================ */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeInUp 0.5s ease;
            font-weight: 500;
            font-size: 14px;
        }
        .alert i {
            font-size: 18px;
        }
        .alert-success {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
            border-left: 4px solid var(--success);
        }
        .alert-danger {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        /* ============================================
           MAP SECTION
        ============================================ */
        .map-section {
            padding: 30px 0 60px;
            position: relative;
            z-index: 1;
        }

        .map-section .map-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .map-section .map-header h2 {
            font-size: 26px;
            color: var(--dark);
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            letter-spacing: -0.5px;
        }
        .map-section .map-header h2 i {
            color: var(--primary);
            font-size: 26px;
        }
        body.dark-mode .map-section .map-header h2 { color: white; }

        .map-section .map-header p {
            color: var(--gray);
            margin-top: 6px;
            font-size: 15px;
        }

        .map-wrapper {
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            background: var(--white);
            padding: 8px;
        }
        body.dark-mode .map-wrapper {
            background: #2D2D44;
        }
        .map-wrapper:hover {
            box-shadow: var(--shadow-hover);
        }

        .map-wrapper iframe {
            width: 100%;
            height: 400px;
            border: none;
            border-radius: var(--radius-sm);
            display: block;
        }

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
            .contact-grid {
                grid-template-columns: 1fr;
                gap: 30px;
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

            /* Contact */
            .contact-section { padding: 40px 0; }
            .contact-info h2 { font-size: 22px; }
            .contact-form { padding: 25px 20px; }
            .contact-form h2 { font-size: 20px; }
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            .form-group input,
            .form-group textarea {
                font-size: 14px;
                padding: 11px 14px;
            }
            .btn { font-size: 15px; padding: 12px 24px; }

            /* Social */
            .contact-info .social-links { justify-content: center; }
            .contact-info .social-links a {
                width: 44px;
                height: 44px;
                font-size: 18px;
            }

            /* Map */
            .map-section { padding: 20px 0 40px; }
            .map-section .map-header h2 { font-size: 22px; }
            .map-section .map-header p { font-size: 13px; }
            .map-wrapper { padding: 6px; }
            .map-wrapper iframe { height: 300px; }

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

            .contact-info .info-item .icon {
                width: 44px;
                height: 44px;
                font-size: 17px;
            }
            .contact-info .info-item .details h4 { font-size: 12px; }
            .contact-info .info-item .details p { font-size: 13px; }

            .contact-form { padding: 20px 16px; }

            .footer .social-links a { width: 40px; height: 40px; font-size: 16px; }

            .map-wrapper iframe { height: 240px; }
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
        <div class="toast-icon"><i class="fas fa-paper-plane"></i></div>
        <div class="toast-content">
            <h4>Message Sent!</h4>
            <p id="toastMessage">Thank you for contacting us</p>
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
                <a href="about.php"><i class="fas fa-info-circle"></i> About</a>
                <a href="contact.php" class="active"><i class="fas fa-envelope"></i> Contact</a>
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
            <div class="header-icon"><i class="fas fa-headset"></i></div>
            <h1>Contact <span class="highlight">Us</span></h1>
            <p>Get in touch with us for any questions or support</p>
        </div>
    </section>

    <!-- ===== CONTACT SECTION ===== -->
    <section class="contact-section">
        <div class="container">
            <div class="contact-grid">
                <!-- ===== CONTACT INFO ===== -->
                <div class="contact-info">
                    <h2><i class="fas fa-comments"></i> Get in Touch</h2>
                    <p class="intro-text">
                        Have questions about our products? Need help choosing the right calculator?
                        We're here to help! Reach out to us through any of the channels below.
                    </p>

                    <div class="info-item">
                        <div class="icon"><i class="fab fa-whatsapp"></i></div>
                        <div class="details">
                            <h4>WhatsApp</h4>
                            <?php if (!empty($settings['whatsapp_number'])): ?>
                                <p><a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank"><?php echo $settings['whatsapp_number']; ?></a></p>
                            <?php else: ?>
                                <p style="color:var(--gray);">Not set</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-phone"></i></div>
                        <div class="details">
                            <h4>Phone</h4>
                            <?php if (!empty($settings['phone_number'])): ?>
                                <p><a href="tel:<?php echo $settings['phone_number']; ?>"><?php echo $settings['phone_number']; ?></a></p>
                            <?php else: ?>
                                <p style="color:var(--gray);">Not set</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-envelope"></i></div>
                        <div class="details">
                            <h4>Email</h4>
                            <?php if (!empty($settings['admin_email'])): ?>
                                <p><a href="mailto:<?php echo $settings['admin_email']; ?>"><?php echo $settings['admin_email']; ?></a></p>
                            <?php else: ?>
                                <p><a href="mailto:info@sci-calc.com">info@sci-calc.com</a></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div class="details">
                            <h4>Location</h4>
                            <?php if (!empty($settings['location'])): ?>
                                <p><?php echo htmlspecialchars($settings['location']); ?></p>
                            <?php else: ?>
                                <p style="color:var(--gray);">Not set</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="social-links">
                        <?php if (!empty($settings['whatsapp_number'])): ?>
                            <a href="https://wa.me/<?php echo $settings['whatsapp_number']; ?>" target="_blank" class="whatsapp" title="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['instagram_link'])): ?>
                            <a href="<?php echo $settings['instagram_link']; ?>" target="_blank" class="instagram" title="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['facebook_link'])): ?>
                            <a href="<?php echo $settings['facebook_link']; ?>" target="_blank" class="facebook" title="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['twitter_link'])): ?>
                            <a href="<?php echo $settings['twitter_link']; ?>" target="_blank" class="twitter" title="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['linkedin_link'])): ?>
                            <a href="<?php echo $settings['linkedin_link']; ?>" target="_blank" class="linkedin" title="LinkedIn">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['phone_number'])): ?>
                            <a href="tel:<?php echo $settings['phone_number']; ?>" class="phone" title="Call Us">
                                <i class="fas fa-phone"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($settings['admin_email'])): ?>
                            <a href="mailto:<?php echo $settings['admin_email']; ?>" class="email" title="Email">
                                <i class="fas fa-envelope"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ===== CONTACT FORM ===== -->
                <div class="contact-form">
                    <h2><i class="fas fa-paper-plane"></i> Send a Message</h2>

                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="" id="contactForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name <span class="required">*</span></label>
                                <input type="text" name="name" placeholder="e.g., John Doe" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" name="email" placeholder="e.g., john@example.com" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Subject <span class="required">*</span></label>
                            <input type="text" name="subject" placeholder="What is your message about?" required>
                        </div>
                        <div class="form-group">
                            <label>Message <span class="required">*</span></label>
                            <textarea name="message" placeholder="Write your message here..." required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== MAP SECTION ===== -->
    <?php if (!empty($settings['location_name']) || !empty($settings['location_address'])): ?>
    <section class="map-section">
        <div class="container">
            <div class="map-header">
                <h2><i class="fas fa-map-marker-alt"></i> Our Location</h2>
                <p><?php echo htmlspecialchars($settings['location_name'] ?? 'Visit Us'); ?></p>
            </div>
            <div class="map-wrapper">
                <?php 
                $mapQuery = '';
                if (!empty($settings['location_lat']) && !empty($settings['location_lng'])) {
                    $mapQuery = $settings['location_lat'] . ',' . $settings['location_lng'];
                } elseif (!empty($settings['location_address'])) {
                    $mapQuery = urlencode($settings['location_address']);
                } elseif (!empty($settings['location'])) {
                    $mapQuery = urlencode($settings['location']);
                } else {
                    $mapQuery = 'Dar es Salaam, Tanzania';
                }
                ?>
                <iframe 
                    src="https://maps.google.com/maps?q=<?php echo $mapQuery; ?>&t=&z=15&ie=UTF8&iwloc=&output=embed"
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
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
                document.getElementById('toastMessage').textContent = message || 'Thank you for contacting us!';
                toast.classList.add('show');
                clearTimeout(toastTimeout);
                toastTimeout = setTimeout(() => toast.classList.remove('show'), 4000);
            };

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
            // 5. FORM VALIDATION
            // ========================================
            const contactForm = document.getElementById('contactForm');
            if (contactForm) {
                contactForm.addEventListener('submit', function(e) {
                    const name = this.querySelector('input[name="name"]').value.trim();
                    const email = this.querySelector('input[name="email"]').value.trim();
                    const subject = this.querySelector('input[name="subject"]').value.trim();
                    const message = this.querySelector('textarea[name="message"]').value.trim();

                    if (!name) {
                        e.preventDefault();
                        showToast('Please enter your full name!');
                        return false;
                    }
                    if (!email) {
                        e.preventDefault();
                        showToast('Please enter your email address!');
                        return false;
                    }
                    if (!subject) {
                        e.preventDefault();
                        showToast('Please enter a subject!');
                        return false;
                    }
                    if (!message) {
                        e.preventDefault();
                        showToast('Please enter your message!');
                        return false;
                    }

                    // Show loading state
                    const btn = this.querySelector('button[type="submit"]');
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                    btn.disabled = true;

                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }, 3000);

                    return true;
                });
            }

            // ========================================
            // 6. AUTO-HIDE ALERTS
            // ========================================
            document.querySelectorAll('.alert').forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s, transform 0.5s';
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-10px)';
                    setTimeout(() => { alert.style.display = 'none'; }, 500);
                }, 6000);
            });

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
                if (e.key === 'Escape') {
                    document.getElementById('mainNav').classList.remove('open');
                    document.querySelector('.hamburger').innerHTML = '<i class="fas fa-bars"></i>';
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
            console.log('%c📞 SCI-CALC Contact Page', 'font-size:30px;font-weight:bold;color:#6C63FF;');
            console.log('%c📞 WhatsApp: <?php echo $settings['whatsapp_number']; ?>', 'font-size:14px;color:#25D366;');
            console.log('%c✅ Contact page loaded successfully!', 'font-size:14px;color:#00C9A7;');
        });
    </script>
</body>
</html>