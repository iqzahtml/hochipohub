<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";
require_once "../includes/functions.php";


$pageTitle = "Add Product";


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

    SELECT

        vendor_id,
        approval_status

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
    (int)$vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| Only Approved Vendor Can Add Products
|--------------------------------------------------------------------------
*/

if (
    $vendor['approval_status']
    !== 'Approved'
) {

    setFlashMessage(
        "error",
        "Your vendor account must be approved before adding products."
    );

    header(
        "Location: dashboard.php"
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$categories =
    $conn->query("

        SELECT

            category_id,
            category_name

        FROM categories

        ORDER BY category_name ASC

    ");


/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    === 'POST'
) {


    $productName =
        trim(
            $_POST['product_name']
            ?? ''
        );


    $description =
        trim(
            $_POST['description']
            ?? ''
        );


    $price =
        (float)(
            $_POST['price']
            ?? 0
        );


    $stock =
        (int)(
            $_POST['stock_quantity']
            ?? 0
        );


    $categoryID =
        (int)(
            $_POST['category_id']
            ?? 0
        );


    $status =
        $_POST['status']
        ?? 'Available';


    $allowedStatus = [

        'Available',
        'Out of Stock',
        'Hidden'

    ];


    if (
        !in_array(
            $status,
            $allowedStatus,
            true
        )
    ) {

        $status =
            'Available';

    }


    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if (
        $productName === ''
        ||
        $categoryID <= 0
        ||
        $price < 0
        ||
        $stock < 0
    ) {

        setFlashMessage(
            "error",
            "Please fill in all required fields correctly."
        );

    } else {


        /*
        |--------------------------------------------------------------------------
        | Image Upload
        |--------------------------------------------------------------------------
        */

        $imageName = null;


        if (
            isset($_FILES['image'])
            &&
            $_FILES['image']['error']
            === UPLOAD_ERR_OK
        ) {


            $extension =
                strtolower(
                    pathinfo(
                        $_FILES['image']['name'],
                        PATHINFO_EXTENSION
                    )
                );


            $allowedExtensions = [

                'jpg',
                'jpeg',
                'png',
                'webp'

            ];


            if (
                in_array(
                    $extension,
                    $allowedExtensions,
                    true
                )
            ) {


                $imageName =
                    uniqid(
                        'product_',
                        true
                    )
                    . '.'
                    . $extension;


                $uploadDirectory =
                    dirname(__DIR__)
                    . '/uploads/products/';


                if (
                    !is_dir(
                        $uploadDirectory
                    )
                ) {

                    mkdir(
                        $uploadDirectory,
                        0777,
                        true
                    );

                }


                move_uploaded_file(

                    $_FILES['image']['tmp_name'],

                    $uploadDirectory
                    . $imageName

                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Automatically Mark Out Of Stock
        |--------------------------------------------------------------------------
        */

        if ($stock <= 0) {

            $status =
                'Out of Stock';

        }


        /*
        |--------------------------------------------------------------------------
        | Insert Product
        |--------------------------------------------------------------------------
        */

        $insert = $conn->prepare("

            INSERT INTO products

            (
                vendor_id,
                category_id,
                product_name,
                description,
                price,
                stock_quantity,
                image,
                status
            )

            VALUES

            (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?
            )

        ");


        $insert->bind_param(

            "iissdiss",

            $vendorID,
            $categoryID,
            $productName,
            $description,
            $price,
            $stock,
            $imageName,
            $status

        );


        if ($insert->execute()) {


            /*
            |--------------------------------------------------------------------------
            | Create Inventory Record
            |--------------------------------------------------------------------------
            */

            $productID =
                $insert->insert_id;


            $inventory = $conn->prepare("

                INSERT INTO inventory

                (
                    product_id,
                    quantity
                )

                VALUES
                (
                    ?,
                    ?
                )

            ");


            $inventory->bind_param(
                "ii",
                $productID,
                $stock
            );


            $inventory->execute();


            setFlashMessage(
                "success",
                "Product added successfully."
            );


            header(
                "Location: products.php"
            );

            exit();


        } else {


            setFlashMessage(
                "error",
                "Unable to add product."
            );

        }

    }

}

?>

<?php include "../includes/header.php"; ?>

<section class="product-form-page">

    <div class="page-title">

        <h1>
            Add Product
        </h1>

        <p>
            Create a new product listing.
        </p>

    </div>


    <div class="form-box">

        <form
            method="POST"
            enctype="multipart/form-data"
        >


            <div class="form-group">

                <label for="product_name">
                    Product Name
                </label>

                <input
                    type="text"
                    id="product_name"
                    name="product_name"
                    maxlength="150"
                    required
                >

            </div>


            <div class="form-group">

                <label for="category_id">
                    Category
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <?php while (
                        $category =
                        $categories->fetch_assoc()
                    ): ?>

                        <option
                            value="<?= $category['category_id']; ?>"
                        >

                            <?= htmlspecialchars(
                                $category['category_name']
                            ); ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="6"
                ></textarea>

            </div>


            <div class="form-group">

                <label for="price">
                    Price (RM)
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    step="0.01"
                    min="0"
                    required
                >

            </div>


            <div class="form-group">

                <label for="stock_quantity">
                    Stock Quantity
                </label>

                <input
                    type="number"
                    id="stock_quantity"
                    name="stock_quantity"
                    min="0"
                    required
                >

            </div>


            <div class="form-group">

                <label for="image">
                    Product Image
                </label>

                <input
                    type="file"
                    id="image"
                    name="image"
                    accept=".jpg,.jpeg,.png,.webp"
                >

            </div>


            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                >

                    <option value="Available">
                        Available
                    </option>

                    <option value="Out of Stock">
                        Out of Stock
                    </option>

                    <option value="Hidden">
                        Hidden
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="btn-primary"
            >
                Add Product
            </button>


        </form>

    </div>

</section>

<?php include "../includes/footer.php"; ?>