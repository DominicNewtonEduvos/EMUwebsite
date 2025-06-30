<?php
session_start();
require_once 'auth.php';
requireAdmin();
include 'db.php';


$results_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $results_per_page;


$stmt = $conn->prepare("SELECT User_ID, UserName, Email, Role, Created_At FROM users LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $results_per_page);
$stmt->execute();
$result = $stmt->get_result();


$total_stmt = $conn->prepare("SELECT COUNT(*) as total FROM users");
$total_stmt->execute();
$total = $total_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total / $results_per_page);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Users | Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="styles.css">
    <style>
        
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .admin-title {
            font-size: 2rem;
            color: #2c3e50;
            margin: 0;
        }
        
       
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
            overflow: hidden;
            border-radius: 8px;
        }
        
        .data-table thead {
            background-color: #2c3e50;
            color: white;
        }
        
        .data-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: middle;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: #f9f9f9;
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
        
        
        .action-link {
            color: #3498db;
            text-decoration: none;
            margin-right: 10px;
            transition: color 0.3s;
        }
        
        .action-link:hover {
            color: #2980b9;
            text-decoration: underline;
        }
        
        .action-link.delete {
            color: #e74c3c;
        }
        
        .action-link.delete:hover {
            color: #c0392b;
        }
        
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        
        .pagination a, 
        .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #2c3e50;
        }
        
        .pagination a:hover {
            background-color: #f5f5f5;
        }
        
        .pagination .active {
            background-color: #4CAF50;
            color: white;
            border-color: #4CAF50;
        }
        
        
        @media (max-width: 768px) {
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .admin-header {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1 class="admin-title">User Management</h1>
            <a href="add-user.php" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($user = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($user['User_ID']) ?></td>
                    <td><?= htmlspecialchars($user['UserName']) ?></td>
                    <td><?= htmlspecialchars($user['Email']) ?></td>
                    <td>
                        <span class="role-badge role-<?= strtolower($user['Role']) ?>">
                            <?= htmlspecialchars($user['Role']) ?>
                        </span>
                    </td>
                    <td><?= date('M j, Y', strtotime($user['Created_At'])) ?></td>
                    <td>
                        <a href="edit-user.php?id=<?= $user['User_ID'] ?>" class="action-link">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete-user.php?id=<?= $user['User_ID'] ?>" 
                           class="action-link delete"
                           onclick="return confirm('Are you sure you want to delete this user?')">
                            <i class="fas fa-trash-alt"></i> Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        
        <?php if($total_pages > 1): ?>
        <div class="pagination">
            <?php if($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i> Previous</a>
            <?php endif; ?>
            
            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                <?php if($i == $page): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="?page=<?= $i ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if($page < $total_pages): ?>
                <a href="?page=<?= $page + 1 ?>">Next <i class="fas fa-chevron-right"></i></a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        
        document.querySelectorAll('.delete').forEach(link => {
            link.addEventListener('click', function(e) {
                if(!confirm('This will permanently delete the user. Continue?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>