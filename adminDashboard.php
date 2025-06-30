<?php
session_start();

function requireAdmin() {
    if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = 'Admin access required';
        header("Location: signIN.php");
        exit();
    }
}

requireAdmin();

require 'db.php';

$stats = [];
try {
    
    $stmt = $conn->prepare("SELECT COUNT(*) as product_count FROM products1");
    $stmt->execute();
    $stats['products'] = $stmt->get_result()->fetch_assoc()['product_count'];

    
    $stmt = $conn->prepare("SELECT COUNT(*) as user_count FROM users");
    $stmt->execute();
    $stats['users'] = $stmt->get_result()->fetch_assoc()['user_count'];

    
    $stmt = $conn->prepare("SELECT COUNT(*) as order_count FROM orders WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $stats['recent_orders'] = $stmt->get_result()->fetch_assoc()['order_count'];

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    
    $stats = array_merge(['products' => 0, 'users' => 0, 'recent_orders' => 0], $stats);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin-top: 0;
            color: #555;
        }
        
        .stat-card .value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .admin-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .admin-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .admin-card h2 {
            margin-top: 0;
            color: #2c3e50;
        }
        
        .admin-card p {
            color: #7f8c8d;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="admin-container">
        <div class="dashboard-header">
            <h1>Admin Dashboard</h1>
            <form action="logout.php" method="post">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
        
        <p>Welcome, <?= htmlspecialchars($_SESSION['UserName']) ?> (Admin)</p>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="value"><?= $stats['products'] ?></div>
                <a href="Product-list.php">Manage Products</a>
            </div>
            
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?= $stats['users'] ?></div>
                <a href="users.php">Manage Users</a>
            </div>
            
            <div class="stat-card">
                <h3>Recent Orders</h3>
                <div class="value"><?= $stats['recent_orders'] ?></div>
                <a href="orders.php">View Orders</a>
            </div>
        </div>
        
        <div class="admin-grid">
            <a href="Product-list.php" class="admin-card">
                <h2>Manage Products</h2>
                <p>View, edit, and approve products</p>
            </a>
            
            <a href="orders.php" class="admin-card">
                <h2>View Orders</h2>
                <p>Manage customer orders</p>
            </a>
            
            <a href="users.php" class="admin-card">
                <h2>Manage Users</h2>
                <p>Administer user accounts</p>
            </a>
            
            <a href="reports.php" class="admin-card">
                <h2>Sales Reports</h2>
                <p>View sales analytics</p>
            </a>
        </div>
    </div>
</body>
</html>