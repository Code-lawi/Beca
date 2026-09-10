<?php
require_once '../config/database.php';

// Redirect if already logged in
if (isAdminLoggedIn() && empty($_SESSION['force_password_change'])) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$step = $_GET['step'] ?? 'login';

// ============================================
// HANDLE PASSWORD RESET REQUEST
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    
    // ===== STEP 1: FORGOT PASSWORD - VERIFY USERNAME =====
    if ($_POST['action'] === 'forgot_password') {
        $username = trim($_POST['username'] ?? '');
        
        if (empty($username)) {
            $error = 'Please enter your username!';
        } else {
            $stmt = $conn->prepare("SELECT id, username FROM admin_users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Generate strong temporary password
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                $digits = '0123456789';
                $special = '!@#$%&*';
                
                $temp_password = 
                    substr(str_shuffle($chars), 0, 4) .
                    substr(str_shuffle($digits), 0, 2) .
                    substr(str_shuffle($special), 0, 2);
                $temp_password = str_shuffle($temp_password);
                
                // Save temp password ONLY (do NOT touch main password)
                $update = $conn->prepare("
                    UPDATE admin_users 
                    SET temp_password = ?, 
                        temp_password_expiry = DATE_ADD(NOW(), INTERVAL 15 MINUTE) 
                    WHERE id = ?
                ");
                $update->bind_param("si", $temp_password, $user['id']);
                
                if ($update->execute()) {
                    $update->close();
                    $_SESSION['temp_password'] = $temp_password;
                    $_SESSION['reset_username'] = $user['username'];
                    header('Location: login.php?step=reset');
                    exit();
                } else {
                    $error = 'Failed to reset password. Please try again.';
                }
                $update->close();
            } else {
                $error = 'Username not found!';
            }
            $stmt->close();
        }
    }
    
    // ===== MAIN LOGIN =====
    if ($_POST['action'] === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        
        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("
                SELECT id, username, password, temp_password, temp_password_expiry 
                FROM admin_users 
                WHERE username = ?
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                $using_temp = false;
                $login_success = false;
                
                // Check temp password first
                if (!empty($user['temp_password']) && $password === $user['temp_password']) {
                    if (strtotime($user['temp_password_expiry']) > time()) {
                        $using_temp = true;
                        $login_success = true;
                    } else {
                        $error = 'Temporary password has expired! Please reset again.';
                    }
                }
                // Then check normal password
                elseif (password_verify($password, $user['password'])) {
                    $login_success = true;
                }
                
                if ($login_success) {
                    if ($using_temp) {
                        // Force password change
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        $_SESSION['force_password_change'] = true;
                        
                        // Clear temp password from DB (one-time use)
                        $clear = $conn->prepare("
                            UPDATE admin_users 
                            SET temp_password = NULL, temp_password_expiry = NULL 
                            WHERE id = ?
                        ");
                        $clear->bind_param("i", $user['id']);
                        $clear->execute();
                        $clear->close();
                        
                        header('Location: login.php?step=change_password');
                        exit();
                    } else {
                        // Normal login
                        $_SESSION['admin_id'] = $user['id'];
                        $_SESSION['admin_username'] = $user['username'];
                        unset($_SESSION['force_password_change']);
                        
                        // Set success flag for animation
                        $_SESSION['login_success_animation'] = true;
                        
                        header('Location: dashboard.php');
                        exit();
                    }
                } elseif (empty($error)) {
                    $error = 'Invalid password!';
                }
            } else {
                $error = 'Invalid username!';
            }
            $stmt->close();
        } else {
            $error = 'Please fill all fields!';
        }
    }
    
    // ===== CHANGE PASSWORD (after temp login) =====
    if ($_POST['action'] === 'change_password') {
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $new_password = trim($_POST['new_password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');
        
        // ===== STRONG PASSWORD VALIDATION =====
        $has_uppercase = preg_match('/[A-Z]/', $new_password);
        $has_lowercase = preg_match('/[a-z]/', $new_password);
        $has_number = preg_match('/[0-9]/', $new_password);
        $has_special = preg_match('/[^A-Za-z0-9]/', $new_password);
        $has_length = strlen($new_password) >= 8;
        
        if (!isset($_SESSION['force_password_change']) || $_SESSION['force_password_change'] !== true) {
            $error = 'Unauthorized access!';
        } elseif (empty($new_password) || empty($confirm_password)) {
            $error = 'Please fill all fields!';
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match!';
        } elseif (!$has_length) {
            $error = 'Password must be at least 8 characters!';
        } elseif (!$has_uppercase) {
            $error = 'Password must contain at least one UPPERCASE letter!';
        } elseif (!$has_lowercase) {
            $error = 'Password must contain at least one lowercase letter!';
        } elseif (!$has_number) {
            $error = 'Password must contain at least one number!';
        } elseif (!$has_special) {
            $error = 'Password must contain at least one special character!';
        } else {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                UPDATE admin_users 
                SET password = ?, temp_password = NULL, temp_password_expiry = NULL 
                WHERE id = ?
            ");
            $stmt->bind_param("si", $hashed, $admin_id);
            
            if ($stmt->execute()) {
                $stmt->close();
                
                // Clear force password change
                unset($_SESSION['force_password_change']);
                unset($_SESSION['temp_password']);
                unset($_SESSION['reset_username']);
                
                // Log the user in directly
                $_SESSION['admin_id'] = $admin_id;
                $_SESSION['admin_username'] = $_SESSION['admin_username'] ?? 'Admin';
                
                // Set success flag for animation
                $_SESSION['login_success_animation'] = true;
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Failed to change password.';
            }
        }
    }
}

// ============================================
// STEP VERIFICATION
// ============================================
$step = $_GET['step'] ?? 'login';

if ($step === 'change_password') {
    if (!isset($_SESSION['force_password_change']) || $_SESSION['force_password_change'] !== true) {
        header('Location: login.php');
        exit();
    }
}

if ($step === 'reset') {
    if (!isset($_SESSION['temp_password']) || !isset($_SESSION['reset_username'])) {
        header('Location: login.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#6C63FF">
    <title>Admin Login - SCI-CALC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --secondary: #FF6B6B;
            --success: #00C9A7;
            --warning: #FFD93D;
            --danger: #FF6B6B;
            --dark: #2D3436;
            --gray: #636E72;
            --light-gray: #DFE6E9;
            --white: #FFFFFF;
            --light-bg: #F5F7FA;
            --gradient: linear-gradient(135deg, #6C63FF 0%, #5A52D5 100%);
            --gradient-warm: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --shadow: 0 4px 20px rgba(0,0,0,0.06);
            --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.3);
            --radius: 20px;
            --radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--gradient-warm);
            padding: 20px;
            padding-top: env(safe-area-inset-top, 20px);
            padding-bottom: env(safe-area-inset-bottom, 20px);
            position: relative;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255,255,255,0.08) 0%, transparent 40%),
                radial-gradient(circle at 80% 70%, rgba(255,255,255,0.05) 0%, transparent 40%);
            animation: rotateBg 30s linear infinite;
            z-index: 0;
        }

        @keyframes rotateBg {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .math-float {
            position: fixed;
            color: rgba(255,255,255,0.06);
            font-size: 32px;
            font-weight: 700;
            font-family: 'Times New Roman', serif;
            pointer-events: none;
            z-index: 0;
            animation: floatMath 20s ease-in-out infinite;
        }
        .math-float:nth-child(1) { top: 10%; left: 5%; animation-delay: 0s; }
        .math-float:nth-child(2) { top: 20%; right: 8%; animation-delay: -3s; font-size: 28px; }
        .math-float:nth-child(3) { bottom: 15%; left: 10%; animation-delay: -6s; font-size: 40px; }
        .math-float:nth-child(4) { bottom: 25%; right: 12%; animation-delay: -9s; font-size: 24px; }
        .math-float:nth-child(5) { top: 50%; left: 2%; animation-delay: -12s; font-size: 36px; }
        .math-float:nth-child(6) { top: 60%; right: 3%; animation-delay: -15s; font-size: 30px; }

        @keyframes floatMath {
            0%, 100% { transform: translateY(0) rotate(0deg); opacity: 0.4; }
            50% { transform: translateY(-30px) rotate(5deg); opacity: 0.7; }
        }

        .login-container {
            background: var(--white);
            border-radius: var(--radius);
            padding: 50px 40px;
            width: 100%;
            max-width: 440px;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* ============================================
           SUCCESS LOGIN ANIMATION OVERLAY
        ============================================ */
        .login-success-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient);
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            z-index: 99999;
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .login-success-overlay.show {
            display: flex;
            opacity: 1;
        }

        .success-icon-wrapper {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 25px;
            animation: successPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: 3px solid rgba(255,255,255,0.3);
        }

        .success-icon-wrapper i {
            font-size: 60px;
            color: white;
            animation: checkmark 0.5s ease 0.3s both;
        }

        @keyframes successPop {
            0% { transform: scale(0); }
            50% { transform: scale(1.15); }
            100% { transform: scale(1); }
        }

        @keyframes checkmark {
            0% { transform: scale(0) rotate(-45deg); opacity: 0; }
            100% { transform: scale(1) rotate(0deg); opacity: 1; }
        }

        .success-text {
            color: white;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
            animation: fadeInUp 0.5s ease 0.4s both;
            text-align: center;
            padding: 0 20px;
        }

        .success-subtext {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
            margin-top: 8px;
            animation: fadeInUp 0.5s ease 0.5s both;
        }

        .success-loader {
            width: 200px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 2px;
            margin-top: 30px;
            overflow: hidden;
            animation: fadeInUp 0.5s ease 0.6s both;
        }

        .success-loader::after {
            content: '';
            display: block;
            width: 40%;
            height: 100%;
            background: white;
            border-radius: 2px;
            animation: loaderMove 1.2s ease-in-out infinite;
        }

        @keyframes loaderMove {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(350%); }
        }

        /* ============================================
           HEADER
        ============================================ */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient);
            border-radius: 22px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 10px 30px rgba(108, 99, 255, 0.3);
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }

        .login-header h1 {
            font-size: 30px;
            font-weight: 900;
            color: var(--dark);
            margin-bottom: 4px;
            letter-spacing: -1px;
        }

        .login-header h1 span {
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-header p {
            color: var(--gray);
            font-size: 14px;
            font-weight: 500;
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert i {
            font-size: 16px;
        }

        .alert-danger {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-success {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-info {
            background: rgba(108, 99, 255, 0.12);
            color: var(--primary);
            border-left: 4px solid var(--primary);
        }

        .alert-warning {
            background: rgba(255, 217, 61, 0.15);
            color: #B7950B;
            border-left: 4px solid var(--warning);
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 13px;
        }

        .form-group label i {
            color: var(--primary);
            margin-right: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 18px;
            padding-right: 50px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 15px;
            transition: var(--transition);
            outline: none;
            background: var(--light-bg);
            color: var(--dark);
            font-family: 'Inter', sans-serif;
        }

        .input-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.1);
            background: var(--white);
        }

        .input-wrapper input::placeholder {
            color: var(--gray);
        }

        .input-wrapper .input-icon {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
            pointer-events: none;
        }

        .input-wrapper .toggle-password {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
            font-size: 16px;
            cursor: pointer;
            background: none;
            border: none;
            padding: 4px;
            transition: var(--transition);
            pointer-events: auto;
        }

        .input-wrapper .toggle-password:hover {
            color: var(--primary);
        }

        .btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: var(--radius-sm);
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Inter', sans-serif;
            min-height: 52px;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-primary {
            background: var(--gradient);
            color: white;
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(108, 99, 255, 0.4);
        }

        .btn-primary:active {
            transform: translateY(-1px) scale(0.99);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none !important;
        }

        .forgot-password {
            text-align: right;
            margin-top: -8px;
            margin-bottom: 18px;
        }

        .forgot-password a {
            color: var(--primary);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .forgot-password a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .temp-password-box {
            background: linear-gradient(135deg, rgba(108, 99, 255, 0.08) 0%, rgba(255, 107, 107, 0.08) 100%);
            border: 2px dashed var(--primary);
            border-radius: var(--radius-sm);
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            animation: pulseBorder 2s infinite;
        }

        @keyframes pulseBorder {
            0%, 100% { border-color: rgba(108, 99, 255, 0.3); }
            50% { border-color: rgba(108, 99, 255, 0.8); }
        }

        .temp-password-box .label {
            font-size: 12px;
            color: var(--gray);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .temp-password-box .password {
            font-size: 26px;
            font-weight: 900;
            color: var(--primary);
            letter-spacing: 2px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
            padding: 12px 20px;
            background: var(--white);
            border-radius: 10px;
            user-select: all;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            word-break: break-all;
        }

        .temp-password-box .password:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.2);
        }

        .temp-password-box .hint {
            font-size: 12px;
            color: var(--gray);
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .temp-password-box .copy-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Inter', sans-serif;
        }

        .temp-password-box .copy-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .temp-password-box .copy-btn.copied {
            background: var(--success);
        }

        .back-link {
            text-align: center;
            margin-top: 18px;
        }

        .back-link a {
            color: var(--gray);
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .back-link a:hover {
            color: var(--primary);
        }

        .step-indicator {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .step-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--light-bg);
            color: var(--gray);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            transition: var(--transition);
            border: 2px solid var(--light-gray);
        }

        .step-dot.active {
            background: var(--gradient);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }

        .step-dot.completed {
            background: var(--success);
            color: white;
            border-color: transparent;
        }

        .step-line {
            width: 30px;
            height: 2px;
            background: var(--light-gray);
            border-radius: 2px;
        }

        .step-line.completed {
            background: var(--success);
        }

        /* ============================================
           PASSWORD REQUIREMENTS
        ============================================ */
        .password-requirements {
            background: var(--light-bg);
            border-radius: var(--radius-sm);
            padding: 14px 16px;
            margin-top: 10px;
            margin-bottom: 18px;
            display: none;
        }

        .password-requirements.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        .password-requirements .req-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .password-requirements .req-list {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
        }

        .password-requirements .req-list li {
            font-size: 12px;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        .password-requirements .req-list li i {
            font-size: 10px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: var(--light-gray);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
        }

        .password-requirements .req-list li.met {
            color: var(--success);
        }

        .password-requirements .req-list li.met i {
            background: var(--success);
        }

        /* ============================================
           PASSWORD STRENGTH METER
        ============================================ */
        .strength-meter {
            display: flex;
            gap: 4px;
            margin-top: 10px;
            margin-bottom: 4px;
        }

        .strength-meter .bar {
            flex: 1;
            height: 4px;
            background: var(--light-gray);
            border-radius: 2px;
            transition: var(--transition);
        }

        .strength-meter .bar.active.weak { background: #FF6B6B; }
        .strength-meter .bar.active.medium { background: #FFC107; }
        .strength-meter .bar.active.strong { background: #FFC107; }
        .strength-meter .bar.active.very-strong { background: #00C9A7; }

        .strength-text {
            font-size: 11px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        @media (max-width: 480px) {
            body { padding: 15px; }
            
            .login-container {
                padding: 35px 25px;
                border-radius: 16px;
            }

            .login-icon {
                width: 70px;
                height: 70px;
                font-size: 30px;
            }

            .login-header h1 {
                font-size: 26px;
            }

            .login-header p {
                font-size: 13px;
            }

            .form-group input {
                padding: 13px 16px;
                padding-right: 46px;
                font-size: 14px;
            }

            .btn {
                padding: 14px;
                font-size: 14px;
                min-height: 50px;
            }

            .temp-password-box {
                padding: 16px;
            }

            .temp-password-box .password {
                font-size: 20px;
                letter-spacing: 1px;
                padding: 10px 14px;
            }

            .step-dot {
                width: 28px;
                height: 28px;
                font-size: 12px;
            }

            .step-line {
                width: 20px;
            }

            .password-requirements .req-list {
                grid-template-columns: 1fr;
            }

            .math-float { font-size: 20px !important; }

            .success-icon-wrapper {
                width: 100px;
                height: 100px;
            }

            .success-icon-wrapper i {
                font-size: 50px;
            }

            .success-text {
                font-size: 20px;
            }
        }

        @media (max-width: 360px) {
            .login-container {
                padding: 25px 18px;
            }

            .login-header h1 {
                font-size: 22px;
            }

            .temp-password-box .password {
                font-size: 16px;
                letter-spacing: 1px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>

    <!-- ============================================
         SUCCESS LOGIN ANIMATION OVERLAY
    ============================================ -->
    <div class="login-success-overlay" id="loginSuccessOverlay">
        <div class="success-icon-wrapper">
            <i class="fas fa-check"></i>
        </div>
        <div class="success-text" id="successText">Welcome Back!</div>
        <div class="success-subtext" id="successSubtext">Redirecting to dashboard...</div>
        <div class="success-loader"></div>
    </div>

    <div class="math-float">∫ f(x) dx</div>
    <div class="math-float">Σ n²</div>
    <div class="math-float">E = mc²</div>
    <div class="math-float">π ≈ 3.14</div>
    <div class="math-float">√(a² + b²)</div>
    <div class="math-float">sin²θ + cos²θ</div>

    <div class="login-container">
        
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <h1>SCI-<span>CALC</span></h1>
            <p>Admin Panel</p>
        </div>

        <?php if ($step === 'reset' || $step === 'change_password'): ?>
        <div class="step-indicator">
            <div class="step-dot <?php echo $step === 'reset' ? 'active' : 'completed'; ?>">
                <i class="fas fa-check" style="<?php echo $step === 'reset' ? 'display:none;' : ''; ?>"></i>
                <span style="<?php echo $step === 'reset' ? '' : 'display:none;'; ?>">1</span>
            </div>
            <div class="step-line <?php echo $step === 'change_password' ? 'completed' : ''; ?>"></div>
            <div class="step-dot <?php echo $step === 'change_password' ? 'active' : ''; ?>">2</div>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- ============================================
             STEP 1: LOGIN FORM
        ============================================ -->
        <?php if ($step === 'login'): ?>
        <form method="POST" action="" id="loginForm">
            <input type="hidden" name="action" value="login">
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <div class="input-wrapper">
                    <input type="text" name="username" id="usernameField" placeholder="Enter your username" required autocomplete="username">
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Password</label>
                <div class="input-wrapper">
                    <input type="password" name="password" id="passwordField" placeholder="Enter your password" required autocomplete="current-password">
                    <button type="button" class="toggle-password" onclick="togglePassword('passwordField', this)" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="forgot-password">
                <a href="?step=forgot">
                    <i class="fas fa-key"></i> Forgot Password?
                </a>
            </div>

            <button type="submit" class="btn btn-primary" id="loginBtn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        <?php endif; ?>

        <!-- ============================================
             STEP 2: FORGOT PASSWORD FORM
        ============================================ -->
        <?php if ($step === 'forgot'): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            Enter your username to generate a temporary password
        </div>

        <form method="POST" action="" id="forgotForm">
            <input type="hidden" name="action" value="forgot_password">
            
            <div class="form-group">
                <label><i class="fas fa-user"></i> Username</label>
                <div class="input-wrapper">
                    <input type="text" name="username" placeholder="Enter your username" required autocomplete="username" autofocus>
                    <i class="fas fa-user input-icon"></i>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="forgotBtn">
                <i class="fas fa-paper-plane"></i> Generate Temporary Password
            </button>
        </form>

        <div class="back-link">
            <a href="login.php">
                <i class="fas fa-arrow-left"></i> Back to Login
            </a>
        </div>
        <?php endif; ?>

        <!-- ============================================
             STEP 3: SHOW TEMP PASSWORD
        ============================================ -->
        <?php if ($step === 'reset'): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            Temporary password generated! Copy it below.
        </div>

        <div class="temp-password-box">
            <div class="label"><i class="fas fa-key"></i> Temporary Password</div>
            <div class="password" id="tempPassword" onclick="selectPassword()">
                <?php echo htmlspecialchars($_SESSION['temp_password'] ?? ''); ?>
            </div>
            <div class="hint">
                <i class="fas fa-clock"></i>
                Valid for 15 minutes only
            </div>
            <button type="button" class="copy-btn" id="copyBtn" onclick="copyPassword()">
                <i class="fas fa-copy"></i> Copy Password
            </button>
        </div>

        <a href="login.php" class="btn btn-primary" style="text-decoration:none;">
            <i class="fas fa-sign-in-alt"></i> Continue to Login
        </a>
        <?php endif; ?>

        <!-- ============================================
             STEP 4: CHANGE PASSWORD FORM
        ============================================ -->
        <?php if ($step === 'change_password'): ?>
        <div class="alert alert-warning">
            <i class="fas fa-shield-alt"></i>
            <div>
                <strong>Security Required:</strong> Set a strong password to continue.
            </div>
        </div>

        <form method="POST" action="" id="changePasswordForm">
            <input type="hidden" name="action" value="change_password">
            
            <div class="form-group">
                <label><i class="fas fa-lock"></i> New Password</label>
                <div class="input-wrapper">
                    <input type="password" name="new_password" id="newPassword" placeholder="Enter strong password" required minlength="8">
                    <button type="button" class="toggle-password" onclick="togglePassword('newPassword', this)" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <!-- Password Strength Meter -->
                <div class="strength-meter" id="strengthMeter">
                    <div class="bar" data-level="1"></div>
                    <div class="bar" data-level="2"></div>
                    <div class="bar" data-level="3"></div>
                    <div class="bar" data-level="4"></div>
                </div>
                <div class="strength-text" id="strengthText" style="color: var(--gray);">
                    <i class="fas fa-shield-alt"></i>
                    <span>Enter a password</span>
                </div>

                <!-- Password Requirements -->
                <div class="password-requirements" id="passwordRequirements">
                    <div class="req-title">
                        <i class="fas fa-list-check"></i>
                        Password Requirements
                    </div>
                    <ul class="req-list">
                        <li data-req="length"><i class="fas fa-check"></i> At least 8 characters</li>
                        <li data-req="uppercase"><i class="fas fa-check"></i> One uppercase letter</li>
                        <li data-req="lowercase"><i class="fas fa-check"></i> One lowercase letter</li>
                        <li data-req="number"><i class="fas fa-check"></i> One number</li>
                        <li data-req="special"><i class="fas fa-check"></i> One special character</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Confirm Password</label>
                <div class="input-wrapper">
                    <input type="password" name="confirm_password" id="confirmPassword" placeholder="Re-enter new password" required minlength="8">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirmPassword', this)" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary" id="changeBtn">
                <i class="fas fa-save"></i> Save Password
            </button>
        </form>
        <?php endif; ?>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            'use strict';

            // ========================================
            // AUTO-FOCUS
            // ========================================
            const firstInput = document.querySelector('input[autofocus]') || document.querySelector('input:not([type="hidden"])');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }

            // ========================================
            // TOGGLE PASSWORD VISIBILITY
            // ========================================
            window.togglePassword = function(fieldId, btn) {
                const field = document.getElementById(fieldId);
                if (!field) return;

                if (field.type === 'password') {
                    field.type = 'text';
                    btn.innerHTML = '<i class="fas fa-eye-slash"></i>';
                } else {
                    field.type = 'password';
                    btn.innerHTML = '<i class="fas fa-eye"></i>';
                }
                field.focus();
            };

            // ========================================
            // COPY PASSWORD
            // ========================================
            window.copyPassword = function() {
                const passwordEl = document.getElementById('tempPassword');
                const copyBtn = document.getElementById('copyBtn');
                if (!passwordEl) return;

                const text = passwordEl.textContent.trim();

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(() => {
                        copyBtn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                        copyBtn.classList.add('copied');
                        setTimeout(() => {
                            copyBtn.innerHTML = '<i class="fas fa-copy"></i> Copy Password';
                            copyBtn.classList.remove('copied');
                        }, 2500);
                    }).catch(() => {
                        fallbackCopy(text, copyBtn);
                    });
                } else {
                    fallbackCopy(text, copyBtn);
                }
            };

            function fallbackCopy(text, btn) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                try {
                    document.execCommand('copy');
                    btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-copy"></i> Copy Password';
                        btn.classList.remove('copied');
                    }, 2500);
                } catch (err) {
                    alert('Please manually copy the password: ' + text);
                }
                document.body.removeChild(textarea);
            }

            // ========================================
            // SELECT PASSWORD
            // ========================================
            window.selectPassword = function() {
                const passwordEl = document.getElementById('tempPassword');
                if (!passwordEl) return;

                const range = document.createRange();
                range.selectNodeContents(passwordEl);
                const selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);
            };

            // ========================================
            // PASSWORD STRENGTH CHECK - STRICT VERSION
            // ========================================
            const newPasswordField = document.getElementById('newPassword');
            const strengthMeter = document.getElementById('strengthMeter');
            const strengthText = document.getElementById('strengthText');
            const passwordRequirements = document.getElementById('passwordRequirements');

            if (newPasswordField) {
                newPasswordField.addEventListener('input', function() {
                    const val = this.value;
                    
                    // Show/hide requirements
                    if (val.length > 0) {
                        passwordRequirements.classList.add('show');
                    } else {
                        passwordRequirements.classList.remove('show');
                    }

                    // Check requirements
                    const checks = {
                        length: val.length >= 8,
                        uppercase: /[A-Z]/.test(val),
                        lowercase: /[a-z]/.test(val),
                        number: /[0-9]/.test(val),
                        special: /[^A-Za-z0-9]/.test(val)
                    };

                    // Update requirements UI
                    Object.keys(checks).forEach(key => {
                        const li = document.querySelector(`li[data-req="${key}"]`);
                        if (li) {
                            if (checks[key]) {
                                li.classList.add('met');
                            } else {
                                li.classList.remove('met');
                            }
                        }
                    });

                    // ===== COUNT MET REQUIREMENTS =====
                    const metCount = Object.values(checks).filter(v => v === true).length;
                    const totalReqs = 5;
                    const allMet = metCount === totalReqs;

                    // ===== STRENGTH LEVEL (STRICT) =====
                    let strengthLevel = 0;
                    if (val.length === 0) {
                        strengthLevel = 0;
                    } else if (allMet) {
                        strengthLevel = 4; // Very Strong - ONLY when all 5 met
                    } else if (metCount >= 4) {
                        strengthLevel = 3; // Strong (not very strong)
                    } else if (metCount >= 3) {
                        strengthLevel = 2; // Medium
                    } else {
                        strengthLevel = 1; // Weak
                    }

                    // Update strength meter
                    const bars = strengthMeter.querySelectorAll('.bar');
                    bars.forEach((bar, index) => {
                        bar.classList.remove('active', 'weak', 'medium', 'strong', 'very-strong');
                        
                        if (index < strengthLevel) {
                            bar.classList.add('active');
                            
                            if (strengthLevel === 4) {
                                bar.classList.add('very-strong');
                            } else if (strengthLevel === 3) {
                                bar.classList.add('strong');
                            } else if (strengthLevel === 2) {
                                bar.classList.add('medium');
                            } else {
                                bar.classList.add('weak');
                            }
                        }
                    });

                    // Update strength text
                    let text, color, icon;
                    if (val.length === 0) {
                        text = 'Enter a password';
                        color = 'var(--gray)';
                        icon = 'fa-shield-alt';
                    } else if (allMet) {
                        text = 'Very Strong Password';
                        color = '#00A889';
                        icon = 'fa-shield-check';
                    } else if (strengthLevel === 3) {
                        text = 'Strong Password (Missing: ' + getMissingReqs(checks) + ')';
                        color = '#FFC107';
                        icon = 'fa-shield-halved';
                    } else if (strengthLevel === 2) {
                        text = 'Medium Password';
                        color = '#FFC107';
                        icon = 'fa-shield-halved';
                    } else {
                        text = 'Weak Password';
                        color = '#FF6B6B';
                        icon = 'fa-shield-alt';
                    }

                    strengthText.innerHTML = `<i class="fas ${icon}" style="color:${color};"></i><span style="color:${color};">${text}</span>`;
                });
            }

            // Helper function to show missing requirements
            function getMissingReqs(checks) {
                const missing = [];
                if (!checks.uppercase) missing.push('Uppercase');
                if (!checks.lowercase) missing.push('Lowercase');
                if (!checks.number) missing.push('Number');
                if (!checks.special) missing.push('Special');
                if (!checks.length) missing.push('8+ chars');
                return missing.join(', ');
            }

            // ========================================
            // CHANGE PASSWORD VALIDATION
            // ========================================
            const changeForm = document.getElementById('changePasswordForm');
            if (changeForm) {
                changeForm.addEventListener('submit', function(e) {
                    const newPass = document.getElementById('newPassword').value;
                    const confirmPass = document.getElementById('confirmPassword').value;
                    let hasError = false;

                    if (newPass.length < 8) {
                        e.preventDefault();
                        showFormError('Password must be at least 8 characters!');
                        hasError = true;
                    } else if (!/[A-Z]/.test(newPass)) {
                        e.preventDefault();
                        showFormError('Password must contain at least one UPPERCASE letter!');
                        hasError = true;
                    } else if (!/[a-z]/.test(newPass)) {
                        e.preventDefault();
                        showFormError('Password must contain at least one lowercase letter!');
                        hasError = true;
                    } else if (!/[0-9]/.test(newPass)) {
                        e.preventDefault();
                        showFormError('Password must contain at least one number!');
                        hasError = true;
                    } else if (!/[^A-Za-z0-9]/.test(newPass)) {
                        e.preventDefault();
                        showFormError('Password must contain at least one special character!');
                        hasError = true;
                    } else if (newPass !== confirmPass) {
                        e.preventDefault();
                        showFormError('Passwords do not match!');
                        hasError = true;
                    }

                    if (!hasError) {
                        // Show success animation
                        showSuccessOverlay('Password Changed!', 'Redirecting to dashboard...');
                        
                        // Submit form after animation
                        const btn = document.getElementById('changeBtn');
                        if (btn) {
                            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                            btn.disabled = true;
                        }
                        
                        return true;
                    }

                    return false;
                });
            }

            function showFormError(message) {
                const existing = document.querySelector('.form-error-toast');
                if (existing) existing.remove();

                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error-toast';
                errorDiv.style.cssText = `
                    position: fixed;
                    top: 20px;
                    left: 50%;
                    transform: translateX(-50%) translateY(-100px);
                    background: #FF6B6B;
                    color: white;
                    padding: 14px 24px;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(255,107,107,0.3);
                    z-index: 99999;
                    font-weight: 600;
                    font-size: 14px;
                    font-family: 'Inter', sans-serif;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    transition: transform 0.3s ease;
                    max-width: 90%;
                `;
                errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                document.body.appendChild(errorDiv);

                requestAnimationFrame(() => {
                    errorDiv.style.transform = 'translateX(-50%) translateY(0)';
                });

                setTimeout(() => {
                    errorDiv.style.transform = 'translateX(-50%) translateY(-100px)';
                    setTimeout(() => errorDiv.remove(), 400);
                }, 3500);
            }

            // ========================================
            // SUCCESS OVERLAY ANIMATION
            // ========================================
            window.showSuccessOverlay = function(title, subtitle) {
                const overlay = document.getElementById('loginSuccessOverlay');
                const successText = document.getElementById('successText');
                const successSubtext = document.getElementById('successSubtext');
                
                if (title) successText.textContent = title;
                if (subtitle) successSubtext.textContent = subtitle;
                
                overlay.classList.add('show');
                
                // Play subtle sound effect
                try {
                    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = audioContext.createOscillator();
                    const gainNode = audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(audioContext.destination);
                    
                    oscillator.frequency.value = 800;
                    oscillator.type = 'sine';
                    
                    gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                    
                    oscillator.start();
                    oscillator.stop(audioContext.currentTime + 0.3);
                } catch (e) {
                    // Silent fail
                }
            };

            // ========================================
            // FORM SUBMISSION - SHOW LOADING
            // ========================================
            const loginForm = document.getElementById('loginForm');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    const username = document.getElementById('usernameField').value.trim();
                    const password = document.getElementById('passwordField').value.trim();
                    
                    if (!username || !password) {
                        return true;
                    }
                    
                    const btn = document.getElementById('loginBtn');
                    if (btn && !btn.disabled) {
                        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging in...';
                        btn.disabled = true;
                    }
                });
            }

            // ========================================
            // AUTO-SCROLL TO TEMP PASSWORD
            // ========================================
            const tempPassEl = document.getElementById('tempPassword');
            if (tempPassEl) {
                setTimeout(() => {
                    tempPassEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 500);
            }

            // ========================================
            // CONSOLE
            // ========================================
            console.log('%c🔐 SCI-CALC Admin Login', 'font-size:24px;font-weight:bold;color:#6C63FF;');
            console.log('%cSecure access to admin panel', 'font-size:14px;color:#636E72;');
            console.log('%c✅ Login page ready!', 'font-size:14px;color:#00C9A7;');
        });
    </script>
</body>
</html>