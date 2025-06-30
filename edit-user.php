<?php
session_start();
require 'auth.php';
requireAdmin(); 
require 'db.php';

$user = null;
$error = '';
$success = '';

L
$userId = $_GET['id'] ?? null;


if ($userId) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE User_ID = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (!$user) {
            throw new Exception("User not found");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
} else {
    $error = "No user ID specified";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    try {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        
        // Validate inputs
        if (empty($username) || empty($email)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        
        $stmt = $conn->prepare("SELECT User_ID FROM users WHERE (UserName = ? OR Email = ?) AND User_ID != ?");
        $stmt->bind_param("sss", $username, $email, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception("Username or email already exists");
        }
        
        // Update user in database
        $stmt = $conn->prepare("UPDATE users SET UserName = ?, Email = ?, Role = ? WHERE User_ID = ?");
        $stmt->bind_param("ssss", $username, $email, $role, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update user: " . $conn->error);
        }
        
        $success = "User updated successfully!";
        // Refresh user data
        $user = array_merge($user, [
            'UserName' => $username,
            'Email' => $email,
            'Role' => $role
        ]);
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit User | EMU Marketplace</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .edit-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .alert.error {
            background: #f2dede;
            color: #a94442;
        }
        
        .alert.success {
            background: #dff0d8;
            color: #3c763d;
        }
        
        .btn-submit {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            margin-left: 10px;
        }
        
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-admin {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        
        .role-customer {
            background-color: #e8f5e9;
            color: #388e3c;
        }
    </style>
</head>
<body>
    <?php include 'admin-nav.php'; ?>
    
    <div class="edit-container">
        <h1>Edit User</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <?php if ($user): ?>
        <form method="POST">
            <div class="form-group">
                <label>User ID</label>
                <input type="text" value="<?= htmlspecialchars($user['User_ID']) ?>" readonly>
            </div>
            
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['UserName']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['Email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Current Role</label>
                <span class="role-badge role-<?= strtolower($user['Role']) ?>">
                    <?= htmlspecialchars($user['Role']) ?>
                </span>
            </div>
            
            <div class="form-group">
                <label>Change Role</label>
                <select name="role">
                    <option value="customer" <?= $user['Role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                    <option value="admin" <?= $user['Role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            
            <button type="submit" name="update_user" class="btn-submit">Update User</button>
            <a href="users.php" class="btn-cancel">Cancel</a>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>