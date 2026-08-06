<?php


require_once dirname(__DIR__).'/config.php';

require_once dirname(__DIR__).'/database/db.php';

require_once __DIR__.'/session.php';

require_once __DIR__.'/functions.php';



$pageTitle =
$pageTitle ?? SITE_NAME;


$currentPage =
basename($_SERVER['PHP_SELF']);


$isLoggedIn =
isLoggedIn();



?>

<!DOCTYPE html>

<html lang="en">


<head>


<meta charset="UTF-8">


<meta name="viewport"
content="width=device-width, initial-scale=1.0">



<title>

<?= htmlspecialchars($pageTitle); ?>

</title>



<link rel="stylesheet"
href="<?= BASE_URL ?>css/style.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/responsive.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/modal.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/login.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/product.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/cart.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/vendor.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/admin.css">


<link rel="stylesheet"
href="<?= BASE_URL ?>css/wishlist.css">



</head>


<body>


<?php include __DIR__.'/navbar.php'; ?>



<main>