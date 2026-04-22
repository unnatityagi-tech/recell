-- LocalPhone Marketplace Database Schema
-- Run this SQL file in phpMyAdmin or MySQL command line

-- Create database
CREATE DATABASE IF NOT EXISTS localphone_marketplace;
USE localphone_marketplace;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'shop', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Shops table
CREATE TABLE IF NOT EXISTS shops (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    shop_name VARCHAR(200) NOT NULL,
    address TEXT,
    phone VARCHAR(20),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shop_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    `condition` ENUM('New', 'Like New', 'Good', 'Fair', 'Poor') NOT NULL,
    description TEXT,
    image VARCHAR(255),
    location VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE
);

-- Inquiries table
CREATE TABLE IF NOT EXISTS inquiries (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT NOT NULL,
    message TEXT NOT NULL,
    contact_email VARCHAR(150),
    contact_phone VARCHAR(20),
    status ENUM('pending', 'replied', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Wishlist table
CREATE TABLE IF NOT EXISTS wishlist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, product_id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin', 'admin@localphone.com', '$2y$10$jpoTws8uP1jTUnc3f5NWd.mB2zvGGSEF5g/.6BpCpBLUfbJ6pcjGu', 'admin');

-- Insert sample shop user (password: shop123)
INSERT INTO users (name, email, password, role) VALUES 
('Mobile World', 'shop@localphone.com', '$2y$10$A7TxpFBFBLVedIm3q19zheVeJmhjtyGaPgs4da9wtYvMbyYScG8bi', 'shop');

-- Insert sample shop
INSERT INTO shops (user_id, shop_name, address, phone, status) VALUES 
(2, 'Mobile World', '123 Main Street, City Center', '9876543210', 'approved');

-- Insert sample products
INSERT INTO products (shop_id, title, brand, model, price, `condition`, description, image, location) VALUES
(1, 'iPhone 12 Pro', 'Apple', 'iPhone 12 Pro', 45000, 'Like New', '128GB storage, excellent condition, all accessories included', 'iphone12pro.jpg', 'City Center'),
(1, 'Samsung Galaxy S21', 'Samsung', 'Galaxy S21', 35000, 'Good', '256GB storage, minor scratches, original box', 'galaxys21.jpg', 'City Center'),
(1, 'OnePlus 9', 'OnePlus', '9', 28000, 'Like New', '128GB storage, mint condition', 'oneplus9.jpg', 'City Center');

-- Insert additional sample shops and users
INSERT INTO users (name, email, password, role) VALUES 
('Tech Hub', 'tech@hub.com', '$2y$10$A7TxpFBFBLVedIm3q19zheVeJmhjtyGaPgs4da9wtYvMbyYScG8bi', 'shop'),
('Mobile Paradise', 'mobile@paradise.com', '$2y$10$A7TxpFBFBLVedIm3q19zheVeJmhjtyGaPgs4da9wtYvMbyYScG8bi', 'shop'),
('Phone World', 'phone@world.com', '$2y$10$A7TxpFBFBLVedIm3q19zheVeJmhjtyGaPgs4da9wtYvMbyYScG8bi', 'shop'),
('Smartphone Store', 'smart@store.com', '$2y$10$A7TxpFBFBLVedIm3q19zheVeJmhjtyGaPgs4da9wtYvMbyYScG8bi', 'shop');

INSERT INTO shops (user_id, shop_name, address, phone, status) VALUES 
(3, 'Tech Hub', '456 Market Street, Downtown', '9876543211', 'approved'),
(4, 'Mobile Paradise', '789 Electronics Lane, Tech Park', '9876543212', 'approved'),
(5, 'Phone World', '321 Gadget Road, Commercial Area', '9876543213', 'approved'),
(6, 'Smartphone Store', '654 Mobile Boulevard, Shopping Complex', '9876543214', 'approved');

-- Insert additional sample products
INSERT INTO products (shop_id, title, brand, model, price, `condition`, description, image, location) VALUES
-- Tech Hub products
(3, 'iPhone 13 Pro', 'Apple', 'iPhone 13 Pro', 52000, 'New', 'Brand new iPhone 13 Pro, 256GB, Pacific Blue color, sealed box with warranty', 'iphone13pro.jpg', 'Downtown'),
(3, 'Samsung Galaxy S22 Ultra', 'Samsung', 'Galaxy S22 Ultra', 48000, 'Like New', 'Excellent condition Galaxy S22 Ultra, 512GB, Phantom Black, minimal use', 'galaxys22ultra.jpg', 'Downtown'),
(3, 'Google Pixel 7 Pro', 'Google', 'Pixel 7 Pro', 38000, 'Good', 'Google Pixel 7 Pro, 128GB, Hazel color, good condition with charger', 'pixel7pro.jpg', 'Downtown'),
-- Mobile Paradise products
(4, 'iPhone 14', 'Apple', 'iPhone 14', 45000, 'Like New', 'iPhone 14, 128GB, Starlight, barely used, original accessories', 'iphone14.jpg', 'Tech Park'),
(4, 'OnePlus 11', 'OnePlus', '11', 32000, 'New', 'Brand new OnePlus 11, 256GB, Eternal Green, sealed pack', 'oneplus11.jpg', 'Tech Park'),
(4, 'Xiaomi 13 Pro', 'Xiaomi', '13 Pro', 28000, 'Good', 'Xiaomi 13 Pro, 256GB, Ceramic Black, good condition with box', 'xiaomi13pro.jpg', 'Tech Park'),
-- Phone World products
(5, 'Samsung Galaxy A54', 'Samsung', 'Galaxy A54', 22000, 'Like New', 'Galaxy A54, 128GB, Awesome Violet, excellent condition', 'galaxya54.jpg', 'Commercial Area'),
(5, 'Realme GT 3', 'Realme', 'GT 3', 18000, 'Good', 'Realme GT 3, 256GB, Pulse White, good condition with charger', 'realmegt3.jpg', 'Commercial Area'),
(5, 'iPhone 12 Mini', 'Apple', 'iPhone 12 Mini', 28000, 'Fair', 'iPhone 12 Mini, 64GB, Purple, fair condition, minor scratches', 'iphone12mini.jpg', 'Commercial Area'),
-- Smartphone Store products
(6, 'Oppo Find X6 Pro', 'Oppo', 'Find X6 Pro', 35000, 'New', 'Brand new Oppo Find X6 Pro, 512GB, Brown, sealed with warranty', 'oppofindx6.jpg', 'Shopping Complex'),
(6, 'Vivo X90 Pro', 'Vivo', 'X90 Pro', 30000, 'Like New', 'Vivo X90 Pro, 256GB, Legend Black, barely used', 'vivoX90pro.jpg', 'Shopping Complex'),
(6, 'Nothing Phone (2)', 'Nothing', 'Phone (2)', 25000, 'Good', 'Nothing Phone 2, 128GB, White, good condition with transparent case', 'nothingphone2.jpg', 'Shopping Complex');