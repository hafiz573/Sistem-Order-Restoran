<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['cart']) || empty($_SESSION['table_number'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty or table not selected']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Get table ID
    $table_query = "SELECT id FROM tables WHERE table_number = :table_number";
    $table_stmt = $db->prepare($table_query);
    $table_stmt->bindParam(':table_number', $_SESSION['table_number']);
    $table_stmt->execute();
    $table = $table_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$table) {
        throw new Exception('Table not found');
    }
    
    // Generate order number
    $order_number = 'ORD' . date('YmdHis') . rand(100, 999);
    
    // Calculate total
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    // Create order
    $order_query = "INSERT INTO orders (table_id, order_number, customer_name, total_amount) 
                    VALUES (:table_id, :order_number, :customer_name, :total_amount)";
    $order_stmt = $db->prepare($order_query);
    $order_stmt->bindParam(':table_id', $table['id']);
    $order_stmt->bindParam(':order_number', $order_number);
    $order_stmt->bindParam(':customer_name', $_SESSION['customer_name']);
    $order_stmt->bindParam(':total_amount', $total);
    $order_stmt->execute();
    
    $order_id = $db->lastInsertId();
    
    // Create order items
    foreach ($_SESSION['cart'] as $item_id => $item) {
        $item_query = "INSERT INTO order_items (order_id, menu_item_id, quantity, price) 
                       VALUES (:order_id, :menu_item_id, :quantity, :price)";
        $item_stmt = $db->prepare($item_query);
        $item_stmt->bindParam(':order_id', $order_id);
        $item_stmt->bindParam(':menu_item_id', $item_id);
        $item_stmt->bindParam(':quantity', $item['quantity']);
        $item_stmt->bindParam(':price', $item['price']);
        $item_stmt->execute();
    }
    
    // Update table status
    $update_table = "UPDATE tables SET status = 'occupied' WHERE id = :table_id";
    $update_stmt = $db->prepare($update_table);
    $update_stmt->bindParam(':table_id', $table['id']);
    $update_stmt->execute();
    
    $db->commit();
    
    // Clear cart
    unset($_SESSION['cart']);
    $_SESSION['table_number'] = '';
    $_SESSION['customer_name'] = '';
    
    echo json_encode(['success' => true, 'order_number' => $order_number]);
    
} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>