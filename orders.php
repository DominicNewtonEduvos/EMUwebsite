<?php
session_start();
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: signIN.php");
    exit();
}

include 'db.php';


$results_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); 
$offset = ($page - 1) * $results_per_page;


$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';


$sql = "SELECT o.Order_ID, u.UserName, o.Order_Date, o.Status, o.Price 
        FROM orders o
        JOIN users u ON o.User_ID = u.User_ID";


$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(u.UserName LIKE ? OR o.Order_ID LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'ss';
}

if (!empty($status_filter) && $status_filter !== 'all') {
    $where[] = "o.Status = ?";
    $params[] = $status_filter;
    $types .= 's';
}


if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}


$sql .= " ORDER BY o.Order_Date DESC LIMIT ?, ?";
$params[] = $offset;
$params[] = $results_per_page;
$types .= 'ii';


$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);


$count_sql = "SELECT COUNT(*) as total FROM orders o JOIN users u ON o.User_ID = u.User_ID";
if (!empty($where)) {
    $count_sql .= " WHERE " . implode(" AND ", $where);
}

$total_stmt = $conn->prepare($count_sql);
if ($types) {
    
    $count_params = array_slice($params, 0, count($params) - 2);
    $count_types = substr($types, 0, -2);
    if (!empty($count_types)) {
        $total_stmt->bind_param($count_types, ...$count_params);
    }
}
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total = $total_result['total'];
$total_pages = ceil($total / $results_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Orders | EMU Marketplace</title>
    <style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
        color: #333;
        line-height: 1.6;
    }

    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
    }

    h1 {
        color: #2c3e50;
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

    /* No orders message */
    .no-orders {
        text-align: center;
        padding: 40px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-top: 20px;
    }

    .no-orders i {
        font-size: 48px;
        color: #6c757d;
        margin-bottom: 15px;
    }

    .no-orders p {
        font-size: 18px;
        color: #6c757d;
   
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #f5f5f5;
        color: #333;
        line-height: 1.6;
    }

    .container {
        max-width: 1200px;
        margin: 20px auto;
        padding: 20px;
        background: #fff;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        border-radius: 5px;
    }

    h1 {
        color: #2c3e50;
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }

   
    .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .filter-form {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .filter-group {
        flex: 1;
        min-width: 200px;
    }

    .filter-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .filter-actions {
        display: flex;
        align-items: flex-end;
        gap: 10px;
    }

    .filter-btn {
        padding: 8px 15px;
        background: #3498db;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }

    .reset-btn {
        padding: 8px 15px;
        background: #6c757d;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
    }

    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .data-table thead {
        background-color: #3498db;
        color: white;
        position: sticky;
        top: 0;
    }

    .data-table th {
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
    }

    .data-table tbody tr {
        border-bottom: 1px solid #ddd;
    }

    .data-table tbody tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    .data-table tbody tr:hover {
        background-color: #f1f1f1;
    }

    .data-table td {
        padding: 12px 15px;
        vertical-align: middle;
    }

    .status-badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 500;
        text-transform: uppercase;
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

    .action-link {
        color: #2980b9;
        text-decoration: none;
        padding: 5px 10px;
        border-radius: 3px;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .action-link:hover {
        background-color: #2980b9;
        color: white;
    }

    .price {
        color: #27ae60;
        font-weight: bold;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 20px;
        gap: 5px;
    }

    .pagination a, 
    .pagination span {
        display: inline-block;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        text-decoration: none;
        color: #2c3e50;
    }

    .pagination a:hover {
        background-color: #f5f5f5;
    }

    .pagination .active {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
    }

    @media (max-width: 768px) {
        .filter-form {
            flex-direction: column;
        }
        
        .filter-group {
            min-width: 100%;
        }
        
        .data-table {
            display: block;
            overflow-x: auto;
        }
    }
    </style>
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="container">
        <h1>Orders Management</h1>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="search">Search</label>
                    <input type="text" id="search" name="search" placeholder="Order ID or Customer" value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="all">All Statuses</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Processing" <?= $status_filter === 'Processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="Shipped" <?= $status_filter === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="Delivered" <?= $status_filter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                        <option value="Cancelled" <?= $status_filter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="filter-btn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="orders.php" class="reset-btn">
                        <i class="fas fa-sync-alt"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <?php if (empty($orders)): ?>
            <div class="no-orders">
                <i class="fas fa-box-open"></i>
                <p>No orders found</p>
                <?php if (!empty($search) || (!empty($status_filter) && $status_filter !== 'all')): ?>
                    <p>Try adjusting your search or filter criteria</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['Order_ID']) ?></td>
                        <td><?= htmlspecialchars($order['UserName']) ?></td>
                        <td><?= date('M j, Y', strtotime($order['Order_Date'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= strtolower($order['Status']) ?>">
                                <?= htmlspecialchars($order['Status']) ?>
                            </span>
                        </td>
                        <td class="price">$<?= number_format($order['Price'], 2) ?></td>
                        <td>
                            <a href="order-details.php?id=<?= $order['Order_ID'] ?>" class="action-link">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
        <?php endif; ?>
    </div>
</body>
</html>