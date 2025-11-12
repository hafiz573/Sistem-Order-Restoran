<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['staff']) || $_SESSION['staff']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Get statistics - FIXED: Use COALESCE to handle NULL values
$stats_query = "
    SELECT 
        (SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()) as today_orders,
        (SELECT COUNT(*) FROM tables WHERE status = 'occupied') as occupied_tables,
        (SELECT COUNT(*) FROM menu_items WHERE is_available = 1) as available_items,
        (SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE DATE(created_at) = CURDATE()) as today_revenue
";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Restaurant</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-header {
            background: var(--dark);
            color: white;
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .admin-nav {
            background: var(--primary);
            padding: 1rem 0;
        }
        .nav-links {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav-links a:hover, .nav-links a.active {
            background: rgba(255,255,255,0.2);
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        .stat-label {
            color: var(--secondary);
            font-size: 0.9rem;
        }
        .admin-main {
            padding: 2rem 0;
            background: #f8fafc;
            min-height: calc(100vh - 200px);
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th,
        .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        .data-table th {
            background: var(--light);
            font-weight: 600;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-xs {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="admin-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="logo">
                    <i class="fas fa-utensils"></i>
                    <span>Admin Dashboard</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['staff']['name']) ?> (<?= $_SESSION['staff']['role'] ?>)</span>
                    <a href="logout.php" style="color: white; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="nav-links">
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="menu_management.php">Menu Management</a></li>
                <li><a href="tables_management.php">Tables Management</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="container">
            <h1 style="margin-bottom: 2rem;">Dashboard Overview</h1>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['today_orders'] ?: 0 ?></div>
                    <div class="stat-label">Today's Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['occupied_tables'] ?: 0 ?></div>
                    <div class="stat-label">Occupied Tables</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['available_items'] ?: 0 ?></div>
                    <div class="stat-label">Available Menu Items</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">Rp <?= number_format($stats['today_revenue']) ?></div>
                    <div class="stat-label">Today's Revenue</div>
                </div>
            </div>

            <!-- Recent Orders -->
<!-- Recent Orders -->
<div class="table-container">
    <div style="padding: 1.5rem; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center;">
        <h3>Recent Orders</h3>
        <div style="display: flex; gap: 1rem; align-items: center;">
            <button class="btn-primary" onclick="refreshOrders()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Order #</th>
                <th>Table</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Time</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="ordersTableBody">
            <?php
            // Get recent orders with their item status
            $recent_orders_query = "
                SELECT o.*, t.table_number,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id AND oi.status != 'ready') as pending_items,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as total_items
                FROM orders o 
                JOIN tables t ON o.table_id = t.id 
                ORDER BY o.created_at DESC 
                LIMIT 15
            ";
            $recent_orders_stmt = $db->prepare($recent_orders_query);
            $recent_orders_stmt->execute();
            
            if ($recent_orders_stmt->rowCount() > 0) {
                while ($order = $recent_orders_stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Determine order status based on items
                    if ($order['status'] == 'completed' || $order['status'] == 'cancelled') {
                        $order_status = $order['status'];
                    } else if ($order['pending_items'] == 0 && $order['total_items'] > 0) {
                        $order_status = 'ready';
                    } else {
                        $order_status = $order['pending_items'] > 0 ? 'preparing' : 'pending';
                    }
                    
                    $status_color = match($order_status) {
                        'pending' => '#f59e0b',
                        'preparing' => '#3b82f6',
                        'ready' => '#10b981',
                        'completed' => '#00ff37ff',
                        'cancelled' => '#ef4444',
                        default => '#6b7280'
                    };
                    
                    echo "
                    <tr data-status=\"$order_status\">
                        <td><strong>{$order['order_number']}</strong></td>
                        <td>{$order['table_number']}</td>
                        <td>" . htmlspecialchars($order['customer_name']) . "</td>
                        <td>Rp " . number_format($order['total_amount']) . "</td>
                        <td>
                            <span class='status-badge' style='background:{$status_color}; color:white;'>
                                " . ucfirst($order_status) . "
                            </span>
                        </td>
                        <td>" . date('H:i', strtotime($order['created_at'])) . "</td>
                        <td>";
                    
                    // Show actions based on status
                    if ($order_status === 'ready') {
                        echo "<div class='action-buttons'>
                                <button class='btn-success btn-xs' onclick='completeOrder({$order['id']})'>
                                    <i class='fas fa-check'></i> Complete
                                </button>
                                <button class='btn-danger btn-xs' onclick='cancelOrder({$order['id']})'>
                                    <i class='fas fa-times'></i> Cancel
                                </button>
                              </div>";
                    } else if ($order_status === 'pending' || $order_status === 'preparing') {
                        echo "<div class='action-buttons'>
                                <button class='btn-danger btn-xs' onclick='cancelOrder({$order['id']})'>
                                    <i class='fas fa-times'></i> Cancel
                                </button>
                              </div>";
                    } else {
                        // For completed/cancelled orders, show view only or nothing
                        echo "<div class='action-buttons'>
                              </div>";
                    }
                    
                    echo "</td>
                    </tr>";
                }
            } else {
                echo '<tr><td colspan="7" style="text-align: center; padding: 2rem;">No orders found</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>
        </div>
    </main>

    <script>
        function refreshOrders() {
            location.reload();
        }

        function completeOrder(orderId) {
            if (confirm('Mark this order as completed?')) {
                fetch(`ajax/update_order_status.php?id=${orderId}&status=completed`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            }
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch(`ajax/update_order_status.php?id=${orderId}&status=cancelled`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
            }
        }

        // Auto refresh every 30 seconds
        setInterval(() => {
            refreshOrders();
        }, 30000);
    </script>
</body>
</html>