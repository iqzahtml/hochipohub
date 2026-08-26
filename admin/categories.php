<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN CATEGORIES
|--------------------------------------------------------------------------
| File: admin/categories.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Location: ../dashboard.php');
    exit;
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();


/*
|--------------------------------------------------------------------------
| HELPER
|--------------------------------------------------------------------------
*/

if (!function_exists('categoryEscape')) {

    function categoryEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| VARIABLES
|--------------------------------------------------------------------------
*/

$errors = [];
$success = '';
$editCategory = null;


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

$uploadDirectory = __DIR__ . '/../uploads/categories/';

if (!is_dir($uploadDirectory)) {
    @mkdir($uploadDirectory, 0755, true);
}


/*
|--------------------------------------------------------------------------
| DELETE IMAGE
|--------------------------------------------------------------------------
*/

function deleteCategoryImage(
    ?string $imageName,
    string $uploadDirectory
): void {

    if (empty($imageName)) {
        return;
    }

    $file = $uploadDirectory . basename($imageName);

    if (
        file_exists($file) &&
        is_file($file)
    ) {
        @unlink($file);
    }
}


/*
|--------------------------------------------------------------------------
| UPLOAD IMAGE
|--------------------------------------------------------------------------
*/

function uploadCategoryImage(
    array $file,
    string $uploadDirectory,
    array &$errors
): ?string {

    if (
        !isset($file['error']) ||
        $file['error'] === UPLOAD_ERR_NO_FILE
    ) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {

        $errors[] = 'There was a problem uploading the category image.';
        return null;
    }

    $maxSize = 5 * 1024 * 1024;

    if (
        !isset($file['size']) ||
        $file['size'] > $maxSize
    ) {

        $errors[] = 'Category image must not exceed 5MB.';
        return null;
    }

    if (
        empty($file['tmp_name']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {

        $errors[] = 'Invalid uploaded image.';
        return null;
    }

    if (!class_exists('finfo')) {

        $errors[] = 'PHP Fileinfo extension is required for image uploads.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);

    $mime = $finfo->file($file['tmp_name']);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'
    ];

    if (!$mime || !isset($allowed[$mime])) {

        $errors[] = 'Only JPG, PNG and WEBP images are allowed.';
        return null;
    }

    $extension = $allowed[$mime];

    $fileName =
        'category_' .
        bin2hex(random_bytes(12)) .
        '.' .
        $extension;

    $destination =
        $uploadDirectory .
        $fileName;

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        $errors[] = 'Failed to save category image.';
        return null;
    }

    return $fileName;
}


/*
|--------------------------------------------------------------------------
| POST ACTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $submittedToken =
        $_POST['csrf_token'] ?? '';

    if (
        empty($submittedToken) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        $errors[] =
            'Invalid security token. Please refresh the page and try again.';
    }

    $action =
        $_POST['action'] ?? '';


    /*
    |--------------------------------------------------------------------------
    | ADD CATEGORY
    |--------------------------------------------------------------------------
    */

    if (
        empty($errors) &&
        $action === 'add'
    ) {

        $categoryName =
            trim(
                $_POST['category_name'] ?? ''
            );

        if ($categoryName === '') {

            $errors[] =
                'Category name is required.';
        }

        if (
            mb_strlen($categoryName) > 100
        ) {

            $errors[] =
                'Category name cannot exceed 100 characters.';
        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICATE
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        SELECT category_id
                        FROM categories
                        WHERE LOWER(category_name) = LOWER(?)
                        LIMIT 1
                    ");

                $stmt->execute([
                    $categoryName
                ]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {

                    $errors[] =
                        'This category already exists.';
                }

            } catch (Throwable $e) {

                $errors[] =
                    'Unable to validate category name.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        $imageName = null;

        if (
            empty($errors) &&
            isset($_FILES['category_image'])
        ) {

            $imageName =
                uploadCategoryImage(
                    $_FILES['category_image'],
                    $uploadDirectory,
                    $errors
                );
        }


        /*
        |--------------------------------------------------------------------------
        | INSERT
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        INSERT INTO categories
                        (
                            category_name,
                            category_image
                        )
                        VALUES
                        (
                            ?,
                            ?
                        )
                    ");

                $stmt->execute([
                    $categoryName,
                    $imageName
                ]);

                header(
                    'Location: categories.php?success=added'
                );

                exit;

            } catch (Throwable $e) {

                if (!empty($imageName)) {

                    deleteCategoryImage(
                        $imageName,
                        $uploadDirectory
                    );
                }

                $errors[] =
                    'Unable to add category. Please try again.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif (
        empty($errors) &&
        $action === 'edit'
    ) {

        $categoryId =
            (int) (
                $_POST['category_id'] ?? 0
            );

        $categoryName =
            trim(
                $_POST['category_name'] ?? ''
            );

        if ($categoryId <= 0) {

            $errors[] =
                'Invalid category ID.';
        }

        if ($categoryName === '') {

            $errors[] =
                'Category name is required.';
        }

        if (
            mb_strlen($categoryName) > 100
        ) {

            $errors[] =
                'Category name cannot exceed 100 characters.';
        }


        /*
        |--------------------------------------------------------------------------
        | CURRENT CATEGORY
        |--------------------------------------------------------------------------
        */

        $existingCategory = null;

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        SELECT
                            category_id,
                            category_name,
                            category_image
                        FROM categories
                        WHERE category_id = ?
                        LIMIT 1
                    ");

                $stmt->execute([
                    $categoryId
                ]);

                $existingCategory =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );

                if (!$existingCategory) {

                    $errors[] =
                        'Category was not found.';
                }

            } catch (Throwable $e) {

                $errors[] =
                    'Unable to load category.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICATE
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        SELECT category_id
                        FROM categories
                        WHERE LOWER(category_name) = LOWER(?)
                        AND category_id != ?
                        LIMIT 1
                    ");

                $stmt->execute([
                    $categoryName,
                    $categoryId
                ]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {

                    $errors[] =
                        'Another category already uses this name.';
                }

            } catch (Throwable $e) {

                $errors[] =
                    'Unable to validate category name.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | NEW IMAGE
        |--------------------------------------------------------------------------
        */

        $oldImage =
            $existingCategory['category_image']
            ?? null;

        $newImage =
            $oldImage;

        $hasNewImage =
            false;

        if (
            empty($errors) &&
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error']
                !== UPLOAD_ERR_NO_FILE
        ) {

            $uploaded =
                uploadCategoryImage(
                    $_FILES['category_image'],
                    $uploadDirectory,
                    $errors
                );

            if (
                empty($errors) &&
                !empty($uploaded)
            ) {

                $newImage =
                    $uploaded;

                $hasNewImage =
                    true;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        UPDATE categories
                        SET
                            category_name = ?,
                            category_image = ?
                        WHERE category_id = ?
                    ");

                $stmt->execute([
                    $categoryName,
                    $newImage,
                    $categoryId
                ]);

                if (
                    $hasNewImage &&
                    !empty($oldImage) &&
                    $oldImage !== $newImage
                ) {

                    deleteCategoryImage(
                        $oldImage,
                        $uploadDirectory
                    );
                }

                header(
                    'Location: categories.php?success=updated'
                );

                exit;

            } catch (Throwable $e) {

                if (
                    $hasNewImage &&
                    !empty($newImage)
                ) {

                    deleteCategoryImage(
                        $newImage,
                        $uploadDirectory
                    );
                }

                $errors[] =
                    'Unable to update category. Please try again.';
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CATEGORY
    |--------------------------------------------------------------------------
    */

    elseif (
        empty($errors) &&
        $action === 'delete'
    ) {

        $categoryId =
            (int) (
                $_POST['category_id'] ?? 0
            );

        if ($categoryId <= 0) {

            $errors[] =
                'Invalid category ID.';
        }

        $category = null;

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        SELECT
                            category_id,
                            category_name,
                            category_image
                        FROM categories
                        WHERE category_id = ?
                        LIMIT 1
                    ");

                $stmt->execute([
                    $categoryId
                ]);

                $category =
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    );

                if (!$category) {

                    $errors[] =
                        'Category was not found.';
                }

            } catch (Throwable $e) {

                $errors[] =
                    'Unable to load category.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT CHECK
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        SELECT COUNT(*)
                        FROM products
                        WHERE category_id = ?
                    ");

                $stmt->execute([
                    $categoryId
                ]);

                $productCount =
                    (int)
                    $stmt->fetchColumn();

                if ($productCount > 0) {

                    $errors[] =
                        'Cannot delete "' .
                        $category['category_name'] .
                        '" because it contains ' .
                        $productCount .
                        (
                            $productCount === 1
                                ? ' product.'
                                : ' products.'
                        );
                }

            } catch (Throwable $e) {

                $errors[] =
                    'Unable to check products using this category.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DELETE
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $db->beginTransaction();

                $stmt =
                    $db->prepare("
                        DELETE FROM categories
                        WHERE category_id = ?
                    ");

                $stmt->execute([
                    $categoryId
                ]);

                $db->commit();

                deleteCategoryImage(
                    $category['category_image']
                    ?? null,
                    $uploadDirectory
                );

                header(
                    'Location: categories.php?success=deleted'
                );

                exit;

            } catch (Throwable $e) {

                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                $errors[] =
                    'Unable to delete category. Please try again.';
            }
        }
    }
}


/*
|--------------------------------------------------------------------------
| SUCCESS
|--------------------------------------------------------------------------
*/

if (isset($_GET['success'])) {

    switch ($_GET['success']) {

        case 'added':

            $success =
                'Category added successfully.';

            break;

        case 'updated':

            $success =
                'Category updated successfully.';

            break;

        case 'deleted':

            $success =
                'Category deleted successfully.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| EDIT CATEGORY
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['edit']) &&
    (int) $_GET['edit'] > 0
) {

    $editId =
        (int) $_GET['edit'];

    try {

        $stmt =
            $db->prepare("
                SELECT
                    category_id,
                    category_name,
                    category_image,
                    created_at
                FROM categories
                WHERE category_id = ?
                LIMIT 1
            ");

        $stmt->execute([
            $editId
        ]);

        $editCategory =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );

        if (!$editCategory) {

            $errors[] =
                'Category selected for editing was not found.';
        }

    } catch (Throwable $e) {

        $errors[] =
            'Unable to load category for editing.';
    }
}


/*
|--------------------------------------------------------------------------
| ALL CATEGORIES
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $sql = "
        SELECT
            c.category_id,
            c.category_name,
            c.category_image,
            c.created_at,
            COUNT(p.product_id) AS product_count
        FROM categories c
        LEFT JOIN products p
            ON p.category_id = c.category_id
    ";

    $params = [];

    if ($search !== '') {

        $sql .= "
            WHERE c.category_name LIKE ?
        ";

        $params[] =
            '%' .
            $search .
            '%';
    }

    $sql .= "
        GROUP BY
            c.category_id,
            c.category_name,
            c.category_image,
            c.created_at
        ORDER BY
            c.category_id DESC
    ";

    $stmt =
        $db->prepare(
            $sql
        );

    $stmt->execute(
        $params
    );

    $categories =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    $categories = [];

    $errors[] =
        'Unable to load categories.';
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalCategories = 0;
$categoriesInUse = 0;
$totalProductsAssigned = 0;

try {

    $totalCategories =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM categories
            ")
            ->fetchColumn();


    $categoriesInUse =
        (int)
        $db
            ->query("
                SELECT COUNT(DISTINCT category_id)
                FROM products
                WHERE category_id IS NOT NULL
            ")
            ->fetchColumn();


    $totalProductsAssigned =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM products
                WHERE category_id IS NOT NULL
            ")
            ->fetchColumn();

} catch (Throwable $e) {

    error_log(
        'Category statistics error: ' .
        $e->getMessage()
    );
}

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
        Categories | HochipoHub Admin
    </title>


    <!-- GOOGLE FONT -->

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >


    <!-- PROJECT CSS -->

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /* ==========================================================
           ROOT
        ========================================================== */

        :root {

            --category-sidebar-width: 260px;

            --blue:
                #2563eb;

            --navy:
                #08265a;

            --border:
                #dce7f3;

            --muted:
                #8294b3;

        }


        /* ==========================================================
           GLOBAL
        ========================================================== */

        * {
            box-sizing: border-box;
        }


        html,
        body {

            margin: 0;
            padding: 0;

            min-height: 100%;

            font-family:
                'Poppins',
                sans-serif;

            background:
                #eef5fd;

        }


        body {
            overflow-x: hidden;
        }


        button,
        input,
        select {

            font-family:
                inherit;

        }


        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {

            font-family:
                'Poppins',
                sans-serif !important;

        }


        /* ==========================================================
           MAIN
        ========================================================== */

        .categories-main {

            min-height:
                100vh;

            margin-left:
                var(
                    --category-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --category-sidebar-width
                    )
                );

            background:

                radial-gradient(
                    circle at 90% 2%,
                    rgba(
                        37,
                        99,
                        235,
                        .12
                    ),
                    transparent 24%
                ),

                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );

        }


        .categories-content {

            width: 100%;

            max-width:
                1450px;

            margin:
                0 auto;

            padding:
                38px
                35px
                70px;

        }


        /* ==========================================================
           HERO
        ========================================================== */

        .categories-hero {

            position:
                relative;

            min-height:
                155px;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            padding:
                34px
                38px;

            margin-bottom:
                26px;

            color:
                #ffffff;

            background:

                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius:
                26px;

            box-shadow:

                0
                20px
                45px
                rgba(
                    18,
                    70,
                    150,
                    .15
                );

        }


        .categories-hero::before {

            content: "";

            position:
                absolute;

            width:
                260px;

            height:
                260px;

            right:
                -70px;

            top:
                -140px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .07
                );

        }


        .categories-hero::after {

            content: "";

            position:
                absolute;

            width:
                170px;

            height:
                170px;

            right:
                155px;

            bottom:
                -110px;

            border-radius:
                50%;

            background:
                rgba(
                    255,
                    255,
                    255,
                    .045
                );

        }


        .categories-hero-text {

            position:
                relative;

            z-index:
                2;

        }


        .categories-hero h1 {

            margin:
                0
                0
                8px;

            color:
                #ffffff;

            font-size:
                38px;

            line-height:
                1.05;

            font-weight:
                800;

            letter-spacing:
                -1.5px;

        }


        .categories-hero p {

            margin: 0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size:
                14px;

            font-weight:
                500;

        }


        .categories-hero-icon {

            position:
                relative;

            z-index:
                2;

            width:
                82px;

            height:
                82px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .28
                );

            border-radius:
                22px;

            background:

                linear-gradient(
                    145deg,
                    rgba(
                        255,
                        255,
                        255,
                        .20
                    ),
                    rgba(
                        255,
                        255,
                        255,
                        .08
                    )
                );

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .25
                ),

                0
                12px
                30px
                rgba(
                    0,
                    35,
                    100,
                    .18
                );

            font-size:
                34px;

            line-height:
                1;

        }


        /* ==========================================================
           ALERT
        ========================================================== */

        .categories-alert {

            margin-bottom:
                22px;

            padding:
                14px
                17px;

            border-radius:
                12px;

            font-size:
                11px;

            font-weight:
                600;

            line-height:
                1.6;

        }


        .categories-alert.success {

            color:
                #166534;

            background:
                #ecfdf5;

            border:
                1px solid
                #bbf7d0;

        }


        .categories-alert.error {

            color:
                #991b1b;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        .categories-alert ul {

            margin:
                7px
                0
                0;

            padding-left:
                20px;

        }


        /* ==========================================================
           STATS
        ========================================================== */

        .categories-stats {

            display:
                grid;

            grid-template-columns:

                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap:
                18px;

            margin-bottom:
                30px;

        }


        .category-stat {

            position:
                relative;

            min-height:
                150px;

            overflow:
                hidden;

            padding:
                26px
                24px;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --border
                );

            border-top:
                4px solid
                #2563eb;

            border-radius:
                20px;

            box-shadow:

                0
                12px
                28px
                rgba(
                    20,
                    60,
                    120,
                    .055
                );

        }


        .category-stat::after {

            content: "";

            position:
                absolute;

            width:
                110px;

            height:
                110px;

            right:
                -29px;

            bottom:
                -45px;

            border-radius:
                50%;

            background:
                #edf4ff;

        }


        .category-stat.used {

            border-top-color:
                #16a34a;

        }


        .category-stat.used::after {

            background:
                #eaf9ef;

        }


        .category-stat.products {

            border-top-color:
                #8b5cf6;

        }


        .category-stat.products::after {

            background:
                #f4efff;

        }


        .category-stat-label {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            margin-bottom:
                15px;

            color:
                #61728e;

            font-size:
                10px;

            font-weight:
                800;

            letter-spacing:
                .75px;

            text-transform:
                uppercase;

        }


        .category-stat-value {

            position:
                relative;

            z-index:
                2;

            display:
                block;

            color:
                #0b326d;

            font-size:
                32px;

            line-height:
                1;

            font-weight:
                800;

        }


        /* ==========================================================
           MAIN PANEL
        ========================================================== */

        .categories-panel {

            overflow:
                hidden;

            background:
                #ffffff;

            border:
                1px solid
                var(
                    --border
                );

            border-radius:
                24px;

            box-shadow:

                0
                14px
                35px
                rgba(
                    24,
                    64,
                    120,
                    .055
                );

        }


        .categories-panel-header {

            min-height:
                110px;

            padding:
                26px
                30px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                20px;

            border-bottom:
                1px solid
                #e7edf5;

        }


        .categories-panel-title {

            display:
                flex;

            align-items:
                center;

            gap:
                16px;

        }


        .categories-panel-icon {

            width:
                53px;

            height:
                53px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                16px;

            background:

                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size:
                22px;

            line-height:
                1;

            box-shadow:

                0
                9px
                20px
                rgba(
                    37,
                    99,
                    235,
                    .22
                );

        }


        .categories-panel-title h2 {

            margin:
                0
                0
                5px;

            color:
                #092e65;

            font-size:
                20px;

            font-weight:
                800;

        }


        .categories-panel-title p {

            margin:
                0;

            color:
                #8999b4;

            font-size:
                11px;

        }


        /* ==========================================================
           ADD BUTTON
        ========================================================== */

        .category-add-btn {

            min-height:
                43px;

            padding:
                0
                18px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            border:
                none;

            border-radius:
                10px;

            box-shadow:

                0
                7px
                15px
                rgba(
                    37,
                    99,
                    235,
                    .20
                );

            font-size:
                10px;

            font-weight:
                800;

            cursor:
                pointer;

        }


        /* ==========================================================
           FILTER
        ========================================================== */

        .categories-filter-wrapper {

            padding:
                22px
                28px;

            background:
                #fbfdff;

            border-bottom:
                1px solid
                #edf1f6;

        }


        .categories-filter {

            display:
                grid;

            grid-template-columns:

                minmax(
                    250px,
                    1fr
                )

                auto
                auto;

            gap:
                10px;

        }


        .categories-filter input {

            width:
                100%;

            height:
                43px;

            padding:
                0
                13px;

            color:
                #26354e;

            background:
                #ffffff;

            outline:
                none;

            border:
                1px solid
                #d8e3ef;

            border-radius:
                10px;

            font-size:
                10px;

        }


        .categories-filter input:focus {

            border-color:
                #3b82f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    59,
                    130,
                    246,
                    .08
                );

        }


        /* ==========================================================
           BUTTON
        ========================================================== */

        .category-btn {

            min-height:
                43px;

            padding:
                0
                17px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                10px;

            font-size:
                10px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

            white-space:
                nowrap;

        }


        .category-btn-primary {

            color:
                #ffffff;

            background:

                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );

            border:
                0;

        }


        .category-btn-secondary {

            color:
                #66758b;

            background:
                #ffffff;

            border:
                1px solid
                #d7e2ee;

        }


        /* ==========================================================
           TABLE
        ========================================================== */

        .category-table-wrapper {

            width:
                100%;

            overflow-x:
                auto;

        }


        .category-table {

            width:
                100%;

            min-width:
                850px;

            border-collapse:
                collapse;

        }


        .category-table thead {

            background:
                #f6f9fd;

        }


        .category-table th {

            height:
                44px;

            padding:
                0
                18px;

            color:
                #65758f;

            border-bottom:
                1px solid
                #dfe7f0;

            font-size:
                8px;

            font-weight:
                800;

            text-align:
                left;

            letter-spacing:
                .55px;

            text-transform:
                uppercase;

        }


        .category-table td {

            padding:
                16px
                18px;

            color:
                #435169;

            border-bottom:
                1px solid
                #edf1f6;

            font-size:
                9px;

            vertical-align:
                middle;

        }


        .category-table tbody tr:hover {

            background:
                #f9fbff;

        }


        /* ==========================================================
           CATEGORY ITEM
        ========================================================== */

        .category-info {

            display:
                flex;

            align-items:
                center;

            gap:
                11px;

        }


        .category-thumb {

            width:
                44px;

            height:
                44px;

            flex-shrink:
                0;

            overflow:
                hidden;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                11px;

            font-size:
                18px;

        }


        .category-thumb img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        .category-name {

            margin-bottom:
                3px;

            color:
                #112b55;

            font-size:
                10px;

            font-weight:
                800;

        }


        .category-sub {

            color:
                #8897ac;

            font-size:
                8px;

        }


        .category-id {

            color:
                #8796ac;

            font-weight:
                800;

        }


        /* ==========================================================
           PRODUCT BADGE
        ========================================================== */

        .category-product-badge {

            min-height:
                27px;

            padding:
                0
                9px;

            display:
                inline-flex;

            align-items:
                center;

            color:
                #2563eb;

            background:
                #eff6ff;

            border:
                1px solid
                #d6e7ff;

            border-radius:
                999px;

            font-size:
                8px;

            font-weight:
                800;

        }


        .category-product-badge.empty {

            color:
                #64748b;

            background:
                #f1f5f9;

            border-color:
                #e2e8f0;

        }


        /* ==========================================================
           ACTION
        ========================================================== */

        .category-actions {

            display:
                flex;

            align-items:
                center;

            gap:
                7px;

        }


        .category-actions form {

            margin:
                0;

        }


        .category-action {

            min-height:
                32px;

            padding:
                0
                11px;

            display:
                inline-flex;

            align-items:
                center;

            justify-content:
                center;

            border-radius:
                8px;

            font-size:
                8px;

            font-weight:
                800;

            text-decoration:
                none;

            cursor:
                pointer;

        }


        .category-edit {

            color:
                #1d4ed8;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

        }


        .category-delete {

            color:
                #b91c1c;

            background:
                #fff1f2;

            border:
                1px solid
                #fecdd3;

        }


        .category-delete.disabled {

            color:
                #94a3b8;

            background:
                #f1f5f9;

            border-color:
                #e2e8f0;

            cursor:
                not-allowed;

        }


        /* ==========================================================
           EMPTY STATE
        ========================================================== */

        .category-empty {

            padding:
                70px
                20px;

            text-align:
                center;

        }


        .category-empty-icon {

            width:
                60px;

            height:
                60px;

            margin:
                0
                auto
                15px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:
                #eff6ff;

            border:
                1px solid
                #dbeafe;

            border-radius:
                17px;

            font-size:
                27px;

        }


        .category-empty h3 {

            margin:
                0
                0
                6px;

            color:
                #49617f;

            font-size:
                14px;

            font-weight:
                800;

        }


        .category-empty p {

            margin:
                0;

            color:
                #94a3b8;

            font-size:
                10px;

        }


        /* ==========================================================
           GLASS MODAL OVERLAY
        ========================================================== */

        .category-modal-overlay {

            position:
                fixed;

            inset:
                0;

            z-index:
                99999;

            padding:
                20px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:

                radial-gradient(
                    circle at 20% 20%,
                    rgba(
                        59,
                        130,
                        246,
                        .18
                    ),
                    transparent 35%
                ),

                radial-gradient(
                    circle at 80% 80%,
                    rgba(
                        96,
                        165,
                        250,
                        .12
                    ),
                    transparent 35%
                ),

                rgba(
                    6,
                    30,
                    70,
                    .48
                );

            -webkit-backdrop-filter:
                blur(14px)
                saturate(125%);

            backdrop-filter:
                blur(14px)
                saturate(125%);

        }


        /* ==========================================================
           GLASS MODAL
        ========================================================== */

        .category-modal {

            position:
                relative;

            width:
                100%;

            max-width:
                550px;

            max-height:
                90vh;

            overflow-y:
                auto;

            padding:
                30px;

            background:

                linear-gradient(
                    135deg,
                    rgba(
                        255,
                        255,
                        255,
                        .76
                    ),
                    rgba(
                        225,
                        239,
                        255,
                        .55
                    )
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .72
                );

            border-radius:
                27px;

            -webkit-backdrop-filter:
                blur(28px)
                saturate(175%);

            backdrop-filter:
                blur(28px)
                saturate(175%);

            box-shadow:

                0
                40px
                100px
                rgba(
                    1,
                    20,
                    60,
                    .34
                ),

                0
                10px
                30px
                rgba(
                    37,
                    99,
                    235,
                    .10
                ),

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .95
                );

        }


        .category-modal::before {

            content: "";

            position:
                absolute;

            top:
                0;

            left:
                0;

            right:
                0;

            height:
                120px;

            pointer-events:
                none;

            border-radius:
                27px
                27px
                0
                0;

            background:

                linear-gradient(
                    180deg,
                    rgba(
                        255,
                        255,
                        255,
                        .32
                    ),
                    transparent
                );

        }


        .category-modal::after {

            content: "";

            position:
                absolute;

            width:
                160px;

            height:
                160px;

            top:
                -90px;

            right:
                -70px;

            border-radius:
                50%;

            pointer-events:
                none;

            background:

                rgba(
                    255,
                    255,
                    255,
                    .16
                );

        }


        /* ==========================================================
           MODAL HEADER
        ========================================================== */

        .category-modal-header {

            position:
                relative;

            z-index:
                2;

            display:
                flex;

            align-items:
                center;

            justify-content:
                space-between;

            gap:
                15px;

            margin-bottom:
                28px;

        }


        .category-modal-title {

            display:
                flex;

            align-items:
                center;

            gap:
                14px;

        }


        .category-modal-icon {

            width:
                48px;

            height:
                48px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            background:

                linear-gradient(
                    135deg,
                    rgba(
                        20,
                        118,
                        232,
                        .96
                    ),
                    rgba(
                        29,
                        149,
                        243,
                        .90
                    )
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .45
                );

            border-radius:
                14px;

            box-shadow:

                0
                10px
                25px
                rgba(
                    37,
                    99,
                    235,
                    .24
                ),

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .35
                );

            font-size:
                21px;

        }


        .category-modal-title h2 {

            margin:
                0
                0
                4px;

            color:
                #08265a;

            font-size:
                20px;

            font-weight:
                800;

            letter-spacing:
                -.4px;

        }


        .category-modal-title p {

            margin:
                0;

            color:
                #7285a5;

            font-size:
                10px;

            font-weight:
                500;

        }


        /* ==========================================================
           CLOSE BUTTON
        ========================================================== */

        .category-modal-close {

            width:
                39px;

            height:
                39px;

            flex-shrink:
                0;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            padding:
                0;

            color:
                #45617f;

            background:

                rgba(
                    255,
                    255,
                    255,
                    .38
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .78
                );

            border-radius:
                12px;

            -webkit-backdrop-filter:
                blur(12px);

            backdrop-filter:
                blur(12px);

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .90
                ),

                0
                5px
                14px
                rgba(
                    20,
                    60,
                    110,
                    .08
                );

            font-size:
                20px;

            font-weight:
                500;

            text-decoration:
                none;

            cursor:
                pointer;

            transition:
                .2s ease;

        }


        .category-modal-close:hover {

            background:

                rgba(
                    255,
                    255,
                    255,
                    .72
                );

            transform:
                translateY(-1px);

        }


        /* ==========================================================
           FORM
        ========================================================== */

        .category-modal form {

            position:
                relative;

            z-index:
                2;

        }


        .category-form-group {

            margin-bottom:
                20px;

        }


        .category-form-group label {

            display:
                block;

            margin-bottom:
                8px;

            color:
                #253c5d;

            font-size:
                10px;

            font-weight:
                700;

        }


        .required {

            color:
                #ef4444;

        }


        /* ==========================================================
           GLASS INPUT
        ========================================================== */

        .category-form-group input[type="text"],
        .category-form-group input[type="file"] {

            width:
                100%;

            min-height:
                48px;

            padding:
                11px
                14px;

            color:
                #172033;

            outline:
                none;

            background:

                rgba(
                    255,
                    255,
                    255,
                    .40
                );

            border:
                1px solid
                rgba(
                    170,
                    197,
                    229,
                    .65
                );

            border-radius:
                13px;

            -webkit-backdrop-filter:
                blur(12px);

            backdrop-filter:
                blur(12px);

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .90
                ),

                0
                4px
                15px
                rgba(
                    31,
                    80,
                    140,
                    .035
                );

            font-size:
                10px;

            transition:
                .2s ease;

        }


        .category-form-group input::placeholder {

            color:
                #8191a9;

        }


        .category-form-group input:focus {

            background:

                rgba(
                    255,
                    255,
                    255,
                    .70
                );

            border-color:
                rgba(
                    37,
                    99,
                    235,
                    .78
                );

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    37,
                    99,
                    235,
                    .10
                ),

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .95
                );

        }


        /* ==========================================================
           FILE BUTTON
        ========================================================== */

        .category-form-group
        input[type="file"]::file-selector-button {

            margin-right:
                12px;

            padding:
                8px
                13px;

            color:
                #174a94;

            background:

                rgba(
                    255,
                    255,
                    255,
                    .70
                );

            border:
                1px solid
                rgba(
                    155,
                    187,
                    225,
                    .68
                );

            border-radius:
                8px;

            font-family:
                'Poppins',
                sans-serif;

            font-size:
                9px;

            font-weight:
                700;

            cursor:
                pointer;

        }


        .category-form-help {

            display:
                block;

            margin-top:
                7px;

            color:
                #7386a3;

            font-size:
                8px;

            font-weight:
                500;

        }


        /* ==========================================================
           IMAGE PREVIEW
        ========================================================== */

        .category-current-image {

            width:
                90px;

            height:
                90px;

            display:
                flex;

            align-items:
                center;

            justify-content:
                center;

            overflow:
                hidden;

            background:

                rgba(
                    255,
                    255,
                    255,
                    .40
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .78
                );

            border-radius:
                15px;

            -webkit-backdrop-filter:
                blur(12px);

            backdrop-filter:
                blur(12px);

            box-shadow:

                0
                8px
                20px
                rgba(
                    20,
                    60,
                    110,
                    .10
                ),

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .88
                );

            font-size:
                30px;

        }


        .category-current-image img {

            width:
                100%;

            height:
                100%;

            object-fit:
                cover;

        }


        /* ==========================================================
           MODAL BUTTONS
        ========================================================== */

        .category-modal-actions {

            display:
                flex;

            align-items:
                center;

            justify-content:
                flex-end;

            gap:
                10px;

            margin-top:
                9px;

        }


        .category-modal-actions
        .category-btn-secondary {

            color:
                #405978;

            background:

                rgba(
                    255,
                    255,
                    255,
                    .40
                );

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .80
                );

            -webkit-backdrop-filter:
                blur(10px);

            backdrop-filter:
                blur(10px);

            box-shadow:

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .90
                );

        }


        .category-modal-actions
        .category-btn-secondary:hover {

            background:

                rgba(
                    255,
                    255,
                    255,
                    .75
                );

        }


        .category-modal-actions
        .category-btn-primary {

            color:
                #ffffff;

            border:
                1px solid
                rgba(
                    255,
                    255,
                    255,
                    .20
                );

            background:

                linear-gradient(
                    135deg,
                    #2673ed,
                    #155ed5
                );

            box-shadow:

                0
                10px
                22px
                rgba(
                    37,
                    99,
                    235,
                    .25
                ),

                inset
                0
                1px
                0
                rgba(
                    255,
                    255,
                    255,
                    .25
                );

        }


        .category-modal-actions
        .category-btn-primary:hover {

            transform:
                translateY(-1px);

            box-shadow:

                0
                13px
                27px
                rgba(
                    37,
                    99,
                    235,
                    .32
                );

        }


        /* ==========================================================
           RESPONSIVE
        ========================================================== */

        @media (max-width: 1000px) {

            .categories-stats {

                grid-template-columns:
                    1fr;

            }

        }


        @media (max-width: 900px) {

            :root {

                --category-sidebar-width:
                    0px;

            }


            .categories-main {

                margin-left:
                    0;

                width:
                    100%;

            }


            .categories-content {

                padding:
                    25px
                    20px
                    50px;

            }

        }


        @media (max-width: 650px) {

            .categories-content {

                padding:
                    18px
                    13px
                    40px;

            }


            .categories-hero {

                min-height:
                    auto;

                padding:
                    25px
                    21px;

                border-radius:
                    20px;

            }


            .categories-hero h1 {

                font-size:
                    27px;

            }


            .categories-hero p {

                max-width:
                    230px;

                font-size:
                    11px;

            }


            .categories-hero-icon {

                width:
                    55px;

                height:
                    55px;

                border-radius:
                    15px;

                font-size:
                    24px;

            }


            .categories-panel-header {

                flex-direction:
                    column;

                align-items:
                    flex-start;

                padding:
                    20px
                    17px;

            }


            .category-add-btn {

                width:
                    100%;

            }


            .categories-filter {

                grid-template-columns:
                    1fr;

            }


            .category-btn {

                width:
                    100%;

            }


            .category-modal {

                padding:
                    22px;

                border-radius:
                    22px;

            }


            .category-modal-actions {

                flex-direction:
                    column-reverse;

            }


            .category-modal-actions
            .category-btn {

                width:
                    100%;

            }

        }

    </style>

