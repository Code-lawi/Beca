<?php
require_once '../config/database.php';
requireAdminLogin();

$error = '';
$success = '';

// Get current settings
$settings = getSettings($conn);

// If no settings exist, create default
if (!$settings) {
    $conn->query("INSERT INTO settings (
        site_name, 
        whatsapp_number, 
        instagram_link,
        location_name,
        location_address,
        location_lat,
        location_lng,
        location_google_maps,
        working_hours,
        payment_number,
        payment_name,
        payment_type
    ) VALUES (
        'SCI-CALC', 
        '0655472287', 
        'https://instagram.com/becast10',
        'SCI-CALC Store',
        'Dar es Salaam, Tanzania',
        '-6.792354',
        '39.208328',
        'https://maps.google.com/maps?q=-6.792354,39.208328&t=&z=15&ie=UTF8&iwloc=&output=embed',
        'Mon-Fri: 8:00 AM - 6:00 PM, Sat: 9:00 AM - 4:00 PM',
        '0655472287',
        'SCI-CALC Store',
        'M-Pesa'
    )");
    $settings = getSettings($conn);
}

// ============================================
// HANDLE ADMIN PASSWORD CHANGE
// ============================================
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['new_password'] ?? '');
$confirm_password = trim($_POST['confirm_password'] ?? '');

$has_password_change = !empty($current_password) || !empty($new_password) || !empty($confirm_password);

if ($has_password_change && $_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (empty($current_password)) {
        $error = 'Please enter your current password!';
    } elseif (empty($new_password)) {
        $error = 'Please enter a new password!';
    } elseif (empty($confirm_password)) {
        $error = 'Please confirm your new password!';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match!';
    } elseif (strlen($new_password) < 8) {
        $error = 'Password must be at least 8 characters!';
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = 'Password must contain at least one UPPERCASE letter!';
    } elseif (!preg_match('/[a-z]/', $new_password)) {
        $error = 'Password must contain at least one lowercase letter!';
    } elseif (!preg_match('/[0-9]/', $new_password)) {
        $error = 'Password must contain at least one number!';
    } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
        $error = 'Password must contain at least one special character!';
    } else {
        $admin_id = $_SESSION['admin_id'] ?? 0;
        $stmt = $conn->prepare("SELECT password FROM admin_users WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            if (password_verify($current_password, $admin['password'])) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE admin_users SET password = ? WHERE id = ?");
                $update->bind_param("si", $hashed, $admin_id);
                
                if ($update->execute()) {
                    $success = 'Password changed successfully! Your new password is now active.';
                    $_POST['current_password'] = '';
                    $_POST['new_password'] = '';
                    $_POST['confirm_password'] = '';
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
                $update->close();
            } else {
                $error = 'Current password is incorrect!';
            }
        }
        $stmt->close();
    }
}

// ============================================
// HANDLE SETTINGS UPDATE
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$has_password_change) {
    $site_name = trim($_POST['site_name']);
    $whatsapp_number = trim($_POST['whatsapp_number']);
    $phone_number = trim($_POST['phone_number']);
    $whatsapp_group = trim($_POST['whatsapp_group']);
    $instagram_link = trim($_POST['instagram_link']);
    $facebook_link = trim($_POST['facebook_link']);
    $twitter_link = trim($_POST['twitter_link']);
    $linkedin_link = trim($_POST['linkedin_link']);
    $location = trim($_POST['location']);
    $about_info = trim($_POST['about_info']);
    $hero_text = trim($_POST['hero_text']);
    $footer_info = trim($_POST['footer_info']);
    $admin_email = trim($_POST['admin_email']);
    
    $location_name = trim($_POST['location_name']);
    $location_address = trim($_POST['location_address']);
    $location_lat = trim($_POST['location_lat']);
    $location_lng = trim($_POST['location_lng']);
    $location_google_maps = trim($_POST['location_google_maps']);
    $working_hours = trim($_POST['working_hours']);
    
    // ============================================
    // PAYMENT FIELDS - NEW
    // ============================================
    $payment_number = trim($_POST['payment_number'] ?? '');
    $payment_name = trim($_POST['payment_name'] ?? '');
    $payment_type = trim($_POST['payment_type'] ?? 'M-Pesa');
    
    $happy_customers = intval($_POST['happy_customers']);
    $products_sold = intval($_POST['products_sold']);
    $calculator_models = intval($_POST['calculator_models']);
    $rating = floatval($_POST['rating']);
    
    // Auto-generate correct Google Maps embed URL from coordinates
    if (!empty($location_lat) && !empty($location_lng)) {
        $location_google_maps = "https://maps.google.com/maps?q={$location_lat},{$location_lng}&t=&z=15&ie=UTF8&iwloc=&output=embed";
    }
    
    // Handle logo upload
    $logo = $settings['logo'];
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['logo'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        $max_size = 2 * 1024 * 1024;
        
        if (!in_array($file['type'], $allowed_types)) {
            $error = 'Only JPG, PNG, GIF, WEBP, and SVG logos are allowed!';
        } elseif ($file['size'] > $max_size) {
            $error = 'Logo size must be less than 2MB!';
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_logo = 'logo_' . time() . '.' . $ext;
            $target_path = UPLOAD_PATH . $new_logo;
            
            if (move_uploaded_file($file['tmp_name'], $target_path)) {
                if (!empty($settings['logo']) && file_exists(UPLOAD_PATH . $settings['logo'])) {
                    unlink(UPLOAD_PATH . $settings['logo']);
                }
                $logo = $new_logo;
            } else {
                $error = 'Failed to upload logo!';
            }
        }
    }
    
    if (empty($error)) {
        $sql = "UPDATE settings SET 
            site_name = '$site_name',
            logo = '$logo',
            whatsapp_number = '$whatsapp_number',
            phone_number = '$phone_number',
            whatsapp_group = '$whatsapp_group',
            instagram_link = '$instagram_link',
            facebook_link = '$facebook_link',
            twitter_link = '$twitter_link',
            linkedin_link = '$linkedin_link',
            location = '$location',
            about_info = '$about_info',
            hero_text = '$hero_text',
            footer_info = '$footer_info',
            admin_email = '$admin_email',
            happy_customers = $happy_customers,
            products_sold = $products_sold,
            calculator_models = $calculator_models,
            rating = $rating,
            location_name = '$location_name',
            location_address = '$location_address',
            location_lat = '$location_lat',
            location_lng = '$location_lng',
            location_google_maps = '$location_google_maps',
            working_hours = '$working_hours',
            payment_number = '$payment_number',
            payment_name = '$payment_name',
            payment_type = '$payment_type'
            WHERE id = " . $settings['id'];
        
        if ($conn->query($sql)) {
            $success = 'Settings updated successfully! 🎉';
            $settings = getSettings($conn);
        } else {
            $error = 'Failed to update settings: ' . $conn->error;
        }
    }
}

