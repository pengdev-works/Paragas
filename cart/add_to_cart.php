<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_id = intval($_POST['item_id']);
    $quantity = intval($_POST['quantity']);
    
    // Check if item already exists in cart
    $check_stmt = $conn->prepare("SELECT * FROM cart WHERE user_id = ? AND item_id = ?");
    $check_stmt->bind_param("ii", $user_id, $item_id);
    $check_stmt->execute();
    $existing_item = $check_stmt->get_result()->fetch_assoc();
    $check_stmt->close();
    
    if ($existing_item) {
        // Update quantity if item exists
        $new_quantity = $existing_item['quantity'] + $quantity;
        $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $update_stmt->bind_param("ii", $new_quantity, $existing_item['cart_id']);
        $result = $update_stmt->execute();
        $update_stmt->close();
    } else {
        // Insert new item
        $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, item_id, quantity) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iii", $user_id, $item_id, $quantity);
        $result = $insert_stmt->execute();
        $insert_stmt->close();
    }
    
    if ($result) {
        // Get updated cart count
        $count_stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $count_stmt->bind_param("i", $user_id);
        $count_stmt->execute();
        $count_result = $count_stmt->get_result()->fetch_assoc();
        $cart_count = $count_result['total'] ?? 0;
        $count_stmt->close();
        
        echo json_encode(['success' => true, 'cart_count' => $cart_count]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>