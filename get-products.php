<?php
header('Content-Type: application/json');
include 'db.php';
$sql = "SELECT * FROM products1";
$result = $conn->query($sql);
$products = [];
while ($row = $result->fetch_assoc()) {
  $products[] = $row;
}
echo json_encode($products);
?>
