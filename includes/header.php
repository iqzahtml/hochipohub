<?php

/*
|--------------------------------------------------------------------------
| HochipoHub Header
|--------------------------------------------------------------------------
|
| Loads:
| - config
| - database
| - functions
| - session
| - CSS
|
|--------------------------------------------------------------------------
*/


require_once dirname(__DIR__) . '/config.php';

require_once dirname(__DIR__) . '/database/db.php';

require_once dirname(__DIR__) . '/includes/session.php';

require_once dirname(__DIR__) . '/includes/functions.php';



/*
|--------------------------------------------------------------------------
| Page Settings
|--------------------------------------------------------------------------
*/


$pageTitle = $pageTitle ?? SITE_NAME;

$pageDescription = $pageDescription ?? 
'HochipoHub - Discover Local Products';



/*
|--------------------------------------------------------------------------
| User Data
|--------------------------------------------------------------------------
*/


$loggedIn = isLogin();

$userID = currentUserID();

$userName = currentUserName();

$userRole = currentUserRole();



/*
|--------------------------------------------------------------------------
| Cart & Wishlist Count
|--------------------------------------------------------------------------
*/


$cartCount = 0;

$wishlistCount = 0;


if($loggedIn && $userRole == 'customer'){


    $cartCount = getCartCount($userID);


    $wishlistCount = getWishlistCount($userID);


}


?>

<!DOCTYPE html>

<html lang="en">


<head>


<meta charset="UTF-8">


<meta name="viewport" content="width=device-width, initial-scale=1.0">


<title>

<?= htmlspecialchars($pageTitle); ?>

| <?= SITE_NAME; ?>

</title>



<meta name="description"

content="<?= htmlspecialchars($pageDescription); ?>">



<!-- Google Font -->

<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>


<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">



<!-- Font Awesome -->

<link rel="stylesheet"

href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">



<!-- Main CSS -->


<link rel="stylesheet"

href="<?= BASE_URL; ?>css/style.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/responsive.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/modal.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/login.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/product.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/dashboard.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/cart.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/checkout.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/vendor.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/admin.css">



<link rel="stylesheet"

href="<?= BASE_URL; ?>css/wishlist.css">



</head>



<body>


<?php

/*
|--------------------------------------------------------------------------
| Navbar
|--------------------------------------------------------------------------
*/

include dirname(__FILE__) . '/navbar.php';


?>


<main class="main-container">