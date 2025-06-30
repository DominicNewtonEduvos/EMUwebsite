// Cart Management
let cart = JSON.parse(localStorage.getItem('cart')) || [];

function updateCartCount() {
  document.getElementById('cart-count').textContent = cart.reduce((total, item) => total + item.quantity, 0);
}

function openCart() {
  const modal = document.getElementById('cartModal');
  const cartList = document.getElementById('cartList');
  const cartTotal = document.getElementById('cartTotal');
  
  
  cartList.innerHTML = cart.map(item => `
    <li>
      <span>${item.name} (×${item.quantity})</span>
      <span>$${(item.price * item.quantity).toFixed(2)}</span>
      <button onclick="removeFromCart(${item.id})">×</button>
    </li>
  `).join('');
  
  
  const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
  cartTotal.textContent = `$${total.toFixed(2)}`;
  
  modal.style.display = 'block';
}

function addToCart(productId, productName, price) {
  const existingItem = cart.find(item => item.id === productId);
  
  if (existingItem) {
    existingItem.quantity++;
  } else {
    cart.push({ id: productId, name: productName, price: price, quantity: 1 });
  }
  
  localStorage.setItem('cart', JSON.stringify(cart));
  updateCartCount();
}

function removeFromCart(productId) {
  cart = cart.filter(item => item.id !== productId);
  localStorage.setItem('cart', JSON.stringify(cart));
  openCart(); 
  updateCartCount();
}

document.querySelector('.mobile-menu-button').addEventListener('click', () => {
  document.querySelector('.nav-tabs').classList.toggle('show');
});
document.addEventListener('DOMContentLoaded', function() {
    
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', async function() {
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            this.disabled = true;
            const originalText = this.textContent;
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
                
                if (!response.ok) {
                    const errorData = await response.json();
                    throw new Error(errorData.message || 'Failed to add to cart');
                }
                
                const result = await response.json();
                
                if (result.success) {
                    
                    const cartCount = document.getElementById('cart-count');
                    if (cartCount) {
                        cartCount.textContent = result.count;
                        cartCount.style.display = 'flex';
                        
                        
                        cartCount.classList.add('pulse');
                        setTimeout(() => cartCount.classList.remove('pulse'), 500);
                    }
                    
                    e
                    showToast(`${productName} added to cart!`, 'success');
                } else {
                    throw new Error(result.message || 'Failed to add to cart');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast(error.message, 'error');
                
                
                if (error.message.includes('Authentication required')) {
                    window.location.href = 'signIN.php';
                }
            } finally {
                this.disabled = false;
                this.textContent = originalText;
            }
        });
    });
});


function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }, 100);
}