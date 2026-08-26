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
| ADMIN AUTHENTICATION
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
| PAGE VARIABLES
|--------------------------------------------------------------------------
*/

$pageTitle = 'Categories';

$errors = [];
$success = '';
$editCategory = null;


/*
|--------------------------------------------------------------------------
| ESCAPE FUNCTION
|--------------------------------------------------------------------------
*/

function categoryEscape($value): string
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| CSRF TOKEN
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['csrf_token']) ||
    empty($_SESSION['csrf_token'])
) {
    $_SESSION['csrf_token'] =
        bin2hex(
            random_bytes(32)
        );
}

$csrfToken =
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    isset($_GET['search'])
        ? trim($_GET['search'])
        : '';


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

$uploadDirectory =
    __DIR__ .
    '/../uploads/categories/';

if (!is_dir($uploadDirectory)) {
    @mkdir(
        $uploadDirectory,
        0755,
        true
    );
}


/*
|--------------------------------------------------------------------------
| DELETE CATEGORY IMAGE
|--------------------------------------------------------------------------
*/

function deleteCategoryImage(
    ?string $imageName,
    string $uploadDirectory
): void {

    if (empty($imageName)) {
        return;
    }

    $imageName =
        basename($imageName);

    $filePath =
        $uploadDirectory .
        $imageName;

    if (
        file_exists($filePath) &&
        is_file($filePath)
    ) {
        @unlink($filePath);
    }
}


