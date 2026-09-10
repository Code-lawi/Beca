<?php
require_once '../config/database.php';
requireAdminLogin();

// Get statistics
$stats = [];

// Total products
$result = $conn->query("SELECT COUNT(*) as total FROM products");
$stats['products'] = $result->fetch_assoc()['total'];

// Total videos
$result = $conn->query("SELECT COUNT(*) as total FROM videos");
$stats['videos'] = $result->fetch_assoc()['total'];

// Active products (visible)
$result = $conn->query("SELECT COUNT(*) as total FROM products WHERE status = 1");
$stats['active_products'] = $result->fetch_assoc()['total'];

// Get order counts
$order_counts = getOrderCounts($conn);

// Get settings
$settings = getSettings($conn);

// Get current time greeting
$hour = date('H');
$greeting = '';
if ($hour < 12) {
    $greeting = '🌅 Good Morning';
} elseif ($hour < 17) {
    $greeting = '☀️ Good Afternoon';
} else {
    $greeting = '🌙 Good Evening';
}

// Get admin name
$admin_name = $_SESSION['admin_username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SCI-CALC Admin</title>
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

        .sidebar-menu li a .badge.success {
            background: var(--success);
            color: white;
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

        .top-bar .greeting {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .top-bar .greeting h1 {
            font-size: 20px;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.3px;
        }

        .top-bar .greeting h1 span {
            color: var(--primary);
        }

        .top-bar .greeting .greeting-emoji {
            font-size: 28px;
        }

        .top-bar .admin-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .top-bar .admin-info .admin-details {
            text-align: right;
        }

        .top-bar .admin-info .admin-details .admin-name {
            font-weight: 600;
            font-size: 14px;
            color: var(--dark);
        }

        .top-bar .admin-info .admin-details .admin-role {
            font-size: 12px;
            color: var(--gray);
        }

        .top-bar .admin-info .avatar {
            width: 44px;
            height: 44px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
            flex-shrink: 0;
            transition: var(--transition);
        }

        .top-bar .admin-info .avatar:hover {
            transform: scale(1.05);
        }

        .top-bar .datetime {
            font-size: 13px;
            color: var(--gray);
            font-weight: 400;
        }

        /* ============================================
           STATS GRID
        ============================================ */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: var(--white);
            padding: 22px 24px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            cursor: default;
            opacity: 0;
            transform: translateY(30px);
            animation: cardFadeIn 0.6s ease forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.15s; }
        .stat-card:nth-child(3) { animation-delay: 0.2s; }
        .stat-card:nth-child(4) { animation-delay: 0.25s; }
        .stat-card:nth-child(5) { animation-delay: 0.3s; }
        .stat-card:nth-child(6) { animation-delay: 0.35s; }

        @keyframes cardFadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
        }

        .stat-card .stat-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stat-card .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }

        .stat-card .stat-icon-wrapper.blue {
            background: rgba(108, 99, 255, 0.12);
            color: var(--primary);
        }

        .stat-card .stat-icon-wrapper.green {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .stat-card .stat-icon-wrapper.yellow {
            background: rgba(255, 193, 7, 0.12);
            color: var(--warning);
        }

        .stat-card .stat-icon-wrapper.red {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .stat-card .stat-icon-wrapper.purple {
            background: rgba(108, 99, 255, 0.12);
            color: var(--primary);
        }

        .stat-card .stat-number {
            font-size: 30px;
            font-weight: 800;
            color: var(--dark);
            letter-spacing: -0.5px;
            line-height: 1.2;
        }

        .stat-card .stat-label {
            color: var(--gray);
            font-size: 13px;
            font-weight: 500;
            margin-top: 2px;
        }

        .stat-card .stat-change {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 10px;
        }

        .stat-card .stat-change.positive {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .stat-card .stat-change.negative {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .stat-card .stat-change.warning {
            background: rgba(255, 193, 7, 0.12);
            color: #B7950B;
        }

        .stat-card .stat-change.pulse {
            animation: pulse-text 2s infinite;
        }

        @keyframes pulse-text {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.6; }
        }

        .stat-card .stat-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            border-radius: 0 4px 0 0;
            transition: width 1s ease;
        }

        .stat-card .stat-progress.blue { background: var(--primary); }
        .stat-card .stat-progress.green { background: var(--success); }
        .stat-card .stat-progress.yellow { background: var(--warning); }
        .stat-card .stat-progress.red { background: var(--danger); }

        /* ============================================
           RECENT SECTION
        ============================================ */
        .recent-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .recent-box {
            background: var(--white);
            border-radius: var(--radius);
            padding: 24px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            opacity: 0;
            transform: translateY(30px);
            animation: cardFadeIn 0.6s ease forwards;
        }

        .recent-box:nth-child(1) { animation-delay: 0.4s; }
        .recent-box:nth-child(2) { animation-delay: 0.45s; }
        .recent-box:nth-child(3) { animation-delay: 0.5s; }

        .recent-box:hover {
            box-shadow: var(--shadow-hover);
        }

        .recent-box h3 {
            font-size: 16px;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .recent-box h3 .view-all {
            font-size: 12px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            padding: 4px 12px;
            border-radius: 20px;
            background: rgba(108, 99, 255, 0.08);
        }

        .recent-box h3 .view-all:hover {
            background: var(--primary);
            color: white;
            transform: translateX(2px);
        }

        .recent-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: var(--transition);
            cursor: default;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item:hover {
            transform: translateX(4px);
        }

        .recent-item .item-info {
            display: flex;
            align-items: center;
            gap: 14px;
            flex: 1;
            min-width: 0;
        }

        .recent-item .item-thumb {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--light-gray);
            flex-shrink: 0;
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .recent-item:hover .item-thumb {
            border-color: var(--primary);
        }

        .recent-item .item-thumb-icon {
            width: 44px;
            height: 44px;
            border-radius: var(--radius-sm);
            background: rgba(108, 99, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .recent-item .item-text {
            flex: 1;
            min-width: 0;
        }

        .recent-item .item-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .recent-item .item-sub {
            font-size: 12px;
            color: var(--gray);
            margin-top: 1px;
        }

        .recent-item .item-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .recent-item .item-status.active {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .recent-item .item-status.inactive {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .recent-item .item-status.pending {
            background: rgba(255, 193, 7, 0.12);
            color: #B7950B;
        }

        .recent-item .item-status.approved {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .recent-item .item-status.rejected {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .empty-state {
            color: var(--gray);
            text-align: center;
            padding: 32px 0;
            font-size: 14px;
        }

        .empty-state .empty-icon {
            font-size: 36px;
            margin-bottom: 8px;
            opacity: 0.5;
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 1200px) {
            .recent-section {
                grid-template-columns: 1fr;
            }
        }

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

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
                gap: 14px;
            }

            .stat-card {
                padding: 16px 18px;
            }

            .stat-card .stat-number {
                font-size: 24px;
            }
        }

        @media (max-width: 600px) {
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 16px;
            }

            .top-bar .greeting h1 {
                font-size: 17px;
            }

            .top-bar .admin-info {
                justify-content: space-between;
            }

            .top-bar .admin-info .admin-details {
                text-align: left;
            }

            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }

            .stat-card .stat-number {
                font-size: 20px;
            }

            .stat-card .stat-icon-wrapper {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            .recent-box {
                padding: 16px;
            }

            .recent-item {
                flex-wrap: wrap;
                gap: 8px;
            }

            .recent-item .item-status {
                margin-left: 58px;
            }

            .main-content {
                padding: 12px 12px 30px;
            }
        }

        @media (max-width: 400px) {
            .stats-grid {
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
            <a href="dashboard.php" class="active">
                <span class="icon"><i class="fas fa-th-large"></i></span>
                <span class="menu-text">Dashboard</span>
            </a>
        </li>
        <li>
            <a href="products.php">
                <span class="icon"><i class="fas fa-box"></i></span>
                <span class="menu-text">Products</span>
                <span class="badge success"><?php echo $stats['products']; ?></span>
            </a>
        </li>
        <li>
            <a href="videos.php">
                <span class="icon"><i class="fas fa-video"></i></span>
                <span class="menu-text">Videos</span>
                <span class="badge"><?php echo $stats['videos']; ?></span>
            </a>
        </li>
        <li>
            <a href="orders.php">
                <span class="icon"><i class="fas fa-clipboard-list"></i></span>
                <span class="menu-text">Orders</span>
                <?php if ($order_counts['pending'] > 0): ?>
                    <span class="badge danger pulse"><?php echo $order_counts['pending']; ?></span>
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
        <div class="greeting">
            <span class="greeting-emoji"><?php echo explode(' ', $greeting)[0]; ?></span>
            <div>
                <h1><?php echo $greeting; ?>, <span><?php echo htmlspecialchars($admin_name); ?></span></h1>
                <div class="datetime" id="currentDateTime"></div>
            </div>
        </div>
        <div class="admin-info">
            <div class="admin-details">
                <div class="admin-name"><?php echo htmlspecialchars($admin_name); ?></div>
                <div class="admin-role">Administrator</div>
            </div>
            <div class="avatar"><?php echo strtoupper(substr($admin_name, 0, 1)); ?></div>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-number"><?php echo $stats['products']; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-icon-wrapper blue"><i class="fas fa-box"></i></div>
            </div>
            <span class="stat-change positive"><i class="fas fa-check-circle"></i> <?php echo $stats['active_products']; ?> active</span>
            <div class="stat-progress blue" style="width: <?php echo $stats['products'] > 0 ? ($stats['active_products'] / $stats['products']) * 100 : 0; ?>%;"></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-number"><?php echo $stats['videos']; ?></div>
                    <div class="stat-label">Total Videos</div>
                </div>
                <div class="stat-icon-wrapper purple"><i class="fas fa-video"></i></div>
            </div>
            <span class="stat-change positive"><i class="fas fa-check-circle"></i> Available</span>
            <div class="stat-progress blue" style="width: 100%;"></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-number" id="pendingCount"><?php echo $order_counts['pending']; ?></div>
                    <div class="stat-label">Pending Orders</div>
                </div>
                <div class="stat-icon-wrapper yellow"><i class="fas fa-clock"></i></div>
            </div>
            <?php if ($order_counts['pending'] > 0): ?>
                <span class="stat-change warning pulse"><i class="fas fa-hourglass-half"></i> <?php echo $order_counts['pending']; ?> waiting</span>
            <?php else: ?>
                <span class="stat-change positive"><i class="fas fa-check-circle"></i> All clear</span>
            <?php endif; ?>
            <div class="stat-progress yellow" style="width: <?php echo $order_counts['pending'] > 0 ? min(($order_counts['pending'] / 10) * 100, 100) : 0; ?>%;"></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-number"><?php echo $order_counts['approved']; ?></div>
                    <div class="stat-label">Approved Orders</div>
                </div>
                <div class="stat-icon-wrapper green"><i class="fas fa-check-circle"></i></div>
            </div>
            <span class="stat-change positive"><i class="fas fa-check-circle"></i> Completed</span>
            <div class="stat-progress green" style="width: 100%;"></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-number"><?php echo $order_counts['rejected']; ?></div>
                    <div class="stat-label">Rejected Orders</div>
                </div>
                <div class="stat-icon-wrapper red"><i class="fas fa-times-circle"></i></div>
            </div>
            <span class="stat-change negative"><i class="fas fa-times-circle"></i> Cancelled</span>
            <div class="stat-progress red" style="width: <?php echo $order_counts['rejected'] > 0 ? min(($order_counts['rejected'] / 10) * 100, 100) : 0; ?>%;"></div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div>
                    <div class="stat-number">1</div>
                    <div class="stat-label">Admin Users</div>
                </div>
                <div class="stat-icon-wrapper blue"><i class="fas fa-user-shield"></i></div>
            </div>
            <span class="stat-change positive"><i class="fas fa-check-circle"></i> Active</span>
            <div class="stat-progress blue" style="width: 100%;"></div>
        </div>
    </div>

    <!-- Recent Section -->
    <div class="recent-section">
        <!-- Recent Products -->
        <div class="recent-box">
            <h3>
                <span><i class="fas fa-box" style="color:var(--primary);margin-right:8px;"></i> Recent Products</span>
                <a href="products.php" class="view-all"><i class="fas fa-arrow-right"></i> View All</a>
            </h3>
            <?php
            $recent_products = getProducts($conn, 5, 1);
            if (count($recent_products) > 0):
                foreach ($recent_products as $product):
            ?>
            <div class="recent-item">
                <div class="item-info">
                    <img src="<?php echo '../uploads/products/' . $product['image']; ?>" class="item-thumb" alt="<?php echo $product['name']; ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="item-thumb-icon" style="display:none;"><i class="fas fa-image"></i></div>
                    <div class="item-text">
                        <div class="item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="item-sub">TSh <?php echo number_format($product['price']); ?></div>
                    </div>
                </div>
                <span class="item-status <?php echo $product['status'] ? 'active' : 'inactive'; ?>">
                    <?php echo $product['status'] ? 'Visible' : 'Hidden'; ?>
                </span>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                No products added yet
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Orders -->
        <div class="recent-box">
            <h3>
                <span><i class="fas fa-clipboard-list" style="color:var(--warning);margin-right:8px;"></i> Recent Orders</span>
                <a href="orders.php" class="view-all"><i class="fas fa-arrow-right"></i> View All</a>
            </h3>
            <?php
            $recent_orders = getPendingOrders($conn, 5);
            if (count($recent_orders) > 0):
                foreach ($recent_orders as $order):
            ?>
            <div class="recent-item">
                <div class="item-info">
                    <div class="item-thumb-icon"><i class="fas fa-receipt"></i></div>
                    <div class="item-text">
                        <div class="item-name"><?php echo htmlspecialchars($order['order_number']); ?></div>
                        <div class="item-sub"><?php echo htmlspecialchars($order['customer_name']); ?> • TSh <?php echo number_format($order['total_amount']); ?></div>
                    </div>
                </div>
                <span class="item-status pending"><i class="fas fa-clock"></i> Pending</span>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                No pending orders
            </div>
            <?php endif; ?>
        </div>

        <!-- Recent Videos -->
        <div class="recent-box" style="grid-column: 1 / -1;">
            <h3>
                <span><i class="fas fa-video" style="color:var(--success);margin-right:8px;"></i> Recent Videos</span>
                <a href="videos.php" class="view-all"><i class="fas fa-arrow-right"></i> View All</a>
            </h3>
            <?php
            $recent_videos = getVideos($conn, 5, 1);
            if (count($recent_videos) > 0):
                foreach ($recent_videos as $video):
            ?>
            <div class="recent-item">
                <div class="item-info">
                    <img src="<?php echo '../uploads/videos/' . $video['thumbnail']; ?>" class="item-thumb" alt="<?php echo $video['title']; ?>"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="item-thumb-icon" style="display:none;"><i class="fas fa-play-circle"></i></div>
                    <div class="item-text">
                        <div class="item-name"><?php echo htmlspecialchars($video['title']); ?></div>
                        <div class="item-sub"><?php echo date('M d, Y', strtotime($video['created_at'])); ?></div>
                    </div>
                </div>
                <span class="item-status <?php echo $video['status'] ? 'active' : 'inactive'; ?>">
                    <?php echo $video['status'] ? 'Visible' : 'Hidden'; ?>
                </span>
            </div>
            <?php
                endforeach;
            else:
            ?>
            <div class="empty-state">
                <div class="empty-icon">🎬</div>
                No videos added yet
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT
============================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // ========================================
        // 1. LIVE DATE & TIME
        // ========================================
        function updateDateTime() {
            const now = new Date();
            const options = {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            };
            const dateTimeStr = now.toLocaleDateString('en-US', options);
            const el = document.getElementById('currentDateTime');
            if (el) {
                el.textContent = '🕐 ' + dateTimeStr;
            }
        }
        updateDateTime();
        setInterval(updateDateTime, 1000);

        // ========================================
        // 2. STATS COUNTER ANIMATION
        // ========================================
        function animateCounters() {
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach(el => {
                const target = parseInt(el.textContent.replace(/,/g, '')) || 0;
                if (target === 0) return;
                
                const duration = 1200;
                const startTime = performance.now();
                
                function updateCounter(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    const current = Math.floor(eased * target);
                    el.textContent = current.toLocaleString();
                    
                    if (progress < 1) {
                        requestAnimationFrame(updateCounter);
                    } else {
                        el.textContent = target.toLocaleString();
                    }
                }
                requestAnimationFrame(updateCounter);
            });
        }

        // Run counter animation after cards are visible
        setTimeout(animateCounters, 600);

        // ========================================
        // 3. LIVE PENDING ORDERS COUNT
        // ========================================
        function updatePendingOrders() {
            fetch('get_orders_count.php')
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    // Update sidebar badge
                    const badge = document.querySelector('.sidebar-menu li a[href="orders.php"] .badge');
                    if (badge) {
                        badge.textContent = data.pending || 0;
                        if (data.pending > 0) {
                            badge.classList.add('danger');
                            badge.classList.add('pulse');
                        } else {
                            badge.classList.remove('danger');
                            badge.classList.remove('pulse');
                        }
                    }
                    
                    // Update pending card
                    const pendingNumber = document.getElementById('pendingCount');
                    const pendingChange = document.querySelector('.stat-card:nth-child(3) .stat-change');
                    
                    if (pendingNumber) {
                        pendingNumber.textContent = data.pending || 0;
                    }
                    
                    if (pendingChange) {
                        if (data.pending > 0) {
                            pendingChange.innerHTML = '<i class="fas fa-hourglass-half"></i> ' + data.pending + ' waiting';
                            pendingChange.className = 'stat-change warning pulse';
                        } else {
                            pendingChange.innerHTML = '<i class="fas fa-check-circle"></i> All clear';
                            pendingChange.className = 'stat-change positive';
                        }
                    }
                    
                    // Update progress bar
                    const progressBar = document.querySelector('.stat-card:nth-child(3) .stat-progress');
                    if (progressBar) {
                        const pct = data.pending > 0 ? Math.min((data.pending / 10) * 100, 100) : 0;
                        progressBar.style.width = pct + '%';
                    }
                })
                .catch(() => {
                    // Silent fail - keep existing values
                });
        }

        // Update every 30 seconds
        setInterval(updatePendingOrders, 30000);

        // ========================================
        // 4. KEYBOARD SHORTCUTS
        // ========================================
        document.addEventListener('keydown', function(e) {
            // Ctrl+1 -> Dashboard
            if ((e.ctrlKey || e.metaKey) && e.key === '1') {
                e.preventDefault();
                window.location.href = 'dashboard.php';
            }
            // Ctrl+2 -> Products
            if ((e.ctrlKey || e.metaKey) && e.key === '2') {
                e.preventDefault();
                window.location.href = 'products.php';
            }
            // Ctrl+3 -> Videos
            if ((e.ctrlKey || e.metaKey) && e.key === '3') {
                e.preventDefault();
                window.location.href = 'videos.php';
            }
            // Ctrl+4 -> Orders
            if ((e.ctrlKey || e.metaKey) && e.key === '4') {
                e.preventDefault();
                window.location.href = 'orders.php';
            }
            // Ctrl+5 -> Settings
            if ((e.ctrlKey || e.metaKey) && e.key === '5') {
                e.preventDefault();
                window.location.href = 'settings.php';
            }
        });

        // ========================================
        // 5. CONSOLE WELCOME
        // ========================================
        console.log('%c🧮 SCI-CALC Admin Dashboard', 'font-size:28px;font-weight:800;color:#6C63FF;');
        console.log('%c📊 Welcome back, <?php echo htmlspecialchars($admin_name); ?>!', 'font-size:16px;color:#2D3436;');
        console.log('%c💡 Keyboard Shortcuts:', 'font-size:14px;font-weight:600;color:#6C63FF;');
        console.log('  Ctrl+1 → Dashboard');
        console.log('  Ctrl+2 → Products');
        console.log('  Ctrl+3 → Videos');
        console.log('  Ctrl+4 → Orders');
        console.log('  Ctrl+5 → Settings');
        console.log('%c✅ Dashboard loaded successfully!', 'font-size:14px;color:#00C9A7;');

        // ========================================
        // 6. SIDEBAR TOGGLE (mobile)
        // ========================================
        // Simple touch feedback for sidebar items
        document.querySelectorAll('.sidebar-menu li a').forEach(link => {
            link.addEventListener('touchstart', function() {
                this.style.transform = 'scale(0.97)';
            }, { passive: true });
            link.addEventListener('touchend', function() {
                this.style.transform = '';
            }, { passive: true });
        });

        // ========================================
        // 7. NOTIFICATION TOAST (optional)
        // ========================================
        // Show a welcome toast if there are pending orders
        <?php if ($order_counts['pending'] > 0): ?>
        setTimeout(function() {
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                background: linear-gradient(135deg, #FFC107, #F9A825);
                color: #333;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 30px rgba(255, 193, 7, 0.3);
                font-family: 'Inter', sans-serif;
                font-size: 14px;
                font-weight: 500;
                z-index: 9999;
                display: flex;
                align-items: center;
                gap: 12px;
                transform: translateY(100px);
                opacity: 0;
                transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
                cursor: pointer;
                max-width: 400px;
            `;
            toast.innerHTML = `
                <span style="font-size:24px;">📋</span>
                <div>
                    <strong>${<?php echo $order_counts['pending']; ?>} pending order${<?php echo $order_counts['pending']; ?> > 1 ? 's' : ''}</strong>
                    <br>
                    <span style="font-size:12px;opacity:0.7;">Click to view orders</span>
                </div>
                <span style="font-size:18px;">→</span>
            `;
            toast.onclick = function() {
                window.location.href = 'orders.php';
            };
            document.body.appendChild(toast);
            
            requestAnimationFrame(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            });
            
            setTimeout(() => {
                toast.style.transform = 'translateY(100px)';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 8000);
        }, 1500);
        <?php endif; ?>

        console.log('%c🎯 Dashboard ready!', 'font-size:14px;color:#6C63FF;');
    });
</script>

</body>
</html>