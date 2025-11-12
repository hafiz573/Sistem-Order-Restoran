<?php
session_start();
require_once '../../config/database.php';

if (!isset($_SESSION['staff'])) {
    exit('Access denied');
}

$database = new Database();
$db = $database->getConnection();

// Get orders with their items
$query = "SELECT o.*, t.table_number, 
                 oi.id as item_id, oi.menu_item_id, oi.quantity, oi.price, oi.status as item_status,
                 mi.name as item_name
          FROM orders o
          JOIN tables t ON o.table_id = t.id
          JOIN order_items oi ON o.id = oi.order_id
          JOIN menu_items mi ON oi.menu_item_id = mi.id
          WHERE o.status IN ('pending', 'preparing', 'ready')
          ORDER BY o.created_at ASC, oi.id ASC";

$stmt = $db->prepare($query);
$stmt->execute();

$orders = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $order_id = $row['id'];
    if (!isset($orders[$order_id])) {
        $orders[$order_id] = [
            'order_number' => $row['order_number'],
            'table_number' => $row['table_number'],
            'customer_name' => $row['customer_name'],
            'created_at' => $row['created_at'],
            'total_amount' => $row['total_amount'],
            'order_status' => $row['status'],
            'items' => [],
            'all_ready' => true // Initialize as true
        ];
    }
    $orders[$order_id]['items'][] = [
        'item_id' => $row['item_id'],
        'name' => $row['item_name'],
        'quantity' => $row['quantity'],
        'price' => $row['price'],
        'status' => $row['item_status']
    ];

    // Check if all items are ready
    if ($row['item_status'] !== 'ready') {
        $orders[$order_id]['all_ready'] = false;
    }
}

foreach ($orders as $order_id => $order) {
    $isUrgent = (strtotime($order['created_at']) < strtotime('-15 minutes'));
    $orderClass = $isUrgent ? 'order-card urgent' : 'order-card';
    
    echo '<div class="' . $orderClass . '">';
    echo '<div class="order-header">';
    echo '<div>';
    echo '<h4>Table ' . $order['table_number'] . ' - ' . $order['order_number'] . '</h4>';
    echo '<small>Ordered: ' . date('H:i', strtotime($order['created_at'])) . '</small>';
    echo '</div>';
    
    // Show order status badge
    $order_status_color = match($order['order_status']) {
        'pending' => '#f59e0b',
        'preparing' => '#3b82f6',
        'ready' => '#10b981',
        default => '#6b7280'
    };
    echo '<span class="status-badge" style="background:' . $order_status_color . '; color:white;">' . 
         ucfirst($order['order_status']) . '</span>';
    
    echo '</div>';
    echo '<div class="order-body">';
    echo '<p><strong>Customer:</strong> ' . htmlspecialchars($order['customer_name']) . '</p>';
    
    $all_items_ready = true;
    foreach ($order['items'] as $item) {
        $statusClass = 'status-' . $item['status'];
        if ($item['status'] !== 'ready') {
            $all_items_ready = false;
        }
        
        echo '<div class="order-item">';
        echo '<div style="display: flex; justify-content: space-between; align-items: start;">';
        echo '<div style="flex: 1;">';
        echo '<strong>' . $item['quantity'] . 'x</strong> ' . htmlspecialchars($item['name']);
        echo '<br><small>Rp ' . number_format($item['price']) . ' each</small>';
        echo '</div>';
        echo '<span class="status-badge ' . $statusClass . '">' . ucfirst($item['status']) . '</span>';
        echo '</div>';
        
        echo '<div class="control-buttons">';
        if ($item['status'] === 'pending') {
            echo '<button class="btn-primary start-preparing-btn" data-item-id="' . $item['item_id'] . '" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                    Start Preparing
                  </button>';
        } elseif ($item['status'] === 'preparing') {
            echo '<button class="btn-success mark-ready-btn" data-item-id="' . $item['item_id'] . '" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                    Mark as Ready
                  </button>';
        }
        echo '</div>';
        echo '</div>';
    }
    
    // Show "All Ready" badge if all items are ready but order status is not ready
    if ($all_items_ready && $order['order_status'] !== 'ready') {
        echo '<div class="all-ready-badge" data-order-id="' . $order_id . '">
                ✅ All Items Ready
              </div>';
    }
    
    echo '<div style="margin-top: 1rem; padding-top: 1rem; border-top: 1px solid var(--border);">';
    echo '<strong>Total: Rp ' . number_format($order['total_amount']) . '</strong>';
    echo '</div>';
    echo '</div>';
    echo '</div>';
}

if (empty($orders)) {
    echo '<div class="empty-state">No pending orders</div>';
}
?>