/*
|--------------------------------------------------------------------------
| UPLOAD CATEGORY IMAGE
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

        $errors[] =
            'There was a problem uploading the category image.';

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | MAX 5MB
    |--------------------------------------------------------------------------
    */

    $maxSize =
        5 * 1024 * 1024;

    if (
        !isset($file['size']) ||
        $file['size'] > $maxSize
    ) {

        $errors[] =
            'Category image must not exceed 5MB.';

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | VALID FILE
    |--------------------------------------------------------------------------
    */

    if (
        empty($file['tmp_name']) ||
        !is_uploaded_file($file['tmp_name'])
    ) {

        $errors[] =
            'Invalid uploaded category image.';

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | MIME CHECK
    |--------------------------------------------------------------------------
    */

    if (!class_exists('finfo')) {

        $errors[] =
            'PHP Fileinfo extension is required for image uploads.';

        return null;
    }

    $finfo =
        new finfo(
            FILEINFO_MIME_TYPE
        );

    $mimeType =
        $finfo->file(
            $file['tmp_name']
        );

    $allowedTypes = [

        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp'

    ];

    if (
        !$mimeType ||
        !isset($allowedTypes[$mimeType])
    ) {

        $errors[] =
            'Only JPG, PNG and WEBP images are allowed.';

        return null;
    }


    /*
    |--------------------------------------------------------------------------
    | FILE NAME
    |--------------------------------------------------------------------------
    */

    $extension =
        $allowedTypes[$mimeType];

    $imageName =
        'category_' .
        bin2hex(
            random_bytes(12)
        ) .
        '.' .
        $extension;

    $destination =
        $uploadDirectory .
        $imageName;


    /*
    |--------------------------------------------------------------------------
    | MOVE FILE
    |--------------------------------------------------------------------------
    */

    if (
        !move_uploaded_file(
            $file['tmp_name'],
            $destination
        )
    ) {

        $errors[] =
            'Failed to save category image.';

        return null;
    }

    return $imageName;
}


/*
|--------------------------------------------------------------------------
| POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CSRF VALIDATION
    |--------------------------------------------------------------------------
    */

    $submittedToken =
        $_POST['csrf_token']
        ?? '';

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


    /*
    |--------------------------------------------------------------------------
    | ACTION
    |--------------------------------------------------------------------------
    */

    $action =
        $_POST['action']
        ?? '';


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
                $_POST['category_name']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | VALIDATE NAME
        |--------------------------------------------------------------------------
        */

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
        | DUPLICATE CHECK
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

                if (
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    )
                ) {

                    $errors[] =
                        'This category already exists.';
                }

            } catch (PDOException $e) {

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

            } catch (PDOException $e) {

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
            isset($_POST['category_id'])
                ? (int) $_POST['category_id']
                : 0;

        $categoryName =
            trim(
                $_POST['category_name']
                ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | VALIDATION
        |--------------------------------------------------------------------------
        */

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

            } catch (PDOException $e) {

                $errors[] =
                    'Unable to load category.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | DUPLICATE CHECK
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

                if (
                    $stmt->fetch(
                        PDO::FETCH_ASSOC
                    )
                ) {

                    $errors[] =
                        'Another category already uses this name.';
                }

            } catch (PDOException $e) {

                $errors[] =
                    'Unable to validate category name.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | IMAGE
        |--------------------------------------------------------------------------
        */

        $oldImage =
            $existingCategory['category_image']
            ?? null;

        $newImage =
            $oldImage;

        $hasNewImage = false;


        if (
            empty($errors) &&
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error']
                !== UPLOAD_ERR_NO_FILE
        ) {

            $uploadedImage =
                uploadCategoryImage(
                    $_FILES['category_image'],
                    $uploadDirectory,
                    $errors
                );

            if (
                empty($errors) &&
                !empty($uploadedImage)
            ) {

                $newImage =
                    $uploadedImage;

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


                /*
                |--------------------------------------------------------------------------
                | DELETE OLD IMAGE
                |--------------------------------------------------------------------------
                */

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

            } catch (PDOException $e) {


                /*
                |--------------------------------------------------------------------------
                | DELETE NEW IMAGE IF UPDATE FAILS
                |--------------------------------------------------------------------------
                */

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
            isset($_POST['category_id'])
                ? (int) $_POST['category_id']
                : 0;


        if ($categoryId <= 0) {

            $errors[] =
                'Invalid category ID.';
        }


        /*
        |--------------------------------------------------------------------------
        | LOAD CATEGORY
        |--------------------------------------------------------------------------
        */

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

            } catch (PDOException $e) {

                $errors[] =
                    'Unable to load category.';
            }
        }


        /*
        |--------------------------------------------------------------------------
        | CHECK PRODUCTS
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

            } catch (PDOException $e) {

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

            } catch (PDOException $e) {

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
| SUCCESS MESSAGE
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
| LOAD CATEGORY FOR EDIT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['edit']) &&
    (int) $_GET['edit'] > 0
) {

    $editId =
        (int)
        $_GET['edit'];

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

    } catch (PDOException $e) {

        $errors[] =
            'Unable to load category for editing.';
    }
}


/*
|--------------------------------------------------------------------------
| LOAD CATEGORIES
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


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            WHERE c.category_name LIKE ?
        ";

        $params[] =
            '%' .
            $search .
            '%';
    }


    /*
    |--------------------------------------------------------------------------
    | GROUP
    |--------------------------------------------------------------------------
    */

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

} catch (PDOException $e) {

    $categories = [];

    $errors[] =
        'Unable to load categories.';
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalCategories =
    count($categories);

$categoriesWithProducts = 0;

$totalProductsInCategories = 0;

foreach ($categories as $category) {

    $count =
        (int)
        $category['product_count'];

    if ($count > 0) {

        $categoriesWithProducts++;
    }

    $totalProductsInCategories +=
        $count;
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
        <?= categoryEscape($pageTitle) ?> - HochipoHub
    </title>


    <!-- ============================================================
         PROJECT CSS
    ============================================================= -->

    <link
        rel="stylesheet"
        href="../css/style.css"
    >

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >

    <link
        rel="stylesheet"
        href="../css/admin.css"
    >

    <link
        rel="stylesheet"
        href="../css/responsive.css"
    >


    <style>

        /*
        |--------------------------------------------------------------------------
        | IMPORTANT ADMIN LAYOUT FIX
        |--------------------------------------------------------------------------
        |
        | Sidebar admin is fixed on the left.
        | Content MUST start after the sidebar.
        |
        |--------------------------------------------------------------------------
        */

        :root {

            --category-sidebar-width: 260px;

        }


        html,
        body {

            margin: 0;

            padding: 0;

            min-height: 100%;

            background: #f4f7fb;

        }


        body {

            overflow-x: hidden;

        }


        /*
        |--------------------------------------------------------------------------
        | FORCE MAIN CONTENT AFTER SIDEBAR
        |--------------------------------------------------------------------------
        */

        body > main.dashboard-main.category-admin-page {

            position: relative !important;

            display: block !important;

            min-height: 100vh !important;

            margin-top: 0 !important;

            margin-right: 0 !important;

            margin-bottom: 0 !important;

            margin-left:
                var(
                    --category-sidebar-width
                ) !important;

            padding: 0 !important;

            width:
                calc(
                    100% -
                    var(
                        --category-sidebar-width
                    )
                ) !important;

            max-width: none !important;

            background: #f4f7fb !important;

            overflow-x: hidden;

            z-index: 1;

        }


        /*
        |--------------------------------------------------------------------------
        | BOX SIZING
        |--------------------------------------------------------------------------
        */

        .category-admin-page,
        .category-admin-page *,
        .category-modal-overlay,
        .category-modal-overlay * {

            box-sizing: border-box;

        }


        /*
        |--------------------------------------------------------------------------
        | PAGE HEADER
        |--------------------------------------------------------------------------
        */

        .category-page-header {

            width: 100%;

            min-height: 105px;

            display: flex;

            align-items: center;

            padding:

                23px
                34px;

            background: #ffffff;

            border-bottom:

                1px solid
                #e6ebf2;

        }


        .category-page-header h1 {

            margin:

                0
                0
                5px;

            color: #111827;

            font-size: 26px;

            line-height: 1.2;

            font-weight: 800;

        }


        .category-page-header p {

            margin: 0;

            color: #8190a5;

            font-size: 12px;

            font-weight: 500;

        }


        /*
        |--------------------------------------------------------------------------
        | MAIN CONTENT
        |--------------------------------------------------------------------------
        */

        .category-content {

            width: 100%;

            max-width: 1280px;

            margin:

                0
                auto;

            padding:

                28px
                28px
                55px;

        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .category-alert {

            margin-bottom: 20px;

            padding:

                14px
                17px;

            border-radius: 12px;

            font-size: 13px;

            line-height: 1.6;

        }


        .category-alert-error {

            background: #fff1f2;

            color: #9f1239;

            border:

                1px solid
                #fecdd3;

        }


        .category-alert-success {

            background: #ecfdf5;

            color: #166534;

            border:

                1px solid
                #bbf7d0;

        }


        .category-alert ul {

            margin:

                7px
                0
                0;

            padding-left: 20px;

        }


        /*
        |--------------------------------------------------------------------------
        | STAT CARDS
        |--------------------------------------------------------------------------
        */

        .category-stat-grid {

            display: grid;

            grid-template-columns:

                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 16px;

            margin-bottom: 20px;

        }


        .category-stat-card {

            position: relative;

            min-height: 115px;

            overflow: hidden;

            padding:

                22px
                20px;

            background: #ffffff;

            border:

                1px solid
                #dce4ee;

            border-top:

                3px solid
                #3478f6;

            border-radius: 14px;

            box-shadow:

                0
                5px
                18px
                rgba(
                    30,
                    55,
                    90,
                    0.04
                );

        }


        .category-stat-card::after {

            content: "";

            position: absolute;

            right: -20px;

            bottom: -35px;

            width: 100px;

            height: 100px;

            border-radius: 50%;

            background: #edf4ff;

        }


        .category-stat-card:nth-child(2)::after {

            background: #e9f8f1;

        }


        .category-stat-card:nth-child(3)::after {

            background: #f2edff;

        }


        .category-stat-top {

            position: relative;

            z-index: 2;

            display: flex;

            align-items: center;

            gap: 9px;

            margin-bottom: 14px;

        }


        .category-stat-icon {

            width: 25px;

            height: 25px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            color: #2463eb;

            background: #edf4ff;

            font-size: 9px;

            font-weight: 900;

        }


        .category-stat-label {

            color: #66758a;

            font-size: 10px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.35px;

        }


        .category-stat-value {

            position: relative;

            z-index: 2;

            color: #0f172a;

            font-size: 28px;

            line-height: 1;

            font-weight: 900;

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER CARD
        |--------------------------------------------------------------------------
        */

        .category-filter-card {

            margin-bottom: 20px;

            padding:

                27px
                30px;

            background: #ffffff;

            border:

                1px solid
                #dce4ee;

            border-radius: 14px;

            box-shadow:

                0
                5px
                18px
                rgba(
                    30,
                    55,
                    90,
                    0.04
                );

        }


        .category-search-form {

            display: grid;

            grid-template-columns:

                minmax(
                    0,
                    1fr
                )
                auto
                auto;

            align-items: center;

            gap: 10px;

        }


        .category-search-input {

            width: 100%;

            height: 43px;

            padding:

                0
                15px;

            outline: none;

            color: #172033;

            background: #ffffff;

            border:

                1px solid
                #d7e0eb;

            border-radius: 8px;

            font-family: inherit;

            font-size: 11px;

        }


        .category-search-input::placeholder {

            color: #9aa7b9;

        }


        .category-search-input:focus {

            border-color: #3478f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    52,
                    120,
                    246,
                    0.08
                );

        }


        /*
        |--------------------------------------------------------------------------
        | BUTTONS
        |--------------------------------------------------------------------------
        */

        .category-btn {

            min-height: 39px;

            padding:

                0
                17px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border: 0;

            border-radius: 8px;

            font-family: inherit;

            font-size: 11px;

            font-weight: 700;

            text-decoration: none;

            white-space: nowrap;

            cursor: pointer;

        }


        .category-btn-primary {

            color: #ffffff;

            background: #2868e8;

            box-shadow:

                0
                5px
                12px
                rgba(
                    40,
                    104,
                    232,
                    0.18
                );

        }


        .category-btn-primary:hover {

            background: #1f5fd7;

        }


        .category-btn-secondary {

            color: #63748b;

            background: #ffffff;

            border:

                1px solid
                #d8e1ed;

        }


        .category-btn-secondary:hover {

            background: #f8fafc;

        }


        /*
        |--------------------------------------------------------------------------
        | LIST CARD
        |--------------------------------------------------------------------------
        */

        .category-list-card {

            padding: 20px;

            background: #ffffff;

            border:

                1px solid
                #dce4ee;

            border-radius: 14px;

            box-shadow:

                0
                5px
                18px
                rgba(
                    30,
                    55,
                    90,
                    0.04
                );

        }


        .category-list-header {

            min-height: 70px;

            padding:

                5px
                14px
                17px;

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            border-bottom:

                1px solid
                #e7edf4;

        }


        .category-list-header h2 {

            margin:

                0
                0
                4px;

            color: #111827;

            font-size: 14px;

            font-weight: 800;

        }


        .category-list-header p {

            margin: 0;

            color: #95a1b2;

            font-size: 9px;

            font-weight: 600;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLE
        |--------------------------------------------------------------------------
        */

        .category-table-wrapper {

            width: 100%;

            margin-top: 16px;

            overflow-x: auto;

            border:

                1px solid
                #dfe6ef;

            border-radius: 10px;

        }


        .category-table {

            width: 100%;

            min-width: 820px;

            border-collapse: collapse;

            background: #ffffff;

        }


        .category-table thead th {

            height: 38px;

            padding:

                0
                17px;

            background: #f6f8fb;

            color: #65748a;

            border-bottom:

                1px solid
                #dfe6ef;

            font-size: 9px;

            font-weight: 800;

            text-align: left;

            text-transform: uppercase;

            letter-spacing: 0.4px;

        }


        .category-table tbody td {

            padding:

                13px
                17px;

            color: #243044;

            border-bottom:

                1px solid
                #e8edf4;

            font-size: 11px;

            vertical-align: middle;

        }


        .category-table tbody tr:last-child td {

            border-bottom: 0;

        }


        .category-table tbody tr:hover {

            background: #fbfcfe;

        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORY INFORMATION
        |--------------------------------------------------------------------------
        */

        .category-info {

            display: flex;

            align-items: center;

            gap: 11px;

        }


        .category-thumb {

            width: 39px;

            height: 39px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            color: #3478f6;

            background: #eef4ff;

            border:

                1px solid
                #dfe8f5;

            border-radius: 9px;

            font-size: 17px;

        }


        .category-thumb img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .category-info-name {

            margin-bottom: 3px;

            color: #111827;

            font-size: 11px;

            font-weight: 800;

        }


        .category-info-sub {

            color: #8d9aad;

            font-size: 9px;

        }


        .category-id {

            color: #8492a6;

            font-size: 10px;

            font-weight: 700;

        }


        /*
        |--------------------------------------------------------------------------
        | PRODUCT COUNT
        |--------------------------------------------------------------------------
        */

        .category-product-badge {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 24px;

            padding:

                0
                9px;

            color: #2261df;

            background: #edf4ff;

            border:

                1px solid
                #d7e5ff;

            border-radius: 20px;

            font-size: 9px;

            font-weight: 800;

        }


        .category-product-badge.empty {

            color: #7c899a;

            background: #f4f6f8;

            border-color: #e6eaf0;

        }


        /*
        |--------------------------------------------------------------------------
        | ACTIONS
        |--------------------------------------------------------------------------
        */

        .category-actions {

            display: flex;

            align-items: center;

            gap: 7px;

        }


        .category-actions form {

            margin: 0;

        }


        .category-action-btn {

            min-width: 49px;

            height: 30px;

            padding:

                0
                10px;

            display: inline-flex;

            align-items: center;

            justify-content: center;

            border-radius: 7px;

            font-family: inherit;

            font-size: 9px;

            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            border: 0;

        }


        .category-edit-btn {

            color: #1f62df;

            background: #edf4ff;

            border:

                1px solid
                #cfe0ff;

        }


        .category-delete-btn {

            color: #d33c47;

            background: #fff0f1;

            border:

                1px solid
                #ffd6da;

        }


        .category-delete-btn.disabled {

            color: #adb6c2;

            background: #f4f6f8;

            border-color: #e7eaf0;

            cursor: not-allowed;

        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY STATE
        |--------------------------------------------------------------------------
        */

        .category-empty-state {

            padding:

                70px
                20px;

            text-align: center;

        }


        .category-empty-state h3 {

            margin:

                0
                0
                6px;

            color: #172033;

            font-size: 15px;

        }


        .category-empty-state p {

            margin: 0;

            color: #8996a8;

            font-size: 11px;

        }


        /*
        |--------------------------------------------------------------------------
        | MODAL
        |--------------------------------------------------------------------------
        */

        .category-modal-overlay {

            position: fixed;

            inset: 0;

            z-index: 99999;

            padding: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            background:

                rgba(
                    15,
                    23,
                    42,
                    0.58
                );

        }


        .category-modal {

            width: 100%;

            max-width: 520px;

            max-height: 90vh;

            overflow-y: auto;

            padding: 25px;

            background: #ffffff;

            border-radius: 14px;

            box-shadow:

                0
                25px
                70px
                rgba(
                    15,
                    23,
                    42,
                    0.25
                );

        }


        .category-modal-header {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-bottom: 23px;

        }


        .category-modal-title h2 {

            margin:

                0
                0
                4px;

            color: #111827;

            font-size: 19px;

            font-weight: 800;

        }


        .category-modal-title p {

            margin: 0;

            color: #8b98aa;

            font-size: 10px;

        }


        .category-modal-close {

            width: 32px;

            height: 32px;

            flex-shrink: 0;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 0;

            color: #536174;

            background: #f3f5f8;

            border: 0;

            border-radius: 8px;

            font-size: 18px;

            text-decoration: none;

            cursor: pointer;

        }


        /*
        |--------------------------------------------------------------------------
        | MODAL FORM
        |--------------------------------------------------------------------------
        */

        .category-form-group {

            margin-bottom: 18px;

        }


        .category-form-group label {

            display: block;

            margin-bottom: 7px;

            color: #374151;

            font-size: 11px;

            font-weight: 700;

        }


        .category-required {

            color: #ef4444;

        }


        .category-form-group input[type="text"],
        .category-form-group input[type="file"] {

            width: 100%;

            min-height: 42px;

            padding:

                10px
                12px;

            outline: none;

            color: #172033;

            background: #ffffff;

            border:

                1px solid
                #d7e0eb;

            border-radius: 8px;

            font-family: inherit;

            font-size: 11px;

        }


        .category-form-group input:focus {

            border-color: #3478f6;

            box-shadow:

                0
                0
                0
                3px
                rgba(
                    52,
                    120,
                    246,
                    0.08
                );

        }


        .category-form-help {

            display: block;

            margin-top: 6px;

            color: #97a3b2;

            font-size: 9px;

        }


        .category-current-image {

            width: 82px;

            height: 82px;

            display: flex;

            align-items: center;

            justify-content: center;

            overflow: hidden;

            margin-bottom: 8px;

            background: #edf4ff;

            border:

                1px solid
                #dbe6f5;

            border-radius: 10px;

            font-size: 28px;

        }


        .category-current-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .category-modal-actions {

            display: flex;

            align-items: center;

            justify-content: flex-end;

            gap: 9px;

            padding-top: 5px;

        }


        /*
        |--------------------------------------------------------------------------
        | TABLET / MOBILE
        |--------------------------------------------------------------------------
        */

        @media (
            max-width: 900px
        ) {

            :root {

                --category-sidebar-width: 0px;

            }


            body > main.dashboard-main.category-admin-page {

                margin-left: 0 !important;

                width: 100% !important;

            }


            .category-stat-grid {

                grid-template-columns: 1fr;

            }


            .category-search-form {

                grid-template-columns: 1fr;

            }


            .category-search-form .category-btn {

                width: 100%;

            }

        }


        @media (
            max-width: 600px
        ) {

            .category-page-header {

                padding:

                    20px
                    16px;

            }


            .category-content {

                padding:

                    20px
                    14px
                    40px;

            }


            .category-filter-card {

                padding: 18px;

            }


            .category-list-card {

                padding: 14px;

            }


            .category-list-header {

                flex-direction: column;

                align-items: stretch;

            }


            .category-list-header .category-btn {

                width: 100%;

            }


            .category-modal-actions {

                flex-direction: column-reverse;

            }


            .category-modal-actions .category-btn {

                width: 100%;

            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| ADMIN SIDEBAR ONLY
|--------------------------------------------------------------------------
|
| DO NOT INCLUDE navbar.php.
|--------------------------------------------------------------------------
*/

$sidebarPath =
    __DIR__ .
    '/../includes/admin_sidebar.php';

if (file_exists($sidebarPath)) {

    require_once $sidebarPath;
}

?>


<!-- ===============================================================
     MAIN CONTENT
================================================================ -->

<main
    class="
        dashboard-main
        category-admin-page
    "
>


    <!-- ===========================================================
         PAGE HEADER
    ============================================================ -->

    <header class="category-page-header">

        <div>

            <h1>
                Categories
            </h1>

            <p>
                Manage HochipoHub product categories.
            </p>

        </div>

    </header>


    <!-- ===========================================================
         CONTENT
    ============================================================ -->

    <div class="category-content">


        <!-- =======================================================
             ERROR
        ======================================================== -->

        <?php if (!empty($errors)): ?>

            <div
                class="
                    category-alert
                    category-alert-error
                "
            >

                <strong>
                    Please fix the following:
                </strong>

                <ul>

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= categoryEscape($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <!-- =======================================================
             SUCCESS
        ======================================================== -->

        <?php if ($success !== ''): ?>

            <div
                class="
                    category-alert
                    category-alert-success
                "
            >

                <?= categoryEscape($success) ?>

            </div>

        <?php endif; ?>


        <!-- =======================================================
             STATISTICS
        ======================================================== -->

        <section class="category-stat-grid">


            <!-- TOTAL -->

            <div class="category-stat-card">

                <div class="category-stat-top">

                    <span class="category-stat-icon">
                        ◆
                    </span>

                    <span class="category-stat-label">
                        Total Categories
                    </span>

                </div>

                <div class="category-stat-value">

                    <?= $totalCategories ?>

                </div>

            </div>


            <!-- IN USE -->

            <div class="category-stat-card">

                <div class="category-stat-top">

                    <span class="category-stat-icon">
                        ◆
                    </span>

                    <span class="category-stat-label">
                        Categories In Use
                    </span>

                </div>

                <div class="category-stat-value">

                    <?= $categoriesWithProducts ?>

                </div>

            </div>


            <!-- PRODUCTS ASSIGNED -->

            <div class="category-stat-card">

                <div class="category-stat-top">

                    <span class="category-stat-icon">
                        ◆
                    </span>

                    <span class="category-stat-label">
                        Products Assigned
                    </span>

                </div>

                <div class="category-stat-value">

                    <?= $totalProductsInCategories ?>

                </div>

            </div>


        </section>


        <!-- =======================================================
             SEARCH
        ======================================================== -->

        <section class="category-filter-card">

            <form
                method="GET"
                action="categories.php"
                class="category-search-form"
            >

                <input
                    type="search"
                    name="search"
                    class="category-search-input"
                    value="<?= categoryEscape($search) ?>"
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

        </section>


        <!-- =======================================================
             CATEGORY LIST
        ======================================================== -->

        <section class="category-list-card">


            <!-- ===================================================
                 HEADER
            ==================================================== -->

            <div class="category-list-header">


                <div>

                    <h2>
                        Category List
                    </h2>

                    <p>

                        <?= $totalCategories ?>

                        <?= $totalCategories === 1
                            ? 'category found'
                            : 'categories found' ?>

                    </p>

                </div>


                <button
                    type="button"
                    class="
                        category-btn
                        category-btn-primary
                    "
                    onclick="openAddCategoryModal()"
                >

                    + Add Category

                </button>


            </div>


            <!-- ===================================================
                 TABLE
            ==================================================== -->

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
                                    $category[
                                        'category_id'
                                    ];

                                $categoryName =
                                    $category[
                                        'category_name'
                                    ];

                                $categoryImage =
                                    $category[
                                        'category_image'
                                    ];

                                $productCount =
                                    (int)
                                    $category[
                                        'product_count'
                                    ];

                                $createdTimestamp =
                                    !empty(
                                        $category[
                                            'created_at'
                                        ]
                                    )
                                        ? strtotime(
                                            $category[
                                                'created_at'
                                            ]
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
                                                    >


                                                <?php else: ?>


                                                    ◈


                                                <?php endif; ?>


                                            </div>


                                            <div>


                                                <div class="category-info-name">

                                                    <?= categoryEscape(
                                                        $categoryName
                                                    ) ?>

                                                </div>


                                                <div class="category-info-sub">

                                                    HochipoHub category

                                                </div>


                                            </div>


                                        </div>


                                    </td>


                                    <!-- PRODUCT -->

                                    <td>

                                        <span
                                            class="
                                                category-product-badge
                                                <?= $productCount === 0
                                                    ? 'empty'
                                                    : '' ?>
                                            "
                                        >

                                            <?= $productCount ?>

                                            <?= $productCount === 1
                                                ? ' product'
                                                : ' products' ?>

                                        </span>

                                    </td>


                                    <!-- CREATED -->

                                    <td>

                                        <?= $createdTimestamp
                                            ? categoryEscape(
                                                date(
                                                    'd M Y',
                                                    $createdTimestamp
                                                )
                                            )
                                            : '-' ?>

                                    </td>


                                    <!-- ACTION -->

                                    <td>


                                        <div class="category-actions">


                                            <!-- EDIT -->

                                            <a
                                                href="categories.php?edit=<?= $categoryId ?>"
                                                class="
                                                    category-action-btn
                                                    category-edit-btn
                                                "
                                            >

                                                Edit

                                            </a>


                                            <!-- DELETE -->

                                            <?php if (
                                                $productCount > 0
                                            ): ?>


                                                <button
                                                    type="button"
                                                    class="
                                                        category-action-btn
                                                        category-delete-btn
                                                        disabled
                                                    "
                                                    disabled
                                                    title="Cannot delete category because products are assigned to it."
                                                >

                                                    Delete

                                                </button>


                                            <?php else: ?>


                                                <form
                                                    method="POST"
                                                    action="categories.php"
                                                    onsubmit="return confirmDelete(
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
                                                            category-action-btn
                                                            category-delete-btn
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


                <div class="category-empty-state">


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


<!-- ===============================================================
     ADD CATEGORY MODAL
================================================================ -->

<div
    id="addCategoryModal"
    class="category-modal-overlay"
    style="display:none;"
    onclick="closeAddModalOverlay(event)"
>


    <div
        class="category-modal"
        onclick="event.stopPropagation();"
    >


        <!-- HEADER -->

        <div class="category-modal-header">


            <div class="category-modal-title">

                <h2>
                    Add Category
                </h2>

                <p>
                    Create a new product category.
                </p>

            </div>


            <button
                type="button"
                class="category-modal-close"
                onclick="closeAddCategoryModal()"
            >

                ×

            </button>


        </div>


        <!-- FORM -->

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

                    <span class="category-required">
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


            <!-- IMAGE -->

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

                    JPG, PNG or WEBP.
                    Maximum file size 5MB.

                </span>


            </div>


            <!-- ACTION -->

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
     EDIT CATEGORY MODAL
================================================================ -->

<?php if ($editCategory): ?>


    <div
        id="editCategoryModal"
        class="category-modal-overlay"
        onclick="closeEditModalOverlay(event)"
    >


        <div
            class="category-modal"
            onclick="event.stopPropagation();"
        >


            <!-- HEADER -->

            <div class="category-modal-header">


                <div class="category-modal-title">

                    <h2>
                        Edit Category
                    </h2>

                    <p>
                        Update category information.
                    </p>

                </div>


                <a
                    href="categories.php"
                    class="category-modal-close"
                >

                    ×

                </a>


            </div>


            <!-- FORM -->

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
                        $editCategory[
                            'category_id'
                        ] ?>"
                >


                <!-- NAME -->

                <div class="category-form-group">


                    <label for="edit_category_name">

                        Category Name

                        <span class="category-required">
                            *
                        </span>

                    </label>


                    <input
                        type="text"
                        id="edit_category_name"
                        name="category_name"
                        maxlength="100"
                        required
                        value="<?= categoryEscape(
                            $editCategory[
                                'category_name'
                            ]
                        ) ?>"
                    >


                </div>


                <!-- CURRENT IMAGE -->

                <div class="category-form-group">


                    <label>
                        Current Image
                    </label>


                    <?php if (
                        !empty(
                            $editCategory[
                                'category_image'
                            ]
                        )
                    ): ?>


                        <div class="category-current-image">


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
                            >


                        </div>


                    <?php else: ?>


                        <div class="category-current-image">

                            ◈

                        </div>


                    <?php endif; ?>


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

                        Leave empty to keep current image.
                        JPG, PNG or WEBP.
                        Maximum 5MB.

                    </span>


                </div>


                <!-- ACTIONS -->

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


<!-- ===============================================================
     JAVASCRIPT
================================================================ -->

<script>

    /*
    |--------------------------------------------------------------------------
    | SIDEBAR AUTO WIDTH FIX
    |--------------------------------------------------------------------------
    |
    | This measures the real admin sidebar.
    | So even if admin_sidebar width changes, page will not hide behind it.
    |
    |--------------------------------------------------------------------------
    */

    function syncCategoryAdminLayout() {

        const possibleSidebars = [

            document.querySelector(
                '.admin-sidebar'
            ),

            document.querySelector(
                '.sidebar'
            ),

            document.querySelector(
                '.dashboard-sidebar'
            ),

            document.querySelector(
                'aside'
            )

        ];


        let sidebar = null;


        for (
            let i = 0;
            i < possibleSidebars.length;
            i++
        ) {

            if (possibleSidebars[i]) {

                sidebar =
                    possibleSidebars[i];

                break;
            }

        }


        if (!sidebar) {

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | MOBILE
        |--------------------------------------------------------------------------
        */

        if (
            window.innerWidth <= 900
        ) {

            document.documentElement.style.setProperty(
                '--category-sidebar-width',
                '0px'
            );

            return;
        }


        const rect =
            sidebar.getBoundingClientRect();


        /*
        |--------------------------------------------------------------------------
        | RIGHT EDGE FROM LEFT SIDE OF VIEWPORT
        |--------------------------------------------------------------------------
        */

        if (rect.right > 0) {

            document.documentElement.style.setProperty(
                '--category-sidebar-width',
                rect.right + 'px'
            );

        }

    }


    /*
    |--------------------------------------------------------------------------
    | RUN SIDEBAR FIX
    |--------------------------------------------------------------------------
    */

    document.addEventListener(
        'DOMContentLoaded',
        function () {

            syncCategoryAdminLayout();

            setTimeout(
                syncCategoryAdminLayout,
                100
            );

            setTimeout(
                syncCategoryAdminLayout,
                400
            );

        }
    );


    window.addEventListener(
        'resize',
        function () {

            syncCategoryAdminLayout();

        }
    );


    /*
    |--------------------------------------------------------------------------
    | OPEN ADD MODAL
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


    /*
    |--------------------------------------------------------------------------
    | CLOSE ADD
    |--------------------------------------------------------------------------
    */

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
    }


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE ADD
    |--------------------------------------------------------------------------
    */

    function closeAddModalOverlay(event) {

        if (
            event.target ===
            event.currentTarget
        ) {

            closeAddCategoryModal();
        }
    }


    /*
    |--------------------------------------------------------------------------
    | CLICK OUTSIDE EDIT
    |--------------------------------------------------------------------------
    */

    function closeEditModalOverlay(event) {

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
    | DELETE
    |--------------------------------------------------------------------------
    */

    function confirmDelete(categoryName) {

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

            if (
                event.key !== 'Escape'
            ) {

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

</script>


</body>

</html>