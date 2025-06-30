<?php
session_start();
require 'auth.php';
requireAdmin(); 
require 'db.php';

$error = '';
$success = '';


$userId = $_GET['id'] ?? null;

if (!$userId) {
    $error = "No user ID specified";
    header("Location: users.php");
    exit();
}


try {
    $stmt = $conn->prepare("SELECT UserName, Email, Role FROM users WHERE User_ID = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found");
    }
} catch (Exception $e) {
    $error = $e->getMessage();
    header("Location: users.php?error=" . urlencode($error));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();
        
        
        $stmt = $conn->prepare("DELETE FROM cartlist WHERE Cart_ID IN (SELECT Cart_ID FROM cart WHERE User_ID = ?)");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE User_ID = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        
        
        $stmt = $conn->prepare("DELETE FROM orders WHERE User_ID = ?");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        
        
        $stmt = $conn->prepare("DELETE FROM users WHERE User_ID = ?");
        $stmt->bind_param("s", $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete user: " . $conn->error);
        }
        
        $conn->commit();
        $success = "User deleted successfully!";
        header("Location: users.php?success=" . urlencode($success));
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        header("Location: users.php?error=" . urlencode($error));
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Delete User | EMU Marketplace</title>
    <style>
        .delete-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .user-info {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
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
        
        .btn-confirm {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }
        
        .btn-cancel {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
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
    
    <div class="delete-container">
        <h1>Delete User</h1>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <p>Are you sure you want to permanently delete this user?</p>
        
        <div class="user-info">
            <h3><?= htmlspecialchars($user['UserName']) ?></h3>
            <p><?= htmlspecialchars($user['Email']) ?></p>
            <span class="role-badge role-<?= strtolower($user['Role']) ?>">
                <?= htmlspecialchars($user['Role']) ?>
            </span>
        </div>
        
        <p><strong>Warning:</strong> This action cannot be undone and will delete all associated data (cart, orders, etc.).</p>
        
        <form method="POST">
            <button type="submit" class="btn-confirm">Yes, Delete Permanently</button>
            <a href="users.php" class="btn-cancel">Cancel</a>
        </form>
    </div>
</body>
</html>