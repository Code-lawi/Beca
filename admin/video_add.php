<?php
require_once '../config/database.php';
requireAdminLogin();

$error = '';
$success = '';
$title = '';
$description = '';
$status = 1;
$show_on_homepage = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = isset($_POST['status']) ? 1 : 0;
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    
    // Validate
    if (empty($title) || empty($description)) {
        $error = 'Please fill all required fields!';
    } else {
        $video_name = '';
        $thumbnail_name = '';
        
        // Handle video upload
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['video'];
            $allowed_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
            $max_size = 100 * 1024 * 1024; // 100MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Only MP4, WEBM, OGG, and MOV videos are allowed!';
            } elseif ($file['size'] > $max_size) {
                $error = 'Video size must be less than 100MB!';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $video_name = time() . '_' . uniqid() . '.' . $ext;
                $target_path = VIDEO_PATH . $video_name;
                
                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $error = 'Failed to upload video!';
                }
            }
        } else {
            $error = 'Please select a video file!';
        }
        
        // Handle thumbnail upload
        if (empty($error)) {
            if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['thumbnail'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024;
                
                if (!in_array($file['type'], $allowed_types)) {
                    $error = 'Only JPG, PNG, GIF, and WEBP thumbnails are allowed!';
                } elseif ($file['size'] > $max_size) {
                    $error = 'Thumbnail size must be less than 5MB!';
                } else {
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $thumbnail_name = time() . '_thumb_' . uniqid() . '.' . $ext;
                    $target_path = VIDEO_PATH . $thumbnail_name;
                    
                    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                        $error = 'Failed to upload thumbnail!';
                    }
                }
            } else {
                // Generate thumbnail from video if not uploaded
                // For now, use a default thumbnail
                $thumbnail_name = 'default_thumb.jpg';
                // In production, you'd use FFmpeg to generate thumbnail
            }
        }
        
        // Insert into database
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO videos (title, description, video_url, thumbnail, status, show_on_homepage) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssii", $title, $description, $video_name, $thumbnail_name, $status, $show_on_homepage);
            
            if ($stmt->execute()) {
                $success = 'Video added successfully! 🎉';
                $title = $description = '';
                $status = 1;
                $show_on_homepage = 1;
            } else {
                $error = 'Failed to add video: ' . $conn->error;
                // Delete uploaded files if database insert fails
                if (!empty($video_name) && file_exists(VIDEO_PATH . $video_name)) {
                    unlink(VIDEO_PATH . $video_name);
                }
                if (!empty($thumbnail_name) && file_exists(VIDEO_PATH . $thumbnail_name)) {
                    unlink(VIDEO_PATH . $thumbnail_name);
                }
            }
            $stmt->close();
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
    <title>Add Video - SCI-CALC Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ============================================
           ROOT VARIABLES
        ============================================ */
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

        /* ============================================
           SCROLLBAR
        ============================================ */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }

        /* ============================================
           SIDEBAR
        ============================================ */
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

        .sidebar-brand h2 span {
            color: var(--primary-light);
        }

        .sidebar-brand h2 .brand-icon {
            font-size: 28px;
        }

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

        .sidebar-menu li {
            margin-bottom: 2px;
        }

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

        .sidebar-menu li a .menu-text {
            flex: 1;
        }

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
            transition: var(--transition);
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

        /* ============================================
           MAIN CONTENT
        ============================================ */
        .main-content {
            margin-left: var(--sidebar-width);
            flex: 1;
            padding: 28px 32px 40px;
            min-height: 100vh;
        }

        /* ============================================
           TOP BAR
        ============================================ */
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
            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        .top-bar h1 i {
            color: var(--danger);
        }

        .top-bar .back-link {
            color: var(--gray);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .top-bar .back-link:hover {
            color: var(--primary);
            transform: translateX(-4px);
        }

        /* ============================================
           ALERTS
        ============================================ */
        .alert {
            padding: 14px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease forwards;
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

        /* ============================================
           FORM CONTAINER
        ============================================ */
        .form-container {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px 36px 36px;
            box-shadow: var(--shadow);
            max-width: 820px;
            animation: cardFadeIn 0.6s ease forwards;
            animation-delay: 0.2s;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes cardFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-container .form-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray);
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--bg);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-container .form-title i {
            color: var(--danger);
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 6px;
            font-size: 13px;
        }

        .form-group label .required {
            color: var(--danger);
            margin-left: 2px;
        }

        .form-group label .label-icon {
            margin-right: 6px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 11px 16px;
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

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #B0B8C8;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-group .helper-text {
            font-size: 12px;
            color: var(--gray);
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .form-group .helper-text i {
            font-size: 12px;
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed var(--light-gray);
            border-radius: var(--radius-sm);
            padding: 30px 20px;
            text-align: center;
            transition: var(--transition);
            cursor: pointer;
            background: var(--bg);
        }

        .file-upload-wrapper:hover {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.03);
        }

        .file-upload-wrapper.dragover {
            border-color: var(--primary);
            background: rgba(108, 99, 255, 0.06);
        }

        .file-upload-wrapper .upload-icon {
            font-size: 40px;
            color: var(--light-gray);
            margin-bottom: 8px;
            transition: var(--transition);
        }

        .file-upload-wrapper:hover .upload-icon {
            color: var(--primary);
        }

        .file-upload-wrapper .upload-text {
            font-weight: 500;
            color: var(--gray);
            font-size: 14px;
        }

        .file-upload-wrapper .upload-sub {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        .file-upload-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        /* Video Preview */
        .video-preview {
            margin-top: 14px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid var(--light-gray);
            padding: 6px;
            display: none;
            background: #000;
        }

        .video-preview.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .video-preview video {
            width: 100%;
            max-height: 300px;
            border-radius: 4px;
            display: block;
        }

        /* Image Preview */
        .image-preview {
            margin-top: 14px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid var(--light-gray);
            padding: 8px;
            display: none;
            background: var(--bg);
            max-width: 200px;
        }

        .image-preview.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            display: block;
        }

        .image-preview .preview-actions {
            display: flex;
            gap: 8px;
            margin-top: 8px;
            justify-content: center;
        }

        .image-preview .preview-actions button {
            padding: 4px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
        }

        .image-preview .preview-actions .btn-remove {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .image-preview .preview-actions .btn-remove:hover {
            background: var(--danger);
            color: white;
        }

        /* Checkbox */
        .form-check {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 6px 0;
        }

        .form-check input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary);
            flex-shrink: 0;
        }

        .form-check label {
            margin-bottom: 0;
            cursor: pointer;
            font-weight: 500;
            color: var(--dark);
            font-size: 14px;
        }

        .form-check .check-sub {
            font-size: 12px;
            color: var(--gray);
            font-weight: 400;
        }

        .checkbox-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        /* ============================================
           BUTTONS
        ============================================ */
        .btn {
            padding: 11px 28px;
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

        .btn-success {
            background: var(--success);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 201, 167, 0.3);
        }

        .btn-success:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 201, 167, 0.4);
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
            margin-top: 8px;
            flex-wrap: wrap;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 992px) {
            :root {
                --sidebar-width: 72px;
            }

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

            .sidebar-menu li a .icon {
                font-size: 22px;
                width: auto;
            }

            .sidebar-footer a {
                justify-content: center;
                padding: 14px;
            }

            .sidebar-footer a .icon {
                font-size: 22px;
            }

            .main-content {
                padding: 20px 16px;
            }

            .form-container {
                padding: 24px;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 16px;
            }

            .top-bar h1 {
                font-size: 18px;
            }

            .form-container {
                padding: 18px;
            }

            .main-content {
                padding: 12px 12px 30px;
            }

            .btn-group {
                flex-direction: column;
                width: 100%;
            }

            .btn-group .btn {
                width: 100%;
                justify-content: center;
            }

            .file-upload-wrapper {
                padding: 20px 16px;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
            }

            .video-preview video {
                max-height: 180px;
            }

            .image-preview {
                max-width: 100%;
            }
        }

        @media (max-width: 400px) {
            .form-container {
                padding: 14px;
            }
        }
    </style>
