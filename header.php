<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$current_page = basename($_SERVER['PHP_SELF']);
$is_logged_in = isset($_SESSION['User_ID']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header class="header">
        
        <div class="header-left">
            <div class="logo">EMU</div>
        </div>
        
        <nav class="nav-tabs">
            <a href="index.php" <?= $current_page === 'index.php' ? 'class="active"' : '' ?>>Home</a>
            <a href="Product.php" <?= $current_page === 'Product.php' ? 'class="active"' : '' ?>>Products</a>
            
            <?php if($is_logged_in): ?>
                <a href="Shop.php" <?= $current_page === 'Shop.php' ? 'class="active"' : '' ?>>Shop</a>
            <?php endif; ?>
            
            <a href="Contact.php" <?= $current_page === 'Contact.php' ? 'class="active"' : '' ?>>Contact</a>
        </nav>

        <div class="header-right">
            <?php if($is_logged_in): ?>
                <a href="Cart.php" class="cart-button">
                    🛒 <span id="cart-count">0</span>
                </a>
            <?php else: ?>
                <a href="signIN.php" class="sign-in-button">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
    fetch('cart-actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'getCount' })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            const countElement = document.getElementById('cart-count');
            if(countElement) {
                countElement.textContent = data.count;
            }
        }
    })
    .catch(error => console.error('Error loading cart count:', error));
});
    </script>
</body>
</html>