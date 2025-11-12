<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['staff']) || $_SESSION['staff']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Handle form actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? '';

// Delete menu item
if ($action === 'delete' && $id) {
    $delete_query = "UPDATE menu_items SET is_available = 0 WHERE id = :id";
    $delete_stmt = $db->prepare($delete_query);
    $delete_stmt->bindParam(':id', $id);
    $delete_stmt->execute();
    
    header('Location: menu_management.php?message=Item deleted successfully');
    exit;
}

// Toggle availability
if ($action === 'toggle' && $id) {
    $toggle_query = "UPDATE menu_items SET is_available = NOT is_available WHERE id = :id";
    $toggle_stmt = $db->prepare($toggle_query);
    $toggle_stmt->bindParam(':id', $id);
    $toggle_stmt->execute();
    
    header('Location: menu_management.php?message=Availability updated');
    exit;
}

// Get categories for dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();

// Get all menu items
$menu_query = "
    SELECT mi.*, c.name as category_name 
    FROM menu_items mi 
    LEFT JOIN categories c ON mi.category_id = c.id 
    ORDER BY mi.created_at DESC
";
$menu_stmt = $db->prepare($menu_query);
$menu_stmt->execute();

$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Restaurant</title>
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
        .admin-main {
            padding: 2rem 0;
            background: #f8fafc;
            min-height: calc(100vh - 200px);
        }
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
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
        .data-table tr:hover {
            background: #f8fafc;
        }
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-available {
            background: #dcfce7;
            color: #16a34a;
        }
        .status-unavailable {
            background: #fee2e2;
            color: #dc2626;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1100;
            align-items: center;
            justify-content: center;
        }
        .modal.show {
            display: flex;
        }
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 1rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
        }
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.5rem;
        }
        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary);
        }
        .menu-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            font-weight: 500;
        }
        .alert-success {
            background: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
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
                    <span>Menu Management</span>
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

    <!-- Navigation -->
    <nav class="admin-nav">
        <div class="container">
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="menu_management.php" class="active">Menu Management</a></li>
                <li><a href="tables_management.php">Tables Management</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="admin-main">
        <div class="container">
            <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 2rem;">
                <h1>Menu Items Management</h1>
                <button class="btn-primary" onclick="showAddModal()">
                    <i class="fas fa-plus"></i> Add New Item
                </button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Menu Items Table -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $menu_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td>
                                <img src="../images/<?= $item['image'] ?: 'default.jpg' ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>" 
                                     class="menu-item-image"
                                     onerror="this.src='../images/default.jpg'">
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($item['category_name']) ?></td>
                            <td>Rp <?= number_format($item['price']) ?></td>
                            <td>
                                <span class="status-badge <?= $item['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                                    <?= $item['is_available'] ? 'Available' : 'Unavailable' ?>
                                </span>
                            </td>
                            <td>
                                <small style="color: var(--secondary);">
                                    <?= htmlspecialchars(substr($item['description'], 0, 50)) ?>
                                    <?= strlen($item['description']) > 50 ? '...' : '' ?>
                                </small>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <button class="btn-secondary btn-sm" 
                                            onclick="editItem(<?= htmlspecialchars(json_encode($item)) ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                    <button class="<?= $item['is_available'] ? 'btn-warning' : 'btn-success' ?> btn-sm"
                                            onclick="toggleAvailability(<?= $item['id'] ?>)">
                                        <i class="fas fa-power-off"></i>
                                        <?= $item['is_available'] ? 'Disable' : 'Enable' ?>
                                    </button>
                                    <button class="btn-danger btn-sm" 
                                            onclick="deleteItem(<?= $item['id'] ?>)">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        
                        <?php if ($menu_stmt->rowCount() == 0): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 2rem;">
                                No menu items found. <a href="javascript:void(0)" onclick="showAddModal()">Add the first item</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Add/Edit Modal -->
    <div class="modal" id="itemModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Menu Item</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="itemForm" action="ajax/save_menu_item.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" id="itemId" name="id">
                    
                    <div class="form-group">
                        <label for="name">Item Name *</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select id="category_id" name="category_id" class="form-control" required>
                            <option value="">Select Category</option>
                            <?php 
                            $categories_stmt->execute();
                            while ($category = $categories_stmt->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price *</label>
                        <input type="number" id="price" name="price" class="form-control" min="0" step="1000" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="image">Image</label>
                        <input type="file" id="image" name="image" class="form-control" accept="image/*">
                        <small style="color: var(--secondary);">Recommended: 400x300 pixels, max 2MB</small>
                        <div id="imagePreview" style="margin-top: 0.5rem;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <input type="checkbox" id="is_available" name="is_available" value="1" checked>
                            Available for ordering
                        </label>
                    </div>
                    
                    <div class="form-actions">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Show add modal
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Menu Item';
            document.getElementById('itemForm').reset();
            document.getElementById('itemId').value = '';
            document.getElementById('imagePreview').innerHTML = '';
            document.getElementById('itemModal').classList.add('show');
        }

        // Edit item
        function editItem(item) {
            document.getElementById('modalTitle').textContent = 'Edit Menu Item';
            document.getElementById('itemId').value = item.id;
            document.getElementById('name').value = item.name;
            document.getElementById('category_id').value = item.category_id;
            document.getElementById('price').value = item.price;
            document.getElementById('description').value = item.description;
            document.getElementById('is_available').checked = item.is_available;
            
            // Show image preview if exists
            if (item.image) {
                document.getElementById('imagePreview').innerHTML = `
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <img src="../images/${item.image}" alt="Current image" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                        <small>Current image</small>
                    </div>
                `;
            } else {
                document.getElementById('imagePreview').innerHTML = '';
            }
            
            document.getElementById('itemModal').classList.add('show');
        }

        // Close modal
        function closeModal() {
            document.getElementById('itemModal').classList.remove('show');
        }

        // Toggle availability
        function toggleAvailability(id) {
            if (confirm('Are you sure you want to change the availability of this item?')) {
                window.location.href = `menu_management.php?action=toggle&id=${id}`;
            }
        }

        // Delete item
        function deleteItem(id) {
            if (confirm('Are you sure you want to delete this menu item? This action cannot be undone.')) {
                window.location.href = `menu_management.php?action=delete&id=${id}`;
            }
        }

        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').innerHTML = `
                        <img src="${e.target.result}" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 4px;">
                    `;
                };
                reader.readAsDataURL(file);
            }
        });

        // Form submission with AJAX
        document.getElementById('itemForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('ajax/save_menu_item.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.href = 'menu_management.php?message=' + encodeURIComponent(data.message);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while saving the item.');
            });
        });

        // Close modal when clicking outside
        document.getElementById('itemModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>