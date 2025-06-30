<?php
session_start();
require_once 'auth.php';
requireAdmin();
include 'db.php';

$sql = "SELECT * FROM products1";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Products</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .admin-title {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
        }
        
        
        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #45a049;
        }
        
        .btn-primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
        }
        
        .btn i {
            margin-right: 8px;
        }
        
        .product-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            border-radius: 8px;
        }
        
        .product-table thead {
            background-color: #2c3e50;
            color: white;
        }
        
        .product-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .product-table td {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        
        .product-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        
        .action-link {
            color: #3498db;
            text-decoration: none;
            margin: 0 5px;
            transition: color 0.3s;
        }
        
        .action-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .action-link.delete {
            color: #e74c3c;
        }
        
        .action-link.delete:hover {
            color: #c0392b;
        }
        
        @media (max-width: 768px) {
            .product-table {
                display: block;
                overflow-x: auto;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">Product Management</h1>
            <a href="Shop.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> Add New Product
            </a>
        </div>
        
        <table class="product-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Description</th>
                    <th>Image</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($product = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($product['Product_ID']) ?></td>
                    <td><?= htmlspecialchars($product['ProductName']) ?></td>
                    <td>R<?= number_format($product['Price'], 2) ?></td>
                    <td><?= htmlspecialchars(substr($product['Description'], 0, 50)) ?>...</td>
                    <td><img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>" class="product-image"></td>
                    <td>
                        <a href="edit-product.php?id=<?= $product['Product_ID'] ?>" class="action-link">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete-product.php?id=<?= $product['Product_ID'] ?>" 
                           class="action-link delete"
                           onclick="return confirm('Are you sure you want to delete this product?')">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <script>
        document.querySelectorAll('.delete').forEach(link => {
            link.addEventListener('click', (e) => {
                if (!confirm('Are you sure you want to delete this product?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>