<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - VENDOR PAGE
|--------------------------------------------------------------------------
| File:
| vendor.php
|
| Purpose:
| - Display approved vendors
| - View products belonging to vendor
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDB();


$vendorId =
    (int) ($_GET['id'] ?? 0);


/*
|--------------------------------------------------------------------------
| GET VENDOR
|--------------------------------------------------------------------------
*/

if ($vendorId > 0) {

    $stmt = $db->prepare("
        SELECT
            v.*,
            u.name,
            u.email,
            u.phone

        FROM vendors v

        INNER JOIN users u
            ON v.user_id = u.user_id

        WHERE v.vendor_id = ?
        AND v.approval_status = 'Approved'

        LIMIT 1
    ");

    $stmt->execute([
        $vendorId
    ]);

    $vendor =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );

    if (!$vendor) {

        redirect('vendor.php');
    }


    /*
    |--------------------------------------------------------------------------
    | GET VENDOR PRODUCTS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT

            p.*,

            c.category_name

        FROM products p

        INNER JOIN categories c
            ON p.category_id = c.category_id

        WHERE p.vendor_id = ?

        AND p.status != 'Hidden'

        ORDER BY p.created_at DESC
    ");

    $stmt->execute([
        $vendorId
    ]);

    $products =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    $pageTitle =
        $vendor['business_name'];

} else {


    /*
    |--------------------------------------------------------------------------
    | ALL VENDORS
    |--------------------------------------------------------------------------
    */

    $stmt = $db->query("
        SELECT

            v.vendor_id,
            v.business_name,
            v.business_logo,
            v.business_description,
            v.category,
            v.delivery_method

        FROM vendors v

        WHERE v.approval_status = 'Approved'

        ORDER BY v.business_name ASC
    ");

    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    $pageTitle =
        'Vendors - ' . SITE_NAME;
}


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>


<main class="dashboard-page">

    <div class="dashboard-container">


        <?php if (
            $vendorId > 0
        ): ?>


            <!-- SINGLE VENDOR -->

            <section class="dashboard-header">

                <div>

                    <span class="small-label">
                        VENDOR
                    </span>

                    <h1>
                        <?= e(
                            $vendor[
                                'business_name'
                            ]
                        ) ?>
                    </h1>

                    <?php if (
                        !empty(
                            $vendor[
                                'category'
                            ]
                        )
                    ): ?>

                        <p>

                            <?= e(
                                $vendor[
                                    'category'
                                ]
                            ) ?>

                        </p>

                    <?php endif; ?>

                </div>


                <div>

                    <a
                        href="vendor.php"
                        class="btn btn-secondary"
                    >
                        ← All Vendors
                    </a>

                </div>

            </section>


            <!-- VENDOR INFO -->

            <section class="dashboard-section">

                <div class="vendor-profile">

                    <div>

                        <img
                            src="<?= e(
                                getVendorImage(
                                    $vendor[
                                        'business_logo'
                                    ]
                                )
                            ) ?>"
                            alt="<?= e(
                                $vendor[
                                    'business_name'
                                ]
                            ) ?>"
                            class="vendor-logo"
                        >

                    </div>


                    <div>

                        <h2>
                            <?= e(
                                $vendor[
                                    'business_name'
                                ]
                            ) ?>
                        </h2>


                        <?php if (
                            !empty(
                                $vendor[
                                    'business_description'
                                ]
                            )
                        ): ?>

                            <p>

                                <?= nl2br(
                                    e(
                                        $vendor[
                                            'business_description'
                                        ]
                                    )
                                ) ?>

                            </p>

                        <?php endif; ?>


                        <p>

                            <strong>
                                Delivery:
                            </strong>

                            <?= e(
                                $vendor[
                                    'delivery_method'
                                ]
                            ) ?>

                        </p>

                    </div>

                </div>

            </section>


            <!-- PRODUCTS -->

            <section class="dashboard-section">

                <div class="section-heading">

                    <div>

                        <span class="small-label">
                            SHOP
                        </span>

                        <h2>
                            Products
                        </h2>

                    </div>

                </div>


                <?php if (
                    empty($products)
                ): ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            📦
                        </div>

                        <h3>
                            No products available
                        </h3>

                        <p>
                            This vendor has not listed
                            any products yet.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="product-grid">

                        <?php foreach (
                            $products
                            as $product
                        ): ?>

                            <article
                                class="product-card"
                            >

                                <a
                                    href="product_details.php?id=<?= (int)
                                        $product[
                                            'product_id'
                                        ] ?>"
                                >

                                    <img
                                        src="<?= e(
                                            getProductImage(
                                                $product[
                                                    'image'
                                                ]
                                            )
                                        ) ?>"
                                        alt="<?= e(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>"
                                    >

                                </a>


                                <div
                                    class="product-card-body"
                                >

                                    <small>

                                        <?= e(
                                            $product[
                                                'category_name'
                                            ]
                                        ) ?>

                                    </small>


                                    <h3>

                                        <?= e(
                                            $product[
                                                'product_name'
                                            ]
                                        ) ?>

                                    </h3>


                                    <strong>

                                        RM
                                        <?= number_format(
                                            (float)
                                            $product[
                                                'price'
                                            ],
                                            2
                                        ) ?>

                                    </strong>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>


        <?php else: ?>


            <!-- ALL VENDORS -->

            <section class="dashboard-header">

                <div>

                    <span class="small-label">
                        MARKETPLACE
                    </span>

                    <h1>
                        Vendors
                    </h1>

                    <p>
                        Discover approved sellers
                        on HochipoHub.
                    </p>

                </div>

            </section>


            <section class="dashboard-section">

                <?php if (
                    empty($vendors)
                ): ?>

                    <div class="empty-state">

                        <div class="empty-icon">
                            🏪
                        </div>

                        <h3>
                            No vendors available
                        </h3>

                        <p>
                            There are currently no approved
                            vendors.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="vendor-grid">

                        <?php foreach (
                            $vendors
                            as $item
                        ): ?>

                            <article
                                class="vendor-card"
                            >

                                <a
                                    href="vendor.php?id=<?= (int)
                                        $item[
                                            'vendor_id'
                                        ] ?>"
                                >

                                    <img
                                        src="<?= e(
                                            getVendorImage(
                                                $item[
                                                    'business_logo'
                                                ]
                                            )
                                        ) ?>"
                                        alt="<?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>"
                                    >

                                </a>


                                <div>

                                    <h2>

                                        <?= e(
                                            $item[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </h2>


                                    <?php if (
                                        !empty(
                                            $item[
                                                'category'
                                            ]
                                        )
                                    ): ?>

                                        <span>

                                            <?= e(
                                                $item[
                                                    'category'
                                                ]
                                            ) ?>

                                        </span>

                                    <?php endif; ?>


                                    <?php if (
                                        !empty(
                                            $item[
                                                'business_description'
                                            ]
                                        )
                                    ): ?>

                                        <p>

                                            <?= e(
                                                mb_strimwidth(
                                                    $item[
                                                        'business_description'
                                                    ],
                                                    0,
                                                    120,
                                                    '...'
                                                )
                                            ) ?>

                                        </p>

                                    <?php endif; ?>


                                    <a
                                        href="vendor.php?id=<?= (int)
                                            $item[
                                                'vendor_id'
                                            ] ?>"
                                        class="btn btn-primary"
                                    >
                                        View Store
                                    </a>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                <?php endif; ?>

            </section>

        <?php endif; ?>


    </div>

</main>


<?php
require_once __DIR__ . '/includes/footer.php';
?>