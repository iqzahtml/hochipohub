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
