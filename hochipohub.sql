CREATE DATABASE hochipohub;

USE hochipohub;


-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE users (

    user_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    phone VARCHAR(20) UNIQUE,

    password VARCHAR(255) NOT NULL,

    profile_image VARCHAR(255),

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
    ) DEFAULT 'active',

    -- MFA
    mfa_enabled BOOLEAN DEFAULT TRUE,

    mfa_code VARCHAR(10),

    mfa_expiry DATETIME,

    -- Password reset
    reset_code VARCHAR(10),

    reset_expiry DATETIME,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

);


-- =========================================================
-- 2. VENDORS
-- =========================================================

CREATE TABLE vendors (

    vendor_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    business_name VARCHAR(150) NOT NULL,

    business_logo VARCHAR(255),

    business_description TEXT,

    business_address TEXT,

    category VARCHAR(100),

    delivery_method ENUM(
        'Pickup',
        'Postage',
        'Both'
    ) DEFAULT 'Both',

    approval_status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Suspended'
    ) DEFAULT 'Pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 3. CATEGORIES
-- =========================================================

CREATE TABLE categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    category_image VARCHAR(255),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP

);


-- =========================================================
-- 4. PRODUCTS
-- =========================================================

CREATE TABLE products (

    product_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    category_id INT NOT NULL,

    product_name VARCHAR(150) NOT NULL,

    description TEXT,

    price DECIMAL(10,2) NOT NULL,

    stock_quantity INT DEFAULT 0,

    image VARCHAR(255),

    status ENUM(
        'Available',
        'Out of Stock',
        'Hidden'
    ) DEFAULT 'Available',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id)
        ON DELETE CASCADE,

    FOREIGN KEY (category_id)
        REFERENCES categories(category_id)

);


-- =========================================================
-- 5. CART
-- =========================================================
-- One customer can add products from MANY vendors.
-- Example:
-- Vendor A product + Vendor B product
-- can exist in the same cart.

CREATE TABLE cart (

    cart_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_customer_product (
        customer_id,
        product_id
    )

);


-- =========================================================
-- 6. ORDERS
-- =========================================================
-- This is the MAIN order created during checkout.
--
-- Example:
-- Customer buys from Vendor A + Vendor B.
--
-- One main order is created here.
-- Then vendor_orders will split it by vendor.

CREATE TABLE orders (

    order_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    total_amount DECIMAL(10,2) NOT NULL,

    delivery_method ENUM(
        'Pickup',
        'Postage'
    ),

    delivery_address TEXT,

    tracking_number VARCHAR(100),

    order_status ENUM(
        'Pending',
        'Processing',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    completed_date DATETIME,

    FOREIGN KEY (customer_id)
        REFERENCES users(user_id)

);


-- =========================================================
-- 7. ORDER DETAILS
-- =========================================================
-- Stores every product inside the main order.

CREATE TABLE order_details (

    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL,

    unit_price DECIMAL(10,2) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)

);


-- =========================================================
-- 8. VENDOR ORDERS
-- =========================================================
-- IMPORTANT FOR MULTI-VENDOR CART
--
-- One customer checkout:
--
-- Order #1001
--      |
--      +-- Vendor A Sub-order
--      |
--      +-- Vendor B Sub-order
--      |
--      +-- Vendor C Sub-order
--
-- Each vendor ONLY sees their own sub-order.

CREATE TABLE vendor_orders (

    vendor_order_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    vendor_id INT NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    delivery_fee DECIMAL(10,2) DEFAULT 0.00,

    vendor_status ENUM(
        'Pending',
        'Processing',
        'Ready',
        'Shipped',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    tracking_number VARCHAR(100),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    completed_at DATETIME,

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id),

    UNIQUE KEY unique_order_vendor (
        order_id,
        vendor_id
    )

);


-- =========================================================
-- 9. PAYMENTS
-- =========================================================

CREATE TABLE payments (

    payment_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    payment_method ENUM(
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ),

    payment_status ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) DEFAULT 'Pending',

    payment_date DATETIME,

    amount DECIMAL(10,2) NOT NULL,

    transaction_reference VARCHAR(100),

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)

);


-- =========================================================
-- 10. REVIEWS
-- =========================================================

CREATE TABLE reviews (

    review_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    rating INT NOT NULL,

    review TEXT,

    image VARCHAR(255),

    status ENUM(
        'Visible',
        'Hidden'
    ) DEFAULT 'Visible',

    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES users(user_id),

    FOREIGN KEY (product_id)
        REFERENCES products(product_id),

    CHECK (rating BETWEEN 1 AND 5)

);


-- =========================================================
-- 11. INVENTORY
-- =========================================================

