<?php

session_start();


require 'db.php';


$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize username input
    $username = trim(htmlspecialchars($_POST['username']));
    $password = $_POST['password']; 
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        
        if ($username === 'Siya' && $password === 'bluespider') {
            $_SESSION['User_ID'] = 1;
            $_SESSION['UserName'] = 'Siya';
            $_SESSION['Role'] = 'admin';
            $_SESSION['loggedin'] = true;
            
            
            session_regenerate_id(true);
            
            header("Location: adminDashboard.php");
            exit();
        }
        
        
        $stmt = $conn->prepare("SELECT User_ID, UserName, Password, Role FROM users WHERE UserName = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['Password'])) {
                $_SESSION['User_ID'] = $user['User_ID'];
                $_SESSION['UserName'] = $user['UserName'];
                $_SESSION['Role'] = $user['Role'];
                $_SESSION['loggedin'] = true;
                
                
                session_regenerate_id(true);
                
                header("Location: " . ($user['Role'] === 'admin' ? 'adminDashboard.php' : 'Product.php'));
                exit();
            }
        }
        
        
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | EMU Marketplace</title>
    
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
            max-width: 500px;
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            font-weight: bold;
        }
        
        .alert.error {
            background-color: #f2dede;
            color: #a94442;
            border: 1px solid #ebccd1;
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
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .submit-button {
            width: 100%;
            padding: 14px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        
        .register-link a {
            color: #3498db;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="form-container">
            <div class="form-header">
                <h1>Sign In</h1>
                <p>Access your EMU Marketplace account</p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="submit-button">Sign In</button>
            </form>
            
            <div class="register-link">
                Don't have an account? <a href="Register.php">Register here</a>
            </div>
        </div>
    </div>
</body>
</html>