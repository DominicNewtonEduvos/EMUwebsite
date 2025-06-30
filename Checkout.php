<html>
<title>Checkout</title>
<body>
    <?php include 'header.php'; ?>

<main class="checkout-page">
  <h1>Checkout</h1>
  <form id="checkout-form">
    <div class="form-group">
      <label>Shipping Address</label>
      <textarea name="address" required></textarea>
    </div>
    <div class="payment-method">
      <h3>Payment Method</h3>
      <label><input type="radio" name="payment" value="card" checked> Credit Card</label>
      <label><input type="radio" name="payment" value="paypal"> PayPal</label>
    </div>
    <button type="submit">Place Order</button>
  </form>
</main>

<script>
const cart = JSON.parse(localStorage.getItem('cart')) || [];

document.getElementById('checkout-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  
  const response = await fetch('process-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      cart: cart,
      address: document.querySelector('[name="address"]').value,
      payment: document.querySelector('[name="payment"]:checked').value
    })
  });
  
  
});
</script>



</body>


</html>