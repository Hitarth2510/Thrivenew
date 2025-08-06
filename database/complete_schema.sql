-- ==========================================
-- THRIVE CAFE POS SYSTEM - COMPLETE DATABASE SCHEMA
-- Version: 2.0.0
-- ==========================================

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `thrive_cafe_db` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `thrive_cafe_db`;

-- ==========================================
-- MAIN TABLES
-- ==========================================

-- 1. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `description` text DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `category_id` int(11) DEFAULT NULL,
    `price` decimal(10,2) NOT NULL,
    `cost` decimal(10,2) DEFAULT 0.00,
    `stock_quantity` int(11) DEFAULT 0,
    `min_stock_level` int(11) DEFAULT 5,
    `sku` varchar(50) DEFAULT NULL,
    `description` text DEFAULT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `is_available` tinyint(1) DEFAULT 1,
    `making_cost` decimal(10,2) DEFAULT 0.00,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_products_name` (`name`),
    UNIQUE KEY `uk_products_sku` (`sku`),
    KEY `fk_products_category` (`category_id`),
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Combos Table
CREATE TABLE IF NOT EXISTS `combos` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `price` decimal(10,2) NOT NULL,
    `cost` decimal(10,2) DEFAULT 0.00,
    `description` text DEFAULT NULL,
    `image_url` varchar(500) DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `is_available` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_combos_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Combo Items Junction Table
CREATE TABLE IF NOT EXISTS `combo_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `combo_id` int(11) NOT NULL,
    `product_id` int(11) NOT NULL,
    `quantity` int(11) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_combo_product` (`combo_id`, `product_id`),
    KEY `fk_combo_items_combo` (`combo_id`),
    KEY `fk_combo_items_product` (`product_id`),
    CONSTRAINT `fk_combo_items_combo` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_combo_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Customers Table
CREATE TABLE IF NOT EXISTS `customers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `mobile` varchar(20) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `address` text DEFAULT NULL,
    `city` varchar(100) DEFAULT NULL,
    `pincode` varchar(10) DEFAULT NULL,
    `loyalty_points` int(11) DEFAULT 0,
    `total_orders` int(11) DEFAULT 0,
    `total_spent` decimal(12,2) DEFAULT 0.00,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_customers_mobile` (`mobile`),
    UNIQUE KEY `uk_customers_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Offers Table
CREATE TABLE IF NOT EXISTS `offers` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL,
    `description` text DEFAULT NULL,
    `offer_type` enum('percentage','fixed_amount','buy_x_get_y') DEFAULT 'percentage',
    `discount_value` decimal(10,2) NOT NULL,
    `discount_percent` decimal(5,2) DEFAULT NULL,
    `min_order_amount` decimal(10,2) DEFAULT 0.00,
    `max_discount_amount` decimal(10,2) DEFAULT NULL,
    `usage_limit` int(11) DEFAULT NULL,
    `usage_count` int(11) DEFAULT 0,
    `coupon_code` varchar(50) DEFAULT NULL,
    `apply_to_all` tinyint(1) DEFAULT 0,
    `start_date` date DEFAULT NULL,
    `end_date` date DEFAULT NULL,
    `start_time` time DEFAULT NULL,
    `end_time` time DEFAULT NULL,
    `valid_from` datetime DEFAULT NULL,
    `valid_until` datetime DEFAULT NULL,
    `is_active` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_offers_coupon_code` (`coupon_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Offer Items Junction Table
