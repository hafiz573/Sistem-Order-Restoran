<?php
session_start();

if ($_POST['table_number'] && $_POST['customer_name']) {
    $_SESSION['table_number'] = $_POST['table_number'];
    $_SESSION['customer_name'] = $_POST['customer_name'];
    echo 'success';
} else {
    echo 'error';
}
?>