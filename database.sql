-- (Sama seperti code sebelumnya)
CREATE DATABASE restaurant;
USE restaurant;

CREATE TABLE tables (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_number VARCHAR(10) UNIQUE NOT NULL,
    capacity INT NOT NULL,
    status ENUM('available', 'occupied') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE menu_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    table_id INT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    customer_name VARCHAR(255),
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id)
);

CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    menu_item_id INT,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    status ENUM('pending', 'preparing', 'ready') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id)
);

CREATE TABLE staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    role ENUM('admin', 'kitchen') DEFAULT 'kitchen',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO tables (table_number, capacity) VALUES 
('T01', 4), ('T02', 2), ('T03', 6), ('T04', 4), ('T05', 8);

INSERT INTO categories (name, description) VALUES 
('Appetizers', 'Start your meal right'),
('Main Course', 'Delicious main dishes'),
('Desserts', 'Sweet endings'),
('Beverages', 'Refreshing drinks');

INSERT INTO menu_items (category_id, name, description, price, image) VALUES 
(1, 'Spring Rolls', 'Crispy vegetable spring rolls', 45000, 'spring_rolls.jpg'),
(1, 'Chicken Satay', 'Grilled chicken skewers with peanut sauce', 55000, 'satay.jpg'),
(2, 'Beef Rendang', 'Spicy beef curry with coconut milk', 85000, 'rendang.jpg'),
(2, 'Grilled Salmon', 'Fresh salmon with lemon butter sauce', 95000, 'salmon.jpg'),
(3, 'Chocolate Cake', 'Rich chocolate layer cake', 35000, 'chocolate_cake.jpg'),
(3, 'Ice Cream', 'Vanilla, chocolate, or strawberry', 25000, 'ice_cream.jpg'),
(4, 'Orange Juice', 'Freshly squeezed orange juice', 20000, 'orange_juice.jpg'),
(4, 'Iced Tea', 'Refreshing iced tea with lemon', 15000, 'iced_tea.jpg');

INSERT INTO staff (username, password, name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Restaurant Admin', 'admin'),
('kitchen', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kitchen Staff', 'kitchen');