<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['staff']) || $_SESSION['staff']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

if ($action === 'toggle' && $id) {
    $toggle_query = "UPDATE tables SET status = IF(status = 'available', 'occupied', 'available') WHERE id = :id";
    $toggle_stmt = $db->prepare($toggle_query);
    $toggle_stmt->bindParam(':id', $id);
    $toggle_stmt->execute();
    
    header('Location: tables_management.php?message=Table status updated');
    exit;
}

if ($action === 'delete' && $id) {
    // Check if table has active orders
    $check_query = "SELECT COUNT(*) as active_orders FROM orders WHERE table_id = :id AND status IN ('pending', 'preparing')";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':id', $id);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['active_orders'] > 0) {
        header('Location: tables_management.php?error=Cannot delete table with active orders');
        exit;
    }
    
    $delete_query = "DELETE FROM tables WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':id', $id);
    $delete_stmt->execute();
    
    header('Location: tables_management.php?message=Table deleted successfully');
    exit;
}

// Get all tables
$tables_query = "SELECT * FROM tables ORDER BY table_number";
$tables_stmt = $db->prepare($tables_query);
$tables_stmt->execute();

$message = $_GET['message'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables Management - Restaurant</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        /* (Same styles as menu_management.php, just change the content) */
        .admin-header { background: var(--dark); color: white; padding: 1rem 0; position: sticky; top: 0; z-index: 100; }
        .admin-nav { background: var(--primary); padding: 1rem 0; }
        .nav-links { display: flex; gap: 2rem; list-style: none; }
        .nav-links a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 4px; transition: background 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(255,255,255,0.2); }
        .admin-main { padding: 2rem 0; background: #f8fafc; min-height: calc(100vh - 200px); }
        .table-container { background: white; border-radius: 8px; box-shadow: var(--shadow); overflow: hidden; }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th, .data-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
        .data-table th { background: var(--light); font-weight: 600; }
        .btn-sm { padding: 0.375rem 0.75rem; font-size: 0.875rem; }
        .status-available { background: #dcfce7; color: #16a34a; }
        .status-occupied { background: #fee2e2; color: #dc2626; }
        .alert { padding: 1rem; border-radius: 6px; margin-bottom: 1rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-error { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    </style>
</head>
<body>
    <!-- Header & Navigation (same as menu_management.php) -->
    <header class="admin-header">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div class="logo">
                    <i class="fas fa-utensils"></i>
                    <span>Tables Management</span>
                </div>
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span>Welcome, <?= htmlspecialchars($_SESSION['staff']['name']) ?></span>
                    <a href="logout.php" style="color: white; text-decoration: none;">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <nav class="admin-nav">
        <div class="container">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="menu_management.php">Menu Management</a></li>
                <li><a href="tables_management.php" class="active">Tables Management</a></li>
            </ul>
        </div>
    </nav>

    <main class="admin-main">
        <div class="container">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
                <h1>Tables Management</h1>
                <button class="btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Table
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Table Number</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($table = $tables_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($table['table_number']) ?></strong></td>
                            <td><?= $table['capacity'] ?> persons</td>
                            <td>
                                <span class="status-<?= $table['status'] ?> status-badge">
                                    <?= ucfirst($table['status']) ?>
                                </span>
                            </td>
                            <td><?= date('M j, Y', strtotime($table['created_at'])) ?></td>
                            <td>
                                <div style="display: flex; gap: 0.5rem;">
                                    <button class="<?= $table['status'] === 'available' ? 'btn-warning' : 'btn-success' ?> btn-sm"
                                            onclick="toggleStatus(<?= $table['id'] ?>)">
                                        <i class="fas fa-power-off"></i>
                                        <?= $table['status'] === 'available' ? 'Occupy' : 'Free' ?>
                                    </button>
                                    <button class="btn-danger btn-sm" 
                                            onclick="deleteTable(<?= $table['id'] ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleStatus(id) {
            if (confirm('Are you sure you want to change the table status?')) {
                window.location.href = `tables_management.php?action=toggle&id=${id}`;
            }
        }

        function deleteTable(id) {
            if (confirm('Are you sure you want to delete this table? This action cannot be undone.')) {
                window.location.href = `tables_management.php?action=delete&id=${id}`;
            }
        }

        function showAddModal() {
            // Implementation for adding new table
            const tableNumber = prompt('Enter table number:');
            const capacity = prompt('Enter table capacity:');
            
            if (tableNumber && capacity) {
                window.location.href = `ajax/add_table.php?table_number=${encodeURIComponent(tableNumber)}&capacity=${capacity}`;
            }
        }
    </script>
</body>
</html>