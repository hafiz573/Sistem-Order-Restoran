<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['staff'])) {
    exit('Access denied');
}

if ($_POST['item_id'] && $_POST['status']) {
    $database = new Database();
    $db = $database->getConnection();
    
    $item_id = $_POST['item_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE order_items SET status = :status WHERE id = :item_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':item_id', $item_id);
    
    if ($stmt->execute()) {
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
?>