<?php
require_once '../config/database.php';
requireAdminLogin();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get video files first
    $stmt = $conn->prepare("SELECT video_url, thumbnail FROM videos WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $video = $result->fetch_assoc();
        // Delete video file
        $video_path = VIDEO_PATH . $video['video_url'];
        if (file_exists($video_path)) {
            unlink($video_path);
        }
        // Delete thumbnail
        $thumb_path = VIDEO_PATH . $video['thumbnail'];
        if (file_exists($thumb_path)) {
            unlink($thumb_path);
        }
    }
    $stmt->close();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM videos WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Video deleted successfully!";
    } else {
        $error = "Failed to delete video!";
    }
    $stmt->close();
}

// Handle toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $conn->prepare("UPDATE videos SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Video status updated!";
    } else {
        $error = "Failed to update status!";
    }
    $stmt->close();
}

// Handle toggle homepage
if (isset($_GET['toggle_home']) && is_numeric($_GET['toggle_home'])) {
    $id = $_GET['toggle_home'];
    $stmt = $conn->prepare("UPDATE videos SET show_on_homepage = NOT show_on_homepage WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Video homepage visibility updated!";
    } else {
        $error = "Failed to update homepage visibility!";
    }
    $stmt->close();
}

// Get all videos
$videos = getVideos($conn, null, null);

// Get order counts for sidebar badge
$order_counts = getOrderCounts($conn);

// Calculate stats
$total_videos = count($videos);
$active_videos = 0;
$homepage_videos = 0;

