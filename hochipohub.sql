CREATE DATABASE hochipohub;

USE hochipohub;


-- =====================================
-- 1. USERS
-- =====================================

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
        'pending'
    ) DEFAULT 'active',


    -- MFA

    mfa_enabled BOOLEAN DEFAULT TRUE,

    mfa_code VARCHAR(10),

    mfa_expiry DATETIME,


    -- Reset password

    reset_code VARCHAR(10),

    reset_expiry DATETIME,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP

);



-- =====================================
-- 2. VENDORS
-- =====================================

CREATE TABLE vendors (

    vendor_id INT AUTO_INCREMENT PRIMARY KEY,


    user_id INT NOT NULL,


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
        'Rejected'
    ) DEFAULT 'Pending',


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,


    FOREIGN KEY(user_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE

);



-- =====================================
-- 3. CATEGORIES
-- =====================================

CREATE TABLE categories (

    category_id INT AUTO_INCREMENT PRIMARY KEY,


    category_name VARCHAR(100) NOT NULL,


    category_image VARCHAR(255)

);



-- =====================================
-- 4. PRODUCTS
-- =====================================

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



    FOREIGN KEY(vendor_id)

    REFERENCES vendors(vendor_id)

    ON DELETE CASCADE,



    FOREIGN KEY(category_id)

    REFERENCES categories(category_id)

);



-- =====================================
-- 5. CART
-- =====================================

CREATE TABLE cart (

    cart_id INT AUTO_INCREMENT PRIMARY KEY,


    customer_id INT NOT NULL,


    product_id INT NOT NULL,


    quantity INT DEFAULT 1,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,



    FOREIGN KEY(customer_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE,



    FOREIGN KEY(product_id)

    REFERENCES products(product_id)

    ON DELETE CASCADE

);



-- =====================================
-- 6. ORDERS
-- =====================================

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



    FOREIGN KEY(customer_id)

    REFERENCES users(user_id)

);



-- =====================================
-- 7. ORDER DETAILS
-- =====================================

CREATE TABLE order_details (

    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,


    order_id INT NOT NULL,


    product_id INT NOT NULL,


    quantity INT NOT NULL,


    unit_price DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,



    FOREIGN KEY(order_id)

    REFERENCES orders(order_id)

    ON DELETE CASCADE,



    FOREIGN KEY(product_id)

    REFERENCES products(product_id)

);



-- =====================================
-- 8. PAYMENT
-- =====================================

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
        'Failed'
    ) DEFAULT 'Pending',


    payment_date DATETIME,


    amount DECIMAL(10,2) NOT NULL,



    FOREIGN KEY(order_id)

    REFERENCES orders(order_id)

);



-- =====================================
-- 9. REVIEWS
-- =====================================

CREATE TABLE reviews (

    review_id INT AUTO_INCREMENT PRIMARY KEY,


    customer_id INT NOT NULL,


    product_id INT NOT NULL,


    rating INT CHECK(
        rating BETWEEN 1 AND 5
    ),


    review TEXT,


    image VARCHAR(255),


    status ENUM(
        'Visible',
        'Hidden'
    ) DEFAULT 'Visible',


    review_date DATETIME DEFAULT CURRENT_TIMESTAMP,



    FOREIGN KEY(customer_id)

    REFERENCES users(user_id),



    FOREIGN KEY(product_id)

    REFERENCES products(product_id)

);



-- =====================================
-- 10. INVENTORY
-- =====================================

CREATE TABLE inventory (

    inventory_id INT AUTO_INCREMENT PRIMARY KEY,


    product_id INT NOT NULL,


    quantity INT NOT NULL,


    last_updated DATETIME DEFAULT CURRENT_TIMESTAMP,



    FOREIGN KEY(product_id)

    REFERENCES products(product_id)

    ON DELETE CASCADE

);



-- =====================================
-- 11. COMMISSION
-- =====================================

CREATE TABLE commission (

    commission_id INT AUTO_INCREMENT PRIMARY KEY,


    vendor_id INT NOT NULL,


    order_id INT NOT NULL,


    commission_rate DECIMAL(5,2),


    commission_amount DECIMAL(10,2),


    status ENUM(
        'Pending',
        'Paid'
    ) DEFAULT 'Pending',



    FOREIGN KEY(vendor_id)

    REFERENCES vendors(vendor_id),



    FOREIGN KEY(order_id)

    REFERENCES orders(order_id)

);



-- =====================================
-- 12. MFA CODE HISTORY
-- =====================================

CREATE TABLE mfa_codes (

    id INT AUTO_INCREMENT PRIMARY KEY,


    user_id INT NOT NULL,


    code VARCHAR(10) NOT NULL,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,


    expires_at DATETIME,



    FOREIGN KEY(user_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE

);



-- =====================================
-- 13. PASSWORD RESET HISTORY
-- =====================================

CREATE TABLE password_resets (

    reset_id INT AUTO_INCREMENT PRIMARY KEY,


    user_id INT NOT NULL,


    reset_code VARCHAR(10),


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,


    expires_at DATETIME,



    FOREIGN KEY(user_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE

);



-- =====================================
-- 14. WISHLIST
-- =====================================

CREATE TABLE wishlist (

    wishlist_id INT AUTO_INCREMENT PRIMARY KEY,


    user_id INT NOT NULL,


    product_id INT NOT NULL,


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,



    FOREIGN KEY(user_id)

    REFERENCES users(user_id)

    ON DELETE CASCADE,



    FOREIGN KEY(product_id)

    REFERENCES products(product_id)

    ON DELETE CASCADE

);



-- =====================================
-- 15. ADMIN LOG
-- =====================================

CREATE TABLE admin_logs (

    log_id INT AUTO_INCREMENT PRIMARY KEY,


    admin_id INT NOT NULL,


    action VARCHAR(255),


    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,



    FOREIGN KEY(admin_id)

    REFERENCES users(user_id)

);



-- =====================================
-- 16. VENDOR APPLICATION
-- =====================================

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

hochipohubadmin_logshochipohub

    FOREIGN KEY(user_id)

    REFERENCES users(user_id)

);hochipohubwishlisthochipohub