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

-- Dumping data for table hochipohub.admin_logs: ~0 rows (approximately)

-- Dumping data for table hochipohub.cart: ~0 rows (approximately)

-- Dumping data for table hochipohub.categories: ~0 rows (approximately)
INSERT INTO `categories` (`category_id`, `category_name`, `category_image`, `created_at`) VALUES
	(1, 'Food', NULL, '2026-08-18 01:26:36'),
	(2, 'Beverages', NULL, '2026-08-18 01:26:36'),
	(3, 'Desserts', NULL, '2026-08-18 01:26:36'),
	(4, 'Snacks', NULL, '2026-08-18 01:26:36');

-- Dumping data for table hochipohub.commission: ~0 rows (approximately)

-- Dumping data for table hochipohub.inventory: ~0 rows (approximately)

-- Dumping data for table hochipohub.mfa_codes: ~0 rows (approximately)

-- Dumping data for table hochipohub.orders: ~0 rows (approximately)

-- Dumping data for table hochipohub.order_details: ~0 rows (approximately)

-- Dumping data for table hochipohub.password_resets: ~0 rows (approximately)

-- Dumping data for table hochipohub.payments: ~0 rows (approximately)

-- Dumping data for table hochipohub.products: ~0 rows (approximately)

-- Dumping data for table hochipohub.reviews: ~0 rows (approximately)

-- Dumping data for table hochipohub.users: ~0 rows (approximately)
INSERT INTO `users` (`user_id`, `name`, `email`, `phone`, `password`, `profile_image`, `role`, `status`, `mfa_enabled`, `mfa_code`, `mfa_expiry`, `reset_code`, `reset_expiry`, `created_at`, `updated_at`) VALUES
	(1, 'iqzah', 'iqzahfarhah@gmail.com', '0194492509', '$2y$10$w3MKyGFjmR/8Y9qNsa1C0OAI3Toz12k/GKx0Wo4rnIy8JFT484Ov.', NULL, 'customer', 'active', 1, NULL, NULL, NULL, NULL, '2026-08-11 06:01:00', '2026-08-11 06:01:00'),
	(2, 'HochipoHub Admin', 'admin@hochipohub.com', NULL, 'REPLACE_WITH_PASSWORD_HASH', NULL, 'admin', 'active', 0, NULL, NULL, NULL, NULL, '2026-08-18 01:26:36', '2026-08-18 01:26:36');

-- Dumping data for table hochipohub.vendors: ~0 rows (approximately)

-- Dumping data for table hochipohub.vendor_applications: ~0 rows (approximately)

-- Dumping data for table hochipohub.vendor_orders: ~0 rows (approximately)

-- Dumping data for table hochipohub.wishlist: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
