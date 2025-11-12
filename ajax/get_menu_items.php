<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$category_id = $_POST['category_id'] ?? 'all';

$query = "SELECT mi.*, c.name as category_name 
          FROM menu_items mi 
          LEFT JOIN categories c ON mi.category_id = c.id 
          WHERE mi.is_available = 1";

if ($category_id !== 'all') {
    $query .= " AND mi.category_id = :category_id";
}

$query .= " ORDER BY mi.name";

$stmt = $db->prepare($query);
if ($category_id !== 'all') {
    $stmt->bindParam(':category_id', $category_id);
}
$stmt->execute();

if ($stmt->rowCount() > 0) {
    while ($item = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $image_path = 'images/' . ($item['image'] ?: 'default.jpg');
        echo '
        <div class="menu-item">
            <img src="' . $image_path . '" alt="' . htmlspecialchars($item['name']) . '" class="menu-item-image">
            <div class="menu-item-content">
                <h3 class="menu-item-title">' . htmlspecialchars($item['name']) . '</h3>
                <p class="menu-item-description">' . htmlspecialchars($item['description']) . '</p>
                <div class="menu-item-footer">
                    <span class="menu-item-price">Rp ' . number_format($item['price']) . '</span>
                    <button class="btn-primary add-to-cart-btn" 
                            data-id="' . $item['id'] . '" 
                            data-name="' . htmlspecialchars($item['name']) . '" 
                            data-price="' . $item['price'] . '">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>';
    }
} else {
    echo '<div class="no-items">No menu items found</div>';
}
?>