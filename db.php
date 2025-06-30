<?php

ini_set('display_errors', '0');
error_reporting(0);

ini_set('log_errors', '1');
ini_set('error_log', __DIR__.'/private_db_errors.log');

$host = 'sql101.infinityfree.com';
$username = 'if0_39265399'; 
$password = 'oBKsqbHjiKduC'; 
$database = 'if0_39265399_emu';

function secure_db_connect($host, $user, $pass, $db) {
    try {
        $conn = new mysqli($host, $user, $pass, $db);
        
        if ($conn->connect_error) {
            error_log("DB Connection Error: [{$conn->connect_errno}] {$conn->connect_error}");
            die("System maintenance in progress. Please try again later.");
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("DB Exception: " . $e->getMessage());
        die("System temporarily unavailable.");
    }
}


$conn = secure_db_connect($host, $username, $password, $database);
$conn->set_charset("utf8mb4");
?>