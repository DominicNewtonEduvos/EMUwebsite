<?php
session_start();
require 'db.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function jsonResponse($success, $data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(['success' => $success] + $data);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        exit(0);
    }


    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        jsonResponse(false, ['message' => 'Method not allowed'], 405);
    }


    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        jsonResponse(false, ['message' => 'Invalid JSON input'], 400);
    }

    $action = $input['action'] ?? '';
    $userId = $_SESSION['User_ID'] ?? null;

    if ($action === 'getCount') {
        $count = 0;
        $total = 0.0;
        
        if ($userId) {
            $stmt = $conn->prepare("
                SELECT IFNULL(SUM(cl.Quantity), 0) as count, 
                       IFNULL(SUM(p.Price * cl.Quantity), 0) as total
                FROM cart c
                LEFT JOIN cartlist cl ON c.Cart_ID = cl.Cart_ID
                LEFT JOIN products1 p ON cl.Product_ID = p.Product_ID
                WHERE c.User_ID = ?
            ");
            $stmt->bind_param("s", $userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $count = (int)$result['count'];
            $total = (float)$result['total'];
        }
        
        jsonResponse(true, ['count' => $count, 'total' => $total]);
    }

    if (!$userId) {
        jsonResponse(false, ['message' => 'Authentication required', 'redirect' => 'signIN.php'], 401);
    }

    $conn->begin_transaction();
    
    try {
        $stmt = $conn->prepare("SELECT Cart_ID FROM cart WHERE User_ID = ? FOR UPDATE");
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $cart = $result->fetch_assoc();
            $cartId = $cart['Cart_ID'];
        } else {
            $cartId = 'cart_' . bin2hex(random_bytes(8));
            $stmt = $conn->prepare("INSERT INTO cart (Cart_ID, User_ID) VALUES (?, ?)");
            $stmt->bind_param("ss", $cartId, $userId);
            if (!$stmt->execute()) {
                throw new RuntimeException("Failed to create cart");
            }
        }

        switch ($action) {
            case 'add':
                $productId = $input['productId'] ?? null;
                if (empty($productId)) {
                    throw new RuntimeException("Product ID required", 400);
                }
            
                $stmt = $conn->prepare("SELECT Price FROM products1 WHERE Product_ID = ?");
                $stmt->bind_param("s", $productId);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                
                if (!$product) {
                    throw new RuntimeException("Product not found", 404);
                }
        
                $stmt = $conn->prepare("
                    INSERT INTO cartlist (Product_ID, Cart_ID, Quantity) 
                    VALUES (?, ?, 1)
                    ON DUPLICATE KEY UPDATE Quantity = Quantity + 1
                ");
                $stmt->bind_param("ss", $productId, $cartId);
                $stmt->execute();
                break;
                
            case 'update':
                $productId = $input['productId'] ?? null;
                $quantity = isset($input['quantity']) ? (int)$input['quantity'] : null;
                
                if (empty($productId) || $quantity === null) {
                    throw new RuntimeException("Product ID and quantity required", 400);
                }
                
                if ($quantity < 1) {
                    $stmt = $conn->prepare("DELETE FROM cartlist WHERE Product_ID = ? AND Cart_ID = ?");
                    $stmt->bind_param("ss", $productId, $cartId);
                } else {
                    $stmt = $conn->prepare("UPDATE cartlist SET Quantity = ? WHERE Product_ID = ? AND Cart_ID = ?");
                    $stmt->bind_param("iss", $quantity, $productId, $cartId);
                }
                $stmt->execute();
                break;
                
            case 'remove':
                $productId = $input['productId'] ?? null;
                if (empty($productId)) {
                    throw new RuntimeException("Product ID required", 400);
                }
                
                $stmt = $conn->prepare("DELETE FROM cartlist WHERE Product_ID = ? AND Cart_ID = ?");
                $stmt->bind_param("ss", $productId, $cartId);
                $stmt->execute();
                break;
                
            default:
                throw new RuntimeException("Invalid action", 400);
        }

        $stmt = $conn->prepare("
            SELECT IFNULL(SUM(cl.Quantity), 0) as count, 
                   IFNULL(SUM(p.Price * cl.Quantity), 0) as total
            FROM cartlist cl
            JOIN products1 p ON cl.Product_ID = p.Product_ID
            WHERE cl.Cart_ID = ?
        ");
        $stmt->bind_param("s", $cartId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        $conn->commit();
        
        jsonResponse(true, [
            'count' => (int)$result['count'],
            'total' => (float)$result['total']
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
    
} catch (RuntimeException $e) {
    jsonResponse(false, [
        'message' => $e->getMessage(),
        'redirect' => ($e->getCode() === 401) ? 'signIN.php' : null
    ], $e->getCode());
} catch (Exception $e) {
    error_log("Cart system error: " . $e->getMessage());
    jsonResponse(false, ['message' => 'Internal server error'], 500);
}