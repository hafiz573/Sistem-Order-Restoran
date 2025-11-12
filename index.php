<?php
session_start();
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get categories
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_stmt = $db->prepare($categories_query);
$categories_stmt->execute();

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['table_number'] = '';
    $_SESSION['customer_name'] = '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Digital Menu - Restaurant</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Navigation -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <i class="fas fa-utensils"></i>
                <span>Restaurant App</span>
            </div>
            <div class="cart-icon" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-badge" id="cartCount">0</span>
            </div>
        </div>
    </header>

    <!-- Table Selection Modal -->
    <div class="modal" id="tableModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Pilih Meja</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="tables-grid" id="tableList">
                    <!-- Tables will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main">
        <div class="container">
            <!-- Category Navigation -->
            <nav class="category-nav">
                <div class="category-tabs">
                    <button class="tab-button active" data-category="all">All Menu</button>
                    <?php while ($category = $categories_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                    <button class="tab-button" data-category="<?= $category['id'] ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </button>
                    <?php endwhile; ?>
                </div>
            </nav>

            <!-- Menu Items -->
            <div class="menu-grid" id="menuItems">
                <!-- Menu items will be loaded here via AJAX -->
            </div>
        </div>
    </main>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h3>Order Summary</h3>
            <button class="close-cart" onclick="toggleCart()">&times;</button>
        </div>
        
        <div class="cart-body">
            <!-- Table & Customer Info -->
            <div class="table-info-card">
                <div id="tableInfo">
                    <?php if (!empty($_SESSION['table_number'])): ?>
                        <p><strong>Table:</strong> <?= $_SESSION['table_number'] ?></p>
                        <p><strong>Customer:</strong> <?= $_SESSION['customer_name'] ?></p>
                        <button class="btn-secondary" onclick="showTableModal()">Change Table</button>
                    <?php else: ?>
                        <button class="btn-primary" onclick="showTableModal()">Select Table</button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Cart Items -->
            <div class="cart-items" id="cartItems">
                <!-- Cart items will be loaded here -->
            </div>

            <!-- Cart Total & Actions -->
            <div class="cart-footer">
                <div class="cart-total">
                    <strong>Total: Rp <span id="cartTotal">0</span></strong>
                </div>
                <button class="btn-success" onclick="placeOrder()" id="placeOrderBtn" disabled>
                    Place Order
                </button>
                <button class="btn-danger" onclick="clearCart()">
                    Clear Cart
                </button>
            </div>
        </div>
    </div>

    <!-- Overlay -->
    <div class="overlay" id="overlay" onclick="toggleCart()"></div>

    <script src="js/app.js"></script>
    <script>
        // Initialize cart count
        updateCartCount();
    </script>
</body>
</html>