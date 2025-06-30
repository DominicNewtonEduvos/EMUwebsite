<?php
header('Content-Type: application/json');
include 'db.php';

if (!isset($_GET['id'])) {
  echo json_encode(["error" => "Missing product ID"]);
  exit;
}

$stmt = $conn->prepare("SELECT * FROM products1 WHERE Product_ID = ?");
$stmt->bind_param("s", $_GET['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
  echo json_encode($row);
} else {
  echo json_encode(["error" => "Product not found"]);
}

$stmt->close();
$conn->close();
?>