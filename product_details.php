<?php

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('pdE')) {
    function pdE($value)
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}

if (!function_exists('pdProductImage')) {
    function pdProductImage($image)
    {
        $image = trim((string) $image);

        if ($image === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $image)) {
            return $image;
        }

        $image = ltrim(
            str_replace('\\', '/', $image),
            '/'
        );

        if (strpos($image, 'uploads/products/') === 0) {
            return BASE_URL . $image;
        }

        return BASE_URL .
            'uploads/products/' .
            basename($image);
    }
}


/*
|--------------------------------------------------------------------------
| PRODUCT ID
|--------------------------------------------------------------------------
*/

$productId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$productId || $productId < 1) {
    header(
        'Location: ' .
        BASE_URL .
        'catalog.php'
    );
    exit;
}


/*
|--------------------------------------------------------------------------
| CURRENT USER
|--------------------------------------------------------------------------
*/

$userId = 0;

if (
    isset($_SESSION['user_id']) &&
    is_numeric($_SESSION['user_id'])
) {
    $userId = (int) $_SESSION['user_id'];
}

$currentRole = strtolower(
    trim(
        (string) (
            $_SESSION['role']
            ?? $_SESSION['user_role']
            ?? ''
        )
    )
);

$isCustomer =
    $userId > 0 &&
    $currentRole === 'customer';


/*
|--------------------------------------------------------------------------
| PRODUCT
|--------------------------------------------------------------------------
|
| IMPORTANT:
| vendors table does NOT use v.logo here.
|--------------------------------------------------------------------------
*/

