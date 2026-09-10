<?php
require_once '../config/database.php';
requireAdminLogin();

header('Content-Type: application/json');

$order_counts = getOrderCounts($conn);
echo json_encode($order_counts);
?>