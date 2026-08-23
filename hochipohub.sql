<<<<<<< HEAD
-- =========================================================
-- HOCHIPOHUB DATABASE
-- =========================================================

CREATE DATABASE IF NOT EXISTS hochipohub
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE hochipohub;


-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    phone VARCHAR(20) UNIQUE,

    password VARCHAR(255) NOT NULL,

    profile_image VARCHAR(255) NULL,

    role ENUM(
        'customer',
        'vendor',
        'admin'
    ) NOT NULL DEFAULT 'customer',

    status ENUM(
        'active',
        'inactive',
        'pending',
        'suspended'
    ) NOT NULL DEFAULT 'active',

    mfa_enabled BOOLEAN NOT NULL DEFAULT TRUE,

    mfa_code VARCHAR(10) NULL,

    mfa_expiry DATETIME NULL,

    reset_code VARCHAR(10) NULL,

    reset_expiry DATETIME NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB;


-- =========================================================
-- 2. VENDORS
-- =========================================================

CREATE TABLE IF NOT EXISTS vendors (
    vendor_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    business_name VARCHAR(150) NOT NULL,

    business_logo VARCHAR(255) NULL,

    business_description TEXT NULL,

    business_address TEXT NULL,

    category VARCHAR(100) NULL,

    delivery_method ENUM(
        'Pickup',
        'Postage',
        'Both'
    ) NOT NULL DEFAULT 'Both',

    approval_status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Suspended'
    ) NOT NULL DEFAULT 'Pending',

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vendors_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

) ENGINE=InnoDB;


-- =========================================================
-- 3. CATEGORIES
-- =========================================================

CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    category_image VARCHAR(255) NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB;


-- =========================================================
-- 4. PRODUCTS
-- =========================================================

CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    category_id INT NOT NULL,

    product_name VARCHAR(150) NOT NULL,

    description TEXT NULL,

    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    stock_quantity INT NOT NULL DEFAULT 0,

    image VARCHAR(255) NULL,

    status ENUM(
        'Available',
        'Out of Stock',
        'Hidden'
    ) NOT NULL DEFAULT 'Available',

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_products_vendor
        FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id)
        REFERENCES categories(category_id)

) ENGINE=InnoDB;


-- =========================================================
-- 5. CART
-- =========================================================

CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_cart_customer
        FOREIGN KEY (customer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_cart_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_customer_product (
        customer_id,
        product_id
    )

) ENGINE=InnoDB;


-- =========================================================
-- 6. ORDERS
-- =========================================================

CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    order_date DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    delivery_method ENUM(
        'Pickup',
        'Postage'
    ) NULL,

    delivery_address TEXT NULL,

    tracking_number VARCHAR(100) NULL,

    order_status ENUM(
        'Pending',
        'Processing',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    completed_date DATETIME NULL,

    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id)
        REFERENCES users(user_id)

) ENGINE=InnoDB;


-- =========================================================
-- 7. ORDER DETAILS
-- =========================================================

CREATE TABLE IF NOT EXISTS order_details (
    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL,

    unit_price DECIMAL(10,2) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,

    CONSTRAINT fk_order_details_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_order_details_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)

) ENGINE=InnoDB;


-- =========================================================
-- 8. VENDOR ORDERS
-- =========================================================

CREATE TABLE IF NOT EXISTS vendor_orders (
    vendor_order_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    vendor_id INT NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    vendor_status ENUM(
        'Pending',
        'Processing',
        'Ready',
        'Shipped',
        'Completed',
        'Cancelled'
    ) NOT NULL DEFAULT 'Pending',

    tracking_number VARCHAR(100) NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    completed_at DATETIME NULL,

    CONSTRAINT fk_vendor_orders_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vendor_orders_vendor
        FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id),

    UNIQUE KEY unique_order_vendor (
        order_id,
        vendor_id
    )

) ENGINE=InnoDB;


-- =========================================================
-- 9. PAYMENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    payment_method ENUM(
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ) NULL,

    payment_status ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) NOT NULL DEFAULT 'Pending',

    payment_date DATETIME NULL,

    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    transaction_reference VARCHAR(100) NULL,

    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)

) ENGINE=InnoDB;


-- =========================================================
-- 10. REVIEWS
-- =========================================================

CREATE TABLE IF NOT EXISTS reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    rating INT NOT NULL,

    review TEXT NULL,

    image VARCHAR(255) NULL,

    status ENUM(
        'Visible',
        'Hidden'
    ) NOT NULL DEFAULT 'Visible',

    review_date DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id)
        REFERENCES users(user_id),

    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id),

    CONSTRAINT chk_reviews_rating
        CHECK (rating BETWEEN 1 AND 5)

) ENGINE=InnoDB;


