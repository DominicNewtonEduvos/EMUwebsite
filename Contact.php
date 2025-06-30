<?php
session_start();
require 'db.php';

// Form handling
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        $error_message = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($email) > 30) {
        $error_message = 'Email must be 30 characters or less.';
    } else {
        
        $stmt = $conn->prepare("INSERT INTO contact (Name, email, Message) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            $success_message = 'Thank you! Your message has been sent.';
            
            $name = $email = $message = '';
        } else {
            $error_message = 'Error submitting form. Please try again later.';
            error_log("Contact form error: " . $conn->error);
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
    <title>Contact Us | EMU Marketplace</title>
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
            max-width: 800px;
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
        
        
        .contact-form {
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
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
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
            transition: background 0.3s;
        }
        
        .submit-button:hover {
            background: #45a049;
        }
        
        
        .contact-info {
            margin-top: 40px;
            padding: 25px;
            background: #f0f8ff;
            border-radius: 8px;
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
                <h1>Contact Us</h1>
                <p>Have questions? We're here to help!</p>
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

            <form method="POST" class="contact-form">
                <div class="form-group">
                    <label for="name" class="required-field">Your Name</label>
                    <input type="text" id="name" name="name" 
                           value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" 
                           required>
                </div>

                <div class="form-group">
                    <label for="email" class="required-field">Email Address</label>
                    <input type="email" id="email" name="email" 
                           value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" 
                           required maxlength="30">
                </div>

                <div class="form-group">
                    <label for="message" class="required-field">Message</label>
                    <textarea id="message" name="message" required><?= isset($message) ? htmlspecialchars($message) : '' ?></textarea>
                </div>

                <button type="submit" class="submit-button">Send Message</button>
            </form>

            <div class="contact-info">
                <h2>Other Ways to Reach Us</h2>
                <p><strong>Email:</strong> EDUV4818782@vossie.net</p>
                <p><strong>Phone:</strong> (+27) 65  826 9095</p>
                
            </div>
        </div>
    </div>
</body>
</html>