$stmt = $db->prepare("
    SELECT
        p.product_id,
        p.vendor_id,
        p.category_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        p.created_at,

        c.category_name,

        v.business_name,
        v.approval_status,

        u.name AS vendor_owner_name

    FROM products p

    LEFT JOIN categories c
        ON c.category_id = p.category_id

    LEFT JOIN vendors v
        ON v.vendor_id = p.vendor_id

    LEFT JOIN users u
        ON u.user_id = v.user_id

    WHERE p.product_id = ?

    LIMIT 1
");

$stmt->execute([
    $productId
]);

$product = $stmt->fetch(
    PDO::FETCH_ASSOC
);

if (!$product) {
    http_response_code(404);
    die('Product not found.');
}


/*
|--------------------------------------------------------------------------
| PRODUCT VALUES
|--------------------------------------------------------------------------
*/

$productName = trim(
    (string) (
        $product['product_name']
        ?? 'Product'
    )
);

$categoryName = trim(
    (string) (
        $product['category_name']
        ?? 'Uncategorized'
    )
);

$description = trim(
    (string) (
        $product['description']
        ?? ''
    )
);

$price = (float) (
    $product['price']
    ?? 0
);

$stock = max(
    0,
    (int) (
        $product['stock_quantity']
        ?? 0
    )
);

$vendorId = (int) (
    $product['vendor_id']
    ?? 0
);

$businessName = trim(
    (string) (
        $product['business_name']
        ?? 'Seller'
    )
);

$vendorOwnerName = trim(
    (string) (
        $product['vendor_owner_name']
        ?? ''
    )
);

$productImage = pdProductImage(
    $product['image']
    ?? ''
);


/*
|--------------------------------------------------------------------------
| CART COUNT
|--------------------------------------------------------------------------
*/

$cartCount = 0;

if ($isCustomer) {
    try {
        $stmt = $db->prepare("
            SELECT
                COALESCE(SUM(quantity), 0)
            FROM cart
            WHERE customer_id = ?
        ");

        $stmt->execute([
            $userId
        ]);

        $cartCount = (int) $stmt->fetchColumn();

    } catch (Throwable $e) {
        $cartCount = 0;
    }
}


/*
|--------------------------------------------------------------------------
| WISHLIST
|--------------------------------------------------------------------------
*/

$wishlistCount = 0;
$isWishlisted = false;

if ($isCustomer) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM wishlist
            WHERE user_id = ?
        ");

        $stmt->execute([
            $userId
        ]);

        $wishlistCount =
            (int) $stmt->fetchColumn();


        $stmt = $db->prepare("
            SELECT COUNT(*)
            FROM wishlist
            WHERE user_id = ?
            AND product_id = ?
        ");

        $stmt->execute([
            $userId,
            $productId
        ]);

        $isWishlisted =
            (int) $stmt->fetchColumn() > 0;

    } catch (Throwable $e) {
        $wishlistCount = 0;
        $isWishlisted = false;
    }
}


/*
|--------------------------------------------------------------------------
| REVIEW SUMMARY
|--------------------------------------------------------------------------
*/

$averageRating = 0;
$reviewCount = 0;

try {
    $stmt = $db->prepare("
        SELECT
            COUNT(*) AS total_reviews,
            COALESCE(
                AVG(rating),
                0
            ) AS average_rating
        FROM reviews
        WHERE product_id = ?
        AND status = 'Visible'
    ");

    $stmt->execute([
        $productId
    ]);

    $summary = $stmt->fetch(
        PDO::FETCH_ASSOC
    );

    if ($summary) {
        $reviewCount =
            (int) (
                $summary['total_reviews']
                ?? 0
            );

        $averageRating = round(
            (float) (
                $summary['average_rating']
                ?? 0
            ),
            1
        );
    }

} catch (Throwable $e) {
    $averageRating = 0;
    $reviewCount = 0;
}


/*
|--------------------------------------------------------------------------
| RATING BREAKDOWN
|--------------------------------------------------------------------------
*/

$ratingBreakdown = [
    5 => 0,
    4 => 0,
    3 => 0,
    2 => 0,
    1 => 0
];

try {
    $stmt = $db->prepare("
        SELECT
            rating,
            COUNT(*) AS total
        FROM reviews
        WHERE product_id = ?
        AND status = 'Visible'
        GROUP BY rating
    ");

    $stmt->execute([
        $productId
    ]);

    while (
        $row = $stmt->fetch(
            PDO::FETCH_ASSOC
        )
    ) {
        $rating = (int) (
            $row['rating']
            ?? 0
        );

        if ($rating >= 1 && $rating <= 5) {
            $ratingBreakdown[$rating] =
                (int) (
                    $row['total']
                    ?? 0
                );
        }
    }

} catch (Throwable $e) {
}


/*
|--------------------------------------------------------------------------
| REVIEWS
|--------------------------------------------------------------------------
*/

$reviews = [];

try {
    $stmt = $db->prepare("
        SELECT
            r.review_id,
            r.customer_id,
            r.order_id,
            r.order_detail_id,
            r.rating,
            r.review_title,
            r.review,
            r.image,
            r.helpful_count,
            r.review_date,

            u.name AS customer_name

        FROM reviews r

        INNER JOIN users u
            ON u.user_id = r.customer_id

        WHERE r.product_id = ?
        AND r.status = 'Visible'

        ORDER BY
            r.review_date DESC,
            r.review_id DESC
    ");

    $stmt->execute([
        $productId
    ]);

    $reviews = $stmt->fetchAll(
        PDO::FETCH_ASSOC
    );

} catch (Throwable $e) {
    $reviews = [];
}


/*
|--------------------------------------------------------------------------
| REVIEW ELIGIBILITY
|--------------------------------------------------------------------------
*/

$eligibleOrderDetailId = 0;

if ($isCustomer) {
    try {
        $stmt = $db->prepare("
            SELECT
                od.order_detail_id

            FROM order_details od

            INNER JOIN orders o
                ON o.order_id = od.order_id

            INNER JOIN vendor_orders vo
                ON vo.order_id = o.order_id
                AND vo.vendor_id = ?

            LEFT JOIN reviews r
                ON r.order_detail_id =
                    od.order_detail_id

            WHERE
                o.customer_id = ?
                AND od.product_id = ?
                AND vo.vendor_status = 'Completed'
                AND r.review_id IS NULL

            ORDER BY
                o.order_date DESC

            LIMIT 1
        ");

        $stmt->execute([
            $vendorId,
            $userId,
            $productId
        ]);

        $eligibleOrderDetailId =
            (int) (
                $stmt->fetchColumn()
                ?: 0
            );

    } catch (Throwable $e) {
        $eligibleOrderDetailId = 0;
    }
}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

$pageTitle =
    $productName .
    ' | HochipoHub';

require_once __DIR__ .
    '/includes/header.php';

?>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    background:
        linear-gradient(
            135deg,
            #f7faff 0%,
            #f2f6ff 55%,
            #f7f4ff 100%
        );
}


/* =========================================================
   CUSTOMER LAYOUT
========================================================= */

.product-customer-layout {
    display: flex;
    align-items: stretch;
    width: 100%;
    min-height: calc(100vh - 90px);
}


/* =========================================================
   SIDEBAR
========================================================= */

.product-customer-layout .customer-sidebar {
    width: 250px;
    min-width: 250px;
    min-height: calc(100vh - 90px);

    padding: 24px 14px;

    background:
        linear-gradient(
            180deg,
            #0b1f46 0%,
            #102b5f 100%
        );

    border-right:
        1px solid
        rgba(255,255,255,.08);

    box-shadow:
        10px 0 30px
        rgba(15,45,100,.08);
}

.product-customer-layout
.customer-sidebar-inner {
    display: flex;
    flex-direction: column;
    gap: 7px;

    position: sticky;
    top: 95px;
}

.product-customer-layout
.customer-sidebar-item {
    min-height: 50px;

    display: flex;
    align-items: center;
    gap: 12px;

    padding: 12px 14px;

    color: #dbe7ff;
    text-decoration: none;

    border-radius: 12px;

    font-size: 14px;
    font-weight: 600;

    transition: .2s ease;
}

.product-customer-layout
.customer-sidebar-item:hover {
    color: #ffffff;
    background: rgba(255,255,255,.10);
    transform: translateX(2px);
}

.product-customer-layout
.customer-sidebar-item.active {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2f76ff,
            #1d5fe5
        );

    box-shadow:
        0 8px 22px
        rgba(47,118,255,.25);
}

.product-customer-layout
.sidebar-icon {
    width: 28px;
    height: 28px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    flex-shrink: 0;

    font-size: 18px;
}

.product-customer-layout
.sidebar-text {
    flex: 1;
    white-space: nowrap;
}

.product-customer-layout
.sidebar-badge {
    min-width: 23px;
    height: 23px;

    padding: 0 7px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    border-radius: 999px;

    background: #ffffff;
    color: #1d5fe5;

    font-size: 11px;
    font-weight: 800;
}

.product-customer-layout
.sidebar-logout {
    margin-top: 10px;
    color: #ffdede;

    border-top:
        1px solid
        rgba(255,255,255,.10);

    border-radius: 0;
    padding-top: 18px;
}


/* =========================================================
   CONTENT
========================================================= */

.product-details-content {
    flex: 1;
    min-width: 0;

    padding: 38px 38px 70px;
}

.product-details-container {
    width: 100%;
    max-width: 1450px;
    margin: 0 auto;
}


/* =========================================================
   BREADCRUMB
========================================================= */

.product-breadcrumb {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;

    margin-bottom: 28px;

    color: #8492ab;

    font-size: 13px;
    font-weight: 600;
}

.product-breadcrumb a {
    color: #61718d;
    text-decoration: none;
}

.product-breadcrumb a:hover {
    color: #2468ed;
}

.product-breadcrumb strong {
    color: #2468ed;
}


/* =========================================================
   MAIN PRODUCT CARD
========================================================= */

.product-main-card {
    display: grid;

    grid-template-columns:
        minmax(360px, .95fr)
        minmax(430px, 1.05fr);

    gap: 52px;

    padding: 46px;

    background: #ffffff;

    border:
        1px solid
        #dfe8f7;

    border-radius: 30px;

    box-shadow:
        0 18px 60px
        rgba(30,65,130,.08);
}


/* =========================================================
   PRODUCT IMAGE
========================================================= */

.product-image-panel {
    min-height: 520px;

    position: relative;

    display: flex;
    align-items: center;
    justify-content: center;

    padding: 36px;

    overflow: hidden;

    background:
        linear-gradient(
            145deg,
            #edf5ff,
            #f7f9ff
        );

    border:
        1px solid
        #dce8f8;

    border-radius: 28px;
}

.product-preview-badge {
    position: absolute;
    top: 24px;
    left: 24px;

    display: inline-flex;
    align-items: center;
    gap: 7px;

    padding: 10px 15px;

    background: rgba(255,255,255,.92);

    color: #1e63e9;

    border:
        1px solid
        #d8e7ff;

    border-radius: 999px;

    font-size: 12px;
    font-weight: 800;
}

.product-image-panel img {
    display: block;

    max-width: 100%;
    max-height: 440px;

    object-fit: contain;

    border-radius: 18px;
}

.product-image-placeholder {
    text-align: center;
    color: #8392aa;
}

.product-image-placeholder i {
    display: block;

    margin-bottom: 14px;

    font-size: 70px;

    color: #aac2e8;
}


/* =========================================================
   PRODUCT INFORMATION
========================================================= */

.product-info-panel {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.product-category-pill {
    width: fit-content;

    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 16px;

    margin-bottom: 20px;

    background: #eef5ff;

    color: #2164e8;

    border:
        1px solid
        #d3e4ff;

    border-radius: 999px;

    font-size: 13px;
    font-weight: 800;
}

.product-title {
    margin: 0 0 18px;

    color: #0b2b62;

    font-size:
        clamp(
            38px,
            4vw,
            64px
        );

    line-height: 1.02;
    letter-spacing: -2px;
}


/* =========================================================
   RATING
========================================================= */

.product-rating-row {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;

    margin-bottom: 32px;
}

.product-stars {
    display: flex;
    gap: 3px;
}

.product-stars i {
    color: #f59e0b;
    font-size: 18px;
}

.product-rating-score {
    color: #172b4d;
    font-weight: 800;
}

.product-review-count {
    color: #94a1b7;
    font-size: 13px;
}


/* =========================================================
   PRICE
========================================================= */

.product-price-label {
    display: block;

    margin-bottom: 5px;

    color: #91a0b8;

    font-size: 11px;
    font-weight: 900;
    letter-spacing: 1px;
}

.product-price {
    margin-bottom: 28px;

    color: #0d3b82;

    font-size: 50px;
    font-weight: 900;

    line-height: 1;
}


/* =========================================================
   DESCRIPTION
========================================================= */

.product-description-box {
    padding: 24px;

    margin-bottom: 24px;

    background: #f8fbff;

    border:
        1px solid
        #dce7f6;

    border-radius: 20px;
}

.product-description-box h3 {
    margin: 0 0 12px;

    color: #12376e;

    font-size: 13px;
    font-weight: 900;
}

.product-description-box p {
    margin: 0;

    color: #71809a;

    line-height: 1.8;
    font-size: 14px;
}


/* =========================================================
   STOCK
========================================================= */

.stock-pill {
    width: fit-content;

    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 10px 16px;

    margin-bottom: 24px;

    border-radius: 999px;

    font-size: 12px;
    font-weight: 800;
}

.stock-pill.in-stock {
    background: #ecfdf3;
    color: #12813a;

    border:
        1px solid
        #b9f0cb;
}

.stock-pill.out-stock {
    background: #fff1f2;
    color: #c4283d;

    border:
        1px solid
        #ffcbd2;
}


/* =========================================================
   PRODUCT ACTIONS
========================================================= */

.product-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}

.product-action-btn {
    min-height: 52px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 9px;

    padding: 13px 22px;

    border: none;
    border-radius: 14px;

    cursor: pointer;

    text-decoration: none;

    font-family: inherit;
    font-size: 14px;
    font-weight: 800;

    transition: .2s ease;
}

.product-action-btn:hover {
    transform: translateY(-2px);
}

.btn-cart {
    flex: 1;
    min-width: 190px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2e78ff,
            #195bdc
        );

    box-shadow:
        0 12px 26px
        rgba(35,104,235,.24);
}

.btn-wishlist {
    background: #ffffff;
    color: #ef476f;

    border:
        1px solid
        #ffd0da;
}

.btn-wishlist.active {
    color: #ffffff;
    background: #ef476f;
}

.btn-disabled {
    flex: 1;

    background: #e8edf5;
    color: #8b98ad;

    cursor: not-allowed;
}


/* =========================================================
   SECTION
========================================================= */

.product-section-card {
    margin-top: 28px;

    padding: 30px;

    background: #ffffff;

    border:
        1px solid
        #dfe8f7;

    border-radius: 24px;

    box-shadow:
        0 12px 40px
        rgba(30,65,130,.06);
}

.section-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 18px;

    margin-bottom: 24px;
}

.section-heading h2 {
    margin: 0;

    color: #102f64;

    font-size: 24px;
}


/* =========================================================
   SELLER
========================================================= */

.seller-card {
    display: flex;
    align-items: center;
    gap: 18px;
}

.seller-avatar {
    width: 72px;
    height: 72px;

    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    background:
        linear-gradient(
            135deg,
            #e8f2ff,
            #dce9ff
        );

    color: #2767df;

    border-radius: 20px;

    font-size: 28px;
    font-weight: 900;
}

.seller-details {
    flex: 1;
}

.seller-details h3 {
    margin: 0 0 5px;

    color: #102f64;

    font-size: 19px;
}

.seller-details p {
    margin: 0;

    color: #8290a6;

    font-size: 13px;
}

.seller-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.seller-btn {
    min-height: 44px;

    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;

    padding: 10px 16px;

    border-radius: 12px;

    text-decoration: none;

    font-size: 13px;
    font-weight: 800;
}

.seller-store-btn {
    color: #2468ed;
    background: #edf4ff;

    border:
        1px solid
        #d4e4ff;
}

.seller-chat-btn {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2468ed,
            #174fbf
        );
}


