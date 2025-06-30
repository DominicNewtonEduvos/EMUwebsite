
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-bottom: 1px solid #eee;
        }

        .product-info {
            padding: 15px;
        }

        .product-title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 5px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-price {
            color: #e63946;
            font-weight: bold;
            font-size: 18px;
            margin: 10px 0;
        }
        
        .product-price::before {
            content: "R";
        }

        .add-to-cart {
            width: 100%;
            padding: 10px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .add-to-cart:hover {
            background: #45a049;
        }

        .product-page h1 {
            text-align: center;
            margin: 20px 0;
            font-size: 2.2rem;
            color: #2c3e50;
        }
        
        
        .product-page h1::before,
        .product-page h1::after {
            content: "—";
            color: #7f8c8d;
            margin: 0 15px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <main class="product-page" >
        <h1>Our Products</h1>
        <div class="product-grid" id="product-list"></div>
    </main>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('get-products.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(products => {
            const container = document.getElementById('product-list');
            container.innerHTML = products.map(product => `
                <div class="product-card">
                    <img src="uploads/${product.Picture}" alt="${product.ProductName}" class="product-image">
                    <div class="product-info">
                        <h3 class="product-title">${product.ProductName}</h3>
                        <div class="product-price">${parseFloat(product.Price).toFixed(2)}</div>
                        <button class="add-to-cart" 
                                data-product-id="${product.Product_ID}"
                                data-product-name="${product.ProductName.replace(/"/g, '&quot;')}">
                            Add to Cart
                        </button>
                    </div>
                </div>
            `).join('');
            
            document.querySelectorAll('.add-to-cart').forEach(button => {
                button.addEventListener('click', async function() {
                    const productId = this.dataset.productId;
                    const productName = this.dataset.productName;
                    
                    this.disabled = true;
                    this.textContent = 'Adding...';
                    
                    try {
                        const response = await fetch('cart-actions.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'add',
                                productId: productId
                            })
                        });
                        
                        if (!response.ok) throw new Error('Network response was not ok');
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            
                            const cartCount = document.getElementById('cart-count');
                            if (cartCount) {
                                cartCount.textContent = result.count;
                                cartCount.style.display = 'flex';
                                cartCount.classList.add('pulse');
                                setTimeout(() => cartCount.classList.remove('pulse'), 500);
                            }
                            
                            alert(`${productName} added to cart!`);
                        } else {
                            throw new Error(result.message || 'Failed to add to cart');
                        }
                    } catch (error) {
                        console.error('Error:', error);
                        alert(`Failed to add to cart: ${error.message}`);
                    } finally {
                        this.disabled = false;
                        this.textContent = 'Add to Cart';
                    }
                });
            });
        })
        .catch(error => {
            console.error('Error loading products:', error);
            document.getElementById('product-list').innerHTML = 
                '<p class="error">Failed to load products. Please try again later.</p>';
        });
});
</script>
</body>
</html>