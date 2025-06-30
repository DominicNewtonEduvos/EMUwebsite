<?php
session_start();
require 'db.php';

$cartItems = [];
$total = 0;

if (isset($_SESSION['User_ID'])) {
    $userId = $_SESSION['User_ID'];
    
    try {
        // cart
        $stmt = $conn->prepare("
            SELECT p.Product_ID, p.ProductName, p.Price, p.Picture, cl.Quantity, 
                   (p.Price * cl.Quantity) as ItemTotal
            FROM cartlist cl
            JOIN products1 p ON cl.Product_ID = p.Product_ID
            JOIN cart c ON cl.Cart_ID = c.Cart_ID
            WHERE c.User_ID = ?
        ");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
            $total += $row['ItemTotal'];
        }
    } catch (Exception $e) {
        error_log("Cart display error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart | EMU Marketplace</title>
    
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .cart-items {
            margin-bottom: 30px;
        }
        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            margin-right: 20px;
        }
        .item-details {
            flex-grow: 1;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            margin: 10px 0;
        }
        .quantity-controls button {
            width: 30px;
            height: 30px;
            background: #f0f0f0;
            border: none;
            cursor: pointer;
        }
        .quantity {
            margin: 0 10px;
            min-width: 20px;
            text-align: center;
        }
        .remove-btn {
            background: #ff4444;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }
        .item-price {
            font-weight: bold;
            min-width: 100px;
            text-align: right;
        }
        .cart-summary {
            text-align: right;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 5px;
        }
        .checkout-btn {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="cart-container">
        <h1>Your Shopping Cart</h1>
        
        <?php if (empty($cartItems)): ?>
            <p>Your cart is empty. <a href="Product.php">Browse products</a></p>
        <?php else: ?>
            <div class="cart-items">
                <?php foreach ($cartItems as $item): ?>
                <div class="cart-item" data-product-id="<?= htmlspecialchars($item['Product_ID']) ?>">
                    <img src="uploads/<?= htmlspecialchars($item['Picture']) ?>" 
                         alt="<?= htmlspecialchars($item['ProductName']) ?>">
                    <div class="item-details">
                        <h3><?= htmlspecialchars($item['ProductName']) ?></h3>
                        <div class="quantity-controls">
                            <button class="decrease-btn" data-product-id="<?= $item['Product_ID'] ?>">-</button>
                            <span class="quantity"><?= $item['Quantity'] ?></span>
                            <button class="increase-btn" data-product-id="<?= $item['Product_ID'] ?>">+</button>
                        </div>
                        <button class="remove-btn" data-product-id="<?= $item['Product_ID'] ?>">
                            Remove
                        </button>
                    </div>
                    <div class="item-price">
                        R<?= number_format($item['ItemTotal'], 2) ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <h3>Total: R<?= number_format($total, 2) ?></h3>
                <button class="checkout-btn" onclick="location.href='Checkout.php'">
                    Proceed to Checkout
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script src="cart.js"></script>
</body>
</html>