CREATE TABLE inventory (

    inventory_id INT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL UNIQUE,

    quantity INT NOT NULL DEFAULT 0,

    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 12. COMMISSION
-- =========================================================
-- HochipoHub's commission from each vendor order.

CREATE TABLE commission (

    commission_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    order_id INT NOT NULL,

    vendor_order_id INT,

    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'Pending',
        'Paid'
    ) DEFAULT 'Pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id),

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    FOREIGN KEY (vendor_order_id)
        REFERENCES vendor_orders(vendor_order_id)
        ON DELETE SET NULL

);


-- =========================================================
-- 13. MFA CODE HISTORY
-- =========================================================

CREATE TABLE mfa_codes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    code VARCHAR(10) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 14. PASSWORD RESET HISTORY
-- =========================================================

CREATE TABLE password_resets (

    reset_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    reset_code VARCHAR(10) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 15. WISHLIST
-- =========================================================

CREATE TABLE wishlist (

    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    product_id INT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_wishlist_product (
        user_id,
        product_id
    )

);


-- =========================================================
-- 16. ADMIN LOGS
-- =========================================================

CREATE TABLE admin_logs (

    log_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    action VARCHAR(255) NOT NULL,

    target_type VARCHAR(50),

    target_id INT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_id)
        REFERENCES users(user_id)

);


-- =========================================================
-- 17. VENDOR APPLICATIONS
-- =========================================================

CREATE TABLE vendor_applications (

    application_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    business_name VARCHAR(150),

    reason TEXT,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) DEFAULT 'Pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    reviewed_at DATETIME,
CREATE DATABASE hochipohub;

USE hochipohub;


-- =========================================================
-- 1. USERS
-- =========================================================

CREATE TABLE users (

    user_id INT AUTO_INCREMENT PRIMARY KEY,

    name VARCHAR(100) NOT NULL,

    email VARCHAR(100) NOT NULL UNIQUE,

    phone VARCHAR(20) UNIQUE,

    password VARCHAR(255) NOT NULL,

    profile_image VARCHAR(255),

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
    ) DEFAULT 'active',

    -- MFA
    mfa_enabled BOOLEAN DEFAULT TRUE,

    mfa_code VARCHAR(10),

    mfa_expiry DATETIME,

    -- Password reset
    reset_code VARCHAR(10),

    reset_expiry DATETIME,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP

);


-- =========================================================
-- 2. VENDORS
-- =========================================================

CREATE TABLE vendors (

    vendor_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL UNIQUE,

    business_name VARCHAR(150) NOT NULL,

    business_logo VARCHAR(255),

    business_description TEXT,

    business_address TEXT,

    category VARCHAR(100),

    delivery_method ENUM(
        'Pickup',
        'Postage',
        'Both'
    ) DEFAULT 'Both',

    approval_status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Suspended'
    ) DEFAULT 'Pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 3. CATEGORIES
-- =========================================================

CREATE TABLE categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,

    category_name VARCHAR(100) NOT NULL UNIQUE,

    category_image VARCHAR(255),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP

);


-- =========================================================
-- 4. PRODUCTS
-- =========================================================

CREATE TABLE products (

    product_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    category_id INT NOT NULL,

    product_name VARCHAR(150) NOT NULL,

    description TEXT,

    price DECIMAL(10,2) NOT NULL,

    stock_quantity INT DEFAULT 0,

    image VARCHAR(255),

    status ENUM(
        'Available',
        'Out of Stock',
        'Hidden'
    ) DEFAULT 'Available',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id)
        ON DELETE CASCADE,

    FOREIGN KEY (category_id)
        REFERENCES categories(category_id)

);


-- =========================================================
-- 5. CART
-- =========================================================
-- One customer can add products from MANY vendors.
-- Example:
-- Vendor A product + Vendor B product
-- can exist in the same cart.

CREATE TABLE cart (

    cart_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL DEFAULT 1,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_customer_product (
        customer_id,
        product_id
    )

);


-- =========================================================
-- 6. ORDERS
-- =========================================================
-- This is the MAIN order created during checkout.
--
-- Example:
-- Customer buys from Vendor A + Vendor B.
--
-- One main order is created here.
-- Then vendor_orders will split it by vendor.

CREATE TABLE orders (

    order_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    order_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    total_amount DECIMAL(10,2) NOT NULL,

    delivery_method ENUM(
        'Pickup',
        'Postage'
    ),

    delivery_address TEXT,

    tracking_number VARCHAR(100),

    order_status ENUM(
        'Pending',
        'Processing',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    completed_date DATETIME,

    FOREIGN KEY (customer_id)
        REFERENCES users(user_id)

);


-- =========================================================
-- 7. ORDER DETAILS
-- =========================================================
-- Stores every product inside the main order.

CREATE TABLE order_details (

    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    product_id INT NOT NULL,

    quantity INT NOT NULL,

    unit_price DECIMAL(10,2) NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL,

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)

);