CREATE TABLE IF NOT EXISTS `offer_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `offer_id` int(11) NOT NULL,
    `item_id` int(11) NOT NULL,
    `item_type` enum('product','combo') NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_offer_item` (`offer_id`, `item_id`, `item_type`),
    KEY `fk_offer_items_offer` (`offer_id`),
    CONSTRAINT `fk_offer_items_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- ORDER MANAGEMENT TABLES
-- ==========================================

-- 8. Orders Table (Main orders system)
CREATE TABLE IF NOT EXISTS `orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_number` varchar(50) NOT NULL,
    `customer_id` int(11) DEFAULT NULL,
    `customer_name` varchar(255) DEFAULT NULL,
    `customer_phone` varchar(20) DEFAULT NULL,
    `customer_email` varchar(255) DEFAULT NULL,
    `order_type` enum('dine_in','takeaway','delivery','online') DEFAULT 'dine_in',
    `table_number` varchar(20) DEFAULT NULL,
    `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
    `discount_amount` decimal(12,2) DEFAULT 0.00,
    `tax_amount` decimal(12,2) DEFAULT 0.00,
    `delivery_charge` decimal(10,2) DEFAULT 0.00,
    `total_amount` decimal(12,2) NOT NULL,
    `coupon_code` varchar(50) DEFAULT NULL,
    `offer_id` int(11) DEFAULT NULL,
    `payment_method` enum('cash','card','upi','wallet','online') DEFAULT 'cash',
    `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
    `order_status` enum('pending','confirmed','preparing','ready','delivered','cancelled') DEFAULT 'pending',
    `status` enum('pending','confirmed','preparing','ready','delivered','cancelled') DEFAULT 'pending',
    `special_instructions` text DEFAULT NULL,
    `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
    `estimated_time` int(11) DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_orders_number` (`order_number`),
    KEY `fk_orders_customer` (`customer_id`),
    KEY `fk_orders_offer` (`offer_id`),
    KEY `idx_orders_date` (`order_date`),
    KEY `idx_orders_status` (`order_status`),
    CONSTRAINT `fk_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_orders_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) DEFAULT NULL,
    `combo_id` int(11) DEFAULT NULL,
    `item_name` varchar(255) NOT NULL,
    `quantity` int(11) NOT NULL,
    `unit_price` decimal(10,2) NOT NULL,
    `total_price` decimal(12,2) NOT NULL,
    `special_instructions` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_order_items_order` (`order_id`),
    KEY `fk_order_items_product` (`product_id`),
    KEY `fk_order_items_combo` (`combo_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_order_items_combo` FOREIGN KEY (`combo_id`) REFERENCES `combos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- LEGACY SALES TABLES (For backward compatibility)
-- ==========================================

-- 10. Sales Orders Table (Legacy)
CREATE TABLE IF NOT EXISTS `sales_orders` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` int(11) DEFAULT NULL,
    `subtotal` decimal(10,2) NOT NULL,
    `discount_amount` decimal(10,2) DEFAULT 0.00,
    `final_amount` decimal(10,2) NOT NULL,
    `payment_type` enum('cash','card','upi','wallet') DEFAULT 'cash',
    `datetime_paid` datetime DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_sales_orders_customer` (`customer_id`),
    CONSTRAINT `fk_sales_orders_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Sales Order Items Table (Legacy)
CREATE TABLE IF NOT EXISTS `sales_order_items` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `item_id` int(11) NOT NULL,
    `item_type` enum('product','combo') NOT NULL,
    `quantity` int(11) NOT NULL,
    `price_per_item` decimal(10,2) NOT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `fk_sales_order_items_order` (`order_id`),
    CONSTRAINT `fk_sales_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- SYSTEM & CONFIGURATION TABLES
-- ==========================================

-- 12. System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL,
    `setting_value` text DEFAULT NULL,
    `setting_type` enum('string','number','boolean','json') DEFAULT 'string',
    `description` text DEFAULT NULL,
    `is_editable` tinyint(1) DEFAULT 1,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_system_settings_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Activity Logs Table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `table_name` varchar(50) DEFAULT NULL,
    `record_id` int(11) DEFAULT NULL,
    `old_values` json DEFAULT NULL,
    `new_values` json DEFAULT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_logs_user` (`user_id`),
    KEY `idx_activity_logs_action` (`action`),
    KEY `idx_activity_logs_table` (`table_name`, `record_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Users Table (For staff management)
CREATE TABLE IF NOT EXISTS `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `full_name` varchar(255) NOT NULL,
    `role` enum('admin','manager','cashier','staff') DEFAULT 'staff',
    `is_active` tinyint(1) DEFAULT 1,
    `last_login` datetime DEFAULT NULL,
    `login_attempts` int(11) DEFAULT 0,
    `locked_until` datetime DEFAULT NULL,
    `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_users_username` (`username`),
    UNIQUE KEY `uk_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- INDEXES FOR PERFORMANCE
-- ==========================================

-- Additional indexes for better performance
CREATE INDEX `idx_products_active` ON `products` (`is_active`, `is_available`);
CREATE INDEX `idx_combos_active` ON `combos` (`is_active`, `is_available`);
CREATE INDEX `idx_offers_active` ON `offers` (`is_active`, `valid_from`, `valid_until`);
CREATE INDEX `idx_orders_customer_date` ON `orders` (`customer_id`, `order_date`);
CREATE INDEX `idx_order_items_product_combo` ON `order_items` (`product_id`, `combo_id`);

