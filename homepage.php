
<!DOCTYPE html>
<html lang="en">
  <link rel="stylesheet" href="styles.css" />
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>EMU Shop</title>
</head>
<body>

  <?php include 'header.php'; ?>

  <main>
    <h1>Welcome to EMU Marketplace</h1>
    <p>Browse our amazing collection of products!</p>

  </main>
  
  <div class="signin-container">
    <h2>Sign In</h2>
    <form action="SignIN.php" method="post">
  <input type="text" name="username" placeholder="Username" required />
  <input type="password" name="password" placeholder="Password" required />
  <input type="submit" value="Sign In" />
  </form>
    <div class="extra">
      Don't have an account? <a href="index.php">Register here</a>
    </div>
  </div>

  <script>
    function openCart() {
      document.getElementById('cartModal').style.display = 'block';
    }

    function closeCart() {
      document.getElementById('cartModal').style.display = 'none';
    }

    window.onclick = function(event) {
      const modal = document.getElementById('cartModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    };
  </script>
</body>
</html>
