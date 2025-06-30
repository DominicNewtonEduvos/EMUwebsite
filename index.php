<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
    try {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } catch (Exception $e) {
        
        $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
    }
}

require 'db.php';

$errors = [];
$username = $email = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = "customer"; 
    
    
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors['username'] = 'Username must be 3-20 characters (letters, numbers, underscores)';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }
    
    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    if (empty($errors)) {
        $check = $conn->prepare("SELECT User_ID FROM users WHERE UserName = ? OR Email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $errors['general'] = 'Username or email already exists';
        }
        $check->close();
    }
    
    if (empty($errors)) {
        $user_id = uniqid("user_");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
        $stmt = $conn->prepare("INSERT INTO users (User_ID, UserName, Email, Role, Password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $user_id, $username, $email, $role, $hashed_password);
        
        if ($stmt->execute()) {
            $_SESSION['User_ID'] = $user_id;
            $_SESSION['UserName'] = $username;
            $_SESSION['Role'] = $role;
            $_SESSION['Email'] = $email;
            
            header("Location: Product.php?registration=success");
            exit();
        } else {
            $errors['general'] = "Registration failed: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EMU Marketplace</title>
    <style>
        
        .main-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 120px);
            padding: 20px;
            background-color: #f5f5f5;
        }
        
        .form-container {
            width: 100%;
            max-width: 500px;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .form-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .form-header p {
            color: #7f8c8d;
        }
        
        .register-form {
            display: grid;
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
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
        
        .password-strength {
            height: 5px;
            background: #eee;
            margin-top: 5px;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0%;
            background: transparent;
            transition: width 0.3s, background 0.3s;
        }
        
        .form-footer {
            margin-top: 20px;
        }
        
        .register-btn {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .register-btn:hover {
            background: #45a049;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #7f8c8d;
        }
        
        .login-link a {
            color: #3498db;
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .error {
            color: #e74c3c;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .required-field::after {
            content: " *";
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="form-container">
            <div class="form-header">
                <h1>Create Account</h1>
                <p>Join the EMU Marketplace community</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert error" style="background: #f2dede; color: #a94442; padding: 10px; border-radius: 4px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($errors['general']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="register-form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="username" class="required-field">Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username); ?>" 
                           placeholder="Enter your username"
                           required>
                    <?php if (!empty($errors['username'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['username']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email" class="required-field">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email); ?>" 
                           placeholder="Enter your email"
                           required>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password" class="required-field">Password</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Create a password"
                           required>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php endif; ?>
                    <div class="password-strength">
                        <div class="strength-meter" id="strength-meter"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="required-field">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your password"
                           required>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <span class="error"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-footer">
                    <button type="submit" class="register-btn">Register Now</button>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    
            </form>
            
            <div class="login-link">
                Already have an account? <a href="signIN.php" >Sign in</a>
            </div>
        </div>
    </div>

    <script>
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            
            if (password.length >= 8) strength += 1;
            if (password.length >= 12) strength += 1;
            
        });
        
        const confirmInput = document.getElementById('confirm_password');
        
        confirmInput.addEventListener('input', function() {
            const password = passwordInput.value;
            const confirm = this.value;
            
            if (confirm && password !== confirm) {
                this.setCustomValidity("Passwords do not match");
            } else {
                this.setCustomValidity("");
            }
        });
    </script>
</body>
</html>