<?php
require_once 'config/database.php';

$settings = getSettings($conn);
$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

// Get product details
$product = null;
if ($product_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

$error = '';
$success = '';
$order_number = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name']);
    $customer_phone = trim($_POST['customer_phone']);
    $customer_email = trim($_POST['customer_email']);
    $delivery_address = trim($_POST['delivery_address']);
    $quantity = intval($_POST['quantity']);
    $special_instructions = trim($_POST['special_instructions']);
    $product_id = intval($_POST['product_id']);
    
    if (empty($customer_name) || empty($customer_phone) || empty($delivery_address) || $product_id <= 0 || $quantity <= 0) {
        $error = 'Please fill all required fields!';
    } else {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            
            $order_number = generateOrderNumber();
            $total_amount = $product['price'] * $quantity;
            
            $stmt = $conn->prepare("INSERT INTO pending_orders 
                (order_number, customer_name, customer_phone, customer_email, delivery_address, 
                 product_id, product_name, product_price, quantity, total_amount, special_instructions) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssisdiss", 
                $order_number,
                $customer_name,
                $customer_phone,
                $customer_email,
                $delivery_address,
                $product_id,
                $product['name'],
                $product['price'],
                $quantity,
                $total_amount,
                $special_instructions
            );
            
            if ($stmt->execute()) {
                $success = true;
                $customer_name = $customer_phone = $customer_email = $delivery_address = '';
                $quantity = 1;
                $special_instructions = '';
            } else {
                $error = 'Failed to place order: ' . $conn->error;
            }
            $stmt->close();
        } else {
            $error = 'Product not found!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes, viewport-fit=cover">
    <meta name="theme-color" content="#6C63FF">
    <title>Place Order - <?php echo htmlspecialchars($settings['site_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6C63FF;
            --primary-dark: #5A52D5;
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
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            -webkit-tap-highlight-color: transparent;
        }

        body {
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--dark);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            background-image: 
                radial-gradient(ellipse at 10% 20%, rgba(108, 99, 255, 0.05) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(255, 107, 107, 0.05) 0%, transparent 50%);
        }

        /* ============================================
           HEADER
        ============================================ */
        .header {
            background: rgba(255,255,255,0.92);
            color: var(--dark);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 20px rgba(0,0,0,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.04);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        body.dark-mode .header {
            background: rgba(26,26,46,0.92);
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .header .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
        body.dark-mode .header .logo { color: white; }

        .header .logo h1 {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.5px;
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
           MAIN CONTAINER
        ============================================ */
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 0 20px;
            flex: 1;
            width: 100%;
        }

        /* ============================================
           ORDER CARD
        ============================================ */
        .order-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 40px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0,0,0,0.03);
        }

        body.dark-mode .order-card {
            background: #2D2D44;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .order-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-soft);
        }

        .order-card:hover {
            box-shadow: var(--shadow-hover);
        }

        .order-card .card-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .order-card .card-header .icon {
            font-size: 56px;
            display: inline-block;
            margin-bottom: 10px;
            animation: float 4s ease-in-out infinite;
        }

        .order-card h1 {
            font-size: 26px;
            color: var(--dark);
            margin-bottom: 6px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        body.dark-mode .order-card h1 { color: #ffffff; }

        .order-card .subtitle {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ============================================
           PRODUCT SUMMARY
        ============================================ */
        .product-summary {
            background: var(--light-bg);
            border-radius: var(--radius-sm);
            padding: 18px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 18px;
            border-left: 4px solid var(--primary);
            transition: var(--transition);
        }

        body.dark-mode .product-summary {
            background: #1A1A2E;
        }

        .product-summary:hover {
            transform: translateX(4px);
        }

        .product-summary .product-image {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: var(--radius-sm);
            background: white;
            padding: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.06);
        }

        .product-summary .info {
            flex: 1;
        }
        .product-summary .info h3 {
            font-size: 16px;
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 4px;
        }
        body.dark-mode .product-summary .info h3 { color: #ffffff; }

        .product-summary .info .price {
            color: var(--success);
            font-weight: 800;
            font-size: 20px;
            letter-spacing: -0.5px;
        }
        .product-summary .info .stock {
            font-size: 12px;
            color: var(--gray);
            margin-top: 2px;
        }

        /* ============================================
           FORM
        ============================================ */
        .form-group {
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }

        .form-group:nth-child(1) { animation-delay: 0.05s; }
        .form-group:nth-child(2) { animation-delay: 0.1s; }
        .form-group:nth-child(3) { animation-delay: 0.15s; }
        .form-group:nth-child(4) { animation-delay: 0.2s; }
        .form-group:nth-child(5) { animation-delay: 0.25s; }

        .form-group label {
            display: block;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 8px;
            font-size: 14px;
        }
        body.dark-mode .form-group label { color: #ddd; }

        .form-group label .required {
            color: var(--secondary);
            margin-left: 2px;
        }

        .form-group label .field-icon {
            margin-right: 6px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 13px 16px;
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
        body.dark-mode .form-group textarea,
        body.dark-mode .form-group select {
            background: #1A1A2E;
            border-color: rgba(255,255,255,0.06);
            color: white;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
            background: white;
        }

        body.dark-mode .form-group input:focus,
        body.dark-mode .form-group textarea:focus {
            background: #2D2D44;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: var(--gray);
        }

        .form-group input.success {
            border-color: var(--success);
            box-shadow: 0 0 0 4px rgba(0, 201, 167, 0.1);
        }

        .form-group input.error,
        .form-group textarea.error {
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(255, 107, 107, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        /* ============================================
           QUANTITY CONTROL
        ============================================ */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--light-bg);
            border-radius: var(--radius-sm);
            padding: 4px;
            border: 2px solid rgba(0,0,0,0.06);
            transition: var(--transition);
            width: fit-content;
        }

        body.dark-mode .quantity-control {
            background: #1A1A2E;
            border-color: rgba(255,255,255,0.06);
        }

        .quantity-control:focus-within {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
        }

        .quantity-control button {
            width: 42px;
            height: 42px;
            border-radius: 8px;
            border: none;
            background: var(--white);
            font-size: 20px;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-weight: 700;
        }

        body.dark-mode .quantity-control button {
            background: #2D2D44;
            color: white;
        }

        .quantity-control button:hover {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }

        .quantity-control button:active {
            transform: scale(0.95);
        }

        .quantity-control input {
            width: 60px;
            text-align: center;
            font-size: 20px;
            font-weight: 700;
            border: none;
            background: transparent;
            color: var(--dark);
            outline: none;
            padding: 8px 0;
        }

        body.dark-mode .quantity-control input {
            color: white;
        }

        .quantity-control input::-webkit-outer-spin-button,
        .quantity-control input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        .quantity-control input[type=number] {
            -moz-appearance: textfield;
        }

        /* ============================================
           TOTAL DISPLAY
        ============================================ */
        .total-display {
            padding: 18px 20px;
            background: linear-gradient(135deg, rgba(108,99,255,0.06) 0%, rgba(0,201,167,0.06) 100%);
            border-radius: var(--radius-sm);
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px dashed rgba(108, 99, 255, 0.2);
        }

        body.dark-mode .total-display {
            background: rgba(108,99,255,0.08);
        }

        .total-display .label {
            font-weight: 600;
            color: var(--gray);
            font-size: 15px;
        }

        .total-display .amount {
            font-size: 26px;
            font-weight: 900;
            color: var(--success);
            transition: var(--transition);
            letter-spacing: -1px;
        }

        .total-display .amount.pop {
            animation: pop 0.3s ease;
        }

        @keyframes pop {
            0% { transform: scale(1); }
            50% { transform: scale(1.15); color: var(--primary); }
            100% { transform: scale(1); }
        }

        /* ============================================
           BUTTONS
        ============================================ */
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 25px;
        }

        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex: 1;
            text-decoration: none;
            font-family: 'Inter', sans-serif;
            min-height: 52px;
            -webkit-tap-highlight-color: transparent;
        }

        .btn-primary {
            background: var(--gradient-soft);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }
        .btn-primary:active { transform: scale(0.98); }

        .btn-primary:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--gray);
        }

        .btn-secondary:hover {
            background: #C8D0D8;
            color: var(--dark);
            transform: translateY(-3px);
        }
        .btn-secondary:active { transform: scale(0.98); }

        /* ============================================
           ALERTS
        ============================================ */
        .alert {
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideDown 0.5s ease;
            font-weight: 500;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
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
           SUCCESS STATE - NO TITLE, NO SUBTITLE, NO ICON
        ============================================ */
        .success-state {
            text-align: center;
            padding: 20px 0;
        }

        .success-state .checkmark-wrapper {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100px;
            height: 100px;
            background: rgba(0, 201, 167, 0.12);
            border-radius: 24px;
            margin-bottom: 20px;
            animation: successPop 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .success-state .checkmark-wrapper i {
            font-size: 48px;
            color: var(--success);
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

        .success-state h2 {
            color: var(--success);
            margin: 0 0 15px;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .success-state .order-number-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            font-weight: 700;
            font-size: 16px;
            padding: 12px 24px;
            background: rgba(108, 99, 255, 0.08);
            border-radius: var(--radius-sm);
            margin: 10px 0 20px;
            border: 2px dashed rgba(108, 99, 255, 0.3);
            letter-spacing: 1px;
            font-family: 'Courier New', monospace;
        }

        .success-state .order-number-box i {
            font-size: 18px;
        }

        .success-state .info-text {
            color: var(--gray);
            margin: 15px 0 25px;
            font-size: 14px;
            line-height: 1.7;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }

        .success-state .info-text i {
            color: #25D366;
            margin-right: 4px;
        }

        .success-state .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 32px;
            background: var(--gradient-soft);
            color: white;
            text-decoration: none;
            border-radius: var(--radius-sm);
            font-weight: 700;
            font-size: 15px;
            transition: var(--transition);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            font-family: 'Inter', sans-serif;
        }

        .success-state .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }
        .success-state .btn-home:active { transform: scale(0.98); }

        /* ============================================
           BACK LINK
        ============================================ */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-top: 15px;
            transition: var(--transition);
        }

        .back-link:hover {
            text-decoration: underline;
            transform: translateX(-4px);
        }

        /* ============================================
           RESPONSIVE
        ============================================ */
        @media (max-width: 600px) {
            .order-card {
                padding: 30px 22px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn-group .btn {
                width: 100%;
            }

            .product-summary {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }

            .product-summary .product-image {
                width: 80px;
                height: 80px;
            }

            .total-display {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                gap: 5px;
            }

            .total-display .amount {
                font-size: 22px;
            }

            .order-card h1 {
                font-size: 22px;
            }

            .success-state h2 {
                font-size: 20px;
            }

            .success-state .checkmark-wrapper {
                width: 85px;
                height: 85px;
            }
            .success-state .checkmark-wrapper i {
                font-size: 40px;
            }
        }

        @media (max-width: 400px) {
            .order-card {
                padding: 22px 16px;
            }

            .quantity-control button {
                width: 38px;
                height: 38px;
                font-size: 18px;
            }

            .quantity-control input {
                width: 50px;
                font-size: 18px;
            }

            .total-display .amount {
                font-size: 20px;
            }
        }

        /* ============================================
           ANIMATIONS
        ============================================ */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(5deg); }
        }

        /* ============================================
           DARK MODE
        ============================================ */
        body.dark-mode {
            --light-bg: #0D0D1A;
            --dark: #FFFFFF;
            --gray: #8899AA;
        }

        /* ============================================
           TOAST
        ============================================ */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
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
            border-left: 4px solid var(--danger);
        }

        .toast.show {
            transform: translateY(0) scale(1);
            opacity: 1;
        }

        .toast .toast-icon {
            font-size: 22px;
            color: var(--danger);
        }
        .toast .toast-content h4 { font-size: 13px; font-weight: 700; }
        .toast .toast-content p { font-size: 12px; opacity: 0.85; }

        @media (max-width: 600px) {
            .toast {
                bottom: 16px;
                right: 16px;
                left: 16px;
                max-width: calc(100% - 32px);
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

<!-- ===== HEADER ===== -->
<header class="header">
    <div class="container">
        <a href="index.php" class="logo">
            <h1>SCI-<span>CALC</span></h1>
        </a>
        <button class="theme-toggle" onclick="toggleTheme()" aria-label="Toggle theme">
            <i class="fas fa-moon"></i>
        </button>
    </div>
</header>

<!-- ===== MAIN CONTENT ===== -->
<div class="container">
    <div class="order-card">

        <?php if ($success): ?>
            <!-- ============================================
                 SUCCESS STATE - NO TITLE, NO SUBTITLE, NO EMOJI ICON
            ============================================ -->
            <div class="success-state">
                <div class="checkmark-wrapper">
                    <i class="fas fa-check"></i>
                </div>
                <h2>Order Placed Successfully!</h2>
                <div class="order-number-box">
                    <i class="fas fa-receipt"></i>
                    <?php echo htmlspecialchars($order_number); ?>
                </div>
                <p class="info-text">
                    <i class="fab fa-whatsapp"></i>
                    We will confirm your order shortly via WhatsApp.
                </p>
                <a href="index.php" class="btn-home">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        <?php else: ?>

            <!-- ===== CARD HEADER (ONLY SHOWS BEFORE SUBMIT) ===== -->
            <div class="card-header">
                <span class="icon">📦</span>
                <h1>Place Your Order</h1>
                <p class="subtitle">Fill in your details to place an order</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($product): ?>
            <div class="product-summary">
                <?php 
                $img = 'uploads/products/' . $product['image'];
                if (!file_exists($img) || empty($product['image'])) {
                    $img = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2270%22 height=%2270%22%3E%3Crect width=%2270%22 height=%2270%22 fill=%22%23f0f2f5%22/%3E%3Ctext x=%2218%22 y=%2245%22 font-size=%2235%22%3E🧮%3C/text%3E%3C/svg%3E';
                }
                ?>
                <img src="<?php echo $img; ?>" class="product-image" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <div class="info">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <div class="price">TSh <?php echo number_format($product['price']); ?> /=</div>
                    <div class="stock">
                        <?php if ($product['stock'] > 0): ?>
                            <i class="fas fa-check-circle" style="color:var(--success);"></i> In Stock (<?php echo $product['stock']; ?>)
                        <?php else: ?>
                            <i class="fas fa-times-circle" style="color:var(--danger);"></i> Out of Stock
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form method="POST" action="" id="orderForm">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">

                <div class="form-group">
                    <label>
                        <span class="field-icon"><i class="fas fa-user"></i></span>
                        Full Name <span class="required">*</span>
                    </label>
                    <input type="text" name="customer_name" id="customer_name" 
                           placeholder="e.g., John Doe" 
                           value="<?php echo htmlspecialchars($customer_name ?? ''); ?>" 
                           required autocomplete="name">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>
                            <span class="field-icon"><i class="fas fa-phone"></i></span>
                            Phone Number <span class="required">*</span>
                        </label>
                        <input type="tel" name="customer_phone" id="customer_phone" 
                               placeholder="e.g., 0712345678" 
                               value="<?php echo htmlspecialchars($customer_phone ?? ''); ?>" 
                               required autocomplete="tel">
                    </div>
                    <div class="form-group">
                        <label>
                            <span class="field-icon"><i class="fas fa-envelope"></i></span>
                            Email <span style="color:var(--gray);font-weight:400;">(optional)</span>
                        </label>
                        <input type="email" name="customer_email" id="customer_email" 
                               placeholder="e.g., john@example.com" 
                               value="<?php echo htmlspecialchars($customer_email ?? ''); ?>" 
                               autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <span class="field-icon"><i class="fas fa-map-marker-alt"></i></span>
                        Delivery Address <span class="required">*</span>
                    </label>
                    <textarea name="delivery_address" id="delivery_address" 
                              placeholder="e.g., Dar es Salaam, Sinza, Block A" 
                              required><?php echo htmlspecialchars($delivery_address ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        <span class="field-icon"><i class="fas fa-sort-numeric-up"></i></span>
                        Quantity <span class="required">*</span>
                    </label>
                    <div class="quantity-control">
                        <button type="button" onclick="changeQty(-1)" aria-label="Decrease quantity">
                            <i class="fas fa-minus"></i>
                        </button>
                        <input type="number" name="quantity" id="quantity" 
                               value="<?php echo $quantity ?? 1; ?>" 
                               min="1" max="<?php echo $product['stock']; ?>" 
                               required>
                        <button type="button" onclick="changeQty(1)" aria-label="Increase quantity">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <div style="font-size:12px;color:var(--gray);margin-top:6px;">
                        Max: <?php echo $product['stock']; ?> units available
                    </div>
                </div>

                <div class="form-group">
                    <label>
                        <span class="field-icon"><i class="fas fa-comment"></i></span>
                        Special Instructions <span style="color:var(--gray);font-weight:400;">(optional)</span>
                    </label>
                    <textarea name="special_instructions" id="special_instructions" 
                              placeholder="Any special requests?"><?php echo htmlspecialchars($special_instructions ?? ''); ?></textarea>
                </div>

                <div class="total-display">
                    <span class="label">Total Amount:</span>
                    <span class="amount" id="totalAmount">TSh <?php echo number_format($product['price'] * ($quantity ?? 1)); ?> /=</span>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Submit Order
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
            <?php else: ?>
                <div style="text-align:center;padding:40px 20px;">
                    <div style="font-size:64px;color:var(--danger);margin-bottom:15px;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3 style="color:var(--dark);margin:15px 0;font-size:20px;">Product not found</h3>
                    <p style="color:var(--gray);margin-bottom:20px;">Please go back and select a valid product.</p>
                    <a href="index.php" class="btn-home" style="display:inline-flex;align-items:center;gap:8px;padding:14px 32px;background:var(--gradient-soft);color:white;text-decoration:none;border-radius:10px;font-weight:700;">
                        <i class="fas fa-home"></i> Back to Home
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<!-- ===== TOAST ===== -->
<div class="toast" id="toast">
    <div class="toast-icon"><i class="fas fa-exclamation-circle"></i></div>
    <div class="toast-content">
        <h4>Attention</h4>
        <p id="toastMessage">Please fill all required fields</p>
    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
    // ========================================
    // QUANTITY CONTROL
    // ========================================
    function changeQty(delta) {
        const input = document.getElementById('quantity');
        if (!input) return;
        let val = parseInt(input.value) || 1;
        val = Math.max(1, val + delta);
        const max = parseInt(input.max) || 999;
        if (val > max) val = max;
        input.value = val;
        updateTotal();
        animateTotal();
    }

    function updateTotal() {
        const input = document.getElementById('quantity');
        if (!input) return;
        const qty = parseInt(input.value) || 1;
        const price = <?php echo $product['price'] ?? 0; ?>;
        const total = qty * price;
        const amountEl = document.getElementById('totalAmount');
        if (amountEl) {
            amountEl.textContent = 'TSh ' + total.toLocaleString() + ' /=';
        }
    }

    function animateTotal() {
        const amountEl = document.getElementById('totalAmount');
        if (!amountEl) return;
        amountEl.classList.remove('pop');
        void amountEl.offsetWidth;
        amountEl.classList.add('pop');
    }

    const qtyInput = document.getElementById('quantity');
    if (qtyInput) {
        qtyInput.addEventListener('change', function() {
            updateTotal();
            animateTotal();
        });

        qtyInput.addEventListener('input', function() {
            let val = parseInt(this.value) || 1;
            const max = parseInt(this.max) || 999;
            if (val > max) val = max;
            if (val < 1) val = 1;
            this.value = val;
            updateTotal();
        });
    }

    // ========================================
    // FORM VALIDATION
    // ========================================
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            const name = document.getElementById('customer_name').value.trim();
            const phone = document.getElementById('customer_phone').value.trim();
            const address = document.getElementById('delivery_address').value.trim();
            const qty = parseInt(document.getElementById('quantity').value) || 0;
            const maxQty = parseInt(document.getElementById('quantity').max) || 0;

            document.querySelectorAll('.form-group input, .form-group textarea').forEach(el => {
                el.classList.remove('success', 'error');
            });

            let isValid = true;
            let errorMsg = '';

            if (!name) {
                document.getElementById('customer_name').classList.add('error');
                errorMsg = 'Please enter your full name!';
                isValid = false;
            } else {
                document.getElementById('customer_name').classList.add('success');
            }

            if (!phone) {
                document.getElementById('customer_phone').classList.add('error');
                errorMsg = 'Please enter your phone number!';
                isValid = false;
            } else if (!/^[0-9]{10,12}$/.test(phone.replace(/[^0-9]/g, ''))) {
                document.getElementById('customer_phone').classList.add('error');
                errorMsg = 'Please enter a valid phone number (10-12 digits)!';
                isValid = false;
            } else {
                document.getElementById('customer_phone').classList.add('success');
            }

            if (!address) {
                document.getElementById('delivery_address').classList.add('error');
                errorMsg = 'Please enter your delivery address!';
                isValid = false;
            } else {
                document.getElementById('delivery_address').classList.add('success');
            }

            if (qty < 1) {
                errorMsg = 'Quantity must be at least 1!';
                isValid = false;
            } else if (qty > maxQty) {
                errorMsg = 'Only ' + maxQty + ' units available in stock!';
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
                showToast(errorMsg);
                
                const firstError = document.querySelector('.form-group input.error, .form-group textarea.error');
                if (firstError) {
                    firstError.focus();
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return false;
            }

            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;

            return true;
        });
    }

    // ========================================
    // REAL-TIME VALIDATION
    // ========================================
    document.querySelectorAll('.form-group input, .form-group textarea').forEach(el => {
        el.addEventListener('blur', function() {
            if (this.value.trim() && this.hasAttribute('required')) {
                this.classList.remove('error');
                this.classList.add('success');
            } else if (this.hasAttribute('required')) {
                this.classList.remove('success');
                this.classList.add('error');
            }
        });

        el.addEventListener('focus', function() {
            this.classList.remove('error', 'success');
        });

        if (el.type === 'tel') {
            el.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9+]/g, '');
            });
        }
    });

    // ========================================
    // TOAST
    // ========================================
    let toastTimeout;

    function showToast(message) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMessage').textContent = message;
        toast.classList.add('show');
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => toast.classList.remove('show'), 4000);
    }

    // ========================================
    // DARK MODE
    // ========================================
    function toggleTheme() {
        const body = document.body;
        const btn = document.querySelector('.theme-toggle');
        body.classList.toggle('dark-mode');
        btn.innerHTML = body.classList.contains('dark-mode') 
            ? '<i class="fas fa-sun"></i>' 
            : '<i class="fas fa-moon"></i>';
        localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
    }

    document.addEventListener('DOMContentLoaded', function() {
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
            document.querySelector('.theme-toggle').innerHTML = '<i class="fas fa-sun"></i>';
        }
    });

    // ========================================
    // KEYBOARD SHORTCUTS
    // ========================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'd' || e.key === 'D') {
            toggleTheme();
        }
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            const form = document.getElementById('orderForm');
            if (form) form.dispatchEvent(new Event('submit'));
        }
        if (e.key === 'Escape') {
            window.location.href = 'index.php';
        }
    });

    console.log('%c📦 SCI-CALC Order Page', 'font-size:24px;font-weight:bold;color:#6C63FF;');
    console.log('%c📱 WhatsApp: <?php echo $settings['whatsapp_number']; ?>', 'font-size:14px;color:#25D366;');
    console.log('%c✅ Order page loaded successfully!', 'font-size:14px;color:#00C9A7;');
</script>

</body>
</html>