/* =========================================================
   REVIEWS
========================================================= */

.review-summary-grid {
    display: grid;

    grid-template-columns:
        240px 1fr;

    gap: 40px;

    padding-bottom: 28px;
    margin-bottom: 28px;

    border-bottom:
        1px solid
        #edf1f7;
}

.review-score-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;

    padding: 24px;

    background: #f7faff;

    border-radius: 20px;
}

.review-big-score {
    color: #0d3b82;

    font-size: 50px;
    font-weight: 900;

    line-height: 1;
}

.review-big-stars {
    margin: 10px 0;
    color: #f59e0b;
}

.review-total {
    color: #8a98ad;
    font-size: 12px;
}

.rating-breakdown {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 10px;
}

.rating-row {
    display: grid;

    grid-template-columns:
        45px 1fr 35px;

    align-items: center;
    gap: 10px;

    color: #67768e;

    font-size: 12px;
    font-weight: 700;
}

.rating-bar {
    height: 8px;

    overflow: hidden;

    background: #edf1f7;

    border-radius: 999px;
}

.rating-fill {
    height: 100%;

    background:
        linear-gradient(
            90deg,
            #ffb020,
            #f59e0b
        );

    border-radius: inherit;
}

.review-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.review-card {
    padding: 22px;

    background: #fbfcff;

    border:
        1px solid
        #e5ebf5;

    border-radius: 18px;
}

