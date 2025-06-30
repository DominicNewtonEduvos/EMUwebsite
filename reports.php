<?php
session_start();
require 'auth.php';
requireAdmin(); // Only admins can view reports
require 'db.php';

// Default to current month if no date range specified
$currentMonth = date('Y-m');
$month = $_GET['month'] ?? $currentMonth;
$year = date('Y', strtotime($month));
$monthName = date('F Y', strtotime($month));

// Initialize report data
$reportData = [
    'total_sales' => 0,
    'total_orders' => 0,
    'total_users' => 0,
    'top_products' => [],
    'sales_by_day' => [],
    'user_registrations' => 0
];

try {
    // Get total sales for the month
    $stmt = $conn->prepare("
        SELECT SUM(Price) as total_sales, COUNT(*) as total_orders 
        FROM orders 
        WHERE YEAR(Order_Date) = ? AND MONTH(Order_Date) = ?
    ");
    $stmt->bind_param("ss", $year, date('m', strtotime($month)));
    $stmt->execute();
    $salesResult = $stmt->get_result()->fetch_assoc();
    $reportData['total_sales'] = $salesResult['total_sales'] ?? 0;
    $reportData['total_orders'] = $salesResult['total_orders'] ?? 0;

    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as total_users FROM users");
    $stmt->execute();
    $reportData['total_users'] = $stmt->get_result()->fetch_assoc()['total_users'] ?? 0;

    // Get top 5 products
    $stmt = $conn->prepare("
        SELECT p.ProductName, SUM(oi.Quantity) as total_sold, SUM(oi.Price * oi.Quantity) as total_revenue
        FROM order_items oi
        JOIN products1 p ON oi.Product_ID = p.Product_ID
        JOIN orders o ON oi.Order_ID = o.Order_ID
        WHERE YEAR(o.Order_Date) = ? AND MONTH(o.Order_Date) = ?
        GROUP BY p.ProductName
        ORDER BY total_sold DESC
        LIMIT 5
    ");
    $stmt->bind_param("ss", $year, date('m', strtotime($month)));
    $stmt->execute();
    $reportData['top_products'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get sales by day
    $stmt = $conn->prepare("
        SELECT DATE(Order_Date) as day, SUM(Price) as daily_sales, COUNT(*) as daily_orders
        FROM orders
        WHERE YEAR(Order_Date) = ? AND MONTH(Order_Date) = ?
        GROUP BY DATE(Order_Date)
        ORDER BY day ASC
    ");
    $stmt->bind_param("ss", $year, date('m', strtotime($month)));
    $stmt->execute();
    $reportData['sales_by_day'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get new user registrations
    $stmt = $conn->prepare("
        SELECT COUNT(*) as new_users 
        FROM users 
        WHERE YEAR(Created_At) = ? AND MONTH(Created_At) = ?
    ");
    $stmt->bind_param("ss", $year, date('m', strtotime($month)));
    $stmt->execute();
    $reportData['user_registrations'] = $stmt->get_result()->fetch_assoc()['new_users'] ?? 0;

} catch (Exception $e) {
    error_log("Report generation error: " . $e->getMessage());
    $error = "Failed to generate reports. Please try again later.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales Reports | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .report-container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .month-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .month-selector input[type="month"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
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
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .chart-container h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        .top-products {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .top-products h2 {
            margin-top: 0;
            color: #2c3e50;
        }

        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .product-table th {
            background: #2c3e50;
            color: white;
            padding: 12px;
            text-align: left;
        }

        .product-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        .product-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            font-weight: bold;
        }

        .alert.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .report-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="report-container">
        <div class="report-header">
            <h1>Sales Reports</h1>
            <form method="GET" class="month-selector">
                <label for="month">Select Month:</label>
                <input type="month" id="month" name="month" value="<?= htmlspecialchars($month) ?>" max="<?= date('Y-m') ?>">
                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i> Apply
                </button>
            </form>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2>Report for <?= htmlspecialchars($monthName) ?></h2>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Sales</h3>
                <div class="value">R<?= number_format($reportData['total_sales'], 2) ?></div>
                <p>Revenue generated</p>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="value"><?= number_format($reportData['total_orders']) ?></div>
                <p>Orders placed</p>
            </div>
            
            <div class="stat-card">
                <h3>New Users</h3>
                <div class="value"><?= number_format($reportData['user_registrations']) ?></div>
                <p>Registered this month</p>
            </div>
            
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="value"><?= number_format($reportData['total_users']) ?></div>
                <p>Registered in system</p>
            </div>
        </div>

        <div class="chart-container">
            <h2>Daily Sales</h2>
            <canvas id="salesChart" height="300"></canvas>
        </div>

        <div class="top-products">
            <h2>Top Selling Products</h2>
            <?php if (!empty($reportData['top_products'])): ?>
                <table class="product-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData['top_products'] as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['ProductName']) ?></td>
                            <td><?= number_format($product['total_sold']) ?></td>
                            <td>R<?= number_format($product['total_revenue'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No product sales data available for this period.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Sales by day chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: [<?php 
                    foreach ($reportData['sales_by_day'] as $day) {
                        echo '"' . date('j M', strtotime($day['day'])) . '",';
                    }
                ?>],
                datasets: [
                    {
                        label: 'Daily Sales (R)',
                        data: [<?php 
                            foreach ($reportData['sales_by_day'] as $day) {
                                echo $day['daily_sales'] . ',';
                            }
                        ?>],
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Number of Orders',
                        data: [<?php 
                            foreach ($reportData['sales_by_day'] as $day) {
                                echo $day['daily_orders'] . ',';
                            }
                        ?>],
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Sales Amount (R)'
                        }
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Number of Orders'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>