</head>


<body>


<div class="admin-wrapper">


    <?php

    require_once __DIR__ .
        '/../includes/admin_sidebar.php';

    ?>


    <main class="categories-main">


        <div class="categories-content">


            <!-- =====================================================
                 HERO
            ====================================================== -->

            <section class="categories-hero">


                <div class="categories-hero-text">

                    <h1>
                        Categories
                    </h1>

                    <p>
                        Manage product categories across the HochipoHub marketplace.
                    </p>

                </div>


                <div class="categories-hero-icon">

                    🗂️

                </div>


            </section>


            <!-- =====================================================
                 ERRORS
            ====================================================== -->

            <?php if (!empty($errors)): ?>


                <div
                    class="
                        categories-alert
                        error
                    "
                >

                    <strong>
                        Please fix the following:
                    </strong>


                    <ul>

                        <?php foreach ($errors as $error): ?>

                            <li>

                                <?= categoryEscape(
                                    $error
                                ) ?>

                            </li>

                        <?php endforeach; ?>

                    </ul>


                </div>


            <?php endif; ?>


            <!-- =====================================================
                 SUCCESS
            ====================================================== -->

            <?php if ($success !== ''): ?>


                <div
                    class="
                        categories-alert
                        success
                    "
                >

                    <?= categoryEscape(
                        $success
                    ) ?>

                </div>


            <?php endif; ?>


            <!-- =====================================================
                 STATISTICS
            ====================================================== -->

            <section class="categories-stats">


                <!-- TOTAL -->

                <div class="category-stat">

                    <span class="category-stat-label">

                        Total Categories

                    </span>


                    <strong class="category-stat-value">

                        <?= number_format(
                            $totalCategories
                        ) ?>

                    </strong>

                </div>


                <!-- IN USE -->

                <div
                    class="
                        category-stat
                        used
                    "
                >

                    <span class="category-stat-label">

                        Categories In Use

                    </span>


                    <strong class="category-stat-value">

                        <?= number_format(
                            $categoriesInUse
                        ) ?>

                    </strong>

                </div>


                <!-- PRODUCTS -->

                <div
                    class="
                        category-stat
                        products
                    "
                >

                    <span class="category-stat-label">

                        Products Assigned

                    </span>


                    <strong class="category-stat-value">

                        <?= number_format(
                            $totalProductsAssigned
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- =====================================================
                 CATEGORY PANEL
            ====================================================== -->

            <section class="categories-panel">


                <!-- HEADER -->

                <div class="categories-panel-header">


                    <div class="categories-panel-title">


                        <div class="categories-panel-icon">

                            🏷️

                        </div>


                        <div>

                            <h2>
                                Category Management
                            </h2>

                            <p>
                                Search, create and manage marketplace categories.
                            </p>

                        </div>


                    </div>


                    <button
                        type="button"
                        class="category-add-btn"
                        onclick="openAddCategoryModal()"
                    >

                        + Add Category

                    </button>


                </div>


                <!-- =================================================
                     SEARCH
                ================================================== -->

                <div class="categories-filter-wrapper">


                    <form
                        method="GET"
                        action="categories.php"
                        class="categories-filter"
                    >


                        <input
                            type="search"
                            name="search"
                            value="<?= categoryEscape(
                                $search
                            ) ?>"
                            placeholder="Search category name..."
                            autocomplete="off"
                        >


                        <button
                            type="submit"
                            class="
                                category-btn
                                category-btn-primary
                            "
                        >

                            Search

                        </button>


                        <a
                            href="categories.php"
                            class="
                                category-btn
                                category-btn-secondary
                            "
                        >

                            Reset

                        </a>


                    </form>


                </div>


                <!-- =================================================
                     CATEGORY TABLE
                ================================================== -->

                <?php if (!empty($categories)): ?>


                    <div class="category-table-wrapper">


                        <table class="category-table">


                            <thead>

                                <tr>

                                    <th>
                                        ID
                                    </th>

                                    <th>
                                        Category
                                    </th>

                                    <th>
                                        Products
                                    </th>

                                    <th>
                                        Created
                                    </th>

                                    <th>
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>


                                <?php foreach ($categories as $category): ?>


                                    <?php

                                    $categoryId =
                                        (int)
                                        $category['category_id'];


                                    $categoryName =
                                        $category['category_name'];


                                    $categoryImage =
                                        $category['category_image']
                                        ?? '';


                                    $productCount =
                                        (int)
                                        $category['product_count'];


                                    $created =
                                        !empty(
                                            $category['created_at']
                                        )
                                            ? strtotime(
                                                $category['created_at']
                                            )
                                            : false;

                                    ?>


                                    <tr>


                                        <!-- ID -->

                                        <td>

                                            <span class="category-id">

                                                #<?= $categoryId ?>

                                            </span>

                                        </td>


                                        <!-- CATEGORY -->

                                        <td>


                                            <div class="category-info">


                                                <div class="category-thumb">


                                                    <?php if (
                                                        !empty(
                                                            $categoryImage
                                                        )
                                                    ): ?>


                                                        <img
                                                            src="<?= categoryEscape(
                                                                '../uploads/categories/' .
                                                                rawurlencode(
                                                                    basename(
                                                                        $categoryImage
                                                                    )
                                                                )
                                                            ) ?>"
                                                            alt="<?= categoryEscape(
                                                                $categoryName
                                                            ) ?>"
                                                            onerror="
                                                                this.style.display='none';
                                                                this.parentElement.innerHTML='📁';
                                                            "
                                                        >


                                                    <?php else: ?>


                                                        📁


                                                    <?php endif; ?>


                                                </div>


                                                <div>


                                                    <div class="category-name">

                                                        <?= categoryEscape(
                                                            $categoryName
                                                        ) ?>

                                                    </div>


                                                    <div class="category-sub">

                                                        HochipoHub category

                                                    </div>


                                                </div>


                                            </div>


                                        </td>


                                        <!-- PRODUCTS -->

                                        <td>

                                            <span
                                                class="
                                                    category-product-badge
                                                    <?= $productCount === 0
                                                        ? 'empty'
                                                        : '' ?>
                                                "
                                            >

                                                <?= number_format(
                                                    $productCount
                                                ) ?>

                                                <?= $productCount === 1
                                                    ? ' product'
                                                    : ' products' ?>

                                            </span>

                                        </td>


                                        <!-- CREATED -->

                                        <td>

                                            <?= $created
                                                ? categoryEscape(
                                                    date(
                                                        'd M Y',
                                                        $created
                                                    )
                                                )
                                                : '-' ?>

                                        </td>


                                        <!-- ACTION -->

                                        <td>


                                            <div class="category-actions">


                                                <a
                                                    href="categories.php?edit=<?= $categoryId ?>"
                                                    class="
                                                        category-action
                                                        category-edit
                                                    "
                                                >

                                                    Edit

                                                </a>


                                                <?php if ($productCount > 0): ?>


                                                    <button
                                                        type="button"
                                                        class="
                                                            category-action
                                                            category-delete
                                                            disabled
                                                        "
                                                        disabled
                                                        title="Products are assigned to this category."
                                                    >

                                                        Delete

                                                    </button>


                                                <?php else: ?>


                                                    <form
                                                        method="POST"
                                                        action="categories.php"
                                                        onsubmit="return confirmCategoryDelete(
                                                            <?= htmlspecialchars(
                                                                json_encode(
                                                                    $categoryName
                                                                ),
                                                                ENT_QUOTES,
                                                                'UTF-8'
                                                            ) ?>
                                                        );"
                                                    >


                                                        <input
                                                            type="hidden"
                                                            name="csrf_token"
                                                            value="<?= categoryEscape(
                                                                $csrfToken
                                                            ) ?>"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="action"
                                                            value="delete"
                                                        >


                                                        <input
                                                            type="hidden"
                                                            name="category_id"
                                                            value="<?= $categoryId ?>"
                                                        >


                                                        <button
                                                            type="submit"
                                                            class="
                                                                category-action
                                                                category-delete
                                                            "
                                                        >

                                                            Delete

                                                        </button>


                                                    </form>


                                                <?php endif; ?>


                                            </div>


                                        </td>


                                    </tr>


                                <?php endforeach; ?>


                            </tbody>


                        </table>


                    </div>


                <?php else: ?>


                    <div class="category-empty">


                        <div class="category-empty-icon">

                            📂

                        </div>


                        <?php if ($search !== ''): ?>


                            <h3>
                                No categories found
                            </h3>

                            <p>
                                Try another category name.
                            </p>


                        <?php else: ?>


                            <h3>
                                No categories yet
                            </h3>

                            <p>
                                Add your first category to get started.
                            </p>


                        <?php endif; ?>


                    </div>


                <?php endif; ?>


            </section>


        </div>


    </main>


