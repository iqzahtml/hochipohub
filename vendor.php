<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';

$db = getDB();

$vendorId = (int) ($_GET['id'] ?? 0);

if ($vendorId <= 0) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| VENDOR
|--------------------------------------------------------------------------
*/

$vendorStmt = $db->prepare("
    SELECT
        v.vendor_id,
        v.user_id,
        v.business_name,
        v.business_logo,
        v.business_description,
        v.business_address,
        v.category,
        v.delivery_method,
        v.approval_status,
        v.created_at,
        u.name AS owner_name
    FROM vendors v
    INNER JOIN users u
        ON u.user_id = v.user_id
    WHERE v.vendor_id = :vendor_id
    AND v.approval_status = 'Approved'
    LIMIT 1
");

$vendorStmt->execute([
    ':vendor_id' => $vendorId
]);

$vendor = $vendorStmt->fetch();

if (!$vendor) {
    redirect(BASE_URL . 'index.php');
}

/*
|--------------------------------------------------------------------------
| VENDOR PRODUCTS
|--------------------------------------------------------------------------
*/

$productStmt = $db->prepare("
    SELECT
        p.product_id,
        p.product_name,
        p.description,
        p.price,
        p.stock_quantity,
        p.image,
        p.status,
        c.category_name
    FROM products p
    INNER JOIN categories c
        ON c.category_id = p.category_id
    WHERE p.vendor_id = :vendor_id
    AND p.status = 'Available'
    ORDER BY p.created_at DESC
");

$productStmt->execute([
    ':vendor_id' => $vendorId
]);

$products =
    $productStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| VENDOR LOGO
|--------------------------------------------------------------------------
*/

$vendorLogo =
    vendorImageUrl(
        $vendor['business_logo']
    );

/*
|--------------------------------------------------------------------------
| PRODUCT COUNT
|--------------------------------------------------------------------------
*/

$productCount =
    count($products);

?>
<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        <?= e($vendor['business_name']) ?>
        | <?= e(APP_NAME) ?>
    </title>

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/style.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/vendor.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/product.css"
    >

    <link
        rel="stylesheet"
        href="<?= BASE_URL ?>css/responsive.css"
    >

    <style>

        .vendor-page {
            min-height: 100vh;
            padding: 45px 5%;
            background:
                radial-gradient(
                    circle at 10% 10%,
                    rgba(37,99,235,.14),
                    transparent 30%
                ),
                #f8fbff;
        }

        .vendor-container {
            max-width: 1200px;
            margin: auto;
        }

        .vendor-hero {
            position: relative;
            overflow: hidden;
            padding: 40px;
            margin-bottom: 30px;
            border-radius: 30px;
            background:
                linear-gradient(
                    135deg,
                    #0f3c91,
                    #2563eb 55%,
                    #0284c7
                );
            color: white;
            box-shadow:
                0 25px 60px rgba(37,99,235,.22);
        }

        .vendor-hero::after {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            right: -100px;
            top: -130px;
            border-radius: 50%;
            background: rgba(255,255,255,.08);
        }

        .vendor-profile {
            position: relative;
            z-index: 2;
            display: grid;
            grid-template-columns: 130px 1fr;
            align-items: center;
            gap: 25px;
        }

        .vendor-logo {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border: 5px solid rgba(255,255,255,.7);
            border-radius: 28px;
            background: white;
        }

        .vendor-kicker {
            margin-bottom: 7px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            opacity: .75;
        }

        .vendor-name {
            margin: 0 0 10px;
            font-size: clamp(30px, 5vw, 48px);
            font-weight: 950;
        }

        .vendor-description {
            max-width: 700px;
            margin: 0 0 18px;
            color: rgba(255,255,255,.82);
            font-size: 13px;
            line-height: 1.7;
        }

        .vendor-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .vendor-tag {
            padding: 7px 11px;
            border-radius: 999px;
            background: rgba(255,255,255,.14);
            font-size: 10px;
            font-weight: 900;
        }

        .vendor-stats {
            display: grid;
            grid-template-columns:
                repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }

        .stat-card {
            padding: 20px;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px rgba(15,23,42,.06);
        }

        .stat-card span {
            display: block;
            margin-bottom: 7px;
            color: #94a3b8;
            font-size: 10px;
            font-weight: 800;
        }

        .stat-card strong {
            color: #1d4ed8;
            font-size: 24px;
            font-weight: 950;
        }

        .products-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 18px;
        }

        .products-header h2 {
            margin: 0;
            color: #0f172a;
            font-size: 23px;
            font-weight: 950;
        }

        .products-header span {
            color: #64748b;
            font-size: 11px;
            font-weight: 800;
        }

        .vendor-product-grid {
            display: grid;
            grid-template-columns:
                repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .vendor-product {
            overflow: hidden;
            border: 1px solid #dbeafe;
            border-radius: 20px;
            background: white;
            box-shadow:
                0 12px 35px rgba(15,23,42,.06);
            transition: .2s ease;
        }

        .vendor-product:hover {
            transform: translateY(-5px);
            box-shadow:
                0 20px 45px rgba(37,99,235,.13);
        }

        .vendor-product-image {
            width: 100%;
            height: 190px;
            object-fit: cover;
            background: #eff6ff;
        }

        .vendor-product-content {
            padding: 15px;
        }

        .vendor-category {
            margin-bottom: 7px;
            color: #2563eb;
            font-size: 9px;
            font-weight: 900;
            text-transform: uppercase;
        }

        .vendor-product-content h3 {
            margin: 0 0 7px;
            color: #0f172a;
            font-size: 13px;
            font-weight: 950;
        }

        .vendor-product-description {
            display: -webkit-box;
            height: 34px;
            margin-bottom: 14px;
            overflow: hidden;
            color: #94a3b8;
            font-size: 10px;
            line-height: 1.7;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }

        .vendor-product-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
        }

        .vendor-price {
            color: #1d4ed8;
            font-size: 16px;
            font-weight: 950;
        }

        .vendor-view {
            padding: 8px 10px;
            border-radius: 9px;
            background: #2563eb;
            color: white;
            text-decoration: none;
            font-size: 9px;
            font-weight: 900;
        }

        .empty-products {
            padding: 60px 20px;
            border: 1px dashed #bfdbfe;
            border-radius: 20px;
            background: white;
            text-align: center;
        }

        .empty-products h3 {
            margin: 0 0 7px;
            color: #0f172a;
        }

        .empty-products p {
            margin: 0;
            color: #94a3b8;
            font-size: 11px;
        }

        @media (max-width: 1000px) {

            .vendor-product-grid {
                grid-template-columns:
                    repeat(3, 1fr);
            }

        }

        @media (max-width: 750px) {

            .vendor-profile {
                grid-template-columns: 1fr;
            }

            .vendor-logo {
                width: 100px;
                height: 100px;
            }

            .vendor-stats {
                grid-template-columns: 1fr;
            }

            .vendor-product-grid {
                grid-template-columns:
                    repeat(2, 1fr);
            }

        }

        @media (max-width: 500px) {

            .vendor-page {
                padding: 25px 15px;
            }

            .vendor-hero {
                padding: 25px 20px;
            }

            .vendor-product-grid {
                grid-template-columns: 1fr;
            }

        }

    </style>

</head>

<body>

<?php require_once __DIR__ . '/includes/navbar.php'; ?>

<main class="vendor-page">

    <div class="vendor-container">

        <section class="vendor-hero">

            <div class="vendor-profile">

                <img
                    src="<?= e($vendorLogo) ?>"
                    alt="<?= e($vendor['business_name']) ?>"
                    class="vendor-logo"
                    onerror="this.src='<?= e(
                        BASE_URL .
                        'image/vendors/default-vendor.jpg'
                    ) ?>'"
                >

                <div>

                    <div class="vendor-kicker">
                        Verified HochipoHub Vendor
                    </div>

                    <h1 class="vendor-name">
                        <?= e(
                            $vendor['business_name']
                        ) ?>
                    </h1>

                    <p class="vendor-description">
                        <?= e(
                            $vendor['business_description']
                            ?: 'Welcome to our HochipoHub store.'
                        ) ?>
                    </p>

                    <div class="vendor-tags">

                        <?php if (
                            !empty(
                                $vendor['category']
                            )
                        ): ?>

                            <span class="vendor-tag">
                                <?= e(
                                    $vendor['category']
                                ) ?>
                            </span>

                        <?php endif; ?>

                        <span class="vendor-tag">
                            <?= e(
                                $vendor['delivery_method']
                            ) ?>
                        </span>

                        <span class="vendor-tag">
                            ✓ Approved
                        </span>

                    </div>

                </div>

            </div>

        </section>


        <section class="vendor-stats">

            <div class="stat-card">

                <span>
                    PRODUCTS
                </span>

                <strong>
                    <?= $productCount ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    DELIVERY
                </span>

                <strong style="font-size:18px;">
                    <?= e(
                        $vendor['delivery_method']
                    ) ?>
                </strong>

            </div>


            <div class="stat-card">

                <span>
                    VENDOR SINCE
                </span>

                <strong style="font-size:18px;">
                    <?= date(
                        'M Y',
                        strtotime(
                            $vendor['created_at']
                        )
                    ) ?>
                </strong>

            </div>

        </section>


        <section>

            <div class="products-header">

                <h2>
                    Products
                </h2>

                <span>
                    <?= $productCount ?>
                    available
                </span>

            </div>


            <?php if (
                empty($products)
            ): ?>

                <div class="empty-products">

                    <h3>
                        No products available
                    </h3>

                    <p>
                        This vendor has not listed
                        any available products yet.
                    </p>

                </div>

            <?php else: ?>

                <div class="vendor-product-grid">

                    <?php foreach (
                        $products
                        as $product
                    ): ?>

                        <article
                            class="vendor-product"
                        >

                            <img
                                src="<?= e(
                                    productImageUrl(
                                        $product['image']
                                    )
                                ) ?>"
                                alt="<?= e(
                                    $product['product_name']
                                ) ?>"
                                class="vendor-product-image"
                                onerror="this.src='<?= e(
                                    BASE_URL .
                                    'image/product/default-product.jpg'
                                ) ?>'"
                            >

                            <div
                                class="vendor-product-content"
                            >

                                <div class="vendor-category">
                                    <?= e(
                                        $product['category_name']
                                    ) ?>
                                </div>

                                <h3>
                                    <?= e(
                                        $product['product_name']
                                    ) ?>
                                </h3>

                                <div
                                    class="vendor-product-description"
                                >
                                    <?= e(
                                        $product['description']
                                        ?: 'No description available.'
                                    ) ?>
                                </div>

                                <div
                                    class="vendor-product-bottom"
                                >

                                    <span class="vendor-price">
                                        <?= formatPrice(
                                            $product['price']
                                        ) ?>
                                    </span>

                                    <a
                                        href="<?= BASE_URL ?>product_details.php?id=<?= (int) $product['product_id'] ?>"
                                        class="vendor-view"
                                    >
                                        View
                                    </a>

                                </div>

                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

</body>

</html>