-- =========================================================
-- 11. INVENTORY
-- =========================================================

CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL UNIQUE,

    quantity INT NOT NULL DEFAULT 0,

    last_updated DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE

) ENGINE=InnoDB;


-- =========================================================
-- 12. COMMISSION
-- =========================================================

CREATE TABLE IF NOT EXISTS commission (
    commission_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    order_id INT NOT NULL,

    vendor_order_id INT NULL,

    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'Pending',
        'Paid'
    ) NOT NULL DEFAULT 'Pending',

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_commission_vendor
        FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id),

    CONSTRAINT fk_commission_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_commission_vendor_order
        FOREIGN KEY (vendor_order_id)
        REFERENCES vendor_orders(vendor_order_id)
        ON DELETE SET NULL

) ENGINE=InnoDB;


-- =========================================================
-- 13. MFA CODE HISTORY
-- =========================================================

CREATE TABLE IF NOT EXISTS mfa_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    code VARCHAR(10) NOT NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    CONSTRAINT fk_mfa_codes_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

) ENGINE=InnoDB;


-- =========================================================
-- 14. PASSWORD RESET HISTORY
-- =========================================================

CREATE TABLE IF NOT EXISTS password_resets (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    reset_code VARCHAR(10) NOT NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    CONSTRAINT fk_password_resets_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

) ENGINE=InnoDB;


-- =========================================================
-- 15. WISHLIST
-- =========================================================

CREATE TABLE IF NOT EXISTS wishlist (
    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    product_id INT NOT NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_wishlist_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_wishlist_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_wishlist_product (
        user_id,
        product_id
    )

) ENGINE=InnoDB;


-- =========================================================
-- 16. ADMIN LOGS
-- =========================================================

CREATE TABLE IF NOT EXISTS admin_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    action VARCHAR(255) NOT NULL,

    target_type VARCHAR(50) NULL,

    target_id INT NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_logs_admin
        FOREIGN KEY (admin_id)
        REFERENCES users(user_id)

) ENGINE=InnoDB;


-- =========================================================
-- 17. VENDOR APPLICATIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS vendor_applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    business_name VARCHAR(150) NULL,

    reason TEXT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) NOT NULL DEFAULT 'Pending',

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    reviewed_at DATETIME NULL,

    reviewed_by INT NULL,

    CONSTRAINT fk_vendor_applications_user
        FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_vendor_applications_reviewer
        FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL

) ENGINE=InnoDB;


-- =========================================================
-- DEFAULT ADMIN ACCOUNT
-- =========================================================
--
-- Email    : admin@hochipohub.com
-- Password : Admin@123456
--
-- =========================================================

INSERT INTO users (
    name,
    email,
    phone,
    password,
    role,
    status,
    mfa_enabled
)
SELECT
    'HochipoHub Admin',
    'admin@hochipohub.com',
    NULL,
    '$2y$10$zMKREp2yfLOMrxor8D72Aeg/iWHUQ0CqDChUegjPiDhnujEPaPqre',
    'admin',
    'active',
    FALSE
WHERE NOT EXISTS (
    SELECT 1
    FROM users
    WHERE email = 'admin@hochipohub.com'
);


-- =========================================================
-- FORCE UPDATE ADMIN ACCOUNT
-- =========================================================
-- Ini penting kalau account admin sudah wujud.
-- Ia akan pastikan password + role + status betul.
-- =========================================================

UPDATE users
SET
    password = '$2y$10$zMKREp2yfLOMrxor8D72Aeg/iWHUQ0CqDChUegjPiDhnujEPaPqre',
    role = 'admin',
    status = 'active',
    mfa_enabled = FALSE
WHERE email = 'admin@hochipohub.com';


-- =========================================================
-- DEFAULT CATEGORIES
-- =========================================================

INSERT INTO categories (category_name)
SELECT 'Food'
WHERE NOT EXISTS (
    SELECT 1
    FROM categories
    WHERE category_name = 'Food'
);


INSERT INTO categories (category_name)
SELECT 'Beverages'
WHERE NOT EXISTS (
    SELECT 1
    FROM categories
    WHERE category_name = 'Beverages'
);


INSERT INTO categories (category_name)
SELECT 'Desserts'
WHERE NOT EXISTS (
    SELECT 1
    FROM categories
    WHERE category_name = 'Desserts'
);


INSERT INTO categories (category_name)
SELECT 'Snacks'
WHERE NOT EXISTS (
    SELECT 1
    FROM categories
    WHERE category_name = 'Snacks'
);


-- =========================================================
-- VERIFY ADMIN
-- =========================================================

SELECT
    user_id,
    name,
    email,
    role,
    status,
    mfa_enabled