</div>


<!-- ===============================================================
     ADD CATEGORY GLASS MODAL
================================================================ -->

<div
    id="addCategoryModal"
    class="category-modal-overlay"
    style="display:none;"
    onclick="closeAddOverlay(event)"
>


    <div
        class="category-modal"
        onclick="event.stopPropagation();"
    >


        <div class="category-modal-header">


            <div class="category-modal-title">


                <div class="category-modal-icon">

                    🏷️

                </div>


                <div>

                    <h2>
                        Add Category
                    </h2>

                    <p>
                        Create a new marketplace category.
                    </p>

                </div>


            </div>


            <button
                type="button"
                class="category-modal-close"
                onclick="closeAddCategoryModal()"
                aria-label="Close"
            >

                ×

            </button>


        </div>


        <form
            method="POST"
            action="categories.php"
            enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="csrf_token"
                value="<?= categoryEscape(
                    $csrfToken
                ) ?>"
            >


            <input
                type="hidden"
                name="action"
                value="add"
            >


            <!-- CATEGORY NAME -->

            <div class="category-form-group">


                <label for="add_category_name">

                    Category Name

                    <span class="required">
                        *
                    </span>

                </label>


                <input
                    type="text"
                    id="add_category_name"
                    name="category_name"
                    maxlength="100"
                    placeholder="Enter category name"
                    required
                >


            </div>


            <!-- CATEGORY IMAGE -->

            <div class="category-form-group">


                <label for="add_category_image">

                    Category Image

                </label>


                <input
                    type="file"
                    id="add_category_image"
                    name="category_image"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                >


                <span class="category-form-help">

                    JPG, PNG or WEBP. Maximum file size 5MB.

                </span>


            </div>


            <!-- BUTTON -->

            <div class="category-modal-actions">


                <button
                    type="button"
                    class="
                        category-btn
                        category-btn-secondary
                    "
                    onclick="closeAddCategoryModal()"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    class="
                        category-btn
                        category-btn-primary
                    "
                >

                    Add Category

                </button>


            </div>


        </form>


    </div>


