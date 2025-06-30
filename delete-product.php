<?php
session_start();
require 'auth.php';
requireAdmin();
require 'db.php';

$error = '';
$success = '';


$productId = $_GET['id'] ?? null;

if (!$productId) {
    $error = "No product ID specified";
    header("Location: Product-list.php");
    exit();
}


try {
    $stmt = $conn->prepare("SELECT ProductName, Picture FROM products1 WHERE Product_ID = ?");
    $stmt->bind_param("s", $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if (!$product) {
        throw new Exception("Product not found");
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    header("Location: Product-list.php?error=" . urlencode($error));
    exit();
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        
        $stmt = $conn->prepare("DELETE FROM cartlist WHERE Product_ID = ?");
        $stmt->bind_param("s", $productId);
        $stmt->execute();
        
        
        $stmt = $conn->prepare("DELETE FROM products1 WHERE Product_ID = ?");
        $stmt->bind_param("s", $productId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete product: " . $conn->error);
        }
        
       
        $imagePath = 'uploads/' . $product['Picture'];
        if (file_exists($imagePath)) {
            unlink($imagePath);
        }
        
        $conn->commit();
        $success = "Product deleted successfully!";
        header("Location: Product-list.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        header("Location: Product-list.php?error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete Product | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .product-image {
            max-width: 200px;
            max-height: 200px;
            margin: 20px auto;
            display: block;
            border: 1px solid #ddd;
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
        
        .btn-confirm {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .btn-cancel {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="delete-container">
        <h1>Delete Product</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <p>Are you sure you want to delete this product?</p>
        
        <h2><?= htmlspecialchars($product['ProductName']) ?></h2>
        <img src="uploads/<?= htmlspecialchars($product['Picture']) ?>" class="product-image" alt="Product image">
        
        <form method="POST">
            <button type="submit" class="btn-confirm">Yes, Delete Permanently</button>
            <a href="Product-list.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>