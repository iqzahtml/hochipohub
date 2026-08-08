<?php

require_once "../config.php";
require_once "../database/db.php";
require_once "../includes/session.php";
require_once "../includes/functions.php";



$pageTitle = "Add Product";



requireRole('vendor');



$userID = currentUserID();





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
    $vendor['vendor_id'];





/*
|--------------------------------------------------------------------------
| Categories
|--------------------------------------------------------------------------
*/

$categories =
    $conn->query("

        SELECT *

        FROM categories

        ORDER BY category_name ASC

    ");





/*
|--------------------------------------------------------------------------
| Add Product
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {



    $productName =
        trim($_POST['product_name'] ?? '');



    $description =
        trim($_POST['description'] ?? '');



    $price =
        (float)($_POST['price'] ?? 0);



    $stock =
        (int)($_POST['stock_quantity'] ?? 0);



    $categoryID =
        (int)($_POST['category_id'] ?? 0);



    $status =
        $_POST['status'] ?? 'Available';





    /*
    |--------------------------------------------------------------------------
    | Image
    |--------------------------------------------------------------------------
    */

    $imageName = '';



    if (
        isset($_FILES['image'])
        &&
        $_FILES['image']['error'] === UPLOAD_ERR_OK
    ) {



        $extension =
            strtolower(
                pathinfo(
                    $_FILES['image']['name'],
                    PATHINFO_EXTENSION
                )
            );



        $allowed = [
            'jpg',
            'jpeg',
            'png',
            'webp'
        ];



        if (
            in_array(
                $extension,
                $allowed,
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
                !is_dir($uploadDirectory)
            ) {

                mkdir(
                    $uploadDirectory,
                    0777,
                    true
                );

            }



            move_uploaded_file(

                $_FILES['image']['tmp_name'],

                $uploadDirectory .
                $imageName

            );

        }

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

            status,

            created_at

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
            ?,
            NOW()

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



        setFlashMessage(

            'success',

            'Product added successfully.'

        );



        header(
            "Location: products.php"
        );

        exit();



    } else {



        setFlashMessage(

            'error',

            'Unable to add product.'

        );

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


<label>
Product Name
</label>


<input

type="text"

name="product_name"

required

>

</div>





<div class="form-group">


<label>
Category
</label>


<select
name="category_id"
required
>


<option value="">
Select Category
</option>



<?php while ($category = $categories->fetch_assoc()): ?>


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


<label>
Description
</label>


<textarea

name="description"

rows="6"

required

></textarea>


</div>





<div class="form-group">


<label>
Price (RM)
</label>


<input

type="number"

name="price"

step="0.01"

min="0"

required

>


</div>





<div class="form-group">


<label>
Stock Quantity
</label>


<input

type="number"

name="stock_quantity"

min="0"

required

>


</div>





<div class="form-group">


<label>
Product Image
</label>


<input

type="file"

name="image"

accept=".jpg,.jpeg,.png,.webp"

>


</div>





<div class="form-group">


<label>
Status
</label>


<select name="status">


<option value="Available">
Available
</option>


<option value="Unavailable">
Unavailable
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