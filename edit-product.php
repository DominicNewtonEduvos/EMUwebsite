<?php
session_start();
require 'auth.php';
requireAdmin();
require 'db.php';

$product = null;
$error = '';
$success = '';

$productId = $_GET['id'] ?? null;

if ($productId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM products1 WHERE Product_ID = ?");
        $stmt->bind_param("s", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product) {
            throw new Exception("Product not found");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = "No product ID specified";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    try {
        $name = trim($_POST['product_name']);
        $price = floatval($_POST['price']);
        $description = trim($_POST['description']);
        
        if (empty($name) || empty($description) || $price <= 0) {
            throw new Exception("All fields are required and price must be positive");
        }

        $imagePath = $product['Picture'];
        if (!empty($_FILES['product_image']['name'])) {
            $uploadDir = 'uploads/';
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 2 * 1024 * 1024; // 2MB
            
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $_FILES['product_image']['tmp_name']);
            
            if (!in_array($mimeType, $allowedTypes)) {
                throw new Exception("Only JPG, PNG, and GIF images are allowed");
            }
            
            if ($_FILES['product_image']['size'] > $maxSize) {
                throw new Exception("Image must be less than 2MB");
            }
            
            $ext = pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION);
            $newFilename = $productId . '.' . $ext;
            $uploadPath = $uploadDir . $newFilename;
            
            if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $uploadPath)) {
                throw new Exception("Failed to upload image");
            }
            
            $imagePath = $newFilename;
        }
        
        $stmt = $conn->prepare("UPDATE products1 SET ProductName = ?, Description = ?, Price = ?, Picture = ? WHERE Product_ID = ?");
        $stmt->bind_param("ssdss", $name, $description, $price, $imagePath, $productId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update product: " . $conn->error);
        }
        
        $success = "Product updated successfully!";
        
        $product = array_merge($product, [
            'ProductName' => $name,
            'Description' => $description,
            'Price' => $price,
            'Picture' => $imagePath
        ]);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Product | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 150px;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 200px;
            margin: 10px 0;
            border: 1px solid #ddd;
            padding: 5px;
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
        
        .btn-submit {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="edit-container">
        <h1>Edit Product</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($product): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" value="<?= htmlspecialchars($product['ProductName']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Price (R)</label>
                <input type="number" name="price" step="0.01" min="0.01" value="<?= htmlspecialchars($product['Price']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required><?= htmlspecialchars($product['Description']) ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Current Image</label>
                <img src="uploads/<?= htmlspecialchars($product['Picture']) ?>" class="current-image" alt="Current product image">
            </div>
            
            <div class="form-group">
                <label>New Image (Leave blank to keep current)</label>
                <input type="file" name="product_image" accept="image/jpeg, image/png, image/gif">
            </div>
            
            <button type="submit" name="update_product" class="btn-submit">Update Product</button>
            <a href="Product-list.php" style="margin-left: 15px;">Back to Products</a>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>