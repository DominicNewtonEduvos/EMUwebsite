class CartManager {
    constructor() {
        this.initEventListeners();
        this.updateCartCount();
    }

    async initEventListeners() {
        // Quantity controls
        document.addEventListener('click', async (e) => {
            if (e.target.classList.contains('increase-btn')) {
                await this.updateCartItem(e.target.dataset.productId, 'increase');
            } else if (e.target.classList.contains('decrease-btn')) {
                await this.updateCartItem(e.target.dataset.productId, 'decrease');
            } else if (e.target.classList.contains('remove-btn')) {
                await this.removeCartItem(e.target.dataset.productId);
            }
        });
    }

    async updateCartItem(productId, action) {
        try {
            const quantityEl = document.querySelector(`[data-product-id="${productId}"] .quantity`);
            if (!quantityEl) return;

            let newQuantity = parseInt(quantityEl.textContent);
            
            if (action === 'increase') {
                newQuantity++;
            } else if (action === 'decrease') {
                newQuantity = Math.max(1, newQuantity - 1);
            }

            const response = await this.sendCartRequest('update', {
                productId,
                quantity: newQuantity
            });

            if (response.success) {
                quantityEl.textContent = newQuantity;
                this.updateCartDisplay(response);
            }
        } catch (error) {
            console.error('Cart update error:', error);
            this.showError('Failed to update cart');
        }
    }

    async removeCartItem(productId) {
        if (!confirm('Remove this item from your cart?')) return;

        try {
            const response = await this.sendCartRequest('remove', { productId });
            
            if (response.success) {
                document.querySelector(`[data-product-id="${productId}"]`)?.remove();
                this.updateCartDisplay(response);
                
                // empty cart message
                if (response.count === 0) {
                    document.querySelector('.cart-items').innerHTML = 
                        '<p>Your cart is empty. <a href="Product.php">Browse products</a></p>';
                }
            }
        } catch (error) {
            console.error('Cart removal error:', error);
            this.showError('Failed to remove item');
        }
    }

    async updateCartCount() {
        try {
            const response = await this.sendCartRequest('getCount');
            if (response.success) {
                this.updateCartBadge(response.count);
            }
        } catch (error) {
            console.error('Cart count update error:', error);
        }
    }

    async sendCartRequest(action, data = {}) {
        const response = await fetch('cart-actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action, ...data })
        });
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Invalid response format');
        }
        return await response.json();
    }

    updateCartDisplay(data) {
        this.updateCartBadge(data.count);
        if (typeof data.total !== 'undefined') {
            const totalElement = document.querySelector('.cart-summary h3');
            if (totalElement) {
                totalElement.textContent = `Total: R${data.total.toFixed(2)}`;
            }
        }
    }

    updateCartBadge(count) {
        const cartCountElement = document.getElementById('cart-count');
        if (cartCountElement) {
            cartCountElement.textContent = count;
            cartCountElement.style.display = count > 0 ? 'flex' : 'none';
            cartCountElement.classList.add('pulse');
            setTimeout(() => cartCountElement.classList.remove('pulse'), 500);
        }
    }

    showError(message) {
        alert(message); 
    }
}


document.addEventListener('DOMContentLoaded', () => {
    new CartManager();
});