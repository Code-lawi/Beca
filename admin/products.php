<?php
require_once '../config/database.php';
requireAdminLogin();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = $_GET['delete'];
    
    // Get image path first
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        // Delete image file
        $image_path = PRODUCT_IMG_PATH . $product['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    $stmt->close();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Product deleted successfully!";
    } else {
        $error = "Failed to delete product!";
    }
    $stmt->close();
}

// Handle toggle status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $conn->prepare("UPDATE products SET status = NOT status WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $success = "Product status updated!";
    } else {
        $error = "Failed to update status!";
    }
    $stmt->close();
}

// Get all products
$products = getProducts($conn, null, null);

// Get order counts for sidebar badge
$order_counts = getOrderCounts($conn);
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - SCI-CALC Admin</title>
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
            color: var(--primary);
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

        .btn-sm {
            padding: 6px 14px;
            font-size: 12px;
            border-radius: 8px;
        }

        /* ============================================
           MESSAGES / ALERTS
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
           TABLE
        ============================================ */
        .table-container {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px 24px 24px;
            box-shadow: var(--shadow);
            overflow-x: auto;
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

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .table-header .table-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--gray);
        }

        .table-header .table-title span {
            color: var(--primary);
        }

        .table-search {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .table-search input {
            padding: 8px 14px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: var(--transition);
            background: var(--bg);
            min-width: 200px;
        }

        .table-search input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
            background: var(--white);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: var(--bg);
            border-radius: var(--radius-sm);
        }

        th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            color: var(--gray);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        td {
            padding: 14px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            vertical-align: middle;
            font-size: 14px;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background: rgba(108, 99, 255, 0.03);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .product-thumb {
            width: 52px;
            height: 52px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            background: var(--bg);
            border: 2px solid transparent;
            transition: var(--transition);
        }

        .product-thumb:hover {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .product-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 14px;
        }

        .product-date {
            font-size: 11px;
            color: var(--gray);
            display: block;
            margin-top: 2px;
        }

        .product-price {
            font-weight: 700;
            color: var(--success);
            font-size: 15px;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
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

        .badge-stock {
            background: rgba(108, 99, 255, 0.08);
            color: var(--primary);
        }

        .badge-outofstock {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .badge-lowstock {
            background: rgba(255, 193, 7, 0.12);
            color: #B7950B;
        }

        .actions-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .actions-group .btn {
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
        }

        .empty-state .empty-icon {
            font-size: 64px;
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

            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
                gap: 10px;
            }

            .stat-mini {
                padding: 10px 14px;
            }

            .table-container {
                padding: 16px;
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
            }

            .table-header {
                flex-direction: column;
                align-items: stretch;
            }

            .table-search {
                width: 100%;
            }

            .table-search input {
                flex: 1;
                min-width: 0;
            }

            .main-content {
                padding: 12px 12px 30px;
            }

            td, th {
                padding: 10px;
                font-size: 13px;
            }

            .actions-group {
                flex-direction: column;
            }

            .actions-group .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
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
            <a href="products.php" class="active">
                <span class="icon"><i class="fas fa-box"></i></span>
                <span class="menu-text">Products</span>
                <span class="badge"><?php echo count($products); ?></span>
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
            <i class="fas fa-box"></i>
            Products
        </h1>
        <div class="actions">
            <a href="product_add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Product
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
    <?php
    $total_products = count($products);
    $active_products = 0;
    $inactive_products = 0;
    $total_stock = 0;
    $low_stock = 0;

    foreach ($products as $p) {
        if ($p['status']) $active_products++;
        else $inactive_products++;
        $total_stock += $p['stock'] ?? 0;
        if (($p['stock'] ?? 0) > 0 && ($p['stock'] ?? 0) < 5) $low_stock++;
    }
    ?>

    <div class="stats-row">
        <div class="stat-mini">
            <div class="stat-icon blue"><i class="fas fa-box"></i></div>
            <div class="stat-info">
                <div class="stat-number" id="totalProducts"><?php echo $total_products; ?></div>
                <div class="stat-label">Total Products</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-icon green"><i class="fas fa-eye"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $active_products; ?></div>
                <div class="stat-label">Visible</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-icon red"><i class="fas fa-eye-slash"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $inactive_products; ?></div>
                <div class="stat-label">Hidden</div>
            </div>
        </div>
        <div class="stat-mini">
            <div class="stat-icon yellow"><i class="fas fa-cubes"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $total_stock; ?></div>
                <div class="stat-label">Total Stock</div>
            </div>
        </div>
        <?php if ($low_stock > 0): ?>
        <div class="stat-mini">
            <div class="stat-icon red"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="stat-info">
                <div class="stat-number"><?php echo $low_stock; ?></div>
                <div class="stat-label">Low Stock</div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Table -->
    <div class="table-container">
        <div class="table-header">
            <div class="table-title">
                <i class="fas fa-list-ul"></i>
                All Products <span>(<?php echo $total_products; ?>)</span>
            </div>
            <div class="table-search">
                <input type="text" id="searchInput" placeholder="🔍 Search products..." onkeyup="filterTable()">
                <span style="font-size:12px;color:var(--gray);">
                    <i class="fas fa-arrows-up-down"></i> Sortable
                </span>
            </div>
        </div>

        <?php if ($total_products > 0): ?>
        <table id="productTable">
            <thead>
                <tr>
                    <th style="width:60px;">Image</th>
                    <th onclick="sortTable(1)" style="cursor:pointer;">Product <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(2)" style="cursor:pointer;">Price <i class="fas fa-sort"></i></th>
                    <th onclick="sortTable(3)" style="cursor:pointer;">Stock <i class="fas fa-sort"></i></th>
                    <th>Status</th>
                    <th style="min-width:200px;">Actions</th>
                </tr>
            </thead>
            <tbody id="productTableBody">
                <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <img src="<?php echo '../uploads/products/' . $product['image']; ?>" 
                             class="product-thumb" 
                             alt="<?php echo $product['name']; ?>"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2252%22 height=%2252%22%3E%3Crect width=%2252%22 height=%2252%22 fill=%22%23f0f2f5%22/%3E%3Ctext x=%2210%22 y=%2232%22 font-size=%2224%22%3E📷%3C/text%3E%3C/svg%3E'">
                    </td>
                    <td>
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <span class="product-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php echo date('M d, Y', strtotime($product['created_at'])); ?>
                        </span>
                    </td>
                    <td class="product-price">TSh <?php echo number_format($product['price']); ?></td>
                    <td>
                        <?php 
                        $stock = $product['stock'] ?? 0;
                        if ($stock > 10): ?>
                            <span class="badge badge-stock"><i class="fas fa-check-circle"></i> <?php echo $stock; ?> in stock</span>
                        <?php elseif ($stock > 0 && $stock <= 10): ?>
                            <span class="badge badge-lowstock"><i class="fas fa-exclamation-circle"></i> <?php echo $stock; ?> left</span>
                        <?php else: ?>
                            <span class="badge badge-outofstock"><i class="fas fa-times-circle"></i> Out of stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge <?php echo $product['status'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php if ($product['status']): ?>
                                <i class="fas fa-eye"></i> Visible
                            <?php else: ?>
                                <i class="fas fa-eye-slash"></i> Hidden
                            <?php endif; ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions-group">
                            <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-warning btn-sm">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="?toggle=<?php echo $product['id']; ?>" class="btn <?php echo $product['status'] ? 'btn-secondary' : 'btn-success'; ?> btn-sm" 
                               onclick="return confirmToggle('<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['status'] ? 'true' : 'false'; ?>)">
                                <?php if ($product['status']): ?>
                                    <i class="fas fa-eye-slash"></i> Hide
                                <?php else: ?>
                                    <i class="fas fa-eye"></i> Show
                                <?php endif; ?>
                            </a>
                            <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirmDelete('<?php echo htmlspecialchars($product['name']); ?>')">
                                <i class="fas fa-trash-alt"></i> Delete
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📦</div>
            <h3>No products added yet</h3>
            <p>Start by adding your first calculator product to the store.</p>
            <a href="product_add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add First Product
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================
     JAVASCRIPT
============================================ -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        // ========================================
        // 1. SEARCH / FILTER
        // ========================================
        window.filterTable = function() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('productTableBody');
            const rows = table.getElementsByTagName('tr');

            let visibleCount = 0;
            for (let i = 0; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;
                
                for (let j = 0; j < cells.length; j++) {
                    const text = cells[j].textContent.toLowerCase();
                    if (text.indexOf(filter) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    row.style.display = '';
                    visibleCount++;
                    row.style.animation = 'fadeIn 0.3s ease';
                } else {
                    row.style.display = 'none';
                }
            }

            // Show/hide empty message
            const emptyMsg = document.querySelector('.empty-state');
            if (emptyMsg) {
                if (visibleCount === 0 && document.querySelectorAll('#productTableBody tr').length > 0) {
                    if (!document.getElementById('noResults')) {
                        const msg = document.createElement('div');
                        msg.id = 'noResults';
                        msg.style.cssText = `
                            text-align: center;
                            padding: 30px;
                            color: var(--gray);
                        `;
                        msg.innerHTML = `
                            <div style="font-size:40px;margin-bottom:10px;">🔍</div>
                            <p style="font-weight:500;">No products found for "<span id="searchTerm">${filter}</span>"</p>
                        `;
                        document.querySelector('.table-container').appendChild(msg);
                    } else {
                        document.getElementById('searchTerm').textContent = filter;
                    }
                } else {
                    const noResults = document.getElementById('noResults');
                    if (noResults) noResults.remove();
                }
            }
        };

        // ========================================
        // 2. SORT TABLE
        // ========================================
        let sortDirection = {};

        window.sortTable = function(columnIndex) {
            const table = document.getElementById('productTableBody');
            const rows = Array.from(table.getElementsByTagName('tr'));
            const isAsc = sortDirection[columnIndex] !== 'asc';
            sortDirection[columnIndex] = isAsc ? 'asc' : 'desc';

            rows.sort((a, b) => {
                const aText = a.getElementsByTagName('td')[columnIndex]?.textContent.trim() || '';
                const bText = b.getElementsByTagName('td')[columnIndex]?.textContent.trim() || '';
                
                // Check if numeric
                const aNum = parseFloat(aText.replace(/[^0-9.]/g, ''));
                const bNum = parseFloat(bText.replace(/[^0-9.]/g, ''));
                
                if (!isNaN(aNum) && !isNaN(bNum)) {
                    return isAsc ? aNum - bNum : bNum - aNum;
                }
                
                return isAsc ? aText.localeCompare(bText) : bText.localeCompare(aText);
            });

            rows.forEach(row => table.appendChild(row));

            // Update sort icons
            document.querySelectorAll('th i.fa-sort').forEach(icon => {
                icon.className = 'fas fa-sort';
            });
            const headers = document.querySelectorAll('th');
            if (headers[columnIndex]) {
                const icon = headers[columnIndex].querySelector('i.fa-sort');
                if (icon) {
                    icon.className = `fas fa-sort-${isAsc ? 'up' : 'down'}`;
                }
            }
        };

        // ========================================
        // 3. CONFIRM DELETE
        // ========================================
        window.confirmDelete = function(productName) {
            return confirm(
                `⚠️ Delete Product\n\n` +
                `Are you sure you want to delete "${productName}"?\n\n` +
                `📌 This action cannot be undone!\n` +
                `🖼️ Product image will also be permanently removed.`
            );
        };

        // ========================================
        // 4. CONFIRM TOGGLE
        // ========================================
        window.confirmToggle = function(productName, isVisible) {
            const action = isVisible ? 'hide' : 'show';
            return confirm(
                `${isVisible ? '🙈' : '👁️'} ${isVisible ? 'Hide' : 'Show'} Product\n\n` +
                `Are you sure you want to ${action} "${productName}"?\n\n` +
                `${isVisible ? '⚠️ It will not be visible to customers.' : '✅ It will become visible to customers.'}`
            );
        };

        // ========================================
        // 5. KEYBOARD SHORTCUTS
        // ========================================
        document.addEventListener('keydown', function(e) {
            // Ctrl+F or Cmd+F for search
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.getElementById('searchInput');
                searchInput.focus();
                searchInput.select();
            }
            
            // ESC to clear search
            if (e.key === 'Escape') {
                const searchInput = document.getElementById('searchInput');
                if (document.activeElement === searchInput) {
                    searchInput.value = '';
                    window.filterTable();
                    searchInput.blur();
                }
            }
        });

        // ========================================
        // 6. COUNTER ANIMATION
        // ========================================
        function animateCounter() {
            const totalEl = document.getElementById('totalProducts');
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

        // Start counter animation after page load
        setTimeout(animateCounter, 500);

        // ========================================
        // 7. TOOLTIP FOR IMAGES
        // ========================================
        document.querySelectorAll('.product-thumb').forEach(img => {
            img.title = img.alt || 'Product image';
        });

        // ========================================
        // 8. CONSOLE WELCOME
        // ========================================
        console.log('%c📦 SCI-CALC Products', 'font-size:24px;font-weight:800;color:#6C63FF;');
        console.log('%c📊 Total products: <?php echo $total_products; ?>', 'font-size:14px;color:#2D3436;');
        console.log('%c👁️ Visible: <?php echo $active_products; ?> | 🙈 Hidden: <?php echo $inactive_products; ?>', 'font-size:14px;color:#2D3436;');
        console.log('%c💡 Tip: Press Ctrl+F to search', 'font-size:12px;color:#8899AA;');
        console.log('%c✅ Products page loaded successfully!', 'font-size:14px;color:#00C9A7;');
    });
</script>

</body>
</html>