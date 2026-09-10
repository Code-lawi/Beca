<?php
require_once '../config/database.php';
requireAdminLogin();

$error = '';
$success = '';

// Get video ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: videos.php');
    exit();
}

$id = $_GET['id'];

// Fetch video data
$stmt = $conn->prepare("SELECT * FROM videos WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: videos.php');
    exit();
}

$video = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status = isset($_POST['status']) ? 1 : 0;
    $show_on_homepage = isset($_POST['show_on_homepage']) ? 1 : 0;
    
    if (empty($title) || empty($description)) {
        $error = 'Please fill all required fields!';
    } else {
        $video_name = $video['video_url'];
        $thumbnail_name = $video['thumbnail'];
        
        // Handle new video upload
        if (isset($_FILES['video']) && $_FILES['video']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['video'];
            $allowed_types = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
            $max_size = 100 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Only MP4, WEBM, OGG, and MOV videos are allowed!';
            } elseif ($file['size'] > $max_size) {
                $error = 'Video size must be less than 100MB!';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_video = time() . '_' . uniqid() . '.' . $ext;
                $target_path = VIDEO_PATH . $new_video;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Delete old video
                    if (!empty($video['video_url']) && file_exists(VIDEO_PATH . $video['video_url'])) {
                        unlink(VIDEO_PATH . $video['video_url']);
                    }
                    $video_name = $new_video;
                } else {
                    $error = 'Failed to upload video!';
                }
            }
        }
        
        // Handle new thumbnail upload
        if (empty($error) && isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['thumbnail'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024;
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Only JPG, PNG, GIF, and WEBP thumbnails are allowed!';
            } elseif ($file['size'] > $max_size) {
                $error = 'Thumbnail size must be less than 5MB!';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $new_thumb = time() . '_thumb_' . uniqid() . '.' . $ext;
                $target_path = VIDEO_PATH . $new_thumb;
                
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    // Delete old thumbnail
                    if (!empty($video['thumbnail']) && file_exists(VIDEO_PATH . $video['thumbnail']) && $video['thumbnail'] !== 'default_thumb.jpg') {
                        unlink(VIDEO_PATH . $video['thumbnail']);
                    }
                    $thumbnail_name = $new_thumb;
                } else {
                    $error = 'Failed to upload thumbnail!';
                }
            }
        }
        
        // Update database
        if (empty($error)) {
            $stmt = $conn->prepare("UPDATE videos SET title = ?, description = ?, video_url = ?, thumbnail = ?, status = ?, show_on_homepage = ? WHERE id = ?");
            $stmt->bind_param("ssssiii", $title, $description, $video_name, $thumbnail_name, $status, $show_on_homepage, $id);
            
            if ($stmt->execute()) {
                $success = 'Video updated successfully! 🎉';
                // Refresh video data
                $video['title'] = $title;
                $video['description'] = $description;
                $video['status'] = $status;
                $video['show_on_homepage'] = $show_on_homepage;
                $video['video_url'] = $video_name;
                $video['thumbnail'] = $thumbnail_name;
            } else {
                $error = 'Failed to update video: ' . $conn->error;
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
    <title>Edit Video - SCI-CALC Admin</title>
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
            color: var(--warning);
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

        .top-bar .video-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: rgba(108, 99, 255, 0.1);
            color: var(--primary);
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
            justify-content: space-between;
            gap: 12px;
        }

        .form-container .form-title .video-id {
            font-size: 12px;
            color: var(--gray);
            font-weight: 400;
            background: var(--bg);
            padding: 4px 14px;
            border-radius: 20px;
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

        /* Current File Display */
        .current-file-wrapper {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 16px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            border: 2px solid var(--light-gray);
        }

        .current-file-wrapper .file-icon {
            font-size: 28px;
            width: 44px;
            height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .current-file-wrapper .file-info {
            flex: 1;
        }

        .current-file-wrapper .file-info .label {
            font-size: 11px;
            color: var(--gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .current-file-wrapper .file-info .filename {
            font-size: 13px;
            font-weight: 500;
            color: var(--dark);
            word-break: break-all;
        }

        .current-file-wrapper .file-size {
            font-size: 12px;
            color: var(--gray);
        }

        /* Current Thumbnail */
        .current-thumb-wrapper {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 10px 16px;
            background: var(--bg);
            border-radius: var(--radius-sm);
            margin-bottom: 10px;
            border: 2px solid var(--light-gray);
        }

        .current-thumb-wrapper img {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            border: 2px solid var(--white);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            background: white;
        }

        .current-thumb-wrapper .thumb-info {
            flex: 1;
        }

        .current-thumb-wrapper .thumb-info .label {
            font-size: 11px;
            color: var(--gray);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .current-thumb-wrapper .thumb-info .filename {
            font-size: 13px;
            font-weight: 500;
            color: var(--dark);
        }

        /* File Upload */
        .file-upload-wrapper {
            position: relative;
            border: 2px dashed var(--light-gray);
            border-radius: var(--radius-sm);
            padding: 16px 20px;
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

        .file-upload-wrapper .upload-text {
            font-weight: 500;
            color: var(--gray);
            font-size: 13px;
        }

        .file-upload-wrapper .upload-sub {
            font-size: 12px;
            color: var(--gray);
            margin-top: 2px;
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

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-dark);
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

            .current-file-wrapper {
                flex-wrap: wrap;
            }

            .current-thumb-wrapper {
                flex-wrap: wrap;
            }

            .form-container .form-title {
                flex-direction: column;
                align-items: flex-start;
            }

            .video-preview video {
                max-height: 180px;
            }

            .checkbox-grid {
                grid-template-columns: 1fr;
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
            <i class="fas fa-edit"></i>
            Edit Video
            <span class="video-badge">
                <i class="fas fa-hashtag"></i> ID: <?php echo $video['id']; ?>
            </span>
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
            <span><i class="fas fa-video" style="color:var(--danger);"></i> Edit Video Information</span>
            <span class="video-id">
                <i class="fas fa-clock"></i> Added: <?php echo date('M d, Y', strtotime($video['created_at'])); ?>
            </span>
        </div>

        <form method="POST" enctype="multipart/form-data" id="videoForm" novalidate>

            <!-- Video File -->
            <div class="form-group">
                <label>
                    <span class="label-icon">🎬</span> Video File
                </label>
                <div class="current-file-wrapper">
                    <div class="file-icon"><i class="fas fa-file-video"></i></div>
                    <div class="file-info">
                        <div class="label">Current Video</div>
                        <div class="filename"><?php echo htmlspecialchars($video['video_url']); ?></div>
                    </div>
                    <div class="file-size">
                        <?php
                        $file_path = VIDEO_PATH . $video['video_url'];
                        if (file_exists($file_path)) {
                            $size = filesize($file_path);
                            if ($size > 1048576) {
                                echo number_format($size / 1048576, 1) . ' MB';
                            } else {
                                echo number_format($size / 1024, 1) . ' KB';
                            }
                        }
                        ?>
                    </div>
                </div>

                <div class="file-upload-wrapper" id="videoDropZone">
                    <div class="upload-text">
                        <i class="fas fa-cloud-upload-alt" style="color:var(--primary);"></i>
                        Click or drag &amp; drop to change video
                    </div>
                    <div class="upload-sub">MP4, WEBM, OGG, MOV (Max 100MB) • Leave empty to keep current</div>
                    <input type="file" name="video" id="videoInput" accept="video/*">
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
                <div class="current-thumb-wrapper">
                    <img src="<?php echo '../uploads/videos/' . $video['thumbnail']; ?>" 
                         alt="Current thumbnail"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2260%22 height=%2260%22%3E%3Crect width=%2260%22 height=%2260%22 fill=%22%23f0f2f5%22/%3E%3Ctext x=%2212%22 y=%2238%22 font-size=%2224%22%3E🖼️%3C/text%3E%3C/svg%3E'">
                    <div class="thumb-info">
                        <div class="label">Current Thumbnail</div>
                        <div class="filename"><?php echo htmlspecialchars($video['thumbnail']); ?></div>
                    </div>
                </div>

                <div class="file-upload-wrapper" id="thumbDropZone">
                    <div class="upload-text">
                        <i class="fas fa-cloud-upload-alt" style="color:var(--primary);"></i>
                        Click or drag &amp; drop to change thumbnail
                    </div>
                    <div class="upload-sub">JPG, PNG, GIF, WEBP (Max 5MB) • Leave empty to keep current</div>
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
                       value="<?php echo htmlspecialchars($video['title']); ?>" required>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    <span id="charCount"><?php echo strlen($video['title']); ?></span> characters
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>
                    <span class="label-icon">📄</span> Description <span class="required">*</span>
                </label>
                <textarea name="description" id="videoDescription" 
                          placeholder="Describe what this video is about..." 
                          required><?php echo htmlspecialchars($video['description']); ?></textarea>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    <span id="descCharCount"><?php echo strlen($video['description']); ?></span> characters
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="checkbox-grid">
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="status" id="status" <?php echo $video['status'] ? 'checked' : ''; ?>>
                        <label for="status">
                            <i class="fas fa-eye" style="color:<?php echo $video['status'] ? 'var(--success)' : 'var(--gray)'; ?>;"></i>
                            Visible
                            <span class="check-sub">— <?php echo $video['status'] ? 'Currently visible' : 'Currently hidden'; ?></span>
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" name="show_on_homepage" id="homepage" <?php echo $video['show_on_homepage'] ? 'checked' : ''; ?>>
                        <label for="homepage">
                            <i class="fas fa-home" style="color:<?php echo $video['show_on_homepage'] ? 'var(--primary)' : 'var(--gray)'; ?>;"></i>
                            Show on Homepage
                            <span class="check-sub">— <?php echo $video['show_on_homepage'] ? 'Currently on homepage' : 'Not on homepage'; ?></span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="btn-group">
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-save"></i> Update Video
                </button>
                <button type="reset" class="btn btn-secondary" onclick="return confirm('Reset all fields to original values?')">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <a href="?delete=<?php echo $video['id']; ?>" class="btn btn-danger" 
                   onclick="return confirmDelete('<?php echo htmlspecialchars($video['title']); ?>')">
                    <i class="fas fa-trash-alt"></i> Delete
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
            } else {
                videoPreview.classList.remove('show');
                previewVideo.src = '';
                videoDropZone.style.borderColor = '';
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

        const videoDesc = document.getElementById('videoDescription');
        const descCharCount = document.getElementById('descCharCount');

        videoDesc.addEventListener('input', function() {
            descCharCount.textContent = this.value.length;
        });

        // ========================================
        // 6. FORM VALIDATION
        // ========================================
        document.getElementById('videoForm').addEventListener('submit', function(e) {
            const title = document.getElementById('videoTitle').value.trim();
            const description = document.getElementById('videoDescription').value.trim();
            
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
            
            // Show loading state
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            btn.disabled = true;
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 3000);
            
            return true;
        });

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
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
        }

        document.querySelectorAll('#videoTitle, #videoDescription').forEach(field => {
            field.addEventListener('input', function() {
                this.style.borderColor = '';
                const error = this.parentElement.querySelector('.field-error');
                if (error) error.remove();
            });
        });

        // ========================================
        // 7. CONFIRM DELETE
        // ========================================
        window.confirmDelete = function(videoTitle) {
            return confirm(
                `🗑️ Delete Video\n\n` +
                `Are you sure you want to delete "${videoTitle}"?\n\n` +
                `📌 This action cannot be undone!\n` +
                `🎬 Video file and thumbnail will be permanently removed.`
            );
        };

        // ========================================
        // 8. KEYBOARD SHORTCUTS
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
        // 9. CONSOLE WELCOME
        // ========================================
        console.log('%c✏️ SCI-CALC Edit Video', 'font-size:24px;font-weight:800;color:#6C63FF;');
        console.log('%c🎬 Editing: <?php echo htmlspecialchars($video['title']); ?>', 'font-size:14px;color:#2D3436;');
        console.log('%c💡 Tip: Ctrl+S to save, ESC to cancel', 'font-size:12px;color:#8899AA;');
        console.log('%c✅ Edit video page loaded successfully!', 'font-size:14px;color:#00C9A7;');
    });
</script>

</body>
</html>