</div>


<!-- ===============================================================
     EDIT CATEGORY GLASS MODAL
================================================================ -->

<?php if ($editCategory): ?>


    <div
        id="editCategoryModal"
        class="category-modal-overlay"
        onclick="closeEditOverlay(event)"
    >


        <div
            class="category-modal"
            onclick="event.stopPropagation();"
        >


            <div class="category-modal-header">


                <div class="category-modal-title">


                    <div class="category-modal-icon">

                        ✏️

                    </div>


                    <div>

                        <h2>
                            Edit Category
                        </h2>

                        <p>
                            Update category information.
                        </p>

                    </div>


                </div>


                <a
                    href="categories.php"
                    class="category-modal-close"
                    aria-label="Close"
                >

                    ×

                </a>


            </div>


            <form
                method="POST"
                action="categories.php"
                enctype="multipart/form-data"
            >


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= categoryEscape(
                        $csrfToken
                    ) ?>"
                >


                <input
                    type="hidden"
                    name="action"
                    value="edit"
                >


                <input
                    type="hidden"
                    name="category_id"
                    value="<?= (int)
                        $editCategory['category_id'] ?>"
                >


                <!-- CATEGORY NAME -->

                <div class="category-form-group">


                    <label for="edit_category_name">

                        Category Name

                        <span class="required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        id="edit_category_name"
                        name="category_name"
                        maxlength="100"
                        value="<?= categoryEscape(
                            $editCategory[
                                'category_name'
                            ]
                        ) ?>"
                        required
                    >


                </div>


                <!-- CURRENT IMAGE -->

                <div class="category-form-group">


                    <label>
                        Current Image
                    </label>


                    <div class="category-current-image">


                        <?php if (
                            !empty(
                                $editCategory[
                                    'category_image'
                                ]
                            )
                        ): ?>


                            <img
                                src="<?= categoryEscape(
                                    '../uploads/categories/' .
                                    rawurlencode(
                                        basename(
                                            $editCategory[
                                                'category_image'
                                            ]
                                        )
                                    )
                                ) ?>"
                                alt="<?= categoryEscape(
                                    $editCategory[
                                        'category_name'
                                    ]
                                ) ?>"
                                onerror="
                                    this.style.display='none';
                                    this.parentElement.innerHTML='📁';
                                "
                            >


                        <?php else: ?>


                            📁


                        <?php endif; ?>


                    </div>


                </div>


                <!-- REPLACE IMAGE -->

                <div class="category-form-group">


                    <label for="edit_category_image">

                        Replace Image

                    </label>


                    <input
                        type="file"
                        id="edit_category_image"
                        name="category_image"
                        accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    >


                    <span class="category-form-help">

                        Leave empty to keep the current image. Maximum 5MB.

                    </span>


                </div>


                <!-- BUTTON -->

                <div class="category-modal-actions">


                    <a
                        href="categories.php"
                        class="
                            category-btn
                            category-btn-secondary
                        "
                    >

                        Cancel

                    </a>


                    <button
                        type="submit"
                        class="
                            category-btn
                            category-btn-primary
                        "
                    >

                        Save Changes

                    </button>


                </div>


            </form>


        </div>


    </div>