</head>
<body>

<!-- ============================================
     SIDEBAR
============================================ -->
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
            <a href="videos.php" class="active">
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
            <a href="settings.php">
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

<!-- ============================================
     MAIN CONTENT
============================================ -->
<div class="main-content">

    <!-- Top Bar -->
    <div class="top-bar">
        <h1>
            <i class="fas fa-plus-circle"></i>
            Add New Video
        </h1>
        <a href="videos.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Videos
        </a>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
            <a href="videos.php" style="margin-left:auto;color:var(--success);font-weight:600;text-decoration:underline;">
                View Videos →
            </a>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Form -->
    <div class="form-container">
        <div class="form-title">
            <i class="fas fa-video"></i>
            Video Information
        </div>

        <form method="POST" enctype="multipart/form-data" id="videoForm" novalidate>

            <!-- Video File -->
            <div class="form-group">
                <label>
                    <span class="label-icon">🎬</span> Video File <span class="required">*</span>
                </label>
                <div class="file-upload-wrapper" id="videoDropZone">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-text">Click or drag &amp; drop to upload video</div>
                    <div class="upload-sub">MP4, WEBM, OGG, MOV (Max 100MB)</div>
                    <input type="file" name="video" id="videoInput" accept="video/*" required>
                </div>
                <div class="video-preview" id="videoPreview">
                    <video id="previewVideo" controls></video>
                </div>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    Recommended: MP4 format for best compatibility
                </div>
            </div>

            <!-- Thumbnail -->
            <div class="form-group">
                <label>
                    <span class="label-icon">🖼️</span> Thumbnail Image
                </label>
                <div class="file-upload-wrapper" id="thumbDropZone">
                    <div class="upload-icon"><i class="fas fa-image"></i></div>
                    <div class="upload-text">Click or drag &amp; drop to upload thumbnail</div>
                    <div class="upload-sub">JPG, PNG, GIF, WEBP (Max 5MB) • Leave empty for auto-generated</div>
                    <input type="file" name="thumbnail" id="thumbInput" accept="image/*">
                </div>
                <div class="image-preview" id="thumbPreview">
                    <img id="previewThumb" src="#" alt="Thumbnail Preview">
                    <div class="preview-actions">
                        <button type="button" class="btn-remove" onclick="removeThumbnail()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    Recommended: 16:9 ratio, at least 640x360px
                </div>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label>
                    <span class="label-icon">📝</span> Video Title <span class="required">*</span>
                </label>
                <input type="text" name="title" id="videoTitle" 
                       placeholder="e.g., How to use Casio fx-991ES Plus" 
                       value="<?php echo htmlspecialchars($title); ?>" required>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    <span id="charCount">0</span> characters
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>
                    <span class="label-icon">📄</span> Description <span class="required">*</span>
                </label>
                <textarea name="description" id="videoDescription" 
                          placeholder="Describe what this video is about..." 
                          required><?php echo htmlspecialchars($description); ?></textarea>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    <span id="descCharCount">0</span> characters
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="checkbox-grid">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="status" id="status" <?php echo $status ? 'checked' : ''; ?>>
                        <label for="status">
                            <i class="fas fa-eye" style="color:var(--success);"></i>
                            Visible
                            <span class="check-sub">— Show to customers</span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="show_on_homepage" id="homepage" <?php echo $show_on_homepage ? 'checked' : ''; ?>>
                        <label for="homepage">
                            <i class="fas fa-home" style="color:var(--primary);"></i>
                            Show on Homepage
                            <span class="check-sub">— Display on homepage</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="btn-group">
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-save"></i> Save Video
                </button>
                <button type="reset" class="btn btn-secondary" onclick="return confirm('Clear all fields?')">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <a href="videos.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT
============================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // ========================================
        // 1. VIDEO PREVIEW
        // ========================================
        const videoInput = document.getElementById('videoInput');
        const videoPreview = document.getElementById('videoPreview');
        const previewVideo = document.getElementById('previewVideo');
        const videoDropZone = document.getElementById('videoDropZone');

        videoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const url = URL.createObjectURL(file);
                previewVideo.src = url;
                videoPreview.classList.add('show');
                previewVideo.load();
                videoDropZone.style.borderColor = 'var(--success)';
                
                // Update file name display
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(1);
                const uploadText = videoDropZone.querySelector('.upload-text');
                uploadText.innerHTML = `<i class="fas fa-check-circle" style="color:var(--success);"></i> ${fileName} (${fileSize} MB)`;
            } else {
                videoPreview.classList.remove('show');
                previewVideo.src = '';
                videoDropZone.style.borderColor = '';
                const uploadText = videoDropZone.querySelector('.upload-text');
                uploadText.innerHTML = '<i class="fas fa-cloud-upload-alt" style="color:var(--gray);"></i> Click or drag & drop to upload video';
            }
        });

        // ========================================
        // 2. THUMBNAIL PREVIEW
        // ========================================
        const thumbInput = document.getElementById('thumbInput');
        const thumbPreview = document.getElementById('thumbPreview');
        const previewThumb = document.getElementById('previewThumb');
        const thumbDropZone = document.getElementById('thumbDropZone');

        thumbInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewThumb.src = e.target.result;
                    thumbPreview.classList.add('show');
                    thumbDropZone.style.borderColor = 'var(--success)';
                    
                    // Update file name display
                    const fileName = file.name;
                    const uploadText = thumbDropZone.querySelector('.upload-text');
                    uploadText.innerHTML = `<i class="fas fa-check-circle" style="color:var(--success);"></i> ${fileName}`;
                };
                reader.readAsDataURL(file);
            } else {
                removeThumbnail();
            }
        });

        window.removeThumbnail = function() {
            thumbInput.value = '';
            thumbPreview.classList.remove('show');
            previewThumb.src = '#';
            thumbDropZone.style.borderColor = '';
            const uploadText = thumbDropZone.querySelector('.upload-text');
            uploadText.innerHTML = '<i class="fas fa-image" style="color:var(--gray);"></i> Click or drag & drop to upload thumbnail';
        };

        // ========================================
        // 3. DRAG & DROP FOR VIDEO
        // ========================================
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            videoDropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            videoDropZone.addEventListener(eventName, function() {
                videoDropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            videoDropZone.addEventListener(eventName, function() {
                videoDropZone.classList.remove('dragover');
            }, false);
        });

        videoDropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                videoInput.files = files;
                videoInput.dispatchEvent(new Event('change'));
            }
        }, false);

        // ========================================
        // 4. DRAG & DROP FOR THUMBNAIL
        // ========================================
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            thumbDropZone.addEventListener(eventName, preventDefaults, false);
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            thumbDropZone.addEventListener(eventName, function() {
                thumbDropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            thumbDropZone.addEventListener(eventName, function() {
                thumbDropZone.classList.remove('dragover');
            }, false);
        });

        thumbDropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                thumbInput.files = files;
                thumbInput.dispatchEvent(new Event('change'));
            }
        }, false);

        // ========================================
        // 5. CHARACTER COUNTERS
        // ========================================
        const videoTitle = document.getElementById('videoTitle');
        const charCount = document.getElementById('charCount');

        videoTitle.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
        charCount.textContent = videoTitle.value.length;

        const videoDesc = document.getElementById('videoDescription');
        const descCharCount = document.getElementById('descCharCount');

        videoDesc.addEventListener('input', function() {
            descCharCount.textContent = this.value.length;
        });
        descCharCount.textContent = videoDesc.value.length;

        // ========================================
        // 6. FORM VALIDATION
        // ========================================
        document.getElementById('videoForm').addEventListener('submit', function(e) {
            const title = document.getElementById('videoTitle').value.trim();
            const description = document.getElementById('videoDescription').value.trim();
            const video = document.getElementById('videoInput').files[0];
            
            if (!title) {
                e.preventDefault();
                showFieldError('videoTitle', 'Please enter video title!');
                return false;
            }
            if (!description) {
                e.preventDefault();
                showFieldError('videoDescription', 'Please enter video description!');
                return false;
            }
            if (!video) {
                e.preventDefault();
                showFieldError('videoInput', 'Please select a video file!');
                return false;
            }
            
            // Show loading state
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

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            if (field) {
                field.style.borderColor = 'var(--danger)';
                field.focus();
                
                const existing = field.parentElement.querySelector('.field-error');
                if (existing) existing.remove();
                
                const error = document.createElement('div');
                error.className = 'field-error';
                error.style.cssText = `
                    color: var(--danger);
                    font-size: 12px;
                    margin-top: 4px;
                    font-weight: 500;
                `;
                error.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
                field.parentElement.appendChild(error);
                
                setTimeout(() => {
                    field.style.borderColor = '';
                    if (error.parentElement) error.remove();
                }, 5000);
            } else {
                // Fallback for file input
                alert(message);
            }
        }

        document.querySelectorAll('#videoTitle, #videoDescription').forEach(field => {
            field.addEventListener('input', function() {
                this.style.borderColor = '';
                const error = this.parentElement.querySelector('.field-error');
                if (error) error.remove();
            });
        });

        // ========================================
        // 7. KEYBOARD SHORTCUTS
        // ========================================
        document.addEventListener('keydown', function(e) {
            // Ctrl+S or Cmd+S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                document.getElementById('videoForm').dispatchEvent(new Event('submit'));
            }
            
            // ESC to cancel
            if (e.key === 'Escape') {
                if (confirm('Cancel and go back to videos?')) {
                    window.location.href = 'videos.php';
                }
            }
        });

        // ========================================
        // 8. CONSOLE WELCOME
        // ========================================
        console.log('%c🎬 SCI-CALC Add Video', 'font-size:24px;font-weight:800;color:#6C63FF;');
        console.log('%c📝 Fill in the form to add a new video', 'font-size:14px;color:#2D3436;');
        console.log('%c💡 Tip: Ctrl+S to save, ESC to cancel', 'font-size:12px;color:#8899AA;');
        console.log('%c✅ Add video page loaded successfully!', 'font-size:14px;color:#00C9A7;');
    });
</script>

</body>
</html>