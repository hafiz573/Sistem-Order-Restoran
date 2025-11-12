<?php
session_start();

if ($_POST['item_id']) {
    $item_id = $_POST['item_id'];
    $name = $_POST['name'];
    $price = floatval($_POST['price']);
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity']++;
    } else {
        $_SESSION['cart'][$item_id] = [
            'name' => $name,
            'price' => $price,
            'quantity' => 1
        ];
    }
    
    echo 'success';
} else {
    echo 'error';
}
?>