.review-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 15px;

    margin-bottom: 13px;
}

.reviewer-name {
    color: #173568;
    font-weight: 900;
}

.review-date {
    margin-top: 4px;

    color: #9aa6b8;

    font-size: 11px;
}

.review-stars i {
    color: #f59e0b;
    font-size: 13px;
}

.review-title {
    margin: 0 0 8px;

    color: #173568;

    font-size: 15px;
}

.review-text {
    margin: 0;

    color: #6f7e95;

    line-height: 1.7;

    font-size: 13px;
}

.verified-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;

    margin-top: 12px;

    padding: 6px 10px;

    color: #15803d;
    background: #ecfdf3;

    border-radius: 999px;

    font-size: 10px;
    font-weight: 800;
}

.review-image {
    display: block;

    width: 100px;
    height: 100px;

    margin-top: 14px;

    object-fit: cover;

    border-radius: 12px;
}

.empty-reviews {
    padding: 38px;

    text-align: center;

    color: #8996aa;

    background: #f8faff;

    border-radius: 18px;
}

.empty-reviews i {
    display: block;

    margin-bottom: 12px;

    color: #aac1e7;

    font-size: 36px;
}

.write-review-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    padding: 11px 17px;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2f76ff,
            #1b5fdc
        );

    border-radius: 12px;

    text-decoration: none;

    font-size: 13px;
    font-weight: 800;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1200px) {

    .product-customer-layout
    .customer-sidebar {
        width: 220px;
        min-width: 220px;
    }

    .product-details-content {
        padding:
            30px 24px
            60px;
    }

    .product-main-card {
        gap: 32px;
        padding: 32px;
    }
}

