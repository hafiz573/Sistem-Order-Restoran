<?php
session_start();

$cart_html = '';
$total = 0;
$item_count = 0;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $id => $item) {
        $item_total = $item['price'] * $item['quantity'];
        $total += $item_total;
        $item_count += $item['quantity'];
        
        $cart_html .= '
        <div class="cart-item">
            <div class="cart-item-header">
                <span class="cart-item-name">' . htmlspecialchars($item['name']) . '</span>
                <span class="cart-item-price">Rp ' . number_format($item['price']) . '</span>
            </div>
            <div class="cart-item-controls">
                <button class="quantity-btn quantity-minus" data-id="' . $id . '">-</button>
                <span class="quantity-display">' . $item['quantity'] . '</span>
                <button class="quantity-btn quantity-plus" data-id="' . $id . '">+</button>
                <button class="remove-item" data-id="' . $id . '">Remove</button>
            </div>
            <div class="cart-item-total">
                <small>Subtotal: Rp ' . number_format($item_total) . '</small>
            </div>
        </div>';
    }
} else {
    $cart_html = '<div class="empty-cart">Your cart is empty</div>';
}

echo json_encode([
    'cart_html' => $cart_html,
    'total' => $total,
    'item_count' => $item_count,
    'table_number' => $_SESSION['table_number'] ?? '',
    'customer_name' => $_SESSION['customer_name'] ?? ''
]);
?>