<?php endif; ?>


<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR WIDTH
    |--------------------------------------------------------------------------
    */

    function syncCategorySidebar() {

        const main =
            document.querySelector(
                '.categories-main'
            );


        if (!main) {
            return;
        }


        if (
            window.innerWidth <= 900
        ) {

            document.documentElement
                .style
                .setProperty(
                    '--category-sidebar-width',
                    '0px'
                );

            return;
        }


        const sidebar =
            document.querySelector(
                '.admin-sidebar'
            ) ||
            document.querySelector(
                '.dashboard-sidebar'
            ) ||
            document.querySelector(
                '.sidebar'
            ) ||
            document.querySelector(
                'aside'
            );


        if (!sidebar) {

            document.documentElement
                .style
                .setProperty(
                    '--category-sidebar-width',
                    '260px'
                );

            return;
        }


        const rect =
            sidebar.getBoundingClientRect();


        if (rect.right > 0) {

            document.documentElement
                .style
                .setProperty(
                    '--category-sidebar-width',
                    rect.right + 'px'
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | ADD MODAL
    |--------------------------------------------------------------------------
    */

    function openAddCategoryModal() {

        const modal =
            document.getElementById(
                'addCategoryModal'
            );


        if (!modal) {
            return;
        }


        modal.style.display =
            'flex';


        document.body.style.overflow =
            'hidden';


        const input =
            document.getElementById(
                'add_category_name'
            );


        if (input) {

            setTimeout(
                function () {

                    input.focus();

                },
                80
            );
        }
    }


    function closeAddCategoryModal() {

        const modal =
            document.getElementById(
                'addCategoryModal'
            );


        if (!modal) {
            return;
        }


        modal.style.display =
            'none';


        document.body.style.overflow =
            '';
    }


    function closeAddOverlay(event) {

        if (
            event.target ===
            event.currentTarget
        ) {

            closeAddCategoryModal();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT MODAL
    |--------------------------------------------------------------------------
    */

    function closeEditOverlay(event) {

        if (
            event.target ===
            event.currentTarget
        ) {

            window.location.href =
                'categories.php';
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE CONFIRMATION
    |--------------------------------------------------------------------------
    */

    function confirmCategoryDelete(
        categoryName
    ) {

        return confirm(
            'Are you sure you want to delete "' +
            categoryName +
            '"?\n\n' +
            'This action cannot be undone.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE KEY
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (event.key !== 'Escape') {
                return;
            }


            const addModal =
                document.getElementById(
                    'addCategoryModal'
                );


            if (
                addModal &&
                addModal.style.display !== 'none'
            ) {

                closeAddCategoryModal();

                return;
            }


            const editModal =
                document.getElementById(
                    'editCategoryModal'
                );


            if (editModal) {

                window.location.href =
                    'categories.php';
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | PAGE LOAD
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncCategorySidebar();


            setTimeout(
                syncCategorySidebar,
                100
            );


            setTimeout(
                syncCategorySidebar,
                400
            );


            const editModal =
                document.getElementById(
                    'editCategoryModal'
                );


            if (editModal) {

                document.body.style.overflow =
                    'hidden';
            }

        }
    );


    /*
    |--------------------------------------------------------------------------
    | RESIZE
    |--------------------------------------------------------------------------
    */

    window.addEventListener(
        'resize',
        syncCategorySidebar
    );

</script>


</body>

</html>