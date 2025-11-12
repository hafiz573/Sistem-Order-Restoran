<?php
session_start();
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['staff']) || $_SESSION['staff']['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $id = $_POST['id'] ?? '';
    $name = trim($_POST['name']);
    $category_id = $_POST['category_id'];
    $price = floatval($_POST['price']);
    $description = trim($_POST['description']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    // Validate required fields
    if (empty($name) || empty($category_id) || empty($price)) {
        throw new Exception('Please fill in all required fields');
    }

    if ($price <= 0) {
        throw new Exception('Price must be greater than 0');
    }

    // Handle file upload
    $image_name = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            throw new Exception('Only JPG, PNG, and GIF images are allowed');
        }
        
        // Validate file size (max 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Image size must be less than 2MB');
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $image_name = uniqid() . '_' . time() . '.' . $file_extension;
        $upload_path = '../../images/' . $image_name;
        
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception('Failed to upload image');
        }
        
        // Delete old image if exists and we're updating
        if ($id) {
            $old_image_query = "SELECT image FROM menu_items WHERE id = :id";
            $old_image_stmt = $db->prepare($old_image_query);
            $old_image_stmt->bindParam(':id', $id);
            $old_image_stmt->execute();
            $old_image = $old_image_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($old_image['image'] && file_exists('../../images/' . $old_image['image'])) {
                unlink('../../images/' . $old_image['image']);
            }
        }
    }

    if ($id) {
        // Update existing item
        if ($image_name) {
            $query = "UPDATE menu_items SET name = :name, category_id = :category_id, price = :price, 
                      description = :description, image = :image, is_available = :is_available 
                      WHERE id = :id";
        } else {
            $query = "UPDATE menu_items SET name = :name, category_id = :category_id, price = :price, 
                      description = :description, is_available = :is_available 
                      WHERE id = :id";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':is_available', $is_available);
        $stmt->bindParam(':id', $id);
        
        if ($image_name) {
            $stmt->bindParam(':image', $image_name);
        }
        
        $stmt->execute();
        $message = 'Menu item updated successfully';
    } else {
        // Insert new item
        $query = "INSERT INTO menu_items (name, category_id, price, description, image, is_available) 
                  VALUES (:name, :category_id, :price, :description, :image, :is_available)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':image', $image_name);
        $stmt->bindParam(':is_available', $is_available);
        $stmt->execute();
        
        $message = 'Menu item added successfully';
    }

    echo json_encode(['success' => true, 'message' => $message]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>