-- ============================================================
-- HOCHIPOHUB
-- FULL FRESH DATABASE FOR PIEx
-- MySQL 8 / HeidiSQL
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS `hochipohub`;

CREATE DATABASE `hochipohub`
DEFAULT CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `hochipohub`;


-- ============================================================
-- 1. USERS
-- ============================================================

CREATE TABLE `users` (
    `user_id` INT NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `email` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `phone` VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `password` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `profile_image` VARCHAR(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `role` ENUM(
        'customer',
        'vendor',
        'admin'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'customer',

    `status` ENUM(
        'active',
        'inactive',
        'pending',
        'suspended'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'active',

    `mfa_enabled` TINYINT(1) NOT NULL DEFAULT '1',
    `mfa_code` VARCHAR(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `mfa_expiry` DATETIME DEFAULT NULL,

    `reset_code` VARCHAR(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `reset_expiry` DATETIME DEFAULT NULL,

    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`user_id`),

    UNIQUE KEY `email` (`email`),
    UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 2. CATEGORIES
-- ============================================================

CREATE TABLE `categories` (
    `category_id` INT NOT NULL AUTO_INCREMENT,

    `category_name` VARCHAR(100)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `category_image` VARCHAR(255)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`category_id`),

    UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. VENDORS
-- ============================================================

CREATE TABLE `vendors` (
    `vendor_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,

    `business_name` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `business_logo` VARCHAR(255)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `business_description` TEXT
        COLLATE utf8mb4_unicode_ci,

    `business_address` TEXT
        COLLATE utf8mb4_unicode_ci,

    `latitude` DECIMAL(10,8) DEFAULT NULL,
    `longitude` DECIMAL(11,8) DEFAULT NULL,

    `category` VARCHAR(100)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `delivery_method` ENUM(
        'Pickup',
        'Postage',
        'Both'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Both',

    `postage_fee` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `allow_vendor_delivery` TINYINT(1)
        NOT NULL DEFAULT '0',

    `cod_enabled` TINYINT(1)
        NOT NULL DEFAULT '0',

    `vendor_delivery_fee` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `commission_rate` DECIMAL(5,2)
        NOT NULL DEFAULT '5.00',

    `approval_status` ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Suspended'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Pending',

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`vendor_id`),

    UNIQUE KEY `user_id` (`user_id`),

    KEY `idx_vendors_location`
        (`latitude`, `longitude`),

    CONSTRAINT `fk_vendors_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_vendor_commission_rate`
        CHECK (
            `commission_rate` >= 0
            AND `commission_rate` <= 100
        ),

    CONSTRAINT `chk_vendor_delivery_fee`
        CHECK (`vendor_delivery_fee` >= 0),

    CONSTRAINT `chk_vendor_postage_fee`
        CHECK (`postage_fee` >= 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 4. PRODUCTS
-- ============================================================

CREATE TABLE `products` (
    `product_id` INT NOT NULL AUTO_INCREMENT,
    `vendor_id` INT NOT NULL,
    `category_id` INT NOT NULL,

    `product_name` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `description` TEXT
        COLLATE utf8mb4_unicode_ci,

    `price` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `stock_quantity` INT
        NOT NULL DEFAULT '0',

    `image` VARCHAR(255)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `status` ENUM(
        'Available',
        'Out of Stock',
        'Hidden'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Available',

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`product_id`),

    KEY `idx_products_vendor` (`vendor_id`),
    KEY `idx_products_category` (`category_id`),
    KEY `idx_products_status` (`status`),

    CONSTRAINT `fk_products_category`
        FOREIGN KEY (`category_id`)
        REFERENCES `categories` (`category_id`),

    CONSTRAINT `fk_products_vendor`
        FOREIGN KEY (`vendor_id`)
        REFERENCES `vendors` (`vendor_id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_product_price`
        CHECK (`price` >= 0),

    CONSTRAINT `chk_product_stock`
        CHECK (`stock_quantity` >= 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 5. INVENTORY
-- ============================================================

CREATE TABLE `inventory` (
    `inventory_id` INT NOT NULL AUTO_INCREMENT,
    `product_id` INT NOT NULL,

    `quantity` INT
        NOT NULL DEFAULT '0',

    `last_updated` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`inventory_id`),

    UNIQUE KEY `product_id` (`product_id`),

    CONSTRAINT `fk_inventory_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_inventory_quantity`
        CHECK (`quantity` >= 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 6. CART
-- ============================================================

CREATE TABLE `cart` (
    `cart_id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,

    `quantity` INT
        NOT NULL DEFAULT '1',

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`cart_id`),

    UNIQUE KEY `unique_customer_product`
        (`customer_id`, `product_id`),

    KEY `fk_cart_product`
        (`product_id`),

    KEY `idx_cart_customer`
        (`customer_id`),

    CONSTRAINT `fk_cart_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_cart_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_cart_quantity`
        CHECK (`quantity` > 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 7. WISHLIST
-- ============================================================

CREATE TABLE `wishlist` (
    `wishlist_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`wishlist_id`),

    UNIQUE KEY `unique_wishlist_product`
        (`user_id`, `product_id`),

    KEY `fk_wishlist_product`
        (`product_id`),

    KEY `idx_wishlist_user`
        (`user_id`),

    CONSTRAINT `fk_wishlist_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_wishlist_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 8. ORDERS
-- ============================================================

CREATE TABLE `orders` (
    `order_id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,

    `order_date` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `total_amount` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `delivery_method` ENUM(
        'Pickup',
        'Postage',
        'Vendor Delivery'
    ) COLLATE utf8mb4_unicode_ci
    DEFAULT NULL,

    `delivery_address` TEXT
        COLLATE utf8mb4_unicode_ci,

    `tracking_number` VARCHAR(100)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `order_status` ENUM(
        'Pending',
        'Processing',
        'Completed',
        'Cancelled'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Pending',

    `completed_date` DATETIME DEFAULT NULL,

    PRIMARY KEY (`order_id`),

    KEY `idx_orders_customer`
        (`customer_id`),

    KEY `idx_orders_status`
        (`order_status`),

    KEY `idx_orders_date`
        (`order_date`),

    CONSTRAINT `fk_orders_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `users` (`user_id`),

    CONSTRAINT `chk_order_total`
        CHECK (`total_amount` >= 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 9. ORDER DETAILS
-- ============================================================

CREATE TABLE `order_details` (
    `order_detail_id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,

    `unit_price` DECIMAL(10,2) NOT NULL,
    `subtotal` DECIMAL(10,2) NOT NULL,

    PRIMARY KEY (`order_detail_id`),

    KEY `idx_order_details_order`
        (`order_id`),

    KEY `idx_order_details_product`
        (`product_id`),

    CONSTRAINT `fk_order_details_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_order_details_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`),

    CONSTRAINT `chk_order_detail_price`
        CHECK (`unit_price` >= 0),

    CONSTRAINT `chk_order_detail_quantity`
        CHECK (`quantity` > 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 10. VENDOR ORDERS
-- ============================================================

CREATE TABLE `vendor_orders` (
    `vendor_order_id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,
    `vendor_id` INT NOT NULL,

    `subtotal` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `delivery_fee` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `vendor_status` ENUM(
        'Pending',
        'Processing',
        'Ready',
        'Shipped',
        'Completed',
        'Cancelled'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Pending',

    `tracking_number` VARCHAR(100)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `completed_at` DATETIME DEFAULT NULL,

    PRIMARY KEY (`vendor_order_id`),

    UNIQUE KEY `unique_order_vendor`
        (`order_id`, `vendor_id`),

    KEY `idx_vendor_orders_vendor`
        (`vendor_id`),

    KEY `idx_vendor_orders_status`
        (`vendor_status`),

    CONSTRAINT `fk_vendor_orders_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_vendor_orders_vendor`
        FOREIGN KEY (`vendor_id`)
        REFERENCES `vendors` (`vendor_id`),

    CONSTRAINT `chk_vendor_order_delivery_fee`
        CHECK (`delivery_fee` >= 0),

    CONSTRAINT `chk_vendor_order_subtotal`
        CHECK (`subtotal` >= 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 11. PAYMENTS
-- ============================================================

CREATE TABLE `payments` (
    `payment_id` INT NOT NULL AUTO_INCREMENT,
    `order_id` INT NOT NULL,

    `payment_method` ENUM(
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ) COLLATE utf8mb4_unicode_ci
    DEFAULT NULL,

    `payment_status` ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Pending',

    `payment_date` DATETIME DEFAULT NULL,

    `amount` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `transaction_reference` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `payment_gateway` VARCHAR(50)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `gateway_order_reference` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`payment_id`),

    KEY `idx_payments_order`
        (`order_id`),

    KEY `idx_payments_status`
        (`payment_status`),

    KEY `idx_payment_transaction`
        (`transaction_reference`),

    KEY `idx_payment_gateway_reference`
        (`gateway_order_reference`),

    CONSTRAINT `fk_payments_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_payment_amount`
        CHECK (`amount` >= 0)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 12. COMMISSION
-- ============================================================

CREATE TABLE `commission` (
    `commission_id` INT NOT NULL AUTO_INCREMENT,
    `vendor_id` INT NOT NULL,
    `order_id` INT NOT NULL,
    `vendor_order_id` INT DEFAULT NULL,

    `commission_rate` DECIMAL(5,2)
        NOT NULL DEFAULT '0.00',

    `commission_amount` DECIMAL(10,2)
        NOT NULL DEFAULT '0.00',

    `status` ENUM(
        'Pending',
        'Paid'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Pending',

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`commission_id`),

    KEY `fk_commission_vendor_order`
        (`vendor_order_id`),

    KEY `idx_commission_vendor`
        (`vendor_id`),

    KEY `idx_commission_order`
        (`order_id`),

    KEY `idx_commission_status`
        (`status`),

    CONSTRAINT `fk_commission_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_commission_vendor`
        FOREIGN KEY (`vendor_id`)
        REFERENCES `vendors` (`vendor_id`),

    CONSTRAINT `fk_commission_vendor_order`
        FOREIGN KEY (`vendor_order_id`)
        REFERENCES `vendor_orders` (`vendor_order_id`)
        ON DELETE SET NULL,

    CONSTRAINT `chk_commission_amount`
        CHECK (`commission_amount` >= 0),

    CONSTRAINT `chk_commission_rate`
        CHECK (
            `commission_rate` >= 0
            AND `commission_rate` <= 100
        )
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 13. REVIEWS
-- ============================================================

CREATE TABLE `reviews` (
    `review_id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,
    `order_detail_id` INT DEFAULT NULL,

    `rating` INT NOT NULL,

    `review_title` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `review` TEXT
        COLLATE utf8mb4_unicode_ci,

    `image` VARCHAR(255)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `helpful_count` INT
        NOT NULL DEFAULT '0',

    `status` ENUM(
        'Visible',
        'Hidden'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Visible',

    `review_date` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`review_id`),

    UNIQUE KEY `unique_review_order_detail`
        (`order_detail_id`),

    KEY `idx_reviews_product`
        (`product_id`),

    KEY `idx_reviews_customer`
        (`customer_id`),

    KEY `idx_reviews_order`
        (`order_id`),

    KEY `idx_reviews_order_detail`
        (`order_detail_id`),

    CONSTRAINT `fk_reviews_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_reviews_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_reviews_order_detail`
        FOREIGN KEY (`order_detail_id`)
        REFERENCES `order_details` (`order_detail_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_reviews_product`
        FOREIGN KEY (`product_id`)
        REFERENCES `products` (`product_id`)
        ON DELETE CASCADE,

    CONSTRAINT `chk_reviews_helpful_count`
        CHECK (`helpful_count` >= 0),

    CONSTRAINT `chk_reviews_rating`
        CHECK (`rating` BETWEEN 1 AND 5)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 14. CONVERSATIONS
-- ============================================================

CREATE TABLE `conversations` (
    `conversation_id` INT NOT NULL AUTO_INCREMENT,
    `customer_id` INT NOT NULL,
    `vendor_id` INT NOT NULL,
    `order_id` INT DEFAULT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `updated_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`conversation_id`),

    KEY `idx_conversations_customer`
        (`customer_id`),

    KEY `idx_conversations_vendor`
        (`vendor_id`),

    KEY `idx_conversations_order`
        (`order_id`),

    KEY `idx_conversations_updated`
        (`updated_at`),

    CONSTRAINT `fk_conversations_customer`
        FOREIGN KEY (`customer_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_conversations_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`order_id`)
        ON DELETE SET NULL,

    CONSTRAINT `fk_conversations_vendor`
        FOREIGN KEY (`vendor_id`)
        REFERENCES `vendors` (`vendor_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 15. MESSAGES
-- ============================================================

CREATE TABLE `messages` (
    `message_id` INT NOT NULL AUTO_INCREMENT,
    `conversation_id` INT NOT NULL,
    `sender_id` INT NOT NULL,

    `message` TEXT
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `is_read` TINYINT(1)
        NOT NULL DEFAULT '0',

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`message_id`),

    KEY `idx_messages_conversation`
        (`conversation_id`),

    KEY `idx_messages_sender`
        (`sender_id`),

    KEY `idx_messages_read`
        (`is_read`),

    KEY `idx_messages_created`
        (`created_at`),

    CONSTRAINT `fk_messages_conversation`
        FOREIGN KEY (`conversation_id`)
        REFERENCES `conversations` (`conversation_id`)
        ON DELETE CASCADE,

    CONSTRAINT `fk_messages_sender`
        FOREIGN KEY (`sender_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 16. CONTACT MESSAGES
-- ============================================================

CREATE TABLE `contact_messages` (
    `contact_message_id` INT NOT NULL AUTO_INCREMENT,

    `user_id` INT DEFAULT NULL,

    `name` VARCHAR(100)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `email` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `subject` VARCHAR(200)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `message` TEXT
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `status` ENUM(
        'New',
        'Read',
        'Replied'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'New',

    `admin_reply` TEXT
        COLLATE utf8mb4_unicode_ci,

    `replied_by` INT DEFAULT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `read_at` DATETIME DEFAULT NULL,
    `replied_at` DATETIME DEFAULT NULL,

    PRIMARY KEY (`contact_message_id`),

    KEY `idx_contact_messages_user`
        (`user_id`),

    KEY `idx_contact_messages_email`
        (`email`),

    KEY `idx_contact_messages_status`
        (`status`),

    KEY `idx_contact_messages_created`
        (`created_at`),

    KEY `idx_contact_messages_replied_by`
        (`replied_by`),

    CONSTRAINT `fk_contact_messages_admin`
        FOREIGN KEY (`replied_by`)
        REFERENCES `users` (`user_id`)
        ON DELETE SET NULL,

    CONSTRAINT `fk_contact_messages_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE SET NULL
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 17. VENDOR APPLICATIONS
-- ============================================================

CREATE TABLE `vendor_applications` (
    `application_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,

    `business_name` VARCHAR(150)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `reason` TEXT
        COLLATE utf8mb4_unicode_ci,

    `status` ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) COLLATE utf8mb4_unicode_ci
    NOT NULL DEFAULT 'Pending',

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `reviewed_at` DATETIME DEFAULT NULL,
    `reviewed_by` INT DEFAULT NULL,

    PRIMARY KEY (`application_id`),

    KEY `fk_vendor_applications_reviewer`
        (`reviewed_by`),

    KEY `idx_vendor_application_user`
        (`user_id`),

    KEY `idx_vendor_application_status`
        (`status`),

    CONSTRAINT `fk_vendor_applications_reviewer`
        FOREIGN KEY (`reviewed_by`)
        REFERENCES `users` (`user_id`)
        ON DELETE SET NULL,

    CONSTRAINT `fk_vendor_applications_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 18. MFA CODES
-- ============================================================

CREATE TABLE `mfa_codes` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,

    `code` VARCHAR(10)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,

    PRIMARY KEY (`id`),

    KEY `idx_mfa_user`
        (`user_id`),

    CONSTRAINT `fk_mfa_codes_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 19. PASSWORD RESETS
-- ============================================================

CREATE TABLE `password_resets` (
    `reset_id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,

    `reset_code` VARCHAR(10)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    `expires_at` DATETIME NOT NULL,
    `used_at` DATETIME DEFAULT NULL,

    PRIMARY KEY (`reset_id`),

    KEY `idx_password_reset_user`
        (`user_id`),

    CONSTRAINT `fk_password_resets_user`
        FOREIGN KEY (`user_id`)
        REFERENCES `users` (`user_id`)
        ON DELETE CASCADE
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 20. ADMIN LOGS
-- ============================================================

CREATE TABLE `admin_logs` (
    `log_id` INT NOT NULL AUTO_INCREMENT,
    `admin_id` INT NOT NULL,

    `action` VARCHAR(255)
        COLLATE utf8mb4_unicode_ci NOT NULL,

    `target_type` VARCHAR(50)
        COLLATE utf8mb4_unicode_ci DEFAULT NULL,

    `target_id` INT DEFAULT NULL,

    `created_at` DATETIME
        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`log_id`),

    KEY `idx_admin_logs_admin`
        (`admin_id`),

    KEY `idx_admin_logs_created`
        (`created_at`),

    CONSTRAINT `fk_admin_logs_admin`
        FOREIGN KEY (`admin_id`)
        REFERENCES `users` (`user_id`)
) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- CREATE DEFAULT ADMIN ACCOUNT
-- ============================================================
-- Email    : hochipohub941@gmail.com
-- Password : admin149@
-- Role     : admin
-- Status   : active
--
-- Password is stored as PHP password_hash(), NOT plain text.
-- ============================================================

INSERT INTO `users`
(
    `name`,
    `email`,
    `phone`,
    `password`,
    `profile_image`,
    `role`,
    `status`,
    `mfa_enabled`,
    `mfa_code`,
    `mfa_expiry`,
    `reset_code`,
    `reset_expiry`,
    `created_at`,
    `updated_at`
)
VALUES
(
    'HochipoHub Admin',
    'hochipohub941@gmail.com',
    NULL,
    '$2y$12$/zb5XdD5UMtR1WCfyc/W9exMcdos8dUe/o3DWD1aX3r9qV71us60.',
    NULL,
    'admin',
    'active',
    1,
    NULL,
    NULL,
    NULL,
    NULL,
    NOW(),
    NOW()
);


-- ============================================================
-- RESTORE FOREIGN KEY CHECKS
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;


-- ============================================================
-- VERIFY TABLES
-- ============================================================

SHOW TABLES;


-- ============================================================
-- VERIFY ADMIN
-- ============================================================

SELECT
    `user_id`,
    `name`,
    `email`,
    `phone`,
    `role`,
    `status`,
    `created_at`
FROM `users`
WHERE `email` = 'hochipohub941@gmail.com';hochipohub