FROM users
WHERE email = 'admin@hochipohub.com';hochipohubhochipohubusers
=======
-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.0.30 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.1.0.6537
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for hochipohub
CREATE DATABASE IF NOT EXISTS `hochipohub` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `hochipohub`;

-- Dumping structure for table hochipohub.admin_logs
CREATE TABLE IF NOT EXISTS `admin_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int NOT NULL,
  `action` varchar(255) NOT NULL,
  `target_type` varchar(50) DEFAULT NULL,
  `target_id` int DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `admin_id` (`admin_id`),
  CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.admin_logs: ~0 rows (approximately)

-- Dumping structure for table hochipohub.cart
CREATE TABLE IF NOT EXISTS `cart` (
  `cart_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_customer_product` (`customer_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.cart: ~0 rows (approximately)

-- Dumping structure for table hochipohub.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  `category_image` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.categories: ~0 rows (approximately)
INSERT INTO `categories` (`category_id`, `category_name`, `category_image`, `created_at`) VALUES
	(1, 'Food', NULL, '2026-08-21 00:28:55'),
	(2, 'Beverages', NULL, '2026-08-21 00:28:55'),
	(3, 'Desserts', NULL, '2026-08-21 00:28:55'),
	(4, 'Snacks', NULL, '2026-08-21 00:28:55');