-- =========================================================
-- 8. VENDOR ORDERS
-- =========================================================
-- IMPORTANT FOR MULTI-VENDOR CART
--
-- One customer checkout:
--
-- Order #1001
--      |
--      +-- Vendor A Sub-order
--      |
--      +-- Vendor B Sub-order
--      |
--      +-- Vendor C Sub-order
--
-- Each vendor ONLY sees their own sub-order.

CREATE TABLE vendor_orders (

    vendor_order_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    vendor_id INT NOT NULL,

    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    delivery_fee DECIMAL(10,2) DEFAULT 0.00,

    vendor_status ENUM(
        'Pending',
        'Processing',
        'Ready',
        'Shipped',
        'Completed',
        'Cancelled'
    ) DEFAULT 'Pending',

    tracking_number VARCHAR(100),

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    completed_at DATETIME,

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id),

    UNIQUE KEY unique_order_vendor (
        order_id,
        vendor_id
    )

);


-- =========================================================
-- 9. PAYMENTS
-- =========================================================

CREATE TABLE payments (

    payment_id INT AUTO_INCREMENT PRIMARY KEY,

    order_id INT NOT NULL,

    payment_method ENUM(
        'FPX',
        'Credit Card',
        'Debit Card',
        'Cash'
    ),

    payment_status ENUM(
        'Pending',
        'Paid',
        'Failed',
        'Refunded'
    ) DEFAULT 'Pending',

    payment_date DATETIME,

    amount DECIMAL(10,2) NOT NULL,

    transaction_reference VARCHAR(100),

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)

);


-- =========================================================
-- 10. REVIEWS
-- =========================================================

CREATE TABLE reviews (

    review_id INT AUTO_INCREMENT PRIMARY KEY,

    customer_id INT NOT NULL,

    product_id INT NOT NULL,

    rating INT NOT NULL,

    review TEXT,

    image VARCHAR(255),

    status ENUM(
        'Visible',
        'Hidden'
    ) DEFAULT 'Visible',

    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (customer_id)
        REFERENCES users(user_id),

    FOREIGN KEY (product_id)
        REFERENCES products(product_id),

    CHECK (rating BETWEEN 1 AND 5)

);


-- =========================================================
-- 11. INVENTORY
-- =========================================================

CREATE TABLE inventory (

    inventory_id INT AUTO_INCREMENT PRIMARY KEY,

    product_id INT NOT NULL UNIQUE,

    quantity INT NOT NULL DEFAULT 0,

    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 12. COMMISSION
-- =========================================================
-- HochipoHub's commission from each vendor order.

CREATE TABLE commission (

    commission_id INT AUTO_INCREMENT PRIMARY KEY,

    vendor_id INT NOT NULL,

    order_id INT NOT NULL,

    vendor_order_id INT,

    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 0.00,

    commission_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,

    status ENUM(
        'Pending',
        'Paid'
    ) DEFAULT 'Pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (vendor_id)
        REFERENCES vendors(vendor_id),

    FOREIGN KEY (order_id)
        REFERENCES orders(order_id)
        ON DELETE CASCADE,

    FOREIGN KEY (vendor_order_id)
        REFERENCES vendor_orders(vendor_order_id)
        ON DELETE SET NULL

);


-- =========================================================
-- 13. MFA CODE HISTORY
-- =========================================================

CREATE TABLE mfa_codes (

    id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    code VARCHAR(10) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 14. PASSWORD RESET HISTORY
-- =========================================================

CREATE TABLE password_resets (

    reset_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    reset_code VARCHAR(10) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    expires_at DATETIME NOT NULL,

    used_at DATETIME NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE

);


-- =========================================================
-- 15. WISHLIST
-- =========================================================

CREATE TABLE wishlist (

    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    product_id INT NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (product_id)
        REFERENCES products(product_id)
        ON DELETE CASCADE,

    UNIQUE KEY unique_wishlist_product (
        user_id,
        product_id
    )

);


-- =========================================================
-- 16. ADMIN LOGS
-- =========================================================

CREATE TABLE admin_logs (

    log_id INT AUTO_INCREMENT PRIMARY KEY,

    admin_id INT NOT NULL,

    action VARCHAR(255) NOT NULL,

    target_type VARCHAR(50),

    target_id INT,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_id)
        REFERENCES users(user_id)

);


-- =========================================================
-- 17. VENDOR APPLICATIONS
-- =========================================================

CREATE TABLE vendor_applications (

    application_id INT AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,

    business_name VARCHAR(150),

    reason TEXT,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) DEFAULT 'Pending',

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    reviewed_at DATETIME,

    reviewed_by INT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL

);
    reviewed_by INT NULL,

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE,

    FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL

);