-- ==========================================
-- DEFAULT SYSTEM SETTINGS
-- ==========================================

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('cafe_name', 'Thrive Cafe', 'string', 'Name of the cafe'),
('tax_rate', '18.0', 'number', 'Tax rate percentage'),
('currency_symbol', '₹', 'string', 'Currency symbol'),
('timezone', 'Asia/Kolkata', 'string', 'System timezone'),
('order_prefix', 'THR', 'string', 'Order number prefix'),
('default_payment_method', 'cash', 'string', 'Default payment method'),
('enable_loyalty_points', '1', 'boolean', 'Enable loyalty points system'),
('points_per_rupee', '1', 'number', 'Loyalty points earned per rupee spent'),
('min_order_amount', '50', 'number', 'Minimum order amount'),
('delivery_charge', '30', 'number', 'Default delivery charge'),
('auto_print_receipt', '1', 'boolean', 'Auto print receipt after order');

-- ==========================================
-- DEFAULT ADMIN USER
-- ==========================================

INSERT INTO `users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@thrivecafe.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin');

-- Note: Default password is 'password' - Change immediately in production!

-- ==========================================
-- SAMPLE CATEGORIES
-- ==========================================

INSERT INTO `categories` (`name`, `description`) VALUES
('Beverages', 'Hot and cold drinks'),
('Snacks', 'Light snacks and appetizers'),
('Main Course', 'Full meals and main dishes'),
('Desserts', 'Sweet treats and desserts'),
('Combo Meals', 'Value combo packages');

-- ==========================================
-- TRIGGERS FOR AUTOMATIC CALCULATIONS
-- ==========================================

DELIMITER $$

-- Update customer stats after order
CREATE TRIGGER `update_customer_stats_after_order` 
AFTER INSERT ON `orders` 
FOR EACH ROW 
BEGIN
    IF NEW.customer_id IS NOT NULL AND NEW.payment_status = 'paid' THEN
        UPDATE `customers` 
        SET 
            `total_orders` = `total_orders` + 1,
            `total_spent` = `total_spent` + NEW.total_amount,
            `loyalty_points` = `loyalty_points` + FLOOR(NEW.total_amount),
            `updated_at` = NOW()
        WHERE `id` = NEW.customer_id;
    END IF;
END$$

-- Update offer usage count
CREATE TRIGGER `update_offer_usage` 
AFTER INSERT ON `orders` 
FOR EACH ROW 
BEGIN
    IF NEW.offer_id IS NOT NULL THEN
        UPDATE `offers` 
        SET `usage_count` = `usage_count` + 1,
            `updated_at` = NOW()
        WHERE `id` = NEW.offer_id;
    END IF;
END$$

DELIMITER ;

-- ==========================================
-- VIEWS FOR REPORTING
-- ==========================================

-- Daily sales summary view
CREATE VIEW `daily_sales` AS
SELECT 
    DATE(order_date) as sale_date,
    COUNT(*) as total_orders,
    SUM(subtotal) as gross_sales,
    SUM(discount_amount) as total_discounts,
    SUM(tax_amount) as total_tax,
    SUM(total_amount) as net_sales,
    AVG(total_amount) as average_order_value
FROM `orders` 
WHERE payment_status = 'paid' 
GROUP BY DATE(order_date)
ORDER BY sale_date DESC;

-- Popular products view
CREATE VIEW `popular_products` AS
SELECT 
    p.id,
    p.name,
    p.price,
    COUNT(oi.id) as order_count,
    SUM(oi.quantity) as total_quantity,
    SUM(oi.total_price) as total_revenue
FROM `products` p
JOIN `order_items` oi ON p.id = oi.product_id
JOIN `orders` o ON oi.order_id = o.id
WHERE o.payment_status = 'paid'
GROUP BY p.id, p.name, p.price
ORDER BY total_quantity DESC;

-- Customer analytics view
CREATE VIEW `customer_analytics` AS
SELECT 
    c.id,
    c.name,
    c.mobile,
    c.total_orders,
    c.total_spent,
    c.loyalty_points,
    DATEDIFF(NOW(), c.created_at) as days_since_registration,
    COALESCE(last_order.last_order_date, c.created_at) as last_order_date,
    DATEDIFF(NOW(), COALESCE(last_order.last_order_date, c.created_at)) as days_since_last_order
FROM `customers` c
LEFT JOIN (
    SELECT customer_id, MAX(order_date) as last_order_date
    FROM `orders` 
    WHERE payment_status = 'paid'
    GROUP BY customer_id
) last_order ON c.id = last_order.customer_id
WHERE c.is_active = 1
ORDER BY c.total_spent DESC;

-- ==========================================
-- END OF SCHEMA
-- ==========================================