-- Dumping structure for table hochipohub.commission
CREATE TABLE IF NOT EXISTS `commission` (
  `commission_id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` int NOT NULL,
  `order_id` int NOT NULL,
  `vendor_order_id` int DEFAULT NULL,
  `commission_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `commission_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('Pending','Paid') DEFAULT 'Pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`commission_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `order_id` (`order_id`),
  KEY `vendor_order_id` (`vendor_order_id`),
  CONSTRAINT `commission_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`),
  CONSTRAINT `commission_ibfk_2` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `commission_ibfk_3` FOREIGN KEY (`vendor_order_id`) REFERENCES `vendor_orders` (`vendor_order_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.commission: ~0 rows (approximately)

-- Dumping structure for table hochipohub.inventory
CREATE TABLE IF NOT EXISTS `inventory` (
  `inventory_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '0',
  `last_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`inventory_id`),
  UNIQUE KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.inventory: ~0 rows (approximately)

-- Dumping structure for table hochipohub.mfa_codes
CREATE TABLE IF NOT EXISTS `mfa_codes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `code` varchar(10) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `mfa_codes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.mfa_codes: ~0 rows (approximately)

-- Dumping structure for table hochipohub.orders
CREATE TABLE IF NOT EXISTS `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `total_amount` decimal(10,2) NOT NULL,
  `delivery_method` enum('Pickup','Postage') DEFAULT NULL,
  `delivery_address` text,
  `tracking_number` varchar(100) DEFAULT NULL,
  `order_status` enum('Pending','Processing','Completed','Cancelled') DEFAULT 'Pending',
  `completed_date` datetime DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.orders: ~0 rows (approximately)

-- Dumping structure for table hochipohub.order_details
CREATE TABLE IF NOT EXISTS `order_details` (
  `order_detail_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_detail_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.order_details: ~0 rows (approximately)

-- Dumping structure for table hochipohub.password_resets
CREATE TABLE IF NOT EXISTS `password_resets` (
  `reset_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `reset_code` varchar(10) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  PRIMARY KEY (`reset_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.password_resets: ~0 rows (approximately)

-- Dumping structure for table hochipohub.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `payment_method` enum('FPX','Credit Card','Debit Card','Cash') DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending',
  `payment_date` datetime DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_reference` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.payments: ~0 rows (approximately)

-- Dumping structure for table hochipohub.products
CREATE TABLE IF NOT EXISTS `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `vendor_id` int NOT NULL,
  `category_id` int NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock_quantity` int DEFAULT '0',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Available','Out of Stock','Hidden') DEFAULT 'Available',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `vendor_id` (`vendor_id`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`) ON DELETE CASCADE,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.products: ~0 rows (approximately)

-- Dumping structure for table hochipohub.reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `review_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `product_id` int NOT NULL,
  `rating` int NOT NULL,
  `review` text,
  `image` varchar(255) DEFAULT NULL,
  `status` enum('Visible','Hidden') DEFAULT 'Visible',
  `review_date` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`review_id`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`),
  CONSTRAINT `reviews_chk_1` CHECK ((`rating` between 1 and 5))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.reviews: ~0 rows (approximately)

-- Dumping structure for table hochipohub.users
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('customer','vendor','admin') NOT NULL DEFAULT 'customer',
  `status` enum('active','inactive','pending','suspended') DEFAULT 'active',
  `mfa_enabled` tinyint(1) DEFAULT '1',
  `mfa_code` varchar(10) DEFAULT NULL,
  `mfa_expiry` datetime DEFAULT NULL,
  `reset_code` varchar(10) DEFAULT NULL,
  `reset_expiry` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.users: ~6 rows (approximately)
INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `profile_image`, `role`, `status`, `mfa_enabled`, `mfa_code`, `mfa_expiry`, `reset_code`, `reset_expiry`, `created_at`, `updated_at`) VALUES
	(1, 'Aqilah', 'qilardzuan16@gmail.com', '01312365498', '$2y$10$XQMz9UUEPCu2Ah0X4XZtsO6kAhk45wFYz4MYPIIP6lNLYnFVMz2iO', NULL, 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-08-11 02:36:47', '2026-08-11 02:36:47'),
	(2, 'lili', 'lili@gmail.com', '011222222', '$2y$10$fY1zA3zEd6l9x72IyIP2JejWdowrwjdpGvGl4dlOuFMQfznS2NPBa', NULL, 'vendor', 'active', 1, NULL, NULL, NULL, NULL, '2026-08-11 10:06:44', '2026-08-11 10:06:44'),
	(3, 'mamu', 'mm@gmail.com', '011777777', '$2y$10$waqH3ETV9JiNAGP2yjxqku3yDwJuKw.N1GqDEJlNWWsQuI2BhmXhG', NULL, 'vendor', 'active', 1, NULL, NULL, NULL, NULL, '2026-08-11 10:14:53', '2026-08-11 10:14:53'),
	(4, 'ain', 'ain@gmail.com', '1111111', '$2y$10$alQ2sxrPL/GqBg3k21/t8.2vcVPr9TKCBpVOI4LdsFxlC9ZsTYhMe', NULL, 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-08-17 23:30:59', '2026-08-17 23:30:59'),
	(5, 'F1101 NUR ALIAH AQILAH BINdwfwgTI RADZUAN', 'qill@gmail.com', '012364589', '$2y$10$IywAnu5Bgl0o3YeJXNJhv.vl5Eo53L5ywkSRRA0ZW1B69FLAGrW6O', NULL, 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-08-20 21:41:20', '2026-08-20 21:41:20'),
	(6, 'HochipoHub Admin', 'admin@hochipohub.com', NULL, '$2y$10$zMKREp2yfLOMrxor8D72Aeg/iWHUQ0CqDChUegjPiDhnujEPaPqre', NULL, 'admin', 'active', 0, NULL, NULL, NULL, NULL, '2026-08-21 00:28:55', '2026-08-21 00:28:55');

-- Dumping structure for table hochipohub.vendors
CREATE TABLE IF NOT EXISTS `vendors` (
  `vendor_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `business_name` varchar(150) NOT NULL,
  `business_logo` varchar(255) DEFAULT NULL,
  `business_description` text,
  `business_address` text,
  `category` varchar(100) DEFAULT NULL,
  `delivery_method` enum('Pickup','Postage','Both') DEFAULT 'Both',
  `approval_status` enum('Pending','Approved','Rejected','Suspended') DEFAULT 'Pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`vendor_id`),
  UNIQUE KEY `user_id` (`user_id`),
  CONSTRAINT `vendors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.vendors: ~2 rows (approximately)
INSERT INTO `vendors` (`vendor_id`, `user_id`, `business_name`, `business_logo`, `business_description`, `business_address`, `category`, `delivery_method`, `approval_status`, `created_at`, `updated_at`) VALUES
	(1, 2, 'lili', NULL, NULL, NULL, NULL, 'Both', 'Pending', '2026-08-11 10:06:44', '2026-08-11 10:06:44'),
	(2, 3, 'mamu', NULL, NULL, NULL, NULL, 'Both', 'Pending', '2026-08-11 10:14:53', '2026-08-11 10:14:53');

-- Dumping structure for table hochipohub.vendor_orders
CREATE TABLE IF NOT EXISTS `vendor_orders` (
  `vendor_order_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `vendor_id` int NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_fee` decimal(10,2) DEFAULT '0.00',
  `vendor_status` enum('Pending','Processing','Ready','Shipped','Completed','Cancelled') DEFAULT 'Pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`vendor_order_id`),
  UNIQUE KEY `unique_order_vendor` (`order_id`,`vendor_id`),
  KEY `vendor_id` (`vendor_id`),
  CONSTRAINT `vendor_orders_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_orders_ibfk_2` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`vendor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.vendor_orders: ~0 rows (approximately)

-- Dumping structure for table hochipohub.wishlist
CREATE TABLE IF NOT EXISTS `wishlist` (
  `wishlist_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`wishlist_id`),
  UNIQUE KEY `unique_wishlist_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `wishlist_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table hochipohub.wishlist: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
>>>>>>> 6695cf635ac6f08caf77cc07108163b42ea8b8d8
