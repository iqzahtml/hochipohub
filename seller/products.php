<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";
require_once "../includes/functions.php";


$pageTitle = "My Products";


if (!isLoggedIn()) {

    header("Location: " . BASE_URL . "index.php");
    exit();

}


if (currentUserRole() !== 'vendor') {

    header("Location: " . BASE_URL . "index.php");
    exit();

}


$userID =
    (int) currentUserID();


/*
|--------------------------------------------------------------------------
| Get Vendor
|--------------------------------------------------------------------------
*/

$vendorQuery = $conn->prepare("

    SELECT vendor_id

    FROM vendors

    WHERE user_id = ?

    LIMIT 1

");


$vendorQuery->bind_param(
    "i",
    $userID
);


$vendorQuery->execute();


$vendorResult =
    $vendorQuery->get_result();


if ($vendorResult->num_rows === 0) {

    header(
        "Location: setup_profile.php"
    );

    exit();

}


$vendor =
    $vendorResult->fetch_assoc();


$vendorID =
    (int) $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| Get Products
|--------------------------------------------------------------------------
*/

$query = $conn->prepare("

    SELECT

        products.product_id,
        products.product_name,
        products.description,
        products.price,
        products.stock_quantity,
        products.image,
        products.status,
        products.created_at,

        categories.category_name

    FROM products

    LEFT JOIN categories

        ON products.category_id =
           categories.category_id

    WHERE products.vendor_id = ?

    ORDER BY products.created_at DESC

");


$query->bind_param(
    "i",
    $vendorID
);


$query->execute();


$products =
    $query->get_result();

?>

<?php include "../includes/header.php"; ?>

<section class="product-page">

    <div class="page-title">

        <div>

            <h1>
                My Products
            </h1>

            <p>
                Manage your HochipoHub products.
            </p>

        </div>


        <a
            href="add_product.php"
            class="btn-primary"
        >
            + Add Product
        </a>

    </div>


    <div class="product-grid">

        <?php if ($products->num_rows > 0): ?>

            <?php while (
                $product =
                $products->fetch_assoc()
            ): ?>

                <div class="product-card">

                    <?php if (
                        !empty($product['image'])
                    ): ?>

                        <img
                            src="<?= BASE_URL; ?>uploads/products/<?= htmlspecialchars(
                                $product['image']
                            ); ?>"
                            alt="<?= htmlspecialchars(
                                $product['product_name']
                            ); ?>"
                        >

                    <?php endif; ?>


                    <h3>
                        <?= htmlspecialchars(
                            $product['product_name']
                        ); ?>
                    </h3>


                    <p>
                        <?= htmlspecialchars(
                            $product['category_name']
                            ?? 'Uncategorized'
                        ); ?>
                    </p>


                    <p>
                        RM <?= number_format(
                            $product['price'],
                            2
                        ); ?>
                    </p>


                    <p>
                        Stock:
                        <?= (int)$product['stock_quantity']; ?>
                    </p>


                    <p>
                        Status:
                        <?= htmlspecialchars(
                            $product['status']
                        ); ?>
                    </p>


                    <div class="product-actions">

                        <a
                            href="edit_product.php?id=<?= $product['product_id']; ?>"
                        >
                            Edit
                        </a>

                        <a
                            href="delete_product.php?id=<?= $product['product_id']; ?>"
                            onclick="return confirm('Delete this product?');"
                        >
                            Delete
                        </a>

                    </div>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <div class="empty-product">

                <h3>
                    No Products Yet
                </h3>

                <p>
                    Add your first product to start selling.
                </p>

                <a
                    href="add_product.php"
                    class="btn-primary"
                >
                    Add Product
                </a>

            </div>

        <?php endif; ?>

    </div>

</section>

<?php include "../includes/footer.php"; ?>