@media (max-width: 1000px) {

    .product-main-card {
        grid-template-columns: 1fr;
    }

    .product-image-panel {
        min-height: 420px;
    }

    .review-summary-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 850px) {

    .product-customer-layout {
        display: block;
    }

    .product-customer-layout
    .customer-sidebar {
        width: 100%;
        min-width: 0;
        min-height: auto;

        padding: 10px 14px;

        overflow-x: auto;
    }

    .product-customer-layout
    .customer-sidebar-inner {
        position: static;
        flex-direction: row;

        min-width: max-content;
    }

    .product-customer-layout
    .customer-sidebar-item {
        min-height: 44px;
        padding: 10px 13px;
    }

    .product-customer-layout
    .sidebar-logout {
        margin-top: 0;
        padding-top: 10px;
        border-top: 0;
    }

    .product-details-content {
        width: 100%;

        padding:
            24px 16px
            50px;
    }
}

@media (max-width: 600px) {

    .product-main-card {
        padding: 18px;
        border-radius: 22px;
    }

    .product-image-panel {
        min-height: 320px;
        padding: 24px;
    }

    .product-title {
        font-size: 38px;
    }

    .product-price {
        font-size: 40px;
    }

    .product-section-card {
        padding: 20px;
    }

    .seller-card {
        align-items: flex-start;
        flex-direction: column;
    }

    .seller-actions {
        width: 100%;
    }

    .seller-btn {
        flex: 1;
    }

    .product-actions {
        flex-direction: column;
    }

    .product-action-btn {
        width: 100%;
    }
}

</style>


<?php if ($isCustomer): ?>

<div class="product-customer-layout">

    <?php
    require_once __DIR__ .
        '/includes/customer_sidebar.php';
    ?>

    <main class="product-details-content">

<?php else: ?>

<main class="product-details-content">

<?php endif; ?>


