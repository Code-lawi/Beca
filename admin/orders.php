<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config/database.php';
requireAdminLogin();

$order_counts = getOrderCounts($conn);
$settings = getSettings($conn);

$success = '';
$error = '';
$whatsapp_redirect = '';
$whatsapp_type = '';

/*
|--------------------------------------------------------------------------
| HELPER: Format WhatsApp Number
|--------------------------------------------------------------------------
*/
function formatWhatsAppNumber($phone)
{
    $phone = preg_replace('/[^0-9]/', '', $phone);

    if (substr($phone, 0, 1) === '0') {
        $phone = '255' . substr($phone, 1);
    }

    if (substr($phone, 0, 3) !== '255' && strlen($phone) === 9) {
        $phone = '255' . $phone;
    }

    return $phone;
}

/*
|--------------------------------------------------------------------------
| HANDLE GET ACTION
|--------------------------------------------------------------------------
*/
$action = $_GET['action'] ?? '';
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

/*
|--------------------------------------------------------------------------
| DELETE ORDER - For Approved & Rejected orders only
|--------------------------------------------------------------------------
*/
if ($action === 'delete' && $order_id > 0) {
    
    $filter = $_GET['filter'] ?? 'pending';
    
    try {
        
        $table = '';
        $id_column = '';
        
        if ($filter === 'approved') {
            $table = 'approved_orders';
            $id_column = 'id';
        } elseif ($filter === 'rejected') {
            $table = 'rejected_orders';
            $id_column = 'id';
        } else {
            $error = "❌ Cannot delete pending orders. Please approve or reject them first.";
        }
        
        if (!empty($table)) {
            
            $stmt = $conn->prepare("SELECT order_number FROM $table WHERE $id_column = ? LIMIT 1");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $error = "❌ Order not found.";
            } else {
                $order = $result->fetch_assoc();
                $order_number = $order['order_number'];
                
                $stmt2 = $conn->prepare("DELETE FROM $table WHERE $id_column = ?");
                $stmt2->bind_param("i", $order_id);
                
                if ($stmt2->execute()) {
                    $success = "🗑️ Order #$order_number has been deleted successfully!";
                    $order_counts = getOrderCounts($conn);
                } else {
                    $error = "❌ Failed to delete order: " . $stmt2->error;
                }
                
                $stmt2->close();
            }
            
            $stmt->close();
        }
        
    } catch (mysqli_sql_exception $e) {
        $error = "❌ Database error: " . $e->getMessage();
        error_log("Database error (delete): " . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| APPROVE ORDER
|--------------------------------------------------------------------------
*/
if ($action === 'approve' && $order_id > 0) {

    try {

        $stmt = $conn->prepare("
            SELECT *
            FROM pending_orders
            WHERE id = ?
            LIMIT 1
        ");

        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 0) {

            $error = "❌ Order haipo au tayari imechakatwa.";

        } else {

            $order = $result->fetch_assoc();

            /*
            |--------------------------------------------------------------------------
            | CHECK IF ALREADY APPROVED
            |--------------------------------------------------------------------------
            */
            $checkApproved = $conn->prepare("
                SELECT id
                FROM approved_orders
                WHERE order_number = ?
                LIMIT 1
            ");

            $checkApproved->bind_param(
                "s",
                $order['order_number']
            );

            $checkApproved->execute();

            $approvedResult = $checkApproved->get_result();

            if ($approvedResult->num_rows > 0) {

                $error = "⚠️ Order #{$order['order_number']} tayari ime-approve.";

                $checkApproved->close();

            } else {

                $checkApproved->close();

                /*
                |--------------------------------------------------------------------------
                | INSERT APPROVED ORDER
                |--------------------------------------------------------------------------
                */
                $stmt2 = $conn->prepare("
                    INSERT INTO approved_orders
                    (
                        order_number,
                        customer_name,
                        customer_phone,
                        customer_email,
                        delivery_address,
                        product_id,
                        product_name,
                        product_price,
                        quantity,
                        total_amount,
                        special_instructions,
                        approved_by,
                        approved_at
                    )
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");

                $approved_by = $_SESSION['admin_username'] ?? 'Admin';

                $stmt2->bind_param(
                    "sssssisdidss",
                    $order['order_number'],
                    $order['customer_name'],
                    $order['customer_phone'],
                    $order['customer_email'],
                    $order['delivery_address'],
                    $order['product_id'],
                    $order['product_name'],
                    $order['product_price'],
                    $order['quantity'],
                    $order['total_amount'],
                    $order['special_instructions'],
                    $approved_by
                );

                if ($stmt2->execute()) {

                    /*
                    |--------------------------------------------------------------------------
                    | DELETE PENDING ORDER
                    |--------------------------------------------------------------------------
                    */
                    $stmt3 = $conn->prepare("
                        DELETE FROM pending_orders
                        WHERE id = ?
                    ");

                    $stmt3->bind_param("i", $order_id);
                    $stmt3->execute();
                    $stmt3->close();

                    /*
                    |--------------------------------------------------------------------------
                    | PREPARE WHATSAPP APPROVAL MESSAGE
                    |--------------------------------------------------------------------------
                    */
                    $whatsapp_number = formatWhatsAppNumber(
                        $order['customer_phone']
                    );

                    // ============================================
                    // PAYMENT INSTRUCTIONS - From Settings
                    // ============================================
                    $payment_number = $settings['payment_number'] ?? WHATSAPP_NUMBER;
                    $payment_name = $settings['payment_name'] ?? $settings['site_name'] ?? 'SCI-CALC Store';
                    $payment_type = $settings['payment_type'] ?? 'M-Pesa';

                    $message = "✅ *Order Confirmed!*\n\n";

                    $message .= "Dear *" .
                        $order['customer_name'] .
                        "*,\n\n";

                    $message .= "Your order #" .
                        $order['order_number'] .
                        " has been *APPROVED*! ✅\n\n";

                    $message .= "📦 *Order Details:*\n";

                    $message .= "• Product: " .
                        $order['product_name'] .
                        "\n";

                    $message .= "• Quantity: " .
                        $order['quantity'] .
                        "\n";

                    $message .= "• Total: TSh " .
                        number_format($order['total_amount']) .
                        "/=\n";

                    $message .= "• Delivery: " .
                        $order['delivery_address'] .
                        "\n\n";

                    // ============================================
                    // PAYMENT INSTRUCTIONS
                    // ============================================
                    $message .= "💰 *" . strtoupper($payment_type) . " PAYMENT:*\n\n";

                    $message .= "Tafadhali fuata taratibu za malipo ili kutumiwa mzigo wako.\n\n";

                    $message .= "📱 *Namba:* " . $payment_number . "\n";
                    $message .= "👤 *Jina:* " . $payment_name . "\n";
                    $message .= "💵 *Kiasi:* TSh " . number_format($order['total_amount']) . "/=\n\n";

                    $message .= "📌 *Hatua za Malipo:*\n";
                    $message .= "1. Nenda kwenye Menu ya " . $payment_type . "\n";
                    $message .= "2. Chagua 'Lipa kwa Namba' / 'Send Money'\n";
                    $message .= "3. Weka namba: *" . $payment_number . "*\n";
                    $message .= "4. Weka kiasi: *TSh " . number_format($order['total_amount']) . "/=*\n";
                    $message .= "5. Thibitisha malipo\n";
                    $message .= "6. Tuma uthibitisho hapa WhatsApp\n\n";

                    $message .= "📸 *Tuma picha ya risiti* hapa ili mzigo wako usafirishwe haraka.\n\n";

                    $message .= "⚠️ Mzigo utasafirishwa mara baada ya malipo kuthibitishwa.\n\n";

                    $message .= "Asante kwa kuchagua SCI-CALC! 🧮\n\n";

                    $message .= "📞 *Mawasiliano:* " . WHATSAPP_NUMBER;

                    $whatsapp_url =
                        "https://wa.me/" .
                        $whatsapp_number .
                        "?text=" .
                        urlencode($message);

                    $success =
                        "✅ Order #{$order['order_number']} approved successfully!";

                    $whatsapp_redirect = $whatsapp_url;
                    $whatsapp_type = 'approved';

                    $order_counts = getOrderCounts($conn);

                    error_log("WhatsApp redirect (approve): " . $whatsapp_redirect);

                } else {

                    $error =
                        "❌ Failed to approve order: " .
                        $stmt2->error;
                }

                $stmt2->close();
            }
        }

        $stmt->close();

    } catch (mysqli_sql_exception $e) {

        $error =
            "❌ Database error: " .
            $e->getMessage();
        
        error_log("Database error (approve): " . $e->getMessage());
    }
}

/*
|--------------------------------------------------------------------------
| REJECT ORDER - POST
|--------------------------------------------------------------------------
*/
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    ($_POST['action'] ?? '') === 'reject'
) {

    $order_id = intval($_POST['order_id'] ?? 0);

    $rejection_reason =
        trim($_POST['rejection_reason'] ?? '');

    if ($order_id <= 0) {

        $error = "❌ Invalid order ID.";

    } elseif ($rejection_reason === '') {

        $error = "❌ Tafadhali weka sababu ya kukataa order.";

    } else {

        try {

            $stmt = $conn->prepare("
                SELECT *
                FROM pending_orders
                WHERE id = ?
                LIMIT 1
            ");

            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {

                $error =
                    "❌ Order haipo au tayari imechakatwa.";

            } else {

                $order = $result->fetch_assoc();

                $checkRejected = $conn->prepare("
                    SELECT id
                    FROM rejected_orders
                    WHERE order_number = ?
                    LIMIT 1
                ");

                $checkRejected->bind_param(
                    "s",
                    $order['order_number']
                );

                $checkRejected->execute();

                $rejectedResult =
                    $checkRejected->get_result();

                if ($rejectedResult->num_rows > 0) {

                    $error =
                        "⚠️ Order #{$order['order_number']} tayari ime-rejectiwa.";

                    $deletePending = $conn->prepare("
                        DELETE FROM pending_orders
                        WHERE id = ?
                    ");

                    $deletePending->bind_param(
                        "i",
                        $order_id
                    );

                    $deletePending->execute();
                    $deletePending->close();

                    $checkRejected->close();

                } else {

                    $checkRejected->close();

                    $stmt2 = $conn->prepare("
                        INSERT INTO rejected_orders
                        (
                            order_number,
                            customer_name,
                            customer_phone,
                            customer_email,
                            delivery_address,
                            product_id,
                            product_name,
                            product_price,
                            quantity,
                            total_amount,
                            special_instructions,
                            rejected_by,
                            rejection_reason,
                            rejected_at
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");

                    $rejected_by =
                        $_SESSION['admin_username'] ?? 'Admin';

                    $stmt2->bind_param(
                        "sssssisdidsss",
                        $order['order_number'],
                        $order['customer_name'],
                        $order['customer_phone'],
                        $order['customer_email'],
                        $order['delivery_address'],
                        $order['product_id'],
                        $order['product_name'],
                        $order['product_price'],
                        $order['quantity'],
                        $order['total_amount'],
                        $order['special_instructions'],
                        $rejected_by,
                        $rejection_reason
                    );

                    if ($stmt2->execute()) {

                        $stmt3 = $conn->prepare("
                            DELETE FROM pending_orders
                            WHERE id = ?
                        ");

                        $stmt3->bind_param(
                            "i",
                            $order_id
                        );

                        $stmt3->execute();
                        $stmt3->close();

                        $whatsapp_number =
                            formatWhatsAppNumber(
                                $order['customer_phone']
                            );

                        $message =
                            "❌ *Order Rejected*\n\n";

                        $message .=
                            "Dear *" .
                            $order['customer_name'] .
                            "*,\n\n";

                        $message .=
                            "Your order #" .
                            $order['order_number'] .
                            " has been *REJECTED*.\n\n";

                        $message .=
                            "📦 *Order Details:*\n";

                        $message .=
                            "• Product: " .
                            $order['product_name'] .
                            "\n";

                        $message .=
                            "• Quantity: " .
                            $order['quantity'] .
                            "\n";

                        $message .=
                            "• Total: TSh " .
                            number_format(
                                $order['total_amount']
                            ) .
                            "/=\n\n";

                        $message .=
                            "📝 *Reason:*\n" .
                            $rejection_reason .
                            "\n\n";

                        $message .=
                            "For more information, contact us:\n";

                        $message .=
                            "📱 " .
                            WHATSAPP_NUMBER;

                        $whatsapp_url =
                            "https://wa.me/" .
                            $whatsapp_number .
                            "?text=" .
                            urlencode($message);

                        $success =
                            "❌ Order #{$order['order_number']} rejected successfully!";

                        $whatsapp_redirect =
                            $whatsapp_url;

                        $whatsapp_type = 'rejected';

                        $order_counts = getOrderCounts($conn);

                        error_log("WhatsApp redirect (reject): " . $whatsapp_redirect);

                    } else {

                        $error =
                            "❌ Failed to reject order: " .
                            $stmt2->error;
                    }

                    $stmt2->close();
                }
            }

            $stmt->close();

        } catch (mysqli_sql_exception $e) {

            $error =
                "❌ Database error: " .
                $e->getMessage();
            
            error_log("Database error (reject): " . $e->getMessage());
        }
    }
}

/*
|--------------------------------------------------------------------------
| GET ORDERS
|--------------------------------------------------------------------------
*/
$filter = $_GET['filter'] ?? 'pending';

$orders = [];

if ($filter === 'pending') {
    $orders = getPendingOrders($conn);
} elseif ($filter === 'approved') {
    $orders = getApprovedOrders($conn);
} elseif ($filter === 'rejected') {
    $orders = getRejectedOrders($conn);
} else {
    $filter = 'pending';
    $orders = getPendingOrders($conn);
}

?>
<!DOCTYPE html>
<html lang="sw">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - SCI-CALC Admin</title>
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

        .top-bar .order-stats {
            display: flex;
            gap: 16px;
            font-size: 13px;
            color: var(--gray);
        }

        .top-bar .order-stats span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .top-bar .order-stats .stat-number {
            font-weight: 700;
            color: var(--dark);
        }

        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            animation: cardFadeIn 0.6s ease forwards;
            animation-delay: 0.15s;
            opacity: 0;
            transform: translateY(20px);
        }

        @keyframes cardFadeIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .tabs a {
            padding: 10px 22px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            background: var(--white);
            color: var(--gray);
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
        }

        .tabs a:hover {
            border-color: var(--primary);
            color: var(--dark);
        }

        .tabs a.active {
            background: var(--primary-gradient);
            color: white;
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(108, 99, 255, 0.3);
        }

        .tabs a .badge {
            background: rgba(255,255,255,0.2);
            padding: 1px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }

        .tabs a.active .badge {
            background: rgba(255,255,255,0.25);
        }

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

        .alert-whatsapp {
            background: rgba(37, 211, 102, 0.10);
            color: #1DA851;
            border-left: 4px solid #25D366;
        }

        .alert-whatsapp .btn-whatsapp {
            background: #25D366;
            color: white;
            padding: 6px 16px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            margin-left: auto;
        }

        .alert-whatsapp .btn-whatsapp:hover {
            background: #1DA851;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .order-card {
            background: var(--white);
            border-radius: var(--radius);
            padding: 20px 24px;
            margin-bottom: 16px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border-left: 4px solid var(--primary);
            animation: cardFadeIn 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .order-card:nth-child(1) { animation-delay: 0.2s; }
        .order-card:nth-child(2) { animation-delay: 0.25s; }
        .order-card:nth-child(3) { animation-delay: 0.3s; }
        .order-card:nth-child(4) { animation-delay: 0.35s; }
        .order-card:nth-child(5) { animation-delay: 0.4s; }

        .order-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-2px);
        }

        .order-card.pending-card { border-left-color: var(--warning); }
        .order-card.approved-card { border-left-color: var(--success); }
        .order-card.rejected-card { border-left-color: var(--danger); }

        .order-card .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .order-card .order-number {
            font-weight: 700;
            font-size: 16px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .order-card .order-number i { color: var(--primary); }

        .order-card .order-date {
            color: var(--gray);
            font-size: 12px;
            font-weight: 400;
        }

        .order-card .order-status {
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .order-card .order-status.pending {
            background: rgba(255, 193, 7, 0.12);
            color: #B7950B;
        }

        .order-card .order-status.approved {
            background: rgba(0, 201, 167, 0.12);
            color: var(--success);
        }

        .order-card .order-status.rejected {
            background: rgba(255, 107, 107, 0.12);
            color: var(--danger);
        }

        .order-card .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
            margin: 14px 0;
        }

        .order-card .order-details .detail { font-size: 13px; }

        .order-card .order-details .detail .label {
            color: var(--gray);
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-card .order-details .detail .value {
            font-weight: 600;
            color: var(--dark);
            margin-top: 2px;
        }

        .order-card .order-details .detail .value .phone {
            font-size: 12px;
            color: var(--gray);
            font-weight: 400;
        }

        .order-card .order-details .detail .total-price {
            color: var(--success);
            font-size: 18px;
        }

        .order-card .order-actions {
            display: flex;
            gap: 8px;
            margin-top: 14px;
            padding-top: 14px;
            border-top: 1px solid rgba(0,0,0,0.05);
            flex-wrap: wrap;
        }

        .btn {
            padding: 8px 18px;
            border: none;
            border-radius: var(--radius-sm);
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: var(--transition);
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: 'Inter', sans-serif;
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: var(--success-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 201, 167, 0.3);
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: var(--danger-dark);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
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

        .btn-secondary {
            background: var(--light-gray);
            color: var(--gray);
        }

        .btn-secondary:hover {
            background: #C8D0D8;
            transform: translateY(-2px);
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #bd2130;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .btn-sm {
            padding: 5px 14px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            animation: cardFadeIn 0.6s ease forwards;
            opacity: 0;
            transform: translateY(20px);
        }

        .empty-state .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            color: var(--dark);
            margin-bottom: 6px;
            font-size: 18px;
        }

        .empty-state p { font-size: 14px; }

        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal.show {
            display: flex;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: 32px;
            max-width: 500px;
            width: 92%;
            box-shadow: 0 30px 80px rgba(0,0,0,0.3);
        }

        .modal-content .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .modal-content .modal-header h3 {
            font-size: 18px;
            color: var(--dark);
        }

        .modal-content .modal-header .close-btn {
            font-size: 24px;
            color: var(--gray);
            cursor: pointer;
            transition: var(--transition);
            background: none;
            border: none;
            font-family: 'Inter', sans-serif;
        }

        .modal-content .modal-header .close-btn:hover {
            color: var(--danger);
            transform: rotate(90deg);
        }

        .modal-content .order-label {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .modal-content .hint-text {
            color: var(--gray);
            margin-bottom: 16px;
            font-size: 13px;
        }

        .modal-content textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--light-gray);
            border-radius: var(--radius-sm);
            resize: vertical;
            min-height: 100px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            transition: var(--transition);
            outline: none;
            background: var(--bg);
        }

        .modal-content textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(108, 99, 255, 0.08);
            background: var(--white);
        }

        .modal-content .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 16px;
        }

        .modal-content .btn-group .btn {
            flex: 1;
            justify-content: center;
        }

        .whatsapp-popup {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
            backdrop-filter: blur(8px);
        }

        .whatsapp-popup.show {
            display: flex;
            animation: modalFadeIn 0.4s ease;
        }

        .whatsapp-popup .popup-content {
            background: var(--white);
            border-radius: var(--radius);
            padding: 40px;
            max-width: 450px;
            width: 92%;
            text-align: center;
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes popIn {
            from { transform: scale(0.8) translateY(30px); opacity: 0; }
            to { transform: scale(1) translateY(0); opacity: 1; }
        }

        .whatsapp-popup .popup-content .icon {
            font-size: 60px;
            margin-bottom: 12px;
        }

        .whatsapp-popup .popup-content h2 {
            font-size: 22px;
            margin-bottom: 8px;
            font-weight: 700;
        }

        .whatsapp-popup .popup-content h2.approved { color: var(--success); }
        .whatsapp-popup .popup-content h2.rejected { color: var(--danger); }

        .whatsapp-popup .popup-content p {
            color: var(--gray);
            font-size: 14px;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .whatsapp-popup .popup-content .btn-group {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .whatsapp-popup .popup-content .btn-group .btn {
            min-width: 140px;
            justify-content: center;
        }

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
            .order-card .order-details { grid-template-columns: 1fr; gap: 10px; }
            .top-bar .order-stats { flex-wrap: wrap; gap: 8px; }
        }

        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
                padding: 16px;
            }

            .top-bar h1 { font-size: 18px; }
            .top-bar .order-stats { justify-content: flex-start; }
            .main-content { padding: 12px 12px 30px; }
            .tabs { flex-direction: column; }
            .tabs a { justify-content: center; }
            .order-card { padding: 16px; }
            .order-card .order-header { flex-direction: column; align-items: flex-start; }
            .order-card .order-actions { flex-direction: column; }
            .order-card .order-actions .btn { width: 100%; justify-content: center; }
            .modal-content { padding: 24px; }
            .modal-content .btn-group { flex-direction: column; }
            .whatsapp-popup .popup-content { padding: 28px; }
            .whatsapp-popup .popup-content .btn-group { flex-direction: column; }
            .whatsapp-popup .popup-content .btn-group .btn { width: 100%; }
        }

        @media (max-width: 400px) {
            .order-card .order-details .detail .total-price { font-size: 15px; }
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
            <a href="orders.php" class="active">
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

<!-- MAIN CONTENT -->
<div class="main-content">

    <div class="top-bar">
        <h1>
            <i class="fas fa-clipboard-list"></i>
            Orders
        </h1>
        <div class="order-stats">
            <span><i class="fas fa-clock" style="color:var(--warning);"></i> Pending: <span class="stat-number"><?php echo $order_counts['pending']; ?></span></span>
            <span><i class="fas fa-check-circle" style="color:var(--success);"></i> Approved: <span class="stat-number"><?php echo $order_counts['approved']; ?></span></span>
            <span><i class="fas fa-times-circle" style="color:var(--danger);"></i> Rejected: <span class="stat-number"><?php echo $order_counts['rejected']; ?></span></span>
        </div>
    </div>

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

    <?php if (isset($whatsapp_redirect) && !empty($whatsapp_redirect)): ?>
        <div class="alert alert-whatsapp">
            <i class="fab fa-whatsapp" style="font-size:20px;"></i>
            <strong>WhatsApp Notification Ready!</strong>
            <a href="<?php echo htmlspecialchars($whatsapp_redirect); ?>" target="_blank" rel="noopener noreferrer" class="btn-whatsapp">
                <i class="fab fa-whatsapp"></i> Open WhatsApp
            </a>
        </div>
    <?php endif; ?>

    <div class="tabs">
        <a href="?filter=pending" class="<?php echo $filter === 'pending' ? 'active' : ''; ?>">
            <i class="fas fa-clock"></i> Pending
            <span class="badge"><?php echo $order_counts['pending']; ?></span>
        </a>
        <a href="?filter=approved" class="<?php echo $filter === 'approved' ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i> Approved
            <span class="badge"><?php echo $order_counts['approved']; ?></span>
        </a>
        <a href="?filter=rejected" class="<?php echo $filter === 'rejected' ? 'active' : ''; ?>">
            <i class="fas fa-times-circle"></i> Rejected
            <span class="badge"><?php echo $order_counts['rejected']; ?></span>
        </a>
    </div>

    <?php if (count($orders) > 0): ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card <?php echo $filter === 'pending' ? 'pending-card' : ($filter === 'approved' ? 'approved-card' : 'rejected-card'); ?>">
                
                <div class="order-header">
                    <div>
                        <span class="order-number">
                            <i class="fas fa-receipt"></i>
                            <?php echo htmlspecialchars($order['order_number']); ?>
                        </span>
                        <span class="order-date">
                            <i class="far fa-calendar-alt"></i>
                            <?php
                            $date_field = 'created_at';
                            if ($filter === 'approved') {
                                $date_field = 'approved_at';
                            } elseif ($filter === 'rejected') {
                                $date_field = 'rejected_at';
                            }
                            echo date('M d, Y H:i', strtotime($order[$date_field] ?? 'now'));
                            ?>
                        </span>
                    </div>
                    <?php if ($filter === 'pending'): ?>
                        <span class="order-status pending"><i class="fas fa-clock"></i> Pending</span>
                    <?php elseif ($filter === 'approved'): ?>
                        <span class="order-status approved"><i class="fas fa-check-circle"></i> Approved</span>
                    <?php else: ?>
                        <span class="order-status rejected"><i class="fas fa-times-circle"></i> Rejected</span>
                    <?php endif; ?>
                </div>

                <div class="order-details">
                    <div class="detail">
                        <div class="label"><i class="fas fa-user"></i> Customer</div>
                        <div class="value">
                            <?php echo htmlspecialchars($order['customer_name']); ?>
                            <div class="phone"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($order['customer_phone']); ?></div>
                        </div>
                    </div>
                    <div class="detail">
                        <div class="label"><i class="fas fa-map-pin"></i> Delivery</div>
                        <div class="value">
                            <?php 
                            $address = htmlspecialchars($order['delivery_address']);
                            echo strlen($address) > 50 ? substr($address, 0, 50) . '...' : $address;
                            ?>
                        </div>
                    </div>
                    <div class="detail">
                        <div class="label"><i class="fas fa-shopping-cart"></i> Product</div>
                        <div class="value">
                            <?php echo htmlspecialchars($order['product_name']); ?>
                            <div class="phone">x<?php echo $order['quantity']; ?></div>
                        </div>
                    </div>
                    <div class="detail">
                        <div class="label"><i class="fas fa-money-bill-wave"></i> Total</div>
                        <div class="value total-price">
                            TSh <?php echo number_format($order['total_amount']); ?> /=
                        </div>
                    </div>
                </div>

                <div class="order-actions">
                    <?php if ($filter === 'pending'): ?>
                        <a href="?action=approve&id=<?php echo $order['id']; ?>" class="btn btn-success btn-sm" 
                           onclick="return confirmApprove(
                               '<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>',
                               '<?php echo htmlspecialchars($order['customer_name'], ENT_QUOTES); ?>',
                               '<?php echo htmlspecialchars($order['customer_phone'], ENT_QUOTES); ?>'
                           );">
                            <i class="fas fa-check"></i> Approve
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" 
                                onclick="showRejectModal(
                                    <?php echo intval($order['id']); ?>,
                                    '<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>'
                                );">
                            <i class="fas fa-times"></i> Reject
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" 
                                onclick="viewOrder('<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>')">
                            <i class="fas fa-eye"></i> View
                        </button>
                    <?php elseif ($filter === 'approved'): ?>
                        <span style="color:var(--success);font-size:12px;padding:5px 0;flex:1;">
                            <i class="fas fa-user-check"></i> Approved by: <?php echo htmlspecialchars($order['approved_by'] ?? 'Admin'); ?>
                            on <?php echo date('M d, Y H:i', strtotime($order['approved_at'] ?? 'now')); ?>
                        </span>
                        <a href="?action=delete&id=<?php echo $order['id']; ?>&filter=approved" class="btn btn-delete btn-sm" 
                           onclick="return confirmDelete('<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>', 'approved');">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    <?php else: ?>
                        <span style="color:var(--danger);font-size:12px;padding:5px 0;flex:1;">
                            <i class="fas fa-user-times"></i> Rejected by: <?php echo htmlspecialchars($order['rejected_by'] ?? 'Admin'); ?>
                            <br><i class="fas fa-comment"></i> Reason: <?php echo htmlspecialchars($order['rejection_reason'] ?? 'No reason provided'); ?>
                        </span>
                        <a href="?action=delete&id=<?php echo $order['id']; ?>&filter=rejected" class="btn btn-delete btn-sm" 
                           onclick="return confirmDelete('<?php echo htmlspecialchars($order['order_number'], ENT_QUOTES); ?>', 'rejected');">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h3>No <?php echo htmlspecialchars($filter); ?> orders</h3>
            <p>All <?php echo htmlspecialchars($filter); ?> orders will appear here.</p>
        </div>
    <?php endif; ?>
</div>

<!-- REJECT MODAL -->
<div class="modal" id="rejectModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle" style="color:var(--danger);"></i> Reject Order</h3>
            <button type="button" class="close-btn" onclick="closeModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="order-label" id="rejectOrderLabel">Order #</div>
        <div class="hint-text">Please provide a reason for rejecting this order:</div>
        <form method="POST" id="rejectForm" action="">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="order_id" id="reject_order_id">
            <textarea name="rejection_reason" id="rejection_reason" 
                      placeholder="e.g., Out of stock, Delivery unavailable, Invalid order..." 
                      required></textarea>
            <div class="btn-group">
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Confirm Reject</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<!-- WHATSAPP POPUP -->
<div class="whatsapp-popup" id="whatsappPopup">
    <div class="popup-content">
        <div class="icon">
            <?php echo (isset($whatsapp_type) && $whatsapp_type === 'rejected') ? '❌' : '💬'; ?>
        </div>
        <h2 class="<?php echo (isset($whatsapp_type) && $whatsapp_type === 'rejected') ? 'rejected' : 'approved'; ?>">
            <?php echo (isset($whatsapp_type) && $whatsapp_type === 'rejected') ? 'Order Rejected' : 'Order Approved!'; ?>
        </h2>
        <p>
            <?php echo (isset($whatsapp_type) && $whatsapp_type === 'rejected') 
                ? 'Send rejection notification to the customer via WhatsApp?' 
                : 'Send approval + payment instructions to the customer via WhatsApp?'; ?>
        </p>
        <div class="btn-group">
            <a href="<?php echo isset($whatsapp_redirect) ? htmlspecialchars($whatsapp_redirect) : '#'; ?>" 
               target="_blank" rel="noopener noreferrer" class="btn btn-success">
                <i class="fab fa-whatsapp"></i> Send via WhatsApp
            </a>
            <a href="orders.php" class="btn btn-secondary">Skip</a>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        'use strict';

        window.showRejectModal = function(orderId, orderNumber) {
            document.getElementById('reject_order_id').value = orderId;
            document.getElementById('rejectOrderLabel').textContent = 'Order #' + orderNumber;
            document.getElementById('rejectForm').action = 'orders.php';
            document.getElementById('rejection_reason').value = '';
            document.getElementById('rejectModal').classList.add('show');
            
            setTimeout(function() {
                document.getElementById('rejection_reason').focus();
            }, 150);
        };

        window.closeModal = function() {
            document.getElementById('rejectModal').classList.remove('show');
        };

        window.viewOrder = function(orderNumber) {
            alert(
                '📋 Order Details\n\n' +
                'Order #' + orderNumber + '\n\n' +
                'Full details will be shown in a dedicated view.\n' +
                'This feature is coming soon! 🚀'
            );
        };

        window.confirmApprove = function(orderNumber, customerName, phone) {
            return confirm(
                '✅ Approve Order\n\n' +
                'Order #' + orderNumber + '\n' +
                'Customer: ' + customerName + '\n' +
                'Phone: ' + phone + '\n\n' +
                'A WhatsApp message with PAYMENT INSTRUCTIONS will be prepared for this customer.'
            );
        };

        window.confirmDelete = function(orderNumber, status) {
            var statusText = status === 'approved' ? 'APPROVED' : 'REJECTED';
            return confirm(
                '🗑️ Delete Order\n\n' +
                'Order #' + orderNumber + '\n' +
                'Status: ' + statusText + '\n\n' +
                '⚠️ This action cannot be undone!\n' +
                '📌 No WhatsApp notification will be sent.'
            );
        };

        document.getElementById('rejectModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });

        <?php if (isset($whatsapp_redirect) && !empty($whatsapp_redirect)): ?>
            console.log('WhatsApp redirect: <?php echo addslashes($whatsapp_redirect); ?>');
            console.log('WhatsApp type: <?php echo isset($whatsapp_type) ? addslashes($whatsapp_type) : 'none'; ?>');

            setTimeout(function() {
                var popup = document.getElementById('whatsappPopup');
                if (popup) {
                    popup.classList.add('show');
                    console.log('WhatsApp popup shown successfully!');
                } else {
                    console.log('WhatsApp popup element not found!');
                }
            }, 800);

            document.getElementById('whatsappPopup').addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        <?php endif; ?>

        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === '1') {
                e.preventDefault();
                window.location.href = 'dashboard.php';
            }
            if ((e.ctrlKey || e.metaKey) && e.key === '2') {
                e.preventDefault();
                window.location.href = 'products.php';
            }
            if ((e.ctrlKey || e.metaKey) && e.key === '3') {
                e.preventDefault();
                window.location.href = 'videos.php';
            }
            if ((e.ctrlKey || e.metaKey) && e.key === '4') {
                e.preventDefault();
                window.location.href = 'orders.php';
            }
            if ((e.ctrlKey || e.metaKey) && e.key === '5') {
                e.preventDefault();
                window.location.href = 'settings.php';
            }
        });

        function updatePendingCount() {
            fetch('get_orders_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.querySelector('.sidebar-menu li a[href="orders.php"] .badge');
                    const pendingStat = document.querySelector('.order-stats span:first-child .stat-number');
                    
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
                    
                    if (pendingStat) {
                        pendingStat.textContent = data.pending || 0;
                    }
                })
                .catch(() => {});
        }

        setInterval(updatePendingCount, 30000);

        console.log('%c📋 SCI-CALC Orders', 'font-size:24px;font-weight:800;color:#6C63FF;');
        console.log('%c📊 Pending: <?php echo $order_counts['pending']; ?> | Approved: <?php echo $order_counts['approved']; ?> | Rejected: <?php echo $order_counts['rejected']; ?>', 'font-size:14px;color:#2D3436;');
        console.log('%c💰 Payment info loaded from settings', 'font-size:12px;color:#8899AA;');
        console.log('%c✅ Orders page loaded successfully!', 'font-size:14px;color:#00C9A7;');
    });
</script>

</body>
</html>