foreach ($videos as $v) {
    if ($v['status']) $active_videos++;
    if ($v['show_on_homepage']) $homepage_videos++;
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Videos - SCI-CALC Admin</title>
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

        .top-bar .actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
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
        }

        .btn-success:hover {
            background: var(--success-dark);
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

        .btn-warning {
            background: var(--warning);
            color: var(--dark);
        }

        .btn-warning:hover {
            background: #E0A800;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--gray);
        }

        .btn-secondary:hover {
            background: #C8D0D8;
            transform: translateY(-2px);
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }

        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 8px;
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
           STATS ROW
        ============================================ */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            animation: slideDown 0.5s ease forwards;
            animation-delay: 0.15s;
            opacity: 0;
            transform: translateY(-10px);
        }

        .stat-mini {
            background: var(--white);
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: var(--transition);
        }

        .stat-mini:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-hover);
        }

        .stat-mini .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }

        .stat-mini .stat-icon.blue {
            background: rgba(108, 99, 255, 0.12);
            color: var(--primary);
        }

        .stat-mini .stat-icon.green {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .stat-mini .stat-icon.yellow {
            background: rgba(255, 193, 7, 0.12);
            color: var(--warning);
        }

        .stat-mini .stat-icon.red {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .stat-mini .stat-info .stat-number {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            line-height: 1.2;
        }

        .stat-mini .stat-info .stat-label {
            font-size: 12px;
            color: var(--gray);
            font-weight: 500;
        }

        /* ============================================
           VIDEO GRID
        ============================================ */
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 24px;
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

        .video-card {
            background: var(--white);
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .video-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
        }

        .video-card .video-thumb {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            cursor: pointer;
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

        .video-card:hover .video-thumb img {
            transform: scale(1.05);
        }

        .video-card .video-thumb .play-btn {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            background: rgba(108, 99, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            transition: var(--transition);
            box-shadow: 0 4px 20px rgba(108, 99, 255, 0.4);
        }

        .video-card .video-thumb:hover .play-btn {
            transform: translate(-50%, -50%) scale(1.15);
            background: var(--primary);
        }

        .video-card .video-thumb .duration-badge {
            position: absolute;
            bottom: 12px;
            right: 12px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            backdrop-filter: blur(4px);
        }

        .video-card .video-body {
            padding: 20px;
        }

        .video-card .video-body .video-title {
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.4;
        }

        .video-card .video-body .video-desc {
            color: var(--gray);
            font-size: 13px;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }

        .video-card .video-body .video-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 12px;
            font-size: 12px;
            color: var(--gray);
        }

        .video-card .video-body .video-meta .meta-date {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .video-card .video-body .video-meta .badge-group {
            display: flex;
            gap: 4px;
            flex-wrap: wrap;
        }

        .badge {
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-active {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .badge-inactive {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .badge-home {
            background: rgba(108, 99, 255, 0.10);
            color: var(--primary);
        }

        .badge-home-inactive {
            background: rgba(99, 110, 114, 0.10);
            color: var(--gray);
        }

        .video-card .video-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            padding-top: 12px;
            border-top: 1px solid rgba(0,0,0,0.05);
        }

        .video-card .video-actions .btn {
            font-size: 11px;
            padding: 5px 12px;
        }

        /* ============================================
           EMPTY STATE
        ============================================ */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .empty-state .empty-icon {
            font-size: 72px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 20px;
        }

        .empty-state p {
            margin-bottom: 20px;
            font-size: 14px;
        }

        /* ============================================
           VIDEO MODAL
        ============================================ */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.92);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(8px);
        }

        .modal.show {
            display: flex;
            animation: modalFadeIn 0.4s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .modal-content {
            position: relative;
            max-width: 900px;
            width: 92%;
            background: #000;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.6);
        }

        .modal-content video {
            width: 100%;
            display: block;
            max-height: 80vh;
        }

        .modal-close {
            position: absolute;
            top: 16px;
            right: 18px;
            color: white;
            font-size: 28px;
            cursor: pointer;
            z-index: 10;
            background: rgba(0,0,0,0.6);
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: var(--transition);
            border: 2px solid rgba(255,255,255,0.1);
        }

        .modal-close:hover {
            background: rgba(255, 107, 107, 0.8);
            transform: rotate(90deg);
            border-color: transparent;
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

            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 18px;
            }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 16px;
            }

            .top-bar .actions {
                width: 100%;
            }

            .top-bar .actions .btn {
                flex: 1;
                justify-content: center;
            }

            .stats-row {
                grid-template-columns: 1fr 1fr;
                gap: 10px;
            }

            .stat-mini {
                padding: 10px 14px;
            }

            .main-content {
                padding: 12px 12px 30px;
            }

            .video-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .video-card .video-actions {
                flex-direction: column;
            }

            .video-card .video-actions .btn {
                width: 100%;
                justify-content: center;
            }

            .modal-content {
                width: 95%;
                border-radius: var(--radius-sm);
            }

            .modal-close {
                top: 10px;
                right: 12px;
                width: 36px;
                height: 36px;
                font-size: 20px;
            }
        }

        @media (max-width: 400px) {
            .stats-row {
                grid-template-columns: 1fr;
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
                <span class="badge"><?php echo $total_videos; ?></span>
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
            <i class="fas fa-video"></i>
            Videos
        </h1>
        <div class="actions">
            <a href="video_add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Video
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($success) && !empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error) && !empty($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <!-- Stats Row -->
    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-icon blue"><i class="fas fa-video"></i></div>
            <div class="stat-info">
                <div class="stat-number" id="totalVideos"><?php echo $total_videos; ?></div>
                <div class="stat-label">Total Videos</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-icon green"><i class="fas fa-eye"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $active_videos; ?></div>
                <div class="stat-label">Visible</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-icon yellow"><i class="fas fa-home"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $homepage_videos; ?></div>
                <div class="stat-label">On Homepage</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-icon red"><i class="fas fa-eye-slash"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_videos - $active_videos; ?></div>
                <div class="stat-label">Hidden</div>
            </div>
        </div>
    </div>

    <!-- Video Grid -->
    <?php if ($total_videos > 0): ?>
    <div class="video-grid">
        <?php foreach ($videos as $video): ?>
        <div class="video-card">
            <div class="video-thumb" onclick="playVideo('<?php echo $video['video_url']; ?>')">
                <img src="<?php echo '../uploads/videos/' . $video['thumbnail']; ?>" 
                     alt="<?php echo $video['title']; ?>"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22300%22 height=%22300%22%3E%3Crect width=%22300%22 height=%22300%22 fill=%22%231a1a2e%22/%3E%3Ctext x=%22110%22 y=%22160%22 font-size=%2260%22%3E🎬%3C/text%3E%3C/svg%3E'">
                <div class="play-btn"><i class="fas fa-play"></i></div>
                <span class="duration-badge"><i class="fas fa-play"></i> Watch</span>
            </div>
            <div class="video-body">
                <div class="video-title"><?php echo htmlspecialchars($video['title']); ?></div>
                <div class="video-desc"><?php echo htmlspecialchars(substr($video['description'], 0, 80)); ?>...</div>
                <div class="video-meta">
                    <span class="meta-date">
                        <i class="far fa-calendar-alt"></i>
                        <?php echo date('M d, Y', strtotime($video['created_at'])); ?>
                    </span>
                    <div class="badge-group">
                        <span class="badge <?php echo $video['status'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $video['status'] ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>'; ?>
                            <?php echo $video['status'] ? 'Visible' : 'Hidden'; ?>
                        </span>
                        <span class="badge <?php echo $video['show_on_homepage'] ? 'badge-home' : 'badge-home-inactive'; ?>">
                            <i class="fas fa-<?php echo $video['show_on_homepage'] ? 'home' : 'home'; ?>"></i>
                            <?php echo $video['show_on_homepage'] ? 'On Home' : 'Hidden'; ?>
                        </span>
                    </div>
                </div>
                <div class="video-actions">
                    <a href="video_edit.php?id=<?php echo $video['id']; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="?toggle=<?php echo $video['id']; ?>" class="btn <?php echo $video['status'] ? 'btn-secondary' : 'btn-success'; ?> btn-sm"
                       onclick="return confirmToggle('<?php echo htmlspecialchars($video['title']); ?>', <?php echo $video['status'] ? 'true' : 'false'; ?>)">
                        <?php if ($video['status']): ?>
                            <i class="fas fa-eye-slash"></i> Hide
                        <?php else: ?>
                            <i class="fas fa-eye"></i> Show
                        <?php endif; ?>
                    </a>
                    <a href="?toggle_home=<?php echo $video['id']; ?>" class="btn <?php echo $video['show_on_homepage'] ? 'btn-secondary' : 'btn-info'; ?> btn-sm"
                       onclick="return confirmHomeToggle('<?php echo htmlspecialchars($video['title']); ?>', <?php echo $video['show_on_homepage'] ? 'true' : 'false'; ?>)">
                        <?php if ($video['show_on_homepage']): ?>
                            <i class="fas fa-home"></i> Remove Home
                        <?php else: ?>
                            <i class="fas fa-home"></i> Add Home
                        <?php endif; ?>
                    </a>
                    <a href="?delete=<?php echo $video['id']; ?>" class="btn btn-danger btn-sm"
                       onclick="return confirmDelete('<?php echo htmlspecialchars($video['title']); ?>')">
                        <i class="fas fa-trash-alt"></i> Delete
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <div class="empty-icon">🎥</div>
        <h3>No videos added yet</h3>
        <p>Add tutorial videos, product reviews, or promotional content to engage your customers.</p>
        <a href="video_add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Add First Video
        </a>
    </div>
    <?php endif; ?>
</div>

<!-- ============================================
     VIDEO PLAYER MODAL
============================================ -->
<div class="modal" id="videoModal">
    <div class="modal-content">
        <span class="modal-close" onclick="closeVideo()"><i class="fas fa-times"></i></span>
        <video id="modalVideo" controls autoplay playsinline>
            <source src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT
============================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // ========================================
        // 1. VIDEO PLAYER
        // ========================================
        window.playVideo = function(videoFile) {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');
            video.src = '../uploads/videos/' + videoFile;
            modal.classList.add('show');
            video.play().catch(() => {});
        };

        window.closeVideo = function() {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('modalVideo');
            video.pause();
            video.currentTime = 0;
            video.src = '';
            modal.classList.remove('show');
        };

        // Close modal on click outside
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideo();
            }
        });

        // Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideo();
            }
        });

        // ========================================
        // 2. CONFIRM DELETE
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
        // 3. CONFIRM TOGGLE
        // ========================================
        window.confirmToggle = function(videoTitle, isVisible) {
            const action = isVisible ? 'hide' : 'show';
            return confirm(
                `${isVisible ? '🙈' : '👁️'} ${isVisible ? 'Hide' : 'Show'} Video\n\n` +
                `Are you sure you want to ${action} "${videoTitle}"?\n\n` +
                `${isVisible ? '⚠️ It will not be visible to customers.' : '✅ It will become visible to customers.'}`
            );
        };

        // ========================================
        // 4. CONFIRM HOME TOGGLE
        // ========================================
        window.confirmHomeToggle = function(videoTitle, isOnHome) {
            const action = isOnHome ? 'remove from' : 'add to';
            return confirm(
                `${isOnHome ? '🏠' : '🏠'} ${isOnHome ? 'Remove from' : 'Add to'} Homepage\n\n` +
                `Are you sure you want to ${action} the homepage "${videoTitle}"?\n\n` +
                `${isOnHome ? '⚠️ It will not be shown on the homepage.' : '✅ It will be shown on the homepage.'}`
            );
        };

        // ========================================
        // 5. COUNTER ANIMATION
        // ========================================
        function animateCounter() {
            const totalEl = document.getElementById('totalVideos');
            if (!totalEl) return;
            
            const target = parseInt(totalEl.textContent) || 0;
            if (target === 0) return;
            
            const duration = 800;
            const startTime = performance.now();
            
            function updateCounter(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(eased * target);
                totalEl.textContent = current;
                
                if (progress < 1) {
                    requestAnimationFrame(updateCounter);
                } else {
                    totalEl.textContent = target;
                }
            }
            requestAnimationFrame(updateCounter);
        }

        setTimeout(animateCounter, 500);

        // ========================================
        // 6. KEYBOARD SHORTCUTS
        // ========================================
        document.addEventListener('keydown', function(e) {
            // Space to play/pause video when modal is open
            if (e.key === ' ' || e.key === 'Spacebar') {
                const modal = document.getElementById('videoModal');
                if (modal.classList.contains('show')) {
                    e.preventDefault();
                    const video = document.getElementById('modalVideo');
                    if (video.paused) {
                        video.play();
                    } else {
                        video.pause();
                    }
                }
            }
        });

        // ========================================
        // 7. CONSOLE WELCOME
        // ========================================
        console.log('%c🎥 SCI-CALC Videos', 'font-size:24px;font-weight:800;color:#6C63FF;');
        console.log('%c📊 Total videos: <?php echo $total_videos; ?>', 'font-size:14px;color:#2D3436;');
        console.log('%c👁️ Visible: <?php echo $active_videos; ?> | 🏠 On Home: <?php echo $homepage_videos; ?>', 'font-size:14px;color:#2D3436;');
        console.log('%c💡 Click on any video thumbnail to play', 'font-size:12px;color:#8899AA;');
        console.log('%c✅ Videos page loaded successfully!', 'font-size:14px;color:#00C9A7;');
    });
</script>

</body>
</html>