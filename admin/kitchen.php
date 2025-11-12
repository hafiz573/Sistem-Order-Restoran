<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['staff'])) {
    header('Location: login.php');
    exit;
}

$staff = $_SESSION['staff'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Display</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .kitchen-header {
            background: var(--dark);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .kitchen-main {
            padding: 2rem 0;
        }
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        .order-card {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            border-left: 4px solid var(--primary);
        }
        .order-card.urgent {
            border-left-color: var(--danger);
        }
        .order-header {
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-body {
            padding: 1rem;
        }
        .order-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border);
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-preparing { background: #dbeafe; color: #1d4ed8; }
        .status-ready { background: #dcfce7; color: #16a34a; }
        .control-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        .refresh-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .all-ready-badge {
            background: #10b981;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            margin-top: 1rem;
            text-align: center;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <header class="kitchen-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="logo">
                    <i class="fas fa-utensils"></i>
                    <span>Kitchen Display</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span>Welcome, <?= htmlspecialchars($staff['name']) ?></span>
                    <a href="logout.php" style="color: white; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="kitchen-main">
        <div class="container">
            <div class="refresh-info">
                <h2>Pending Orders</h2>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <button class="btn-primary" onclick="loadOrders()">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                    <span style="color: var(--secondary);">
                        Auto-refresh in <span id="countdown">30</span>s
                    </span>
                </div>
            </div>

            <div class="orders-grid" id="ordersContainer">
                <!-- Orders will be loaded here -->
            </div>
        </div>
    </main>

    <script>
        let refreshCountdown = 30;

        // Load orders
        function loadOrders() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'ajax/get_kitchen_orders.php', true);
            xhr.onload = function() {
                if (this.status === 200) {
                    document.getElementById('ordersContainer').innerHTML = this.responseText;
                    resetCountdown();
                    
                    // Add event listeners to action buttons
                    document.querySelectorAll('.start-preparing-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const itemId = this.getAttribute('data-item-id');
                            updateOrderItem(itemId, 'preparing');
                        });
                    });
                    
                    document.querySelectorAll('.mark-ready-btn').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const itemId = this.getAttribute('data-item-id');
                            updateOrderItem(itemId, 'ready');
                        });
                    });

                    // Add event listener for "All Ready" badge
                    document.querySelectorAll('.all-ready-badge').forEach(badge => {
                        badge.addEventListener('click', function() {
                            const orderId = this.getAttribute('data-order-id');
                            markOrderReady(orderId);
                        });
                    });
                }
            };
            xhr.send();
        }

        // Update order item status
        function updateOrderItem(itemId, status) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'ajax/update_order_item.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (this.status === 200) {
                    loadOrders();
                }
            };
            xhr.send('item_id=' + itemId + '&status=' + status);
        }

        // Mark entire order as ready (update order status)
        function markOrderReady(orderId) {
            if (confirm('Mark this entire order as ready for serving?')) {
                fetch(`ajax/update_order_status.php?id=${orderId}&status=ready`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            loadOrders();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            }
        }

        // Reset countdown timer
        function resetCountdown() {
            refreshCountdown = 30;
        }

        // Auto refresh countdown
        setInterval(function() {
            refreshCountdown--;
            document.getElementById('countdown').textContent = refreshCountdown;
            
            if (refreshCountdown <= 0) {
                loadOrders();
            }
        }, 1000);

        // Initial load
        loadOrders();
    </script>
</body>
</html>