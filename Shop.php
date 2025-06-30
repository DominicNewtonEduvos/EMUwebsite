<?php
session_start();
require 'db.php';


error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['User_ID'])) {
    header("Location: signIN.php");
    exit();
}


$upload_dir = 'uploads/';
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 2 * 1024 * 1024; // 2MB


if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        die("Failed to create upload directory. Check permissions.");
    }
}


if (!is_writable($upload_dir)) {
    die("Upload directory is not writable. Please check permissions.");
}

$seller_id = $_SESSION['User_ID'];
$current_view = $_GET['view'] ?? 'list'; // 'list', 'add', 'edit', 'stats'
$product_id = $_GET['id'] ?? null;
$success_message = '';
$error_message = '';


$products = [];
$sales_stats = [];
$stmt = $conn->prepare("SELECT * FROM products1 WHERE Seller_ID = ?");
$stmt->bind_param("s", $seller_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}


if ($current_view === 'stats') {
    $stmt = $conn->prepare("
        SELECT 
            p.Product_ID,
            p.ProductName,
            COUNT(oi.OrderItem_ID) as total_sold,
            SUM(oi.Quantity) as total_quantity,
            SUM(oi.Price * oi.Quantity) as total_revenue
        FROM products1 p
        LEFT JOIN order_items oi ON p.Product_ID = oi.Product_ID
        LEFT JOIN orders o ON oi.Order_ID = o.Order_ID
        WHERE p.Seller_ID = ?
        GROUP BY p.Product_ID
    ");
    $stmt->bind_param("s", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $sales_stats[] = $row;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    
    $stmt = $conn->prepare("SELECT Picture FROM products1 WHERE Product_ID = ? AND Seller_ID = ?");
    $stmt->bind_param("ss", $product_id, $seller_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    
    if ($product) {
        $conn->begin_transaction();
        try {
            
            $stmt = $conn->prepare("DELETE FROM products1 WHERE Product_ID = ?");
            $stmt->bind_param("s", $product_id);
            $stmt->execute();
            
            
            if (!empty($product['Picture'])) {
                $file_path = 'uploads/' . $product['Picture'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            
            $conn->commit();
            $success_message = "Product deleted successfully!";
            header("Location: Shop.php?view=list");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error deleting product: " . $e->getMessage();
        }
    } else {
        $error_message = "Product not found or you don't have permission to delete it";
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $name = trim($_POST['product_name']);
    $desc = trim($_POST['description']);
    $price = floatval($_POST['price']);
    
    
    if (empty($name) || strlen($name) > 100) {
        $error_message = "Product name must be 1-100 characters";
    } elseif (empty($desc)) {
        $error_message = "Description is required";
    } elseif ($price <= 0) {
        $error_message = "Price must be greater than 0";
    } else {
        
        $stmt = $conn->prepare("SELECT Product_ID FROM products1 WHERE Product_ID = ? AND Seller_ID = ?");
        $stmt->bind_param("ss", $product_id, $seller_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            
            $image_update = '';
            $filename = '';
            if (isset($_FILES['product_image']['error']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $_FILES['product_image']['tmp_name']);
                
                if (!in_array($mime_type, $allowed_types)) {
                    $error_message = "Only JPG, PNG, and GIF images are allowed";
                } elseif ($_FILES['product_image']['size'] > $max_size) {
                    $error_message = "Image must be less than 2MB";
                } else {
                    
                    $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $filename = $product_id . '.' . $ext;
                    $upload_path = $upload_dir . $filename;

                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        $error_message = "Failed to upload image";
                    } else {
                        $image_update = ", Picture = ?";
                    }
                }
            }
            
            if (empty($error_message)) {
               
                $stmt = $conn->prepare("
                    UPDATE products1 
                    SET ProductName = ?, Description = ?, Price = ? $image_update
                    WHERE Product_ID = ?
                ");
                
                if (!empty($image_update)) {
                    $stmt->bind_param("ssdss", $name, $desc, $price, $filename, $product_id);
                } else {
                    $stmt->bind_param("ssds", $name, $desc, $price, $product_id);
                }
                
                if ($stmt->execute()) {
                    $success_message = "Product updated successfully!";
                    header("Location: Shop.php?view=list");
                    exit();
                } else {
                    $error_message = "Error updating product: " . $conn->error;
                }
            }
        } else {
            $error_message = "Product not found or you don't have permission to edit it";
        }
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error_message = "Invalid form submission";
    } else {
        
        $name = trim($_POST['product_name']);
        $desc = trim($_POST['description']);
        $price = floatval($_POST['price']);
        
        
        $product_id = 'prod_' . substr(uniqid(), 0, 10); // 15 chars total
        
        
        if (empty($name) || strlen($name) > 15) {
            $error_message = "Product name must be 1-15 characters";
        } elseif (empty($desc)) {
            $error_message = "Description is required";
        } elseif ($price <= 0) {
            $error_message = "Price must be greater than 0";
        } elseif (!isset($_FILES['product_image']['error']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "Please select a valid image file";
        } else {
            
            $file_info = finfo_open(FILEINFO_MIME_TYPE);
            $mime_type = finfo_file($file_info, $_FILES['product_image']['tmp_name']);
            
            if (!in_array($mime_type, $allowed_types)) {
                $error_message = "Only JPG, PNG, and GIF images are allowed";
            } elseif ($_FILES['product_image']['size'] > $max_size) {
                $error_message = "Image must be less than 2MB";
            } else {
                $ext = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                $filename = $product_id . '.' . $ext;
                $upload_path = $upload_dir . $filename;

                $conn->begin_transaction();
                
                try {
                    if (!move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                        throw new Exception("Failed to move uploaded file");
                    }
                    
                    $stmt = $conn->prepare("INSERT INTO products1 
                                          (Product_ID, ProductName, Description, Price, Picture, Seller_ID) 
                                          VALUES (?, ?, ?, ?, ?, ?)");
                    
                    if (!$stmt) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    $stmt->bind_param("sssdss", $product_id, $name, $desc, $price, $filename, $seller_id);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Execute failed: " . $stmt->error);
                    }
                    
                    $conn->commit();
                    $success_message = "Product uploaded successfully!";
                    header("Location: Shop.php?view=list");
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollback();
                    if (file_exists($upload_path)) {
                        unlink($upload_path);
                    }
                    $error_message = "Error: " . $e->getMessage();
                    error_log("Product upload error: " . $e->getMessage());
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Dashboard | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 120px);
            padding: 40px 20px;
            background-color: #f5f5f5;
        }
        
        
        .form-container {
            width: 100%;
            max-width: 1200px;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        
        .dashboard-nav {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        .dashboard-nav a {
            padding: 10px 15px;
            margin-right: 5px;
            text-decoration: none;
            color: #333;
            border: 1px solid transparent;
        }
        .dashboard-nav a.active {
            border: 1px solid #ddd;
            border-bottom: 1px solid white;
            margin-bottom: -1px;
            background: white;
            font-weight: bold;
        }
        
        
        .product-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .product-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            position: relative;
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            margin-bottom: 10px;
        }
        .product-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .button {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .button.danger {
            background-color: #ff4444;
            color: white;
        }
        
        
        .stats-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .stats-table th, .stats-table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }
        .stats-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .stats-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            font-weight: bold;
        }
        .alert.success {
            background-color: #dff0d8;
            color: #3c763d;
            border: 1px solid #d6e9c6;
        }
        .alert.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
        }
        
       
        .product-form {
            display: grid;
            gap: 25px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            outline: none;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .form-group input[type="file"] {
            padding: 8px;
            border: 1px dashed #ddd;
        }
        .form-footer {
            margin-top: 20px;
            display: flex;
            gap: 15px;
        }
        .submit-btn {
            flex: 1;
            padding: 14px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .submit-btn:hover {
            background: #45a049;
        }
        .cancel-btn {
            flex: 1;
            padding: 14px;
            background: #f5f5f5;
            color: #555;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            text-align: center;
            text-decoration: none;
            transition: background 0.3s;
        }
        .cancel-btn:hover {
            background: #e0e0e0;
        }
        .required-field::after {
            content: " *";
            color: #e74c3c;
        }
        small {
            display: block;
            margin-top: 5px;
            color: #7f8c8d;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="form-container">
            <div class="dashboard-nav">
                <a href="?view=list" class="<?= $current_view === 'list' ? 'active' : '' ?>">My Products</a>
                <a href="?view=add" class="<?= $current_view === 'add' ? 'active' : '' ?>">Add Product</a>
                <a href="?view=stats" class="<?= $current_view === 'stats' ? 'active' : '' ?>">Sales Statistics</a>
            </div>
            
            <?php if ($success_message): ?>
                <div class="alert success">
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php elseif ($error_message): ?>
                <div class="alert error">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if ($current_view === 'list'): ?>
                <h2>My Products</h2>
                <?php if (empty($products)): ?>
                    <p>You haven't listed any products yet. <a href="?view=add">Add your first product</a></p>
                <?php else: ?>
                    <div class="product-list">
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <img src="uploads/<?= htmlspecialchars($product['Picture']) ?>" 
                                     alt="<?= htmlspecialchars($product['ProductName']) ?>">
                                <h3><?= htmlspecialchars($product['ProductName']) ?></h3>
                                <p>R<?= number_format($product['Price'], 2) ?></p>
                                <div class="product-actions">
                                    <a href="?view=edit&id=<?= $product['Product_ID'] ?>" class="button">Edit</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['Product_ID'] ?>">
                                        <button type="submit" name="delete_product" class="button danger"
                                                onclick="return confirm('Are you sure you want to delete this product?')">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php elseif ($current_view === 'add'): ?>
                <div class="form-header">
                    <h1>Sell Your Product</h1>
                    <p>List your item to the EMU Marketplace</p>
                </div>

                <form method="POST" enctype="multipart/form-data" class="product-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="add_product" value="1">
                    
                    <div class="form-group">
                        <label for="product_name" class="required-field">Product Name</label>
                        <input type="text" id="product_name" name="product_name" 
                               value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" 
                               required minlength="3" maxlength="15"
                               placeholder="Enter product name">
                    </div>

                    <div class="form-group">
                        <label for="description" class="required-field">Description</label>
                        <textarea id="description" name="description" required
                                  minlength="10" maxlength="500" 
                                  placeholder="Describe your product in detail"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <small>Minimum 10 characters, maximum 500 characters</small>
                    </div>

                    <div class="form-group">
                        <label for="price" class="required-field">Price (R)</label>
                        <input type="number" id="price" name="price" 
                               value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" 
                               step="0.01" min="0.01" required
                               placeholder="0.00">
                    </div>

                    <div class="form-group">
                        <label for="product_image" class="required-field">Product Image</label>
                        <input type="file" id="product_image" name="product_image" 
                               accept="image/jpeg, image/png, image/gif" required>
                        <small>Accepted formats: JPEG, PNG, GIF (Max 2MB)</small>
                    </div>

                    <div class="form-footer">
                        <button type="submit" class="submit-btn">List Product</button>
                        <a href="?view=list" class="cancel-btn">Cancel</a>
                    </div>
                </form>

            <?php elseif ($current_view === 'edit' && $product_id): ?>
                <?php 
                $product_to_edit = null;
                foreach ($products as $product) {
                    if ($product['Product_ID'] === $product_id) {
                        $product_to_edit = $product;
                        break;
                    }
                }
                if ($product_to_edit): ?>
                    <div class="form-header">
                        <h1>Edit Product</h1>
                        <p>Update your product listing</p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="product-form">
                        <input type="hidden" name="product_id" value="<?= $product_to_edit['Product_ID'] ?>">
                        <input type="hidden" name="edit_product" value="1">
                        
                        <div class="form-group">
                            <label for="product_name" class="required-field">Product Name</label>
                            <input type="text" id="product_name" name="product_name" 
                                   value="<?= htmlspecialchars($product_to_edit['ProductName']) ?>" 
                                   required minlength="3" maxlength="15">
                        </div>

                        <div class="form-group">
                            <label for="description" class="required-field">Description</label>
                            <textarea id="description" name="description" required
                                      minlength="10" maxlength="500"><?= 
                                      htmlspecialchars($product_to_edit['Description']) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="price" class="required-field">Price (R)</label>
                            <input type="number" id="price" name="price" 
                                   value="<?= htmlspecialchars($product_to_edit['Price']) ?>" 
                                   step="0.01" min="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="product_image">Product Image</label>
                            <small>Current: <?= htmlspecialchars($product_to_edit['Picture']) ?></small>
                            <input type="file" id="product_image" name="product_image" 
                                   accept="image/jpeg, image/png, image/gif">
                        </div>

                        <div class="form-footer">
                            <button type="submit" class="submit-btn">Update Product</button>
                            <a href="?view=list" class="cancel-btn">Cancel</a>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert error">Product not found or you don't have permission to edit it</div>
                <?php endif; ?>

            <?php elseif ($current_view === 'stats'): ?>
                <h2>Sales Statistics</h2>
                <?php if (empty($sales_stats)): ?>
                    <p>No sales data available yet.</p>
                <?php else: ?>
                    <table class="stats-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Times Sold</th>
                                <th>Total Quantity</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_stats as $stat): ?>
                                <tr>
                                    <td><?= htmlspecialchars($stat['ProductName']) ?></td>
                                    <td><?= $stat['total_sold'] ?></td>
                                    <td><?= $stat['total_quantity'] ?></td>
                                    <td>R<?= number_format($stat['total_revenue'] ?? 0, 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const price = parseFloat(this.querySelector('#price')?.value);
                if (price && (isNaN(price) || price <= 0)) {
                    alert('Please enter a valid price greater than R0');
                    e.preventDefault();
                    return;
                }
                
                
                if (this.querySelector('[name="add_product"]')) {
                    const fileInput = this.querySelector('#product_image');
                    if (fileInput.files.length === 0) {
                        alert('Please select an image file');
                        e.preventDefault();
                        return;
                    }
                    
                    const file = fileInput.files[0];
                    if (file.size > 2097152) {
                        alert('File size must be less than 2MB');
                        e.preventDefault();
                    }
                }
            });
        });

        
        document.getElementById('price')?.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    </script>
</body>
</html>