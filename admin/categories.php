<?php

/**
 * =========================================================
 * HOCHIPOHUB
 * ADMIN - CATEGORY MANAGEMENT
 * File: admin/categories.php
 *
 * Features:
 * - View categories
 * - Search categories
 * - Add category
 * - Edit category
 * - Delete category
 * - Upload category image
 * - Replace category image
 * - Product count
 * - Prevent deleting categories that contain products
 *
 * Database:
 * categories
 * - category_id
 * - category_name
 * - category_image
 * - created_at
 *
 * Database connection:
 * PDO
 * =========================================================
 */


/*
|--------------------------------------------------------------------------
| LOAD DATABASE + SESSION + FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once DIR . '/../database/db.php';
require_once DIR . '/../includes/session.php';
require_once DIR . '/../includes/functions.php';


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
| SECURITY - LOGIN
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        'Location: ../index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| SECURITY - ADMIN ONLY
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {

    header(
        'Location: ../dashboard.php'
    );

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
| PAGE SETTINGS
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Category Management - HochipoHub';


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
| VARIABLES
|--------------------------------------------------------------------------
*/

$errors = [];

$success = '';

$editCategory = null;

$search = isset($_GET['search'])
    ? trim($_GET['search'])
    : '';


/*
|--------------------------------------------------------------------------
| UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

$uploadDirectory =
    DIR .
    '/../uploads/categories/';


/*
|--------------------------------------------------------------------------
| CREATE UPLOAD DIRECTORY
|--------------------------------------------------------------------------
*/

if (!is_dir($uploadDirectory)) {

    @mkdir(
        $uploadDirectory,
        0755,
        true
    );

}


/*
|--------------------------------------------------------------------------
| HELPER - DELETE CATEGORY IMAGE
|--------------------------------------------------------------------------
*/

function deleteCategoryImage(
    string $imageName,
    string $uploadDirectory
): void {

    if (
        $imageName === '' ||
        $imageName === null
    ) {

        return;

    }


    $filePath =
        $uploadDirectory .
        basename($imageName);


    if (
        file_exists($filePath) &&
        is_file($filePath)
    ) {

        @unlink($filePath);

    }

}


