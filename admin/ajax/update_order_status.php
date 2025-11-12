<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['staff']) || $_SESSION['staff']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $order_id = $_GET['id'];
    $status = $_GET['status'];
    
    // Validate status
    $allowed_statuses = ['ready', 'completed', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        throw new Exception('Invalid status');
    }
    
    // Update order status
    $query = "UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $order_id);
    $stmt->execute();
    
    // If order is completed or cancelled, free up the table
    if ($status === 'completed' || $status === 'cancelled') {
        $table_query = "UPDATE tables t 
                       JOIN orders o ON t.id = o.table_id 
                       SET t.status = 'available' 
                       WHERE o.id = :order_id";
        $table_stmt = $db->prepare($table_query);
        $table_stmt->bindParam(':order_id', $order_id);
        $table_stmt->execute();
    }
    
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>