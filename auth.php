<?php
function requireAdmin() {
    session_start();
    if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = 'Admin access required';
        header("Location: signIN.php");
        exit();
    }
}

function requireLogin() {
    session_start();
    if (!isset($_SESSION['User_ID'])) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        $_SESSION['error'] = 'Please login first';
        header("Location: signIN.php");
        exit();
    }
}
?>