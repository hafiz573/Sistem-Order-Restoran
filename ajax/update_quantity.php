<?php
session_start();

if (isset($_POST['item_id']) && isset($_POST['change'])) {
    $item_id = $_POST['item_id'];
    $change = intval($_POST['change']);
    
    if (isset($_SESSION['cart'][$item_id])) {
        $_SESSION['cart'][$item_id]['quantity'] += $change;
        
        // Remove item if quantity becomes 0 or less
        if ($_SESSION['cart'][$item_id]['quantity'] <= 0) {
            unset($_SESSION['cart'][$item_id]);
        }
        
        echo 'success';
    } else {
        echo 'error';
    }
} else {
    echo 'error';
}
?>