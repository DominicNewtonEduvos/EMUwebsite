
<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['User_ID'])) {
  die(json_encode(["error" => "Not logged in"]));
}
$user_id = $_SESSION['User_ID'];
$cart_items = $db->query("SELECT * FROM cart WHERE User_ID = '$user_id'");
echo json_encode($cart_items);
?>