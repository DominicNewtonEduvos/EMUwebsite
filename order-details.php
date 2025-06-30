<?php
session_start();
require 'auth.php';
requireAdmin(); // Only admins can view order details
require 'db.php';

$orderId = $_GET['id'] ?? null;
$order = [];
$orderItems = [];
$error = '';

if (!$orderId) {
    $error = "No order ID specified";
} else {
    try {
        // Get order details
        $stmt = $conn->prepare("
            SELECT o.*, u.UserName, u.Email 
            FROM orders o
            JOIN users u ON o.User_ID = u.User_ID
            WHERE o.Order_ID = ?
        ");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            throw new Exception("Order not found");
        }

        // Get order items
        $stmt = $conn->prepare("
            SELECT oi.*, p.ProductName, p.Picture 
            FROM order_items oi
            JOIN products1 p ON oi.Product_ID = p.Product_ID
            WHERE oi.Order_ID = ?
        ");
        $stmt->bind_param("s", $orderId);
        $stmt->execute();
        $orderItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Update order status if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    try {
        $newStatus = $_POST['status'];
        $validStatuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Invalid status selected");
        }

        $stmt = $conn->prepare("UPDATE orders SET Status = ? WHERE Order_ID = ?");
        $stmt->bind_param("ss", $newStatus, $orderId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update order status: " . $conn->error);
        }
        
        // Refresh order data
        $order['Status'] = $newStatus;
        $success = "Order status updated successfully!";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Details | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .order-container {
            max-width: 1000px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-shipped {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .order-section {
            margin-bottom: 30px;
        }

        .order-section h2 {
            color: #2c3e50;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #eee;
        }

        .order-items {
            width: 100%;
            border-collapse: collapse;
        }

        .order-items th {
            background-color: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .order-items td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .order-items tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .order-items tr:hover {
            background-color: #f1f1f1;
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .customer-info, .shipping-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }

        .info-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }

        .info-card h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1rem;
        }

        .status-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-form select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        .btn-update {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert.error {
            background: #f2dede;
            color: #a94442;
        }

        .alert.success {
            background: #dff0d8;
            color: #3c763d;
        }

        .total-row {
            font-weight: bold;
            background-color: #f8f9fa !important;
        }

        .total-row td {
            padding-top: 20px;
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include 'admin-nav.php'; ?>

    <div class="order-container">
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($order): ?>
            <div class="order-header">
                <h1>Order #<?= htmlspecialchars($order['Order_ID']) ?></h1>
                <span class="order-status status-<?= strtolower($order['Status']) ?>">
                    <?= htmlspecialchars($order['Status']) ?>
                </span>
            </div>

            <div class="order-section">
                <h2><i class="fas fa-info-circle"></i> Order Information</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h3>Order Date</h3>
                        <p><?= date('M j, Y H:i', strtotime($order['Order_Date'])) ?></p>
                    </div>
                    <div class="info-card">
                        <h3>Total Amount</h3>
                        <p>R<?= number_format($order['Price'], 2) ?></p>
                    </div>
                    <div class="info-card">
                        <h3>Payment Method</h3>
                        <p><?= htmlspecialchars($order['Payment_Method'] ?? 'Credit Card') ?></p>
                    </div>
                </div>
            </div>

            <div class="order-section">
                <h2><i class="fas fa-user"></i> Customer Information</h2>
                <div class="customer-info">
                    <div class="info-card">
                        <h3>Customer Name</h3>
                        <p><?= htmlspecialchars($order['UserName']) ?></p>
                    </div>
                    <div class="info-card">
                        <h3>Email</h3>
                        <p><?= htmlspecialchars($order['Email']) ?></p>
                    </div>
                </div>
            </div>

            <div class="order-section">
                <h2><i class="fas fa-truck"></i> Shipping Information</h2>
                <div class="shipping-info">
                    <div class="info-card">
                        <h3>Shipping Address</h3>
                        <p><?= nl2br(htmlspecialchars($order['Shipping_Address'])) ?></p>
                    </div>
                    <div class="info-card">
                        <h3>Tracking Number</h3>
                        <p><?= $order['Tracking_Number'] ? htmlspecialchars($order['Tracking_Number']) : 'Not available' ?></p>
                    </div>
                </div>
            </div>

            <div class="order-section">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <h2><i class="fas fa-shopping-cart"></i> Order Items</h2>
                    <form method="POST" class="status-form">
                        <select name="status">
                            <option value="Pending" <?= $order['Status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="Processing" <?= $order['Status'] === 'Processing' ? 'selected' : '' ?>>Processing</option>
                            <option value="Shipped" <?= $order['Status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="Delivered" <?= $order['Status'] === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="Cancelled" <?= $order['Status'] === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-update">Update Status</button>
                    </form>
                </div>
                
                <table class="order-items">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <img src="uploads/<?= htmlspecialchars($item['Picture']) ?>" class="product-image" alt="<?= htmlspecialchars($item['ProductName']) ?>">
                                    <span><?= htmlspecialchars($item['ProductName']) ?></span>
                                </div>
                            </td>
                            <td>R<?= number_format($item['Price'], 2) ?></td>
                            <td><?= htmlspecialchars($item['Quantity']) ?></td>
                            <td>R<?= number_format($item['Price'] * $item['Quantity'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">Total:</td>
                            <td>R<?= number_format($order['Price'], 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="orders.php" class="btn-cancel">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>