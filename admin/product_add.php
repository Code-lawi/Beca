<?php
require_once '../config/database.php';
requireAdminLogin();

$error = '';
$success = '';
$name = '';
$price = '';
$description = '';
$stock = 10;
$status = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $price = trim($_POST['price']);
    $description = trim($_POST['description']);
    $stock = (int)$_POST['stock'];
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Validate
    if (empty($name) || empty($price) || empty($description)) {
        $error = 'Please fill all required fields!';
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = 'Please enter a valid price!';
    } else {
        // Handle image upload
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowed_types)) {
                $error = 'Only JPG, PNG, GIF, and WEBP images are allowed!';
            } elseif ($file['size'] > $max_size) {
                $error = 'Image size must be less than 5MB!';
            } else {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $image_name = time() . '_' . uniqid() . '.' . $ext;
                $target_path = PRODUCT_IMG_PATH . $image_name;
                
                if (!move_uploaded_file($file['tmp_name'], $target_path)) {
                    $error = 'Failed to upload image!';
                }
            }
        } else {
            $error = 'Please select a product image!';
        }
        
        // Insert into database
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO products (name, price, description, image, stock, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdssii", $name, $price, $description, $image_name, $stock, $status);
            
            if ($stmt->execute()) {
                $success = 'Product added successfully! 🎉';
                // Clear form
                $name = $price = $description = '';
                $stock = 10;
                $status = 1;
            } else {
                $error = 'Failed to add product: ' . $conn->error;
                // Delete uploaded image if database insert fails
                if (!empty($image_name) && file_exists(PRODUCT_IMG_PATH . $image_name)) {
                    unlink(PRODUCT_IMG_PATH . $image_name);
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
    <title>Add Product - SCI-CALC Admin</title>
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
            color: var(--success);
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
            color: var(--primary);
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
            min-height: 110px;
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

        /* File Input */
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

        .image-preview {
            margin-top: 14px;
            border-radius: var(--radius-sm);
            overflow: hidden;
            border: 2px solid var(--light-gray);
            padding: 8px;
            display: none;
            background: var(--bg);
            max-width: 280px;
        }

        .image-preview.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        .image-preview img {
            width: 100%;
            height: auto;
            border-radius: 6px;
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

        /* Row */
        .row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
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

            .row {
                grid-template-columns: 1fr;
                gap: 0;
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
            <a href="products.php" class="active">
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
            Add New Product
        </h1>
        <a href="products.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Products
        </a>
    </div>

    <!-- Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $success; ?>
            <a href="products.php" style="margin-left:auto;color:var(--success);font-weight:600;text-decoration:underline;">
                View Products →
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
            <i class="fas fa-box"></i>
            Product Information
        </div>

        <form method="POST" enctype="multipart/form-data" id="productForm" novalidate>
            <!-- Image -->
            <div class="form-group">
                <label>
                    <span class="label-icon">📷</span> Product Image <span class="required">*</span>
                </label>
                <div class="file-upload-wrapper" id="dropZone">
                    <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                    <div class="upload-text">Click or drag &amp; drop to upload</div>
                    <div class="upload-sub">JPG, PNG, GIF, WEBP (Max 5MB)</div>
                    <input type="file" name="image" id="imageInput" accept="image/*" required>
                </div>
                <div class="image-preview" id="imagePreview">
                    <img id="previewImg" src="#" alt="Preview">
                    <div class="preview-actions">
                        <button type="button" class="btn-remove" onclick="removeImage()">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                </div>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    Recommended: Square image, at least 500x500px
                </div>
            </div>

            <!-- Name -->
            <div class="form-group">
                <label>
                    <span class="label-icon">📝</span> Product Name <span class="required">*</span>
                </label>
                <input type="text" name="name" id="productName" 
                       placeholder="e.g., Casio fx-991ES Plus Scientific Calculator" 
                       value="<?php echo htmlspecialchars($name); ?>" required>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    <span id="charCount">0</span> characters
                </div>
            </div>

            <!-- Price & Stock -->
            <div class="row">
                <div class="form-group">
                    <label>
                        <span class="label-icon">💰</span> Price (TSh) <span class="required">*</span>
                    </label>
                    <input type="number" name="price" id="productPrice" 
                           placeholder="e.g., 18000" step="100" min="0" 
                           value="<?php echo htmlspecialchars($price); ?>" required>
                    <div class="helper-text">
                        <i class="fas fa-info-circle"></i>
                        Enter price in Tanzanian Shillings
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <span class="label-icon">📦</span> Stock Quantity
                    </label>
                    <input type="number" name="stock" id="productStock" 
                           placeholder="e.g., 10" value="<?php echo $stock; ?>" min="0">
                    <div class="helper-text">
                        <i class="fas fa-info-circle"></i>
                        Leave as 0 for out of stock
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label>
                    <span class="label-icon">📄</span> Description <span class="required">*</span>
                </label>
                <textarea name="description" id="productDescription" 
                          placeholder="Describe the calculator features, specifications, and benefits..." 
                          required><?php echo htmlspecialchars($description); ?></textarea>
                <div class="helper-text">
                    <i class="fas fa-info-circle"></i>
                    <span id="descCharCount">0</span> characters
                </div>
            </div>

            <!-- Status -->
            <div class="form-group">
                <div class="form-check">
                    <input type="checkbox" name="status" id="status" <?php echo $status ? 'checked' : ''; ?>>
                    <label for="status">
                        <i class="fas fa-eye" style="color:var(--success);"></i>
                        Visible on homepage
                        <span class="check-sub">— Product will be shown to customers</span>
                    </label>
                </div>
            </div>

            <!-- Buttons -->
            <div class="btn-group">
                <button type="submit" class="btn btn-success" id="submitBtn">
                    <i class="fas fa-save"></i> Save Product
                </button>
                <button type="reset" class="btn btn-secondary" onclick="return confirm('Clear all fields?')">
                    <i class="fas fa-undo"></i> Reset
                </button>
                <a href="products.php" class="btn btn-secondary">
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
        // 1. IMAGE PREVIEW
        // ========================================
        const imageInput = document.getElementById('imageInput');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');
        const dropZone = document.getElementById('dropZone');

        imageInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.classList.add('show');
                    dropZone.style.borderColor = 'var(--success)';
                };
                reader.readAsDataURL(file);
            } else {
                removeImage();
            }
        });

        function removeImage() {
            imageInput.value = '';
            imagePreview.classList.remove('show');
            previewImg.src = '#';
            dropZone.style.borderColor = '';
        }
        window.removeImage = removeImage;

        // ========================================
        // 2. DRAG & DROP
        // ========================================
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, function() {
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, function() {
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                imageInput.files = files;
                imageInput.dispatchEvent(new Event('change'));
            }
        }, false);

        // ========================================
        // 3. CHARACTER COUNTERS
        // ========================================
        const productName = document.getElementById('productName');
        const charCount = document.getElementById('charCount');

        productName.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
        charCount.textContent = productName.value.length;

        const productDesc = document.getElementById('productDescription');
        const descCharCount = document.getElementById('descCharCount');

        productDesc.addEventListener('input', function() {
            descCharCount.textContent = this.value.length;
        });
        descCharCount.textContent = productDesc.value.length;

        // ========================================
        // 4. PRICE FORMAT
        // ========================================
        document.getElementById('productPrice').addEventListener('blur', function() {
            if (this.value) {
                this.value = parseInt(this.value);
            }
        });

        // ========================================
        // 5. FORM VALIDATION
        // ========================================
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const name = document.getElementById('productName').value.trim();
            const price = document.getElementById('productPrice').value.trim();
            const description = document.getElementById('productDescription').value.trim();
            const image = document.getElementById('imageInput').files[0];
            
            // Check if we're editing (has existing image)
            const isEdit = false; // Set to true if editing
            
            if (!name) {
                e.preventDefault();
                showFieldError('productName', 'Please enter product name!');
                return false;
            }
            if (!price || parseFloat(price) <= 0) {
                e.preventDefault();
                showFieldError('productPrice', 'Please enter a valid price!');
                return false;
            }
            if (!description) {
                e.preventDefault();
                showFieldError('productDescription', 'Please enter product description!');
                return false;
            }
            if (!image && !isEdit) {
                e.preventDefault();
                showFieldError('imageInput', 'Please select a product image!');
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
            field.style.borderColor = 'var(--danger)';
            field.focus();
            
            // Remove existing error
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

        // Clear error on input
        document.querySelectorAll('#productName, #productPrice, #productDescription').forEach(field => {
            field.addEventListener('input', function() {
                this.style.borderColor = '';
                const error = this.parentElement.querySelector('.field-error');
                if (error) error.remove();
            });
        });

        // ========================================
        // 6. KEYBOARD SHORTCUTS
        // ========================================
        document.addEventListener('keydown', function(e) {
            // Ctrl+S or Cmd+S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                document.getElementById('productForm').dispatchEvent(new Event('submit'));
            }
            
            // ESC to cancel
            if (e.key === 'Escape') {
                if (confirm('Cancel and go back to products?')) {
                    window.location.href = 'products.php';
                }
            }
        });

        // ========================================
        // 7. CONSOLE WELCOME
        // ========================================
        console.log('%c➕ SCI-CALC Add Product', 'font-size:24px;font-weight:800;color:#6C63FF;');
        console.log('%c📝 Fill in the form to add a new product', 'font-size:14px;color:#2D3436;');
        console.log('%c💡 Tip: Ctrl+S to save, ESC to cancel', 'font-size:12px;color:#8899AA;');
        console.log('%c✅ Add product page loaded successfully!', 'font-size:14px;color:#00C9A7;');
    });
</script>

</body>
</html>