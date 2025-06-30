<?php
session_start();
require 'db.php';

$errors = [];
$username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation 
    if (empty($errors)) {
        $user_id = uniqid("user_");
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if ($hashed_password === false) {
            die("Password hashing failed");
        }
        
        $stmt = $conn->prepare("INSERT INTO users (User_ID, UserName, Email, Password, Role) VALUES (?, ?, ?, ?, 'customer')");
        $stmt->bind_param("ssss", $user_id, $username, $email, $hashed_password);
        
        if ($stmt->execute()) {
            error_log("New user registered: $username | Hash: $hashed_password");
            
            $_SESSION['User_ID'] = $user_id;
            $_SESSION['UserName'] = $username;
            header("Location: Product.php");
            exit();
        } else {
            $errors['general'] = "Registration failed: " . $conn->error;
        }
    }
}
?>