/*
|--------------------------------------------------------------------------| HANDLE POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    /*
    |--------------------------------------------------------------------------
    | CHECK CSRF
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


        /*
        |--------------------------------------------------------------------------
        | GET CATEGORY NAME
        |--------------------------------------------------------------------------
        */

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

        if ($categoryName === '') {

            $errors[] =
                'Category name is required.';

        }


        if (
            strlen($categoryName) > 100
        ) {

            $errors[] =
                'Category name cannot exceed 100 characters.';

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE CATEGORY
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $checkStmt =
                    $db->prepare("
                        SELECT category_id
                        FROM categories
                        WHERE LOWER(category_name) = LOWER(?)
                        LIMIT 1
                    ");

                $checkStmt->execute([
                    $categoryName
                ]);

                $existingCategory =
                    $checkStmt->fetch(
                        PDO::FETCH_ASSOC
                    );


                if ($existingCategory) {

                    $errors[] =
                        'This category already exists.';

                }

            } catch (PDOException $e) {

                $errors[] =
                    'Unable to validate category. Please try again.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CATEGORY IMAGE
        |--------------------------------------------------------------------------
        */

        $imageName = null;

        $uploadedImagePath = null;


        if (
            empty($errors) &&
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error']
                !== UPLOAD_ERR_NO_FILE
        ) {

            $file =
                $_FILES['category_image'];


            /*
            |--------------------------------------------------------------------------
            | UPLOAD ERROR
            |--------------------------------------------------------------------------
            */

            if (
                $file['error']
                !== UPLOAD_ERR_OK
            ) {

                $errors[] =
                    'There was a problem uploading the category image.';

            }


            /*|--------------------------------------------------------------------------
            | FILE SIZE
            |--------------------------------------------------------------------------
            */

            if (
                empty($errors) &&
                $file['size'] > 5 * 1024 * 1024
            ) {

                $errors[] =
                    'Category image must not exceed 5MB.';

            }


            /*
            |--------------------------------------------------------------------------
            | MIME TYPE
            |--------------------------------------------------------------------------
            */

            $realMime = '';


            if (empty($errors)) {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );

                $realMime =
                    $finfo->file(
                        $file['tmp_name']
                    );


                $allowedTypes = [
                    'image/jpeg',
                    'image/png',
                    'image/webp'
                ];


                if (
                    !in_array(
                        $realMime,
                        $allowedTypes,
                        true
                    )
                ) {

                    $errors[] =
                        'Only JPG, PNG and WEBP images are allowed.';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | GENERATE IMAGE NAME
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $extensionMap = [

                    'image/jpeg' =>
                        'jpg',

                    'image/png' =>
                        'png',

                    'image/webp' =>
                        'webp'

                ];


                $extension =
                    $extensionMap[$realMime];


                $imageName =
                    'category_' .
                    bin2hex(
                        random_bytes(12)
                    ) .
                    '.' .
                    $extension;


                $uploadedImagePath =
                    $uploadDirectory .
                    $imageName;


                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $uploadedImagePath
                    )
                ) {

                    $errors[] =
                        'Failed to save category image.';

                    $imageName = null;

                    $uploadedImagePath = null;

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | INSERT CATEGORY
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        INSERT INTO categories (
                            category_name,
                            category_image
                        )
                        VALUES (?, ?)
                    ");


                $stmt->execute([

                    $categoryName,

                    $imageName

                ]);


                /*
                |--------------------------------------------------------------------------
                | SUCCESS
                |--------------------------------------------------------------------------
                */

                $success =
                    'Category added successfully.';


                /*
                |--------------------------------------------------------------------------
                | REDIRECT
                |--------------------------------------------------------------------------
                || Prevent duplicate POST when refreshing.
                |
                */

                header(
                    'Location: categories.php?success=added'
                );

                exit;


            } catch (PDOException $e) {


                /*
                |--------------------------------------------------------------------------
                | REMOVE IMAGE IF DATABASE INSERT FAILED
                |--------------------------------------------------------------------------
                */

                if (
                    $imageName !== null
                ) {

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


        /*
        |--------------------------------------------------------------------------
        | CATEGORY ID
        |--------------------------------------------------------------------------
        */

        $categoryId =
            isset($_POST['category_id'])
                ? (int) $_POST['category_id']
                : 0;


        /*
        |--------------------------------------------------------------------------
        | CATEGORY NAME
        |--------------------------------------------------------------------------
        */

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
            strlen($categoryName) > 100
        ) {

            $errors[] =
                'Category name cannot exceed 100 characters.';

        }


        /*
        |--------------------------------------------------------------------------
        | GET EXISTING CATEGORY
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
                    'Unable to find category.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK DUPLICATE NAME
        |--------------------------------------------------------------------------
        */

        if (empty($errors)) {

            try {

                $stmt =
                    $db->prepare("
                        SELECT category_id
                        FROM categoriesWHERE LOWER(category_name) = LOWER(?)
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
        | IMAGE VARIABLES
        |--------------------------------------------------------------------------
        */

        $newImageName =
            $existingCategory['category_image']
            ?? null;

        $newImagePath = null;


        /*
        |--------------------------------------------------------------------------
        | NEW IMAGE UPLOAD
        |--------------------------------------------------------------------------
        */

        if (
            empty($errors) &&
            isset($_FILES['category_image']) &&
            $_FILES['category_image']['error']
                !== UPLOAD_ERR_NO_FILE
        ) {

            $file =
                $_FILES['category_image'];


            /*
            |--------------------------------------------------------------------------
            | UPLOAD ERROR
            |--------------------------------------------------------------------------
            */

            if (
                $file['error']
                !== UPLOAD_ERR_OK
            ) {

                $errors[] =
                    'There was a problem uploading the category image.';

            }


            /*
            |--------------------------------------------------------------------------
            | SIZE
            |--------------------------------------------------------------------------
            */

            if (
                empty($errors) &&
                $file['size'] > 5 * 1024 * 1024
            ) {

                $errors[] =
                    'Category image must not exceed 5MB.';

            }


            /*
            |--------------------------------------------------------------------------
            | MIME
            |--------------------------------------------------------------------------
            */

            $realMime = '';


            if (empty($errors)) {

                $finfo =
                    new finfo(
                        FILEINFO_MIME_TYPE
                    );

                $realMime =
                    $finfo->file(
                        $file['tmp_name']
                    );


                $allowedTypes = [

                    'image/jpeg',
                    'image/png',
                    'image/webp'

                ];


                if (
                    !in_array(
                        $realMime,
                        $allowedTypes,
                        true
                    )
                ) {

                    $errors[] =
                        'Only JPG, PNG and WEBP images are allowed.';

                }

            }


            /*
            |--------------------------------------------------------------------------
            | SAVE NEW IMAGE
            |--------------------------------------------------------------------------
            */

            if (empty($errors)) {

                $extensionMap = [

                    'image/jpeg' =>
                        'jpg',

                    'image/png' =>
                        'png',

                    'image/webp' =>
                        'webp'

                ];


                $extension =
                    $extensionMap[$realMime];


                $newImageName ='category_' .
                    bin2hex(
                        random_bytes(12)
                    ) .
                    '.' .
                    $extension;


                $newImagePath =
                    $uploadDirectory .
                    $newImageName;


                if (
                    !move_uploaded_file(
                        $file['tmp_name'],
                        $newImagePath
                    )
                ) {

                    $errors[] =
                        'Failed to save new category image.';

                    $newImageName =
                        $existingCategory['category_image'];

                    $newImagePath = null;

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE CATEGORY
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

                    $newImageName,

                    $categoryId

                ]);


                /*
                |--------------------------------------------------------------------------
                | DELETE OLD IMAGE
                |--------------------------------------------------------------------------
                */

                if (
                    $newImageName !==
                    $existingCategory['category_image']
                ) {

                    deleteCategoryImage(
                        $existingCategory['category_image']
                        ?? '',
                        $uploadDirectory
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | REDIRECT
                |--------------------------------------------------------------------------
                */

                header(
                    'Location: categories.php?success=updated'
                );

                exit;


            } catch (PDOException $e) {


                /*
                |--------------------------------------------------------------------------
                | REMOVE NEW IMAGE IF UPDATE FAILED
                |--------------------------------------------------------------------------
                */

                if (
                    $newImagePath !== null &&
                    file_exists($newImagePath)
                ) {

                    @unlink(
                        $newImagePath
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


        /*
        |--------------------------------------------------------------------------
        | CATEGORY ID
        |--------------------------------------------------------------------------
        */

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
        | GET CATEGORY|--------------------------------------------------------------------------
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
                    'Unable to find category.';

            }

        }


        /*
        |--------------------------------------------------------------------------
        | CHECK PRODUCT COUNT
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
                    (int) $stmt->fetchColumn();


                if ($productCount > 0) {

                    $errors[] =
                        'Cannot delete "' .
                        $category['category_name'] .
                        '" because it contains ' .
                        $productCount .
                        ' product' .
                        (
                            $productCount === 1
                                ? ''
                                : 's'
                        ) .
                        '. Please move or remove the products first.';

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


                /*
                |--------------------------------------------------------------------------
                | DELETE CATEGORY
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        DELETE FROM categories
                        WHERE category_id = ?
                    ");

                $stmt->execute([
                    $categoryId
                ]);


                /*
                |--------------------------------------------------------------------------
                | COMMIT
                |--------------------------------------------------------------------------
                */

                $db->commit();


                /*
                |--------------------------------------------------------------------------
                | DELETE IMAGE
                |--------------------------------------------------------------------------
                */

                deleteCategoryImage(
                    $category['category_image']
                    ?? '',
                    $uploadDirectory
                );


                /*
                |--------------------------------------------------------------------------
                | REDIRECT|--------------------------------------------------------------------------
                */

                header(
                    'Location: categories.php?success=deleted'
                );

                exit;


            } catch (PDOException $e) {

                if (
                    $db->inTransaction()
                ) {

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
| SUCCESS MESSAGE FROM REDIRECT
|--------------------------------------------------------------------------
*/

if (
    isset($_GET['success'])
) {

    switch (
        $_GET['success']
    ) {

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
| EDIT MODE
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
            ON c.category_id = p.category_id
    ";


    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            WHERE
                c.category_name LIKE ?
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
            c.category_name ASC
    ";


    $stmt =
        $db->prepare($sql);


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


foreach ($categories
    as $category
) {

    $count =
        (int)
        $category['product_count'];


    if ($count > 0) {

        $categoriesWithProducts++;

    }


    $totalProductsInCategories +=
        $count;

}


/*
|--------------------------------------------------------------------------
| PAGE
|--------------------------------------------------------------------------
*/

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
        <?= e($pageTitle) ?>
    </title>


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
        | CATEGORY MANAGEMENT
        |--------------------------------------------------------------------------
        */

        .admin-category-page {

            padding: 30px;

            max-width: 1450px;

            margin: 0 auto;

        }


        .category-page-header {

            display: flex;

            justify-content: space-between;

            align-items: flex-start;

            gap: 20px;

            margin-bottom: 30px;

        }


        .category-page-header h1 {

            margin: 0 0 7px;

            font-size: 32px;

            font-weight: 900;

            color: #0f172a;

        }


        .category-page-header p {

            margin: 0;

            color: #64748b;

        }


        .category-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    3,
                    minmax(
                        0,
                        1fr
                    )
                );

            gap: 18px;

            margin-bottom: 25px;

        }


        .category-stat-card {

            padding: 22px;

            border-radius: 18px;

            background: #ffffff;

            border:
                1px solid
                #e2e8f0;

            box-shadow:
                0 8px 25px
                rgba(
                    15,
                    23,
                    42,
                    0.06
                );

        }


        .category-stat-label {

            display: block;

            margin-bottom: 8px;

            color: #64748b;

            font-size: 13px;

            font-weight: 700;

        }


        .category-stat-number {

            display: block;

            color: #0f172a;

            font-size: 30px;

            font-weight: 900;

        }


        .category-management-card {

            background: #ffffff;

            border:
                1px solid
                #e2e8f0;

            border-radius: 20px;

            box-shadow:
                0 8px 30px
                rgba(
                    15,
                    23,
                    42,
                    0.06
                );

            overflow: hidden;

        }


        .category-card-toolbar {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            padding: 20px;

            border-bottom:
                1px solid
                #e2e8f0;

        }


        .category-search {

            display: flex;

            gap: 10px;

            width: 100%;

            max-width: 500px;

        }


        .category-search input {

            flex: 1;

            min-width: 0;

            padding: 12px 15px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 10px;

            outline: none;

        }


        .category-search input:focus {

            border-color:
                #2563eb;

            box-shadow:0 0 0 3px
                rgba(
                    37,
                    99,
                    235,
                    0.10
                );

        }


        .category-table-wrapper {

            width: 100%;

            overflow-x: auto;

        }


        .category-table {

            width: 100%;

            border-collapse: collapse;

            min-width: 850px;

        }


        .category-table th {

            padding:
                15px
                20px;

            text-align: left;

            background:
                #f8fafc;

            color: #64748b;

            font-size: 12px;

            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: .5px;

        }


        .category-table td {

            padding:
                16px
                20px;

            border-top:
                1px solid
                #eef2f7;

            color: #334155;

            vertical-align: middle;

        }


        .category-table tr:hover td {

            background:
                #f8fbff;

        }


        .category-image {

            width: 58px;

            height: 58px;

            border-radius: 14px;

            overflow: hidden;

            background:
                linear-gradient(
                    135deg,
                    #e8f1ff,
                    #f4f8ff
                );

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 25px;

        }


        .category-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .category-name {

            color: #0f172a;

            font-weight: 800;

        }


        .category-id {

            color: #94a3b8;

            font-size: 12px;

        }


        .product-count-badge {

            display: inline-flex;

            align-items: center;

            padding:
                6px
                10px;

            border-radius: 999px;

            background:
                #eaf2ff;

            color:
                #1d4ed8;

            font-size: 12px;

            font-weight: 800;

        }


        .product-count-badge.empty {

            background:
                #f1f5f9;

            color:
                #64748b;

        }


        .category-actions {

            display: flex;

            gap: 8px;

        }


        .category-action-btn {

            display: inline-flex;

            align-items: center;

            justify-content: center;

            min-height: 36px;

            padding:
                0
                12px;

            border-radius: 9px;

            text-decoration: none;

            font-size: 12px;

            font-weight: 800;

            border: none;

            cursor: pointer;

        }


        .category-edit-btn {

            background:
                #eaf2ff;

            color:
                #1d4ed8;

        }


        .category-delete-btn {

            background:
                #fee2e2;

            color:
                #b91c1c;

        }


        .category-delete-btn.disabled {

            background:
                #f1f5f9;

            color:
                #94a3b8;

            cursor: not-allowed;

        }


        .category-empty-state {

            padding:
                70px
                30px;

            text-align: center;

        }


        .category-empty-state-icon {

            font-size: 50px;

            margin-bottom: 15px;

        }


        .category-empty-state h3 {

            margin:
                0
                0
                8px;

            color:
                #0f172a;

        }


        .category-empty-state p {

            margin: 0;

            color:
                #64748b;

        }


        /*
        |--------------------------------------------------------------------------
        | MODAL|--------------------------------------------------------------------------
        */

        .category-modal-overlay {

            position: fixed;

            inset: 0;

            z-index: 9999;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;

            background:
                rgba(
                    15,
                    23,
                    42,
                    0.55
                );

        }


        .category-modal {

            width: 100%;

            max-width: 560px;

            max-height: 90vh;

            overflow-y: auto;

            padding: 28px;

            background: #ffffff;

            border-radius: 20px;

            box-shadow:
                0 30px 80px
                rgba(
                    15,
                    23,
                    42,
                    0.25
                );

        }


        .category-modal-header {

            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-bottom: 22px;

        }


        .category-modal-header h2 {

            margin: 0;

            color:
                #0f172a;

            font-size: 22px;

        }


        .category-modal-close {

            width: 36px;

            height: 36px;

            border: none;

            border-radius: 50%;

            background:
                #f1f5f9;

            cursor: pointer;

            font-size: 20px;

        }


        .category-form-group {

            margin-bottom: 20px;

        }


        .category-form-group label {

            display: block;

            margin-bottom: 8px;

            color:
                #334155;

            font-size: 13px;

            font-weight: 800;

        }


        .category-form-group input[type="text"],
        .category-form-group input[type="file"] {

            width: 100%;

            box-sizing: border-box;

            padding: 12px 14px;

            border:
                1px solid
                #cbd5e1;

            border-radius: 10px;

            background:
                #ffffff;

        }


        .category-form-group small {

            display: block;

            margin-top: 7px;

            color:
                #64748b;

            font-size: 12px;

        }


        .category-current-image {

            width: 90px;

            height: 90px;

            margin-bottom: 12px;

            overflow: hidden;

            border-radius: 15px;

            background:
                #eef4ff;

        }


        .category-current-image img {

            width: 100%;

            height: 100%;

            object-fit: cover;

        }


        .category-modal-actions {

            display: flex;

            justify-content: flex-end;

            gap: 10px;

            margin-top: 25px;

        }


        .category-btn {

            padding:
                11px
                17px;

            border: none;

            border-radius: 10px;

            cursor: pointer;

            text-decoration: none;

            font-size: 13px;

            font-weight: 800;

        }


        .category-btn-secondary {

            background:
                #f1f5f9;

            color:
                #475569;

        }


        .category-btn-primary {

            background:
                #2563eb;

            color:
                #ffffff;

        }


        .category-alert {

            margin-bottom: 20px;

            padding:
                15px
                18px;

            border-radius: 12px;

            font-size: 13px;

            line-height: 1.6;

        }


        .category-alert-error {

            background:
                #fee2e2;

            color:
                #991b1b;

            border:
                1px solid
                #fecaca;

        }


        .category-alert-success {

            background:
                #dcfce7;color:
                #166534;

            border:
                1px solid
                #bbf7d0;

        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 800px) {

            .admin-category-page {

                padding: 20px 15px;

            }


            .category-page-header {

                flex-direction: column;

            }


            .category-stats {

                grid-template-columns: 1fr;

            }


            .category-card-toolbar {

                flex-direction: column;

                align-items: stretch;

            }


            .category-search {

                max-width: none;

            }

        }

    </style>

</head>


<body>


<?php

/*
|--------------------------------------------------------------------------
| NAVBAR
|--------------------------------------------------------------------------
*/

$navbarPath =
    DIR .
    '/../includes/navbar.php';

if (
    file_exists($navbarPath)
) {

    require_once $navbarPath;

}


/*
|--------------------------------------------------------------------------
| ADMIN SIDEBAR
|--------------------------------------------------------------------------
*/

$sidebarPath =
    DIR .
    '/../includes/admin_sidebar.php';

if (
    file_exists($sidebarPath)
) {

    require_once $sidebarPath;

}

?>


<main class="dashboard-main">

    <div class="admin-category-page">


        <!-- =====================================================
             PAGE HEADER
        ====================================================== -->

        <div class="category-page-header">

            <div>

                <h1>
                    Category Management
                </h1>

                <p>
                    Create, edit and manage product categories
                    on HochipoHub.
                </p>

            </div>


            <button
                type="button"
                class="category-btn category-btn-primary"
                onclick="openAddCategoryModal()"
            >

                + Add Category

            </button>

        </div>


        <!-- =====================================================
             ALERTS
        ====================================================== -->

        <?php if (!empty($errors)): ?>

            <div class="category-alert category-alert-error">

                <strong>
                    Please fix the following:
                </strong>

                <ul
                    style="
                        margin:8px 0 0;
                        padding-left:20px;
                    "
                >

                    <?php foreach ($errors as $error): ?>

                        <li>
                            <?= e($error) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>


        <?php if ($success !== ''): ?>

            <div
                class="
                    category-alert
                    category-alert-success
                "
            >

                <?= e($success) ?>

            </div>

        <?php endif; ?>


        <!-- =====================================================
             STATISTICS
        ====================================================== -->

        <div class="category-stats">


            <div class="category-stat-card">

                <span
                    class="category-stat-label"
                >
                    TOTAL CATEGORIES
                </span>

                <span
                    class="category-stat-number"
                >
                    <?= $totalCategories ?>
                </span>

            </div>


            <div class="category-stat-card">

                <span
                    class="category-stat-label">
                    CATEGORIES IN USE
                </span>

                <span
                    class="category-stat-number"
                >
                    <?= $categoriesWithProducts ?>
                </span>

            </div>


            <div class="category-stat-card">

                <span
                    class="category-stat-label"
                >
                    PRODUCTS ASSIGNED
                </span>

                <span
                    class="category-stat-number"
                >
                    <?= $totalProductsInCategories ?>
                </span>

            </div>


        </div>


        <!-- =====================================================
             CATEGORY MANAGEMENT CARD
        ====================================================== -->

        <section
            class="category-management-card"
        >


            <!-- TOOLBAR -->

            <div
                class="category-card-toolbar"
            >

                <form
                    method="GET"
                    action="categories.php"
                    class="category-search"
                >

                    <input
                        type="search"
                        name="search"
                        value="<?= e($search) ?>"
                        placeholder="Search categories..."
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


                    <?php if ($search !== ''): ?>

                        <a
                            href="categories.php"
                            class="
                                category-btn
                                category-btn-secondary
                            "
                        >
                            Clear
                        </a>

                    <?php endif; ?>

                </form>


                <span
                    style="
                        color:#64748b;
                        font-size:13px;
                        font-weight:700;
                    "
                >

                    <?= $totalCategories ?>

                    categor<?= $totalCategories === 1
                        ? 'y'
                        : 'ies' ?>

                </span>

            </div>


            <!-- =================================================
                 TABLE
            ================================================== -->

            <?php if (!empty($categories)): ?>

                <div
                    class="category-table-wrapper"
                >

                    <table
                        class="category-table"
                    >

                        <thead>

                            <tr>

                                <th>
                                    Image
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
                                    Actions
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach (
                                $categories
                                as $category
                            ): ?>


                                <?php

                                $categoryId =
                                    (int)$category[
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

                                ?>


                                <tr>


                                    <!-- IMAGE -->

                                    <td>

                                        <div
                                            class="category-image"
                                        >

                                            <?php if (
                                                !empty(
                                                    $categoryImage
                                                )
                                            ): ?>

                                                <img
                                                    src="<?= e(
                                                        '../uploads/categories/' .
                                                        $categoryImage
                                                    ) ?>"
                                                    alt="<?= e(
                                                        $categoryName
                                                    ) ?>"
                                                    loading="lazy"
                                                >

                                            <?php else: ?>

                                                🛍️

                                            <?php endif; ?>

                                        </div>

                                    </td>


                                    <!-- NAME -->

                                    <td>

                                        <div
                                            class="category-name"
                                        >

                                            <?= e(
                                                $categoryName
                                            ) ?>

                                        </div>

                                        <div
                                            class="category-id"
                                        >

                                            ID:
                                            #<?= $categoryId ?>

                                        </div>

                                    </td>


                                    <!-- PRODUCT COUNT -->

                                    <td>

                                        <span
                                            class="
                                                product-count-badge
                                                <?= $productCount === 0
                                                    ? 'empty'
                                                    : '' ?>
                                            "
                                        >

                                            <?= $productCount ?>

                                            <?= $productCount === 1
                                                ? 'product'
                                                : 'products' ?>

                                        </span>

                                    </td>


                                    <!-- CREATED -->

                                    <td><?php

                                        $createdTimestamp =
                                            strtotime(
                                                $category[
                                                    'created_at'
                                                ]
                                            );

                                        ?>

                                        <?= $createdTimestamp
                                            ? e(
                                                date(
                                                    'd M Y',
                                                    $createdTimestamp
                                                )
                                            )
                                            : '-' ?>

                                    </td>


                                    <!-- ACTIONS -->

                                    <td>

                                        <div
                                            class="
                                                category-actions
                                            "
                                        >


                                            <!-- EDIT -->

                                            <a
                                                href="
                                                    categories.php
                                                    ?edit=<?= $categoryId ?>
                                                "
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
                                                    title="
                                                        Cannot delete a category
                                                        that contains products.
                                                    "
                                                >

                                                    Delete

                                                </button>

                                            <?php else: ?>

                                                <form
                                                    method="POST"
                                                    style="
                                                        display:inline;
                                                    "
                                                    onsubmit="
                                                        return confirmDelete(
                                                            '<?= e(
                                                                $categoryName
                                                            ) ?>'
                                                        );
                                                    "
                                                ><input
                                                        type="hidden"
                                                        name="csrf_token"
                                                        value="<?= e(
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


                <div
                    class="
                        category-empty-state
                    "
                >

                    <div
                        class="
                            category-empty-state-icon
                        "
                    >
                        🗂️
                    </div>

                    <h3>
                        <?php if (
                            $search !== ''
                        ): ?>

                            No categories found

                        <?php else: ?>

                            No categories yet

                        <?php endif; ?>
                    </h3>

                    <p>

                        <?php if (
                            $search !== ''
                        ): ?>

                            Try another search keyword.

                        <?php else: ?>

                            Add your first category
                            to get started.

                        <?php endif; ?>

                    </p>

                </div>

            <?php endif; ?>


        </section>


    </div>

</main>


<!-- =========================================================
     ADD CATEGORY MODAL
========================================================= -->

<div
    id="addCategoryModal"
    class="category-modal-overlay"
    style="display:none;"
    onclick="closeModalOnOverlay(event)"
>


    <div
        class="category-modal"
        onclick="event.stopPropagation();"
    >


        <div
            class="category-modal-header"
        >

            <h2>
                Add Category
            </h2>


            <button
                type="button"
                class="category-modal-close"
                onclick="closeAddCategoryModal()"
            >
                ×
            </button>

        </div>


        <form
            method="POST"enctype="multipart/form-data"
        >


            <input
                type="hidden"
                name="csrf_token"
                value="<?= e($csrfToken) ?>"
            >


            <input
                type="hidden"
                name="action"
                value="add"
            >


            <div
                class="category-form-group"
            >

                <label
                    for="add_category_name"
                >
                    Category Name
                </label>


                <input
                    type="text"
                    id="add_category_name"
                    name="category_name"
                    maxlength="100"
                    required
                    placeholder="e.g. Fashion"
                >

            </div>


            <div
                class="category-form-group"
            >

                <label
                    for="add_category_image"
                >
                    Category Image
                </label>


                <input
                    type="file"
                    id="add_category_image"
                    name="category_image"
                    accept=".jpg,.jpeg,.png,.webp"
                >


                <small>
                    JPG, PNG or WEBP. Maximum 5MB.
                </small>

            </div>


            <div
                class="category-modal-actions"
            >

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


<!-- =========================================================
     EDIT CATEGORY MODAL
========================================================= -->

<?php if (
    $editCategory
): ?>


    <div
        id="editCategoryModal"
        class="category-modal-overlay"
        onclick="closeModalOnOverlay(event)"
    >


        <div
            class="category-modal"
            onclick="event.stopPropagation();"
        >


            <div
                class="category-modal-header"
            >

                <h2>
                    Edit Category
                </h2>


                <a
                    href="categories.php"
                    class="category-modal-close"
                    style="
                        display:flex;
                        align-items:center;
                        justify-content:center;
                        text-decoration:none;
                        color:#0f172a;
                    "
                >
                    ×
                </a>

            </div>


            <form
                method="POST"
                enctype="multipart/form-data"
            >


                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= e($csrfToken) ?>"
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


                <div
                    class="category-form-group"
                >

                    <label
                        for="edit_category_name"
                    >
                        Category Name
                    </label><input
                        type="text"
                        id="edit_category_name"
                        name="category_name"
                        maxlength="100"
                        required
                        value="<?= e(
                            $editCategory[
                                'category_name'
                            ]
                        ) ?>"
                    >

                </div>


                <div
                    class="category-form-group"
                >

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

                        <div
                            class="
                                category-current-image
                            "
                        >

                            <img
                                src="<?= e(
                                    '../uploads/categories/' .
                                    $editCategory[
                                        'category_image'
                                    ]
                                ) ?>"
                                alt="<?= e(
                                    $editCategory[
                                        'category_name'
                                    ]
                                ) ?>"
                            >

                        </div>

                    <?php else: ?>

                        <div
                            class="
                                category-current-image
                            "
                            style="
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                font-size:30px;
                            "
                        >
                            🛍️
                        </div>

                    <?php endif; ?>

                </div>


                <div
                    class="category-form-group"
                >

                    <label
                        for="edit_category_image"
                    >
                        Replace Image
                    </label>


                    <input
                        type="file"
                        id="edit_category_image"
                        name="category_image"
                        accept=".jpg,.jpeg,.png,.webp"
                    >


                    <small>
                        Leave empty to keep the current image.
                        JPG, PNG or WEBP. Maximum 5MB.
                    </small>

                </div>


                <div
                    class="category-modal-actions"
                >

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


<!-- =========================================================
     JAVASCRIPT
========================================================= -->

<script>

function openAddCategoryModal() {

    const modal =
        document.getElementById(
            'addCategoryModal'
        );

    if (modal) {

        modal.style.display =
            'flex';

    }

}


function closeAddCategoryModal() {

    const modal =
        document.getElementById(
            'addCategoryModal'
        );

    if (modal) {

        modal.style.display =
            'none';

    }

}


function closeModalOnOverlay(event) {

    if (
        event.target ===
        event.currentTarget
    ) {

        const addModal =
            document.getElementById(
                'addCategoryModal'
            );


        if (addModal) {

            addModal.style.display =
                'none';

        }

    }

}


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
| ESC KEY
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key === 'Escape'
        ) {

            closeAddCategoryModal();

        }

    }
);

</script>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

$footerPath =
    DIR .
    '/../includes/footer.php';

if (
    file_exists($footerPath)
) {

    require_once $footerPath;

}

?>

</body>

</html>