// Get order counts for sidebar badge
$order_counts = getOrderCounts($conn);
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - SCI-CALC Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
            --primary-light: #8B83FF;
            --primary-gradient: linear-gradient(135deg, #6C63FF 0%, #5A52D5 100%);
            --success: #00C9A7;
            --success-dark: #00A889;
            --warning: #FFC107;
            --danger: #FF6B6B;
            --danger-dark: #E55A5A;
            --dark: #2D3436;
            --gray: #636E72;
            --light-gray: #DFE6E9;
            --bg: #F0F2F5;
            --white: #FFFFFF;
            --shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 12px 40px rgba(108, 99, 255, 0.15);
            --radius: 16px;
            --radius-sm: 10px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --sidebar-width: 260px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            display: flex;
            min-height: 100vh;
            color: var(--dark);
            opacity: 0;
            animation: fadeIn 0.6s ease forwards;
        }

        @keyframes fadeIn {
            to { opacity: 1; }
        }

        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: var(--primary); border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--primary-dark); }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            padding: 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            padding: 28px 24px 24px;
            border-bottom: 1px solid rgba(255,255,255,0.06);
            margin-bottom: 8px;
        }

        .sidebar-brand h2 {
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.5px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar-brand h2 span { color: var(--primary-light); }
        .sidebar-brand h2 .brand-icon { font-size: 28px; }

        .sidebar-brand small {
            color: rgba(255,255,255,0.5);
            font-size: 12px;
            font-weight: 400;
            display: block;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        .sidebar-menu {
            list-style: none;
            padding: 8px 12px;
            flex: 1;
        }

        .sidebar-menu .menu-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: rgba(255,255,255,0.3);
            padding: 16px 12px 8px;
            font-weight: 600;
        }

        .sidebar-menu li { margin-bottom: 2px; }

        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 16px;
            color: rgba(255,255,255,0.6);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
            gap: 14px;
            position: relative;
        }

        .sidebar-menu li a .icon {
            font-size: 18px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }

        .sidebar-menu li a .menu-text { flex: 1; }

        .sidebar-menu li a:hover {
            background: rgba(108, 99, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }

        .sidebar-menu li a.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.4);
        }

        .sidebar-menu li a .badge {
            background: rgba(255,255,255,0.15);
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .sidebar-menu li a .badge.danger {
            background: var(--danger);
            color: white;
            animation: pulse-badge 2s infinite;
        }

        @keyframes pulse-badge {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: auto;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            border-radius: var(--radius-sm);
            transition: var(--transition);
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-footer a:hover {
            background: rgba(255, 107, 107, 0.15);
            color: var(--danger);
        }

        /* MAIN CONTENT */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 28px 32px 40px;
            min-height: 100vh;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--white);
            padding: 16px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
            animation: slideDown 0.5s ease forwards;
            opacity: 0;
            transform: translateY(-20px);
            animation-delay: 0.1s;
        }

        @keyframes slideDown {
            to { opacity: 1; transform: translateY(0); }
        }

        .top-bar h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .top-bar h1 i { color: var(--primary); }

        .top-bar .view-site {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .top-bar .view-site:hover { color: var(--primary); }

        .settings-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .settings-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            animation: cardFadeIn 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .settings-card:nth-child(1) { animation-delay: 0.1s; }
        .settings-card:nth-child(2) { animation-delay: 0.15s; }
        .settings-card:nth-child(3) { animation-delay: 0.2s; }
        .settings-card:nth-child(4) { animation-delay: 0.25s; }
        .settings-card:nth-child(5) { animation-delay: 0.3s; }
        .settings-card:nth-child(6) { animation-delay: 0.35s; }
        .settings-card:nth-child(7) { animation-delay: 0.4s; }
        .settings-card:nth-child(8) { animation-delay: 0.45s; }

        @keyframes cardFadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .settings-card:hover { box-shadow: var(--shadow-hover); }
        .settings-card.full-width { grid-column: 1 / -1; }

        .settings-card h3 {
            color: var(--dark);
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--bg);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .settings-card h3 i { color: var(--primary); }

        .form-group { margin-bottom: 16px; }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 5px;
            font-size: 13px;
        }

        .form-group label .required { color: var(--danger); }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition);
            outline: none;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--dark);
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
            background: var(--white);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-group .helper-text {
            font-size: 11px;
            color: var(--gray);
            margin-top: 4px;
        }

        /* LOGO */
        .form-group .current-logo {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            background: var(--bg);
            border-radius: var(--radius-sm);
        }

        .form-group .current-logo img {
            max-height: 60px;
            border-radius: var(--radius-sm);
            border: 2px solid var(--light-gray);
            padding: 5px;
            background: var(--white);
        }

        .form-group .current-logo .logo-placeholder {
            font-size: 40px;
            color: #ccc;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input-wrapper input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-wrapper .file-label {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary-gradient);
            color: white;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .file-input-wrapper .file-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(108, 99, 255, 0.3);
        }

        .file-input-wrapper .file-name {
            margin-left: 10px;
            font-size: 13px;
            color: var(--gray);
        }

        /* PAYMENT CARD HIGHLIGHT */
        .settings-card.payment-card {
            border: 2px solid rgba(0, 201, 167, 0.2);
            position: relative;
        }

        .settings-card.payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #00C9A7 0%, #00A889 100%);
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .settings-card.payment-card h3 i {
            color: var(--success);
        }

        /* IMPACT GRID */
        .impact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .impact-item {
            background: var(--bg);
            padding: 15px;
            border-radius: var(--radius-sm);
            text-align: center;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .impact-item:hover {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.04);
        }

        .impact-item .impact-icon {
            font-size: 28px;
            margin-bottom: 5px;
        }

        .impact-item label {
            display: block;
            font-weight: 600;
            color: var(--gray);
            font-size: 12px;
            margin-bottom: 5px;
        }

        .impact-item input {
            width: 100%;
            padding: 8px 12px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 16px;
            font-weight: 700;
            text-align: center;
            transition: var(--transition);
            outline: none;
            background: var(--white);
            font-family: 'Inter', sans-serif;
        }

        .impact-item input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
        }

        /* LOCATION SEARCH */
        .location-search-wrapper {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .location-search-wrapper input {
            flex: 1;
            padding: 10px 14px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 14px;
            transition: var(--transition);
            outline: none;
            font-family: 'Inter', sans-serif;
            background: var(--bg);
        }

        .location-search-wrapper input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
            background: var(--white);
        }

        .location-search-wrapper .btn-search {
            padding: 10px 20px;
            background: var(--primary-gradient);
            color: white;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            white-space: nowrap;
        }

        .location-search-wrapper .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(108, 99, 255, 0.3);
        }

        .location-search-wrapper .btn-search:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .location-coords {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .location-coords .form-group { margin-bottom: 0; }

        #map {
            width: 100%;
            height: 400px;
            border-radius: var(--radius-sm);
            border: 2px solid var(--light-gray);
            margin-top: 10px;
            background: var(--bg);
            overflow: hidden;
            position: relative;
        }

        #map iframe {
            width: 100%;
            height: 100%;
            border: none;
            border-radius: var(--radius-sm);
            display: block;
            position: absolute;
            top: 0;
            left: 0;
        }

        .location-preview-card {
            background: var(--bg);
            border-radius: var(--radius-sm);
            padding: 12px 15px;
            margin-top: 10px;
            border: 2px solid var(--light-gray);
        }

        .location-preview-card .preview-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 0;
            font-size: 13px;
        }

        .location-preview-card .preview-item i {
            width: 20px;
            color: var(--primary);
            font-size: 14px;
        }

        .location-preview-card .preview-item strong { color: var(--dark); }

        .search-result-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            margin-bottom: 4px;
            cursor: pointer;
            transition: var(--transition);
            border: 2px solid transparent;
        }

        .search-result-item:hover {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.04);
        }

        .search-result-item .result-name {
            font-weight: 500;
            font-size: 13px;
        }

        .search-result-item .result-address {
            font-size: 12px;
            color: var(--gray);
        }

        .search-result-item .btn-select {
            padding: 4px 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .search-results {
            max-height: 200px;
            overflow-y: auto;
            margin-top: 8px;
        }

        .search-loading {
            text-align: center;
            padding: 20px;
            color: var(--gray);
        }

        /* BUTTONS */
        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: var(--transition);
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(108, 99, 255, 0.4);
        }

        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 201, 167, 0.3);
        }

        .btn-success:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--gray);
        }

        .btn-secondary:hover {
            background: #C8D0D8;
            transform: translateY(-2px);
        }

        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 6px;
            flex-wrap: wrap;
        }

        /* ALERTS */
        .alert {
            padding: 12px 18px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease;
            font-weight: 500;
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

        /* PASSWORD STRENGTH */
        .pw-bar {
            flex: 1;
            height: 4px;
            background: var(--light-gray);
            border-radius: 2px;
            transition: all 0.3s;
        }

        .toggle-pw-btn {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--gray);
            font-size: 14px;
            padding: 4px;
        }

        .toggle-pw-btn:hover { color: var(--primary); }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            :root { --sidebar-width: 72px; }

            .sidebar-brand h2 .brand-text,
            .sidebar-brand small,
            .sidebar-menu .menu-label,
            .sidebar-menu li a .menu-text,
            .sidebar-menu li a .badge,
            .sidebar-footer a .menu-text {
                display: none;
            }

            .sidebar-menu li a {
                padding: 14px;
                justify-content: center;
            }

            .sidebar-menu li a .icon { font-size: 22px; width: auto; }
            .sidebar-footer a { justify-content: center; padding: 14px; }
            .sidebar-footer a .icon { font-size: 22px; }
            .main-content { padding: 20px 16px; }
            .settings-container { grid-template-columns: 1fr; }
            .settings-card.full-width { grid-column: 1; }
            .location-coords { grid-template-columns: 1fr; }
            .impact-grid { grid-template-columns: 1fr 1fr; }
            .location-search-wrapper { flex-direction: column; }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 16px;
            }

            .top-bar h1 { font-size: 18px; }
            .main-content { padding: 12px 12px 30px; }
            .settings-card { padding: 18px; }
            .btn-group { flex-direction: column; width: 100%; }
            .btn-group .btn { width: 100%; justify-content: center; }
            .impact-grid { grid-template-columns: 1fr; }
            .location-coords { grid-template-columns: 1fr; }
            #map { height: 300px; }
        }

        @media (max-width: 400px) {
            .settings-card { padding: 14px; }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<nav class="sidebar">
    <div class="sidebar-brand">
        <h2>
            <span class="brand-icon">🧮</span>
            <span class="brand-text">SCI-<span>CALC</span></span>
        </h2>
        <small>Administration Panel</small>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-label">Main</li>
        <li>
            <a href="dashboard.php">
                <span class="icon"><i class="fas fa-th-large"></i></span>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="products.php">
                <span class="icon"><i class="fas fa-box"></i></span>
                <span class="menu-text">Products</span>
            </a>
        </li>
        <li>
            <a href="videos.php">
                <span class="icon"><i class="fas fa-video"></i></span>
                <span class="menu-text">Videos</span>
            </a>
        </li>
        <li>
            <a href="orders.php">
                <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                <span class="menu-text">Orders</span>
                <?php if ($order_counts['pending'] > 0): ?>
                    <span class="badge danger"><?php echo $order_counts['pending']; ?></span>
                <?php else: ?>
                    <span class="badge">0</span>
                <?php endif; ?>
            </a>
        </li>
        <li class="menu-label">System</li>
        <li>
            <a href="settings.php" class="active">
                <span class="icon"><i class="fas fa-cog"></i></span>
                <span class="menu-text">Settings</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <a href="logout.php">
            <span class="icon"><i class="fas fa-sign-out-alt"></i></span>
            <span class="menu-text">Logout</span>
        </a>
    </div>
</nav>

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="top-bar">
        <h1>
            <i class="fas fa-cog"></i>
            Website Settings
        </h1>
        <a href="../index.php" target="_blank" class="view-site">
            <i class="fas fa-external-link-alt"></i> View Site →
        </a>
    </div>

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

    <form method="POST" enctype="multipart/form-data" id="settingsForm">
        <div class="settings-container">

            <!-- SITE IDENTITY -->
            <div class="settings-card">
                <h3><i class="fas fa-tag"></i> Site Identity</h3>
                <div class="form-group">
                    <label>Site Name <span class="required">*</span></label>
                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($settings['site_name']); ?>" required placeholder="e.g., SCI-CALC">
                </div>
                <div class="form-group">
                    <label>Logo</label>
                    <div class="current-logo">
                        <?php if (!empty($settings['logo']) && file_exists(UPLOAD_PATH . $settings['logo'])): ?>
                            <img src="<?php echo '../uploads/' . $settings['logo']; ?>" alt="Logo">
                        <?php else: ?>
                            <span class="logo-placeholder">🧮</span>
                        <?php endif; ?>
                    </div>
                    <div class="file-input-wrapper">
                        <input type="file" name="logo" accept="image/*" id="logoInput">
                        <span class="file-label" onclick="document.getElementById('logoInput').click()">
                            <i class="fas fa-upload"></i> Choose Logo
                        </span>
                        <span class="file-name" id="fileName">No file chosen</span>
                    </div>
                    <div class="helper-text">Recommended: PNG with transparent background, max 2MB</div>
                </div>
            </div>

            <!-- CONTACT INFORMATION -->
            <div class="settings-card">
                <h3><i class="fas fa-address-book"></i> Contact Information</h3>
                <div class="form-group">
                    <label>WhatsApp Number</label>
                    <input type="text" name="whatsapp_number" value="<?php echo htmlspecialchars($settings['whatsapp_number']); ?>" placeholder="e.g., 0655472287">
                    <div class="helper-text">Without + or spaces</div>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($settings['phone_number']); ?>" placeholder="e.g., 0712345678">
                </div>
                <div class="form-group">
                    <label>Admin Email</label>
                    <input type="email" name="admin_email" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>" placeholder="admin@example.com">
                </div>
                <div class="form-group">
                    <label>Location (City/Area)</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($settings['location']); ?>" placeholder="e.g., Dar es Salaam, Tanzania">
                </div>
            </div>

            <!-- PAYMENT INFORMATION - NEW SECTION -->
            <div class="settings-card payment-card">
                <h3><i class="fas fa-credit-card"></i> Payment Information</h3>
                <div class="form-group">
                    <label>Payment Type <span class="required">*</span></label>
                    <select name="payment_type" required>
                        <option value="M-Pesa" <?php echo ($settings['payment_type'] ?? '') === 'M-Pesa' ? 'selected' : ''; ?>>M-Pesa (Vodacom)</option>
                        <option value="Tigo Pesa" <?php echo ($settings['payment_type'] ?? '') === 'Tigo Pesa' ? 'selected' : ''; ?>>Tigo Pesa (Tigo/Yas)</option>
                        <option value="Airtel Money" <?php echo ($settings['payment_type'] ?? '') === 'Airtel Money' ? 'selected' : ''; ?>>Airtel Money (Airtel)</option>
                        <option value="HaloPesa" <?php echo ($settings['payment_type'] ?? '') === 'HaloPesa' ? 'selected' : ''; ?>>HaloPesa (Halotel)</option>
                        <option value="Mixx by Yas" <?php echo ($settings['payment_type'] ?? '') === 'Mixx by Yas' ? 'selected' : ''; ?>>Mixx by Yas</option>
                        <option value="CRDB Bank" <?php echo ($settings['payment_type'] ?? '') === 'CRDB Bank' ? 'selected' : ''; ?>>CRDB Bank</option>
                        <option value="NMB Bank" <?php echo ($settings['payment_type'] ?? '') === 'NMB Bank' ? 'selected' : ''; ?>>NMB Bank</option>
                        <option value="Other" <?php echo ($settings['payment_type'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <div class="helper-text">Chagua aina ya malipo unayotumia</div>
                </div>
                <div class="form-group">
                    <label>Payment Number <span class="required">*</span></label>
                    <input type="text" name="payment_number" 
                           value="<?php echo htmlspecialchars($settings['payment_number'] ?? ''); ?>" 
                           placeholder="e.g., 0655472287" required>
                    <div class="helper-text">Namba ya malipo (bila + au spaces)</div>
                </div>
                <div class="form-group">
                    <label>Payment Name <span class="required">*</span></label>
                    <input type="text" name="payment_name" 
                           value="<?php echo htmlspecialchars($settings['payment_name'] ?? ''); ?>" 
                           placeholder="e.g., SCI-CALC Store" required>
                    <div class="helper-text">Jina la mlipaji linaloonekana kwenye malipo</div>
                </div>
                <div style="background:rgba(0,201,167,0.08);border-left:3px solid var(--success);border-radius:8px;padding:12px;font-size:12px;color:var(--gray);">
                    <i class="fas fa-info-circle" style="color:var(--success);"></i>
                    <strong>Maelezo:</strong> Taarifa hizi zitatumika kwenye WhatsApp message wakati wa kuthibitisha order.
                </div>
            </div>

            <!-- STORE LOCATION -->
            <div class="settings-card full-width">
                <h3><i class="fas fa-map-marker-alt"></i> Store Location</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <div class="form-group">
                            <label>Location Name <span class="required">*</span></label>
                            <input type="text" name="location_name" id="locationName" 
                                   value="<?php echo htmlspecialchars($settings['location_name'] ?? 'SCI-CALC Store'); ?>" 
                                   placeholder="e.g., SCI-CALC Main Store" required>
                        </div>

                        <div class="form-group">
                            <label>Full Address <span class="required">*</span></label>
                            <textarea name="location_address" id="locationAddress" rows="2" 
                                      placeholder="Full street address..." required><?php echo htmlspecialchars($settings['location_address'] ?? ''); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Working Hours</label>
                            <input type="text" name="working_hours" id="workingHours" 
                                   value="<?php echo htmlspecialchars($settings['working_hours'] ?? 'Mon-Fri: 8:00 AM - 6:00 PM'); ?>" 
                                   placeholder="e.g., Mon-Fri: 8:00 AM - 6:00 PM">
                        </div>

                        <div class="form-group">
                            <label><i class="fas fa-search"></i> Search Location</label>
                            <div class="location-search-wrapper">
                                <input type="text" id="locationSearch" placeholder="Search for a place, city, or address..." 
                                       onkeydown="if(event.key==='Enter'){event.preventDefault();searchLocation();}">
                                <button type="button" class="btn-search" id="searchBtn" onclick="searchLocation()">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="helper-text">Type a place name, city, or address (e.g., "Dar es Salaam", "Arusha", "Moshi")</div>
                            <div id="searchResults" class="search-results"></div>
                        </div>

                        <div class="location-coords">
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" name="location_lat" id="location_lat" 
                                       value="<?php echo htmlspecialchars($settings['location_lat'] ?? '-6.792354'); ?>" 
                                       placeholder="-6.792354">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" name="location_lng" id="location_lng" 
                                       value="<?php echo htmlspecialchars($settings['location_lng'] ?? '39.208328'); ?>" 
                                       placeholder="39.208328">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Google Maps Embed URL</label>
                            <input type="url" name="location_google_maps" id="locationGoogleMaps" 
                                   value="<?php echo htmlspecialchars($settings['location_google_maps'] ?? ''); ?>" 
                                   placeholder="https://maps.google.com/maps?q=...&output=embed">
                            <div class="helper-text">Auto-generated from coordinates</div>
                        </div>
                    </div>

                    <!-- MAP & PREVIEW -->
                    <div>
                        <label>Google Maps Preview</label>
                        <div id="map">
                            <?php 
                            $defaultLat = $settings['location_lat'] ?? '-6.792354';
                            $defaultLng = $settings['location_lng'] ?? '39.208328';
                            $mapEmbedUrl = "https://maps.google.com/maps?q={$defaultLat},{$defaultLng}&t=&z=15&ie=UTF8&iwloc=&output=embed";
                            ?>
                            <iframe 
                                src="<?php echo htmlspecialchars($mapEmbedUrl); ?>" 
                                width="100%" 
                                height="100%" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                            </iframe>
                        </div>
                        <div class="location-preview-card" id="locationPreview">
                            <div class="preview-item">
                                <i class="fas fa-store"></i>
                                <strong>Store:</strong> 
                                <span id="previewName"><?php echo htmlspecialchars($settings['location_name'] ?? 'SCI-CALC Store'); ?></span>
                            </div>
                            <div class="preview-item">
                                <i class="fas fa-map-pin"></i>
                                <strong>Address:</strong> 
                                <span id="previewAddress"><?php echo htmlspecialchars($settings['location_address'] ?? ''); ?></span>
                            </div>
                            <div class="preview-item">
                                <i class="fas fa-clock"></i>
                                <strong>Hours:</strong> 
                                <span id="previewHours"><?php echo htmlspecialchars($settings['working_hours'] ?? 'Mon-Fri: 8:00 AM - 6:00 PM'); ?></span>
                            </div>
                            <div class="preview-item">
                                <i class="fas fa-globe"></i>
                                <strong>Coordinates:</strong> 
                                <span id="previewCoords"><?php echo htmlspecialchars($settings['location_lat'] ?? '-6.792354'); ?>, <?php echo htmlspecialchars($settings['location_lng'] ?? '39.208328'); ?></span>
                            </div>
                        </div>
                        <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                            <button type="button" class="btn btn-primary" onclick="updateGoogleMap()">
                                <i class="fas fa-map"></i> Update Map
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="getCurrentLocation()">
                                <i class="fas fa-location-dot"></i> Current Location
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="resetLocation()">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SOCIAL MEDIA -->
            <div class="settings-card">
                <h3><i class="fas fa-share-alt"></i> Social Media</h3>
                <div class="form-group">
                    <label><i class="fab fa-whatsapp" style="color:#25D366;"></i> WhatsApp Group Link</label>
                    <input type="url" name="whatsapp_group" value="<?php echo htmlspecialchars($settings['whatsapp_group'] ?? ''); ?>" placeholder="https://chat.whatsapp.com/...">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-instagram" style="color:#E4405F;"></i> Instagram Link</label>
                    <input type="url" name="instagram_link" value="<?php echo htmlspecialchars($settings['instagram_link']); ?>" placeholder="https://instagram.com/...">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-facebook" style="color:#1877F2;"></i> Facebook Link</label>
                    <input type="url" name="facebook_link" value="<?php echo htmlspecialchars($settings['facebook_link'] ?? ''); ?>" placeholder="https://facebook.com/...">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-twitter" style="color:#1DA1F2;"></i> Twitter/X Link</label>
                    <input type="url" name="twitter_link" value="<?php echo htmlspecialchars($settings['twitter_link'] ?? ''); ?>" placeholder="https://twitter.com/...">
                </div>
                <div class="form-group">
                    <label><i class="fab fa-linkedin" style="color:#0A66C2;"></i> LinkedIn Link</label>
                    <input type="url" name="linkedin_link" value="<?php echo htmlspecialchars($settings['linkedin_link'] ?? ''); ?>" placeholder="https://linkedin.com/...">
                </div>
            </div>

            <!-- HERO, ABOUT & FOOTER -->
            <div class="settings-card full-width">
                <h3><i class="fas fa-file-alt"></i> Hero, About & Footer</h3>
                <div class="form-group">
                    <label>Hero Text</label>
                    <textarea name="hero_text" rows="2" placeholder="Welcome message for homepage"><?php echo htmlspecialchars($settings['hero_text']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>About Information</label>
                    <textarea name="about_info" rows="3" placeholder="About SCI-CALC..."><?php echo htmlspecialchars($settings['about_info']); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Footer Information</label>
                    <textarea name="footer_info" rows="2" placeholder="Footer text..."><?php echo htmlspecialchars($settings['footer_info']); ?></textarea>
                </div>
            </div>

            <!-- IMPACT STATS -->
            <div class="settings-card full-width">
                <h3><i class="fas fa-chart-line"></i> Our Impact Stats</h3>
                <p style="color:var(--gray);font-size:14px;margin-bottom:15px;">Update the numbers shown in the "Our Impact" section on the website</p>
                <div class="impact-grid">
                    <div class="impact-item">
                        <div class="impact-icon">😊</div>
                        <label>Happy Customers</label>
                        <input type="number" name="happy_customers" value="<?php echo $settings['happy_customers'] ?? 500; ?>" min="0">
                    </div>
                    <div class="impact-item">
                        <div class="impact-icon">🛒</div>
                        <label>Products Sold</label>
                        <input type="number" name="products_sold" value="<?php echo $settings['products_sold'] ?? 50; ?>" min="0">
                    </div>
                    <div class="impact-item">
                        <div class="impact-icon">🧮</div>
                        <label>Calculator Models</label>
                        <input type="number" name="calculator_models" value="<?php echo $settings['calculator_models'] ?? 15; ?>" min="0">
                    </div>
                    <div class="impact-item">
                        <div class="impact-icon">⭐</div>
                        <label>Rating (out of 5)</label>
                        <input type="number" name="rating" value="<?php echo $settings['rating'] ?? 4.8; ?>" min="0" max="5" step="0.1">
                    </div>
                </div>
            </div>

            <!-- SAVE SETTINGS BUTTON -->
            <div class="settings-card full-width">
                <div class="btn-group">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <i class="fas fa-save"></i> Save All Settings
                    </button>
                    <button type="reset" class="btn btn-secondary" onclick="return confirm('Reset all fields to current values?')">
                        <i class="fas fa-undo"></i> Reset
                    </button>
                </div>
            </div>

        </div>
    </form>

    <!-- CHANGE PASSWORD FORM -->
    <form method="POST" action="" id="passwordForm" style="margin-top:24px;">
        <div class="settings-container">
            <div class="settings-card full-width">
                <h3><i class="fas fa-key"></i> Change Admin Password</h3>
                <p style="color:var(--gray);font-size:14px;margin-bottom:20px;">
                    Update your admin password for better security.
                </p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                    <div>
                        <div class="form-group">
                            <label>Current Password <span class="required">*</span></label>
                            <div style="position:relative;">
                                <input type="password" name="current_password" id="currentPassword" 
                                       placeholder="Enter current password">
                                <button type="button" class="toggle-pw-btn" 
                                        onclick="togglePassword('currentPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>New Password <span class="required">*</span></label>
                            <div style="position:relative;">
                                <input type="password" name="new_password" id="newPassword" 
                                       placeholder="Enter new strong password"
                                       oninput="checkPasswordStrength(this.value)">
                                <button type="button" class="toggle-pw-btn" 
                                        onclick="togglePassword('newPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            
                            <div id="pwStrengthMeter" style="display:none;margin-top:10px;">
                                <div style="display:flex;gap:4px;margin-bottom:6px;">
                                    <div class="pw-bar"></div>
                                    <div class="pw-bar"></div>
                                    <div class="pw-bar"></div>
                                    <div class="pw-bar"></div>
                                </div>
                                <div id="pwStrengthText" style="font-size:11px;font-weight:600;display:flex;align-items:center;gap:5px;"></div>
                            </div>
                            
                            <div id="pwRequirements" style="display:none;background:var(--bg);border-radius:8px;padding:10px 12px;margin-top:10px;">
                                <div style="font-size:11px;font-weight:700;color:var(--gray);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:6px;">
                                    <i class="fas fa-list-check"></i> Requirements
                                </div>
                                <ul id="pwReqList" style="list-style:none;display:grid;grid-template-columns:1fr 1fr;gap:4px;">
                                    <li data-req="length" style="font-size:11px;color:var(--gray);display:flex;align-items:center;gap:5px;transition:all 0.3s;">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> 8+ characters
                                    </li>
                                    <li data-req="uppercase" style="font-size:11px;color:var(--gray);display:flex;align-items:center;gap:5px;transition:all 0.3s;">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> Uppercase
                                    </li>
                                    <li data-req="lowercase" style="font-size:11px;color:var(--gray);display:flex;align-items:center;gap:5px;transition:all 0.3s;">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> Lowercase
                                    </li>
                                    <li data-req="number" style="font-size:11px;color:var(--gray);display:flex;align-items:center;gap:5px;transition:all 0.3s;">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> Number
                                    </li>
                                    <li data-req="special" style="font-size:11px;color:var(--gray);display:flex;align-items:center;gap:5px;transition:all 0.3s;">
                                        <i class="fas fa-circle" style="font-size:6px;"></i> Special char
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Confirm New Password <span class="required">*</span></label>
                            <div style="position:relative;">
                                <input type="password" name="confirm_password" id="confirmPassword" 
                                       placeholder="Re-enter new password">
                                <button type="button" class="toggle-pw-btn" 
                                        onclick="togglePassword('confirmPassword', this)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div style="background:linear-gradient(135deg, rgba(108,99,255,0.08), rgba(255,107,107,0.08));border:2px dashed var(--primary);border-radius:12px;padding:20px;height:100%;display:flex;flex-direction:column;justify-content:center;">
                            <div style="text-align:center;">
                                <div style="font-size:48px;color:var(--primary);margin-bottom:15px;">
                                    <i class="fas fa-shield-alt"></i>
                                </div>
                                <h4 style="font-size:16px;color:var(--dark);margin-bottom:8px;font-weight:700;">
                                    Password Security
                                </h4>
                                <p style="font-size:13px;color:var(--gray);line-height:1.6;margin-bottom:15px;">
                                    Strong password must contain:
                                </p>
                                <ul style="list-style:none;text-align:left;font-size:12px;color:var(--gray);line-height:2;">
                                    <li><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> At least 8 characters</li>
                                    <li><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> Uppercase letter (A-Z)</li>
                                    <li><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> Lowercase letter (a-z)</li>
                                    <li><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> Number (0-9)</li>
                                    <li><i class="fas fa-check-circle" style="color:var(--success);margin-right:8px;"></i> Special character (!@#$%&*)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="btn-group" style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" id="changePwBtn">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </div>
            </div>
        </div>
    </form>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // ========================================
    // 1. UPDATE GOOGLE MAP - FIXED FOR CORRECT LOCATION
    // ========================================
    window.updateGoogleMap = function() {
        const lat = document.getElementById('location_lat').value.trim() || '-6.792354';
        const lng = document.getElementById('location_lng').value.trim() || '39.208328';
        const mapContainer = document.getElementById('map');
        
        const embedUrl = `https://maps.google.com/maps?q=${lat},${lng}&t=&z=15&ie=UTF8&iwloc=&output=embed`;
        
        document.getElementById('locationGoogleMaps').value = embedUrl;
        
        mapContainer.innerHTML = `
            <iframe 
                src="${embedUrl}" 
                width="100%" 
                height="100%" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        `;
        
        updatePreview();
    };

    // ========================================
    // 2. SEARCH LOCATION
    // ========================================
    window.searchLocation = function() {
        const query = document.getElementById('locationSearch').value.trim();
        const resultsContainer = document.getElementById('searchResults');
        const searchBtn = document.getElementById('searchBtn');
        
        if (!query) {
            resultsContainer.innerHTML = `<div style="color:var(--gray);padding:10px;text-align:center;">Please enter a place name</div>`;
            return;
        }

        resultsContainer.innerHTML = `<div class="search-loading"><i class="fas fa-spinner fa-spin"></i> Searching...</div>`;
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=5&addressdetails=1&accept-language=en`;

        fetch(url, {
            headers: { 'User-Agent': 'SCI-CALC Admin Panel' }
        })
        .then(response => response.json())
        .then(data => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            
            if (data.length === 0) {
                resultsContainer.innerHTML = `<div style="color:var(--gray);padding:10px;text-align:center;">No results found</div>`;
                return;
            }

            let html = '';
            data.forEach(item => {
                const name = item.display_name.split(',').slice(0, 3).join(', ');
                const lat = parseFloat(item.lat).toFixed(6);
                const lon = parseFloat(item.lon).toFixed(6);
                html += `
                    <div class="search-result-item" onclick="selectLocation('${lat}', '${lon}', '${item.display_name.replace(/'/g, "\\'")}')">
                        <div>
                            <div class="result-name"><i class="fas fa-map-pin"></i> ${name}</div>
                            <div class="result-address">${item.display_name}</div>
                        </div>
                        <button class="btn-select">Select</button>
                    </div>
                `;
            });
            resultsContainer.innerHTML = html;
        })
        .catch(error => {
            searchBtn.disabled = false;
            searchBtn.innerHTML = '<i class="fas fa-search"></i> Search';
            resultsContainer.innerHTML = `<div style="color:var(--danger);padding:10px;text-align:center;">Error: ${error.message}</div>`;
        });
    };

    // ========================================
    // 3. SELECT LOCATION
    // ========================================
    window.selectLocation = function(lat, lng, address) {
        document.getElementById('location_lat').value = lat;
        document.getElementById('location_lng').value = lng;
        document.getElementById('locationAddress').value = address;
        document.getElementById('locationSearch').value = '';
        document.getElementById('searchResults').innerHTML = '';
        
        updateGoogleMap();
        updatePreview();
        
        const resultsContainer = document.getElementById('searchResults');
        resultsContainer.innerHTML = `
            <div style="color:var(--success);padding:8px 12px;background:rgba(0,201,167,0.08);border-radius:8px;border-left:3px solid var(--success);">
                <i class="fas fa-check-circle"></i> Location selected!
            </div>
        `;
        setTimeout(() => { resultsContainer.innerHTML = ''; }, 3000);
    };

    // ========================================
    // 4. UPDATE PREVIEW
    // ========================================
    function updatePreview() {
        const name = document.querySelector('input[name="location_name"]')?.value || 'SCI-CALC Store';
        const address = document.querySelector('textarea[name="location_address"]')?.value || 'No address set';
        const hours = document.querySelector('input[name="working_hours"]')?.value || 'Mon-Fri: 8:00 AM - 6:00 PM';
        const lat = document.getElementById('location_lat').value || '-6.792354';
        const lng = document.getElementById('location_lng').value || '39.208328';
        
        document.getElementById('previewName').textContent = name;
        document.getElementById('previewAddress').textContent = address;
        document.getElementById('previewHours').textContent = hours;
        document.getElementById('previewCoords').textContent = lat + ', ' + lng;
    }

    // ========================================
    // 5. GET CURRENT LOCATION
    // ========================================
    window.getCurrentLocation = function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude.toFixed(6);
                    const lng = position.coords.longitude.toFixed(6);
                    document.getElementById('location_lat').value = lat;
                    document.getElementById('location_lng').value = lng;
                    
                    updateGoogleMap();
                    updatePreview();
                    
                    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`, {
                        headers: { 'User-Agent': 'SCI-CALC Admin Panel' }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.display_name) {
                            document.getElementById('locationAddress').value = data.display_name;
                            updatePreview();
                        }
                    })
                    .catch(() => {});
                },
                function(error) {
                    alert('Unable to get location: ' + error.message);
                }
            );
        } else {
            alert('Geolocation is not supported.');
        }
    };

    // ========================================
    // 6. RESET LOCATION
    // ========================================
    window.resetLocation = function() {
        document.getElementById('location_lat').value = '-6.792354';
        document.getElementById('location_lng').value = '39.208328';
        document.getElementById('locationAddress').value = 'Dar es Salaam, Tanzania';
        
        updateGoogleMap();
        updatePreview();
    };

    // ========================================
    // 7. LOGO UPLOAD PREVIEW
    // ========================================
    document.getElementById('logoInput').addEventListener('change', function(e) {
        const fileName = document.getElementById('fileName');
        if (this.files && this.files.length > 0) {
            fileName.textContent = this.files[0].name;
            const reader = new FileReader();
            reader.onload = function(e) {
                const currentLogo = document.querySelector('.current-logo');
                currentLogo.innerHTML = `<img src="${e.target.result}" alt="Logo Preview">`;
            };
            reader.readAsDataURL(this.files[0]);
        } else {
            fileName.textContent = 'No file chosen';
        }
    });

    // ========================================
    // 8. LIVE PREVIEW UPDATES
    // ========================================
    document.querySelectorAll('input[name="location_name"], textarea[name="location_address"], input[name="working_hours"]').forEach(field => {
        field.addEventListener('input', updatePreview);
    });

    document.getElementById('location_lat').addEventListener('input', function() {
        updateGoogleMap();
        updatePreview();
    });

    document.getElementById('location_lng').addEventListener('input', function() {
        updateGoogleMap();
        updatePreview();
    });

    // ========================================
    // 9. PASSWORD TOGGLE
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
    };

    // ========================================
    // 10. PASSWORD STRENGTH CHECKER
    // ========================================
    window.checkPasswordStrength = function(val) {
        const meter = document.getElementById('pwStrengthMeter');
        const reqDiv = document.getElementById('pwRequirements');
        const strengthText = document.getElementById('pwStrengthText');
        const reqList = document.getElementById('pwReqList');
        
        if (!meter) return;
        
        if (val.length === 0) {
            meter.style.display = 'none';
            reqDiv.style.display = 'none';
            return;
        }
        
        meter.style.display = 'block';
        reqDiv.style.display = 'block';
        
        const checks = {
            length: val.length >= 8,
            uppercase: /[A-Z]/.test(val),
            lowercase: /[a-z]/.test(val),
            number: /[0-9]/.test(val),
            special: /[^A-Za-z0-9]/.test(val)
        };
        
        Object.keys(checks).forEach(key => {
            const li = reqList.querySelector(`li[data-req="${key}"]`);
            if (li) {
                if (checks[key]) {
                    li.style.color = 'var(--success)';
                    li.querySelector('i').style.color = 'var(--success)';
                    li.querySelector('i').className = 'fas fa-check-circle';
                } else {
                    li.style.color = 'var(--gray)';
                    li.querySelector('i').style.color = 'var(--gray)';
                    li.querySelector('i').className = 'fas fa-circle';
                }
            }
        });
        
        const metCount = Object.values(checks).filter(v => v).length;
        const allMet = metCount === 5;
        
        const bars = meter.querySelectorAll('.pw-bar');
        bars.forEach((bar, index) => {
            bar.style.background = 'var(--light-gray)';
            
            let fillCount = 0;
            if (allMet) fillCount = 4;
            else if (metCount >= 4) fillCount = 3;
            else if (metCount >= 3) fillCount = 2;
            else if (metCount >= 1) fillCount = 1;
            
            if (index < fillCount) {
                if (allMet) {
                    bar.style.background = '#00C9A7';
                } else if (metCount >= 4) {
                    bar.style.background = '#FFC107';
                } else if (metCount >= 3) {
                    bar.style.background = '#FFC107';
                } else {
                    bar.style.background = '#FF6B6B';
                }
            }
        });
        
        let text, color, icon;
        if (allMet) {
            text = 'Very Strong Password';
            color = '#00A889';
            icon = 'fa-shield-check';
        } else if (metCount >= 4) {
            text = 'Strong Password (Missing: ' + getMissing(checks) + ')';
            color = '#FFC107';
            icon = 'fa-shield-halved';
        } else if (metCount >= 3) {
            text = 'Medium Password';
            color = '#FFC107';
            icon = 'fa-shield-halved';
        } else {
            text = 'Weak Password';
            color = '#FF6B6B';
            icon = 'fa-shield-alt';
        }
        
        strengthText.innerHTML = `<i class="fas ${icon}" style="color:${color};"></i><span style="color:${color};">${text}</span>`;
    };

    function getMissing(checks) {
        const missing = [];
        if (!checks.uppercase) missing.push('Uppercase');
        if (!checks.lowercase) missing.push('Lowercase');
        if (!checks.number) missing.push('Number');
        if (!checks.special) missing.push('Special');
        if (!checks.length) missing.push('8+ chars');
        return missing.join(', ');
    }

    // ========================================
    // 11. PASSWORD FORM VALIDATION
    // ========================================
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', function(e) {
            const currentPw = document.getElementById('currentPassword').value;
            const newPw = document.getElementById('newPassword').value;
            const confirmPw = document.getElementById('confirmPassword').value;
            
            if (!currentPw && !newPw && !confirmPw) {
                return true;
            }
            
            let hasError = false;
            
            if (!currentPw) {
                e.preventDefault();
                showAlert('Please enter your current password!', 'danger');
                hasError = true;
            } else if (!newPw) {
                e.preventDefault();
                showAlert('Please enter a new password!', 'danger');
                hasError = true;
            } else if (!confirmPw) {
                e.preventDefault();
                showAlert('Please confirm your new password!', 'danger');
                hasError = true;
            } else if (newPw.length < 8) {
                e.preventDefault();
                showAlert('Password must be at least 8 characters!', 'danger');
                hasError = true;
            } else if (!/[A-Z]/.test(newPw)) {
                e.preventDefault();
                showAlert('Password must contain an UPPERCASE letter!', 'danger');
                hasError = true;
            } else if (!/[a-z]/.test(newPw)) {
                e.preventDefault();
                showAlert('Password must contain a lowercase letter!', 'danger');
                hasError = true;
            } else if (!/[0-9]/.test(newPw)) {
                e.preventDefault();
                showAlert('Password must contain a number!', 'danger');
                hasError = true;
            } else if (!/[^A-Za-z0-9]/.test(newPw)) {
                e.preventDefault();
                showAlert('Password must contain a special character!', 'danger');
                hasError = true;
            } else if (newPw !== confirmPw) {
                e.preventDefault();
                showAlert('Passwords do not match!', 'danger');
                hasError = true;
            }
            
            if (!hasError) {
                const btn = document.getElementById('changePwBtn');
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Changing...';
                btn.disabled = true;
            }
            
            return !hasError;
        });
    }

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.style.cssText = `
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-100px);
            z-index: 9999;
            max-width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        `;
        alertDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        document.body.appendChild(alertDiv);
        
        requestAnimationFrame(() => {
            alertDiv.style.transition = 'transform 0.3s ease';
            alertDiv.style.transform = 'translateX(-50%) translateY(0)';
        });
        
        setTimeout(() => {
            alertDiv.style.transform = 'translateX(-50%) translateY(-100px)';
            setTimeout(() => alertDiv.remove(), 400);
        }, 3500);
    }

    // ========================================
    // 12. SETTINGS FORM VALIDATION
    // ========================================
    document.getElementById('settingsForm').addEventListener('submit', function(e) {
        const siteName = this.querySelector('input[name="site_name"]').value.trim();
        
        if (!siteName) {
            e.preventDefault();
            showAlert('Please enter site name!', 'danger');
            return false;
        }
        
        const btn = document.getElementById('submitBtn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }, 3000);
        
        return true;
    });

    // ========================================
    // 13. IMPACT STATS ANIMATION
    // ========================================
    document.querySelectorAll('.impact-item input').forEach(input => {
        input.addEventListener('input', function() {
            const parent = this.closest('.impact-item');
            const icon = parent.querySelector('.impact-icon');
            
            if (this.value && parseInt(this.value) > 0) {
                parent.style.borderColor = '#00C9A7';
                parent.style.background = 'rgba(0,201,167,0.04)';
            } else {
                parent.style.borderColor = '#FF6B6B';
                parent.style.background = 'rgba(255,107,107,0.04)';
            }
            
            icon.style.transition = 'transform 0.3s';
            icon.style.transform = 'scale(1.3)';
            setTimeout(() => {
                icon.style.transform = 'scale(1)';
            }, 300);
        });
    });

    // ========================================
    // 14. INITIALIZE
    // ========================================
    updatePreview();

    console.log('%c⚙️ SCI-CALC Settings', 'font-size:24px;font-weight:800;color:#6C63FF;');
    console.log('%c💰 Payment Information section added!', 'font-size:14px;color:#2D3436;');
    console.log('%c📍 Google Maps fixed - shows correct location!', 'font-size:14px;color:#2D3436;');
    console.log('%c🔑 Change Password feature added!', 'font-size:14px;color:#2D3436;');
    console.log('%c✅ Settings page loaded successfully!', 'font-size:14px;color:#00C9A7;');
});
</script>

</body>
</html>