<div class="product-details-container">


    <!-- BREADCRUMB -->

    <div class="product-breadcrumb">

        <a href="<?= pdE(BASE_URL . 'index.php') ?>">
            Home
        </a>

        <span>›</span>

        <a href="<?= pdE(BASE_URL . 'catalog.php') ?>">
            Catalog
        </a>

        <span>›</span>

        <a
            href="<?=
                pdE(
                    BASE_URL .
                    'category.php?id=' .
                    (int) (
                        $product['category_id']
                        ?? 0
                    )
                )
            ?>"
        >
            <?= pdE($categoryName) ?>
        </a>

        <span>›</span>

        <strong>
            <?= pdE($productName) ?>
        </strong>

    </div>


    <!-- PRODUCT -->

    <section class="product-main-card">


        <!-- IMAGE -->

        <div class="product-image-panel">

            <span class="product-preview-badge">

                <i class="bi bi-box-seam"></i>

                Product Preview

            </span>


            <?php if ($productImage !== ''): ?>

                <img
                    src="<?= pdE($productImage) ?>"
                    alt="<?= pdE($productName) ?>"
                >

            <?php else: ?>

                <div class="product-image-placeholder">

                    <i class="bi bi-image"></i>

                    <strong>
                        No product image
                    </strong>

                </div>

            <?php endif; ?>

        </div>


        <!-- PRODUCT INFORMATION -->

        <div class="product-info-panel">

            <span class="product-category-pill">

                <i class="bi bi-grid-fill"></i>

                <?= pdE($categoryName) ?>

            </span>


            <h1 class="product-title">
                <?= pdE($productName) ?>
            </h1>


            <!-- RATING -->

            <div class="product-rating-row">

                <div class="product-stars">

                    <?php for ($star = 1; $star <= 5; $star++): ?>

                        <?php if ($averageRating >= $star): ?>

                            <i class="bi bi-star-fill"></i>

                        <?php elseif (
                            $averageRating >= ($star - 0.5)
                        ): ?>

                            <i class="bi bi-star-half"></i>

                        <?php else: ?>

                            <i class="bi bi-star"></i>

                        <?php endif; ?>

                    <?php endfor; ?>

                </div>


                <span class="product-rating-score">

                    <?= number_format(
                        $averageRating,
                        1
                    ) ?> / 5

                </span>


                <span class="product-review-count">

                    <?= $reviewCount ?>

                    <?= $reviewCount === 1
                        ? 'review'
                        : 'reviews' ?>

                </span>

            </div>


            <!-- PRICE -->

            <span class="product-price-label">
                PRODUCT PRICE
            </span>

            <div class="product-price">

                RM<?= number_format(
                    $price,
                    2
                ) ?>

            </div>


            <!-- DESCRIPTION -->

            <div class="product-description-box">

                <h3>
                    DESCRIPTION
                </h3>

                <p>

                    <?php if ($description !== ''): ?>

                        <?= nl2br(
                            pdE($description)
                        ) ?>

                    <?php else: ?>

                        No product description provided.

                    <?php endif; ?>

                </p>

            </div>


            <!-- STOCK -->

            <?php if ($stock > 0): ?>

                <span class="stock-pill in-stock">

                    <i class="bi bi-check-circle-fill"></i>

                    In Stock ·
                    <?= $stock ?>
                    available

                </span>

            <?php else: ?>

                <span class="stock-pill out-stock">

                    <i class="bi bi-x-circle-fill"></i>

                    Out of Stock

                </span>

            <?php endif; ?>


            <!-- ACTIONS -->

            <div class="product-actions">

                <?php if ($stock > 0): ?>

                    <?php if ($isCustomer): ?>

                        <button
                            type="button"
                            class="
                                product-action-btn
                                btn-cart
                                js-add-cart
                            "
                            data-product-id="<?= $productId ?>"
                        >

                            <i class="bi bi-cart-plus"></i>

                            Add to Cart

                        </button>


                        <button
                            type="button"
                            class="
                                product-action-btn
                                btn-wishlist
                                js-wishlist
                                <?= $isWishlisted
                                    ? 'active'
                                    : '' ?>
                            "
                            data-product-id="<?= $productId ?>"
                        >

                            <i
                                class="
                                    bi
                                    <?= $isWishlisted
                                        ? 'bi-heart-fill'
                                        : 'bi-heart' ?>
                                "
                            ></i>

                            <span>

                                <?= $isWishlisted
                                    ? 'Wishlisted'
                                    : 'Wishlist' ?>

                            </span>

                        </button>

                    <?php else: ?>

                        <a
                            href="<?= pdE(
                                BASE_URL .
                                'index.php'
                            ) ?>"
                            class="
                                product-action-btn
                                btn-cart
                            "
                        >

                            <i class="bi bi-box-arrow-in-right"></i>

                            Login to Purchase

                        </a>

                    <?php endif; ?>

                <?php else: ?>

                    <button
                        type="button"
                        class="
                            product-action-btn
                            btn-disabled
                        "
                        disabled
                    >
                        Out of Stock
                    </button>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- SELLER -->

    <section class="product-section-card">

        <div class="section-heading">

            <h2>
                Sold by
            </h2>

        </div>


        <div class="seller-card">


            <!--
                No v.logo is used.
                Seller avatar uses business initial.
            -->

            <div class="seller-avatar">

                <?= pdE(
                    strtoupper(
                        substr(
                            $businessName,
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div class="seller-details">

                <h3>
                    <?= pdE($businessName) ?>
                </h3>

                <p>

                    <?php if ($vendorOwnerName !== ''): ?>

                        Seller:
                        <?= pdE($vendorOwnerName) ?>

                    <?php else: ?>

                        HochipoHub Seller

                    <?php endif; ?>

                </p>

            </div>


            <div class="seller-actions">

                <a
                    href="<?=
                        pdE(
                            BASE_URL .
                            'vendor.php?id=' .
                            $vendorId
                        )
                    ?>"
                    class="
                        seller-btn
                        seller-store-btn
                    "
                >

                    <i class="bi bi-shop"></i>

                    View Store

                </a>


                <?php if ($isCustomer): ?>

                    <a
                        href="<?=
                            pdE(
                                BASE_URL .
                                'messages.php?vendor_id=' .
                                $vendorId
                            )
                        ?>"
                        class="
                            seller-btn
                            seller-chat-btn
                        "
                    >

                        <i class="bi bi-chat-dots"></i>

                        Chat Seller

                    </a>

                <?php endif; ?>

            </div>

        </div>

    </section>


    <!-- REVIEWS -->

    <section class="product-section-card">

        <div class="section-heading">

            <h2>
                Customer Reviews
            </h2>


            <?php if (
                $eligibleOrderDetailId > 0
            ): ?>

                <a
                    href="<?=
                        pdE(
                            BASE_URL .
                            'review.php?order_detail_id=' .
                            $eligibleOrderDetailId
                        )
                    ?>"
                    class="write-review-btn"
                >

                    <i class="bi bi-pencil-square"></i>

                    Write Review

                </a>

            <?php endif; ?>

        </div>


        <!-- REVIEW SUMMARY -->

        <div class="review-summary-grid">


            <div class="review-score-box">

                <div class="review-big-score">

                    <?= number_format(
                        $averageRating,
                        1
                    ) ?>

                </div>


                <div class="review-big-stars">

                    <?php for ($star = 1; $star <= 5; $star++): ?>

                        <?php if ($averageRating >= $star): ?>

                            <i class="bi bi-star-fill"></i>

                        <?php elseif (
                            $averageRating >= ($star - 0.5)
                        ): ?>

                            <i class="bi bi-star-half"></i>

                        <?php else: ?>

                            <i class="bi bi-star"></i>

                        <?php endif; ?>

                    <?php endfor; ?>

                </div>


                <div class="review-total">

                    Based on
                    <?= $reviewCount ?>

                    <?= $reviewCount === 1
                        ? 'review'
                        : 'reviews' ?>

                </div>

            </div>


            <div class="rating-breakdown">

                <?php for (
                    $rating = 5;
                    $rating >= 1;
                    $rating--
                ): ?>

                    <?php

                    $ratingTotal =
                        $ratingBreakdown[$rating]
                        ?? 0;

                    $percentage =
                        $reviewCount > 0
                        ? (
                            $ratingTotal /
                            $reviewCount
                        ) * 100
                        : 0;

                    ?>

                    <div class="rating-row">

                        <span>
                            <?= $rating ?> ★
                        </span>

                        <div class="rating-bar">

                            <div
                                class="rating-fill"
                                style="
                                    width:
                                    <?= number_format(
                                        $percentage,
                                        2,
                                        '.',
                                        ''
                                    ) ?>%;
                                "
                            ></div>

                        </div>

                        <span>
                            <?= $ratingTotal ?>
                        </span>

                    </div>

                <?php endfor; ?>

            </div>

        </div>


        <!-- REVIEW LIST -->

        <?php if (!empty($reviews)): ?>

            <div class="review-list">

                <?php foreach ($reviews as $review): ?>

                    <?php

                    $reviewRating = max(
                        1,
                        min(
                            5,
                            (int) (
                                $review['rating']
                                ?? 0
                            )
                        )
                    );

                    $reviewTitle = trim(
                        (string) (
                            $review['review_title']
                            ?? ''
                        )
                    );

                    $reviewText = trim(
                        (string) (
                            $review['review']
                            ?? ''
                        )
                    );

                    $reviewImage = trim(
                        (string) (
                            $review['image']
                            ?? ''
                        )
                    );

                    $isVerified =
                        !empty(
                            $review['order_detail_id']
                        );

                    ?>


                    <article class="review-card">

                        <div class="review-card-header">

                            <div>

                                <div class="reviewer-name">

                                    <?= pdE(
                                        $review['customer_name']
                                        ?? 'Customer'
                                    ) ?>

                                </div>


                                <div class="review-date">

                                    <?php

                                    $reviewDate =
                                        $review['review_date']
                                        ?? '';

                                    if ($reviewDate !== '') {

                                        $timestamp =
                                            strtotime(
                                                $reviewDate
                                            );

                                        echo pdE(
                                            $timestamp
                                            ? date(
                                                'd M Y',
                                                $timestamp
                                            )
                                            : $reviewDate
                                        );
                                    }

                                    ?>

                                </div>

                            </div>


                            <div class="review-stars">

                                <?php for (
                                    $star = 1;
                                    $star <= 5;
                                    $star++
                                ): ?>

                                    <i
                                        class="
                                            bi
                                            <?= $star <= $reviewRating
                                                ? 'bi-star-fill'
                                                : 'bi-star' ?>
                                        "
                                    ></i>

                                <?php endfor; ?>

                            </div>

                        </div>


                        <?php if ($reviewTitle !== ''): ?>

                            <h3 class="review-title">

                                <?= pdE(
                                    $reviewTitle
                                ) ?>

                            </h3>

                        <?php endif; ?>


                        <?php if ($reviewText !== ''): ?>

                            <p class="review-text">

                                <?= nl2br(
                                    pdE(
                                        $reviewText
                                    )
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <?php if ($isVerified): ?>

                            <span class="verified-badge">

                                <i class="bi bi-patch-check-fill"></i>

                                Verified Purchase

                            </span>

                        <?php endif; ?>


                        <?php if ($reviewImage !== ''): ?>

                            <?php

                            $reviewImageUrl =
                                $reviewImage;

                            if (
                                !preg_match(
                                    '/^https?:\/\//i',
                                    $reviewImageUrl
                                )
                            ) {
                                $reviewImageUrl =
                                    BASE_URL .
                                    ltrim(
                                        $reviewImageUrl,
                                        '/'
                                    );
                            }

                            ?>

                            <img
                                src="<?= pdE(
                                    $reviewImageUrl
                                ) ?>"
                                alt="Review image"
                                class="review-image"
                            >

                        <?php endif; ?>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="empty-reviews">

                <i class="bi bi-chat-square-heart"></i>

                <strong>
                    No reviews yet
                </strong>

                <p>
                    Be the first verified customer
                    to review this product.
                </p>

            </div>

        <?php endif; ?>

    </section>


</div>

</main>


<?php if ($isCustomer): ?>

</div>

<?php endif; ?>


<script>

document.addEventListener(
    'DOMContentLoaded',
    function () {

        /*
        |--------------------------------------------------------------------------
        | ADD TO CART
        |--------------------------------------------------------------------------
        */

        const cartButton =
            document.querySelector(
                '.js-add-cart'
            );

        if (cartButton) {

            cartButton.addEventListener(
                'click',
                async function () {

                    const productId =
                        this.dataset.productId;

                    const originalHTML =
                        this.innerHTML;

                    this.disabled = true;

                    this.innerHTML =
                        '<i class="bi bi-arrow-repeat"></i> Adding...';

                    try {

                        const body =
                            new URLSearchParams();

                        body.append(
                            'product_id',
                            productId
                        );

                        body.append(
                            'quantity',
                            '1'
                        );


                        const response =
                            await fetch(
                                '<?= pdE(
                                    BASE_URL .
                                    'ajax/add_cart.php'
                                ) ?>',
                                {
                                    method: 'POST',

                                    headers: {
                                        'Content-Type':
                                            'application/x-www-form-urlencoded; charset=UTF-8'
                                    },

                                    body:
                                        body.toString()
                                }
                            );


                        const text =
                            await response.text();

                        let data = null;

                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            data = null;
                        }


                        if (
                            response.ok &&
                            (
                                data === null ||
                                data.success === true ||
                                data.status === 'success'
                            )
                        ) {

                            this.innerHTML =
                                '<i class="bi bi-check-circle-fill"></i> Added to Cart';

                            setTimeout(
                                function () {
                                    window.location.reload();
                                },
                                650
                            );

                            return;
                        }


                        alert(
                            data?.message
                            ?? text
                            ?? 'Unable to add product to cart.'
                        );

                    } catch (error) {

                        alert(
                            'Unable to add product to cart.'
                        );

                    } finally {

                        this.disabled = false;

                        if (
                            !this.innerHTML.includes(
                                'Added to Cart'
                            )
                        ) {
                            this.innerHTML =
                                originalHTML;
                        }
                    }

                }
            );
        }


        /*
        |--------------------------------------------------------------------------
        | WISHLIST
        |--------------------------------------------------------------------------
        */

        const wishlistButton =
            document.querySelector(
                '.js-wishlist'
            );

        if (wishlistButton) {

            wishlistButton.addEventListener(
                'click',
                async function () {

                    const productId =
                        this.dataset.productId;

                    const active =
                        this.classList.contains(
                            'active'
                        );

                    const endpoint =
                        active
                        ? 'ajax/remove_wishlist.php'
                        : 'ajax/add_wishlist.php';

                    this.disabled = true;

                    try {

                        const body =
                            new URLSearchParams();

                        body.append(
                            'product_id',
                            productId
                        );


                        const response =
                            await fetch(
                                '<?= pdE(BASE_URL) ?>' +
                                endpoint,
                                {
                                    method: 'POST',

                                    headers: {
                                        'Content-Type':
                                            'application/x-www-form-urlencoded; charset=UTF-8'
                                    },

                                    body:
                                        body.toString()
                                }
                            );


                        const text =
                            await response.text();

                        let data = null;

                        try {
                            data = JSON.parse(text);
                        } catch (error) {
                            data = null;
                        }


                        if (
                            response.ok &&
                            (
                                data === null ||
                                data.success === true ||
                                data.status === 'success'
                            )
                        ) {

                            window.location.reload();
                            return;
                        }


                        alert(
                            data?.message
                            ?? text
                            ?? 'Unable to update wishlist.'
                        );

                    } catch (error) {

                        alert(
                            'Unable to update wishlist.'
                        );

                    } finally {

                        this.disabled = false;
                    }

                }
            );
        }

    }
);

</script>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>