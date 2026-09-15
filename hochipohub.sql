-- =========================================================
-- HOCHIPOHUB DATABASE
-- FULL FINAL UPDATED DATABASE STRUCTURE
-- VERIFIED PURCHASE REVIEW SYSTEM INCLUDED
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

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


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


    -- =====================================================
    -- BASIC DELIVERY
    -- =====================================================

    delivery_method ENUM(
        'Pickup',
        'Postage',
        'Both'
    ) NOT NULL DEFAULT 'Both',


    -- =====================================================
    -- POSTAGE FEE
    -- =====================================================

    postage_fee DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,


    -- =====================================================
    -- VENDOR DELIVERY
    -- =====================================================

    allow_vendor_delivery TINYINT(1)
        NOT NULL
        DEFAULT 0,


    -- =====================================================
    -- COD
    -- =====================================================

    cod_enabled TINYINT(1)
        NOT NULL
        DEFAULT 0,


    -- =====================================================
    -- VENDOR DELIVERY FEE
    -- =====================================================

    vendor_delivery_fee DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,


    -- =====================================================
    -- COMMISSION
    -- =====================================================

    commission_rate DECIMAL(5,2)
        NOT NULL
        DEFAULT 5.00,


    -- =====================================================
    -- APPROVAL
    -- =====================================================

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
        ON DELETE CASCADE,


    CONSTRAINT chk_vendor_postage_fee
        CHECK (
            postage_fee >= 0
        ),


    CONSTRAINT chk_vendor_delivery_fee
        CHECK (
            vendor_delivery_fee >= 0
        ),


    CONSTRAINT chk_vendor_commission_rate
        CHECK (
            commission_rate >= 0
            AND commission_rate <= 100
        )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 3. CATEGORIES
-- =========================================================

CREATE TABLE IF NOT EXISTS categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100)
        NOT NULL
        UNIQUE,

    category_image VARCHAR(255) NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 4. PRODUCTS
-- =========================================================

CREATE TABLE IF NOT EXISTS products (

    product_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    category_id INT NOT NULL,

    product_name VARCHAR(150)
        NOT NULL,

    description TEXT NULL,

    price DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,

    stock_quantity INT
        NOT NULL
        DEFAULT 0,

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
        REFERENCES categories(category_id),


    CONSTRAINT chk_product_price
        CHECK (
            price >= 0
        ),


    CONSTRAINT chk_product_stock
        CHECK (
            stock_quantity >= 0
        ),


    INDEX idx_products_vendor (
        vendor_id
    ),

    INDEX idx_products_category (
        category_id
    ),

    INDEX idx_products_status (
        status
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 5. CART
-- =========================================================

CREATE TABLE IF NOT EXISTS cart (

    cart_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT
        NOT NULL
        DEFAULT 1,

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


    CONSTRAINT chk_cart_quantity
        CHECK (
            quantity > 0
        ),


    UNIQUE KEY unique_customer_product (
        customer_id,
        product_id
    ),


    INDEX idx_cart_customer (
        customer_id
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 6. ORDERS
-- =========================================================

CREATE TABLE IF NOT EXISTS orders (

    order_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    order_date DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    total_amount DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,


    delivery_method ENUM(
        'Pickup',
        'Postage',
        'Vendor Delivery'
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
        REFERENCES users(user_id),


    CONSTRAINT chk_order_total
        CHECK (
            total_amount >= 0
        ),


    INDEX idx_orders_customer (
        customer_id
    ),

    INDEX idx_orders_status (
        order_status
    ),

    INDEX idx_orders_date (
        order_date
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 7. ORDER DETAILS
-- =========================================================

CREATE TABLE IF NOT EXISTS order_details (

    order_detail_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

    order_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL,

    unit_price DECIMAL(10,2)
        NOT NULL,

    subtotal DECIMAL(10,2)
        NOT NULL,


    CONSTRAINT fk_order_details_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,


    CONSTRAINT fk_order_details_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id),


    CONSTRAINT chk_order_detail_quantity
        CHECK (
            quantity > 0
        ),


    CONSTRAINT chk_order_detail_price
        CHECK (
            unit_price >= 0
        ),


    INDEX idx_order_details_order (
        order_id
    ),

    INDEX idx_order_details_product (
        product_id
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 8. VENDOR ORDERS
-- =========================================================

CREATE TABLE IF NOT EXISTS vendor_orders (

    vendor_order_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

    order_id INT NOT NULL,

    vendor_id INT NOT NULL,

    subtotal DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,

    delivery_fee DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,


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


    CONSTRAINT chk_vendor_order_subtotal
        CHECK (
            subtotal >= 0
        ),


    CONSTRAINT chk_vendor_order_delivery_fee
        CHECK (
            delivery_fee >= 0
        ),


    UNIQUE KEY unique_order_vendor (
        order_id,
        vendor_id
    ),


    INDEX idx_vendor_orders_vendor (
        vendor_id
    ),

    INDEX idx_vendor_orders_status (
        vendor_status
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 9. PAYMENTS
-- =========================================================

CREATE TABLE IF NOT EXISTS payments (

    payment_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

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

    amount DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,


    transaction_reference VARCHAR(150) NULL,

    payment_gateway VARCHAR(50) NULL,

    gateway_order_reference VARCHAR(150) NULL,


    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,


    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,


    CONSTRAINT chk_payment_amount
        CHECK (
            amount >= 0
        ),


    INDEX idx_payments_order (
        order_id
    ),

    INDEX idx_payments_status (
        payment_status
    ),

    INDEX idx_payment_transaction (
        transaction_reference
    ),

    INDEX idx_payment_gateway_reference (
        gateway_order_reference
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 10. REVIEWS
-- VERIFIED PURCHASE REVIEW SYSTEM
-- =========================================================

CREATE TABLE IF NOT EXISTS reviews (

    review_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    -- Order yang customer buat
    order_id INT NULL,

    -- Item spesifik dalam order tersebut
    order_detail_id INT NULL,

    rating INT NOT NULL,

    -- Tajuk review
    review_title VARCHAR(150) NULL,

    review TEXT NULL,

    -- Gambar review customer
    image VARCHAR(255) NULL,

    -- Bilangan orang tekan helpful
    helpful_count INT
        NOT NULL
        DEFAULT 0,


    status ENUM(
        'Visible',
        'Hidden'
    ) NOT NULL DEFAULT 'Visible',


    review_date DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,


    -- Customer yang menulis review
    CONSTRAINT fk_reviews_customer
        FOREIGN KEY (customer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,


    -- Product yang direview
    CONSTRAINT fk_reviews_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,


    -- Order asal pembelian
    CONSTRAINT fk_reviews_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,


    -- Item spesifik dalam order
    CONSTRAINT fk_reviews_order_detail
        FOREIGN KEY (order_detail_id)
        REFERENCES order_details(order_detail_id)
        ON DELETE CASCADE,


    CONSTRAINT chk_reviews_rating
        CHECK (
            rating BETWEEN 1 AND 5
        ),


    CONSTRAINT chk_reviews_helpful_count
        CHECK (
            helpful_count >= 0
        ),


    -- Satu purchased item hanya boleh direview sekali
    UNIQUE KEY unique_review_order_detail (
        order_detail_id
    ),


    INDEX idx_reviews_product (
        product_id
    ),

    INDEX idx_reviews_customer (
        customer_id
    ),

    INDEX idx_reviews_order (
        order_id
    ),

    INDEX idx_reviews_order_detail (
        order_detail_id
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 11. INVENTORY
-- =========================================================

CREATE TABLE IF NOT EXISTS inventory (

    inventory_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

    product_id INT
        NOT NULL
        UNIQUE,

    quantity INT
        NOT NULL
        DEFAULT 0,

    last_updated DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,


    CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,


    CONSTRAINT chk_inventory_quantity
        CHECK (
            quantity >= 0
        )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 12. COMMISSION
-- =========================================================

CREATE TABLE IF NOT EXISTS commission (

    commission_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

    vendor_id INT NOT NULL,

    order_id INT NOT NULL,

    vendor_order_id INT NULL,

    commission_rate DECIMAL(5,2)
        NOT NULL
        DEFAULT 0.00,

    commission_amount DECIMAL(10,2)
        NOT NULL
        DEFAULT 0.00,


    status ENUM(
        'Pending',
        'Paid'
    ) NOT NULL DEFAULT 'Pending',


    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,


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
        ON DELETE SET NULL,


    CONSTRAINT chk_commission_rate
        CHECK (
            commission_rate >= 0
            AND commission_rate <= 100
        ),


    CONSTRAINT chk_commission_amount
        CHECK (
            commission_amount >= 0
        ),


    INDEX idx_commission_vendor (
        vendor_id
    ),

    INDEX idx_commission_order (
        order_id
    ),

    INDEX idx_commission_status (
        status
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


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
        ON DELETE CASCADE,


    INDEX idx_mfa_user (
        user_id
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


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
        ON DELETE CASCADE,


    INDEX idx_password_reset_user (
        user_id
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 15. WISHLIST
-- =========================================================

CREATE TABLE IF NOT EXISTS wishlist (

    wishlist_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

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
    ),


    INDEX idx_wishlist_user (
        user_id
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


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
        REFERENCES users(user_id),


    INDEX idx_admin_logs_admin (
        admin_id
    ),

    INDEX idx_admin_logs_created (
        created_at
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 17. VENDOR APPLICATIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS vendor_applications (

    application_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

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
        ON DELETE SET NULL,


    INDEX idx_vendor_application_user (
        user_id
    ),

    INDEX idx_vendor_application_status (
        status
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 18. CONVERSATIONS
-- =========================================================

CREATE TABLE IF NOT EXISTS conversations (

    conversation_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

    customer_id INT NOT NULL,

    vendor_id INT NOT NULL,

    order_id INT NULL,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,


    CONSTRAINT fk_conversations_customer
        FOREIGN KEY (customer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,


    CONSTRAINT fk_conversations_vendor
        FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id)
        ON DELETE CASCADE,


    CONSTRAINT fk_conversations_order
        FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE SET NULL,


    INDEX idx_conversations_customer (
        customer_id
    ),

    INDEX idx_conversations_vendor (
        vendor_id
    ),

    INDEX idx_conversations_order (
        order_id
    ),

    INDEX idx_conversations_updated (
        updated_at
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- 19. MESSAGES
-- =========================================================

CREATE TABLE IF NOT EXISTS messages (

    message_id INT
        AUTO_INCREMENT
        PRIMARY KEY,

    conversation_id INT NOT NULL,

    sender_id INT NOT NULL,

    message TEXT NOT NULL,

    is_read TINYINT(1)
        NOT NULL
        DEFAULT 0,

    created_at DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,


    CONSTRAINT fk_messages_conversation
        FOREIGN KEY (conversation_id)
        REFERENCES conversations(conversation_id)
        ON DELETE CASCADE,


    CONSTRAINT fk_messages_sender
        FOREIGN KEY (sender_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,


    INDEX idx_messages_conversation (
        conversation_id
    ),

    INDEX idx_messages_sender (
        sender_id
    ),

    INDEX idx_messages_read (
        is_read
    ),

    INDEX idx_messages_created (
        created_at
    )

) ENGINE=InnoDB
DEFAULT CHARSET=utf8mb4
COLLATE=utf8mb4_unicode_ci;


-- =========================================================
-- DEFAULT ADMIN ACCOUNT
-- =========================================================

INSERT INTO users
(
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

WHERE NOT EXISTS
(
    SELECT 1
    FROM users
    WHERE email = 'admin@hochipohub.com'
);


-- =========================================================
-- FORCE ADMIN ROLE / STATUS
-- =========================================================

UPDATE users
SET
    role = 'admin',
    status = 'active',
    mfa_enabled = FALSE
WHERE email = 'admin@hochipohub.com';


-- =========================================================
-- DEFAULT CATEGORY - FOOD
-- =========================================================

INSERT INTO categories
(
    category_name
)

SELECT
    'Food'

WHERE NOT EXISTS
(
    SELECT 1
    FROM categories
    WHERE category_name = 'Food'
);


-- =========================================================
-- DEFAULT CATEGORY - BEVERAGES
-- =========================================================

INSERT INTO categories
(
    category_name
)

SELECT
    'Beverages'

WHERE NOT EXISTS
(
    SELECT 1
    FROM categories
    WHERE category_name = 'Beverages'
);


-- =========================================================
-- DEFAULT CATEGORY - DESSERTS
-- =========================================================

INSERT INTO categories
(
    category_name
)

SELECT
    'Desserts'

WHERE NOT EXISTS
(
    SELECT 1
    FROM categories
    WHERE category_name = 'Desserts'
);


-- =========================================================
-- DEFAULT CATEGORY - SNACKS
-- =========================================================

INSERT INTO categories
(
    category_name
)

SELECT
    'Snacks'

WHERE NOT EXISTS
(
    SELECT 1
    FROM categories
    WHERE category_name = 'Snacks'
);


-- =========================================================
-- VERIFY TABLES
-- =========================================================

SHOW TABLES;


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
WHERE email = 'admin@hochipohub.com';


-- =========================================================
-- VERIFY VENDOR SETTINGS
-- =========================================================

DESCRIBE vendors;


-- =========================================================
-- VERIFY ORDERS
-- =========================================================

DESCRIBE orders;


-- =========================================================
-- VERIFY VENDOR ORDERS
-- =========================================================

DESCRIBE vendor_orders;


-- =========================================================
-- VERIFY PAYMENT
-- =========================================================

DESCRIBE payments;


-- =========================================================
-- VERIFY REVIEW SYSTEM
-- =========================================================

DESCRIBE reviews;


-- =========================================================
-- DONE
-- =========================================================hochipohubreviews