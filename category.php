<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CATEGORY PAGE
|--------------------------------------------------------------------------
| File:
| category.php
|
| Purpose:
| - Display all product categories
| - Allow customer to browse by category
| - Show product count for each category
| - Link selected category to product listing
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD DATABASE + SESSION + FUNCTIONS
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


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

$pageTitle = 'Categories - HochipoHub';


/*
|--------------------------------------------------------------------------
| GET CATEGORY ID
|--------------------------------------------------------------------------
|
| If user clicks a category:
|
| category.php?id=3
|
|--------------------------------------------------------------------------
*/

$selectedCategoryId = isset($_GET['id'])
    ? (int) $_GET['id']
    : 0;


/*
|--------------------------------------------------------------------------
| GET ALL CATEGORIES
|--------------------------------------------------------------------------
|
| Product count is calculated from products table.
|
|--------------------------------------------------------------------------
*/

$categories = [];

try {

    $stmt = $db->query("
        SELECT
            c.category_id,
            c.category_name,
            COUNT(p.product_id) AS product_count

        FROM categories c

        LEFT JOIN products p
            ON c.category_id = p.category_id

        GROUP BY
            c.category_id,
            c.category_name

        ORDER BY
            c.category_name ASC
    ");

    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    $categories = [];

}


/*
|--------------------------------------------------------------------------
| CATEGORY ICONS
|--------------------------------------------------------------------------
|
| These are visual only.
| The actual category names still come from database.
|
|--------------------------------------------------------------------------
*/

$categoryIcons = [

    'fashion' =>
        '👕',

    'clothing' =>
        '👕',

    'apparel' =>
        '👗',

    'electronics' =>
        '💻',

    'electronic' =>
        '💻',

    'gadgets' =>
        '📱',

    'beauty' =>
        '💄',

    'cosmetics' =>
        '💄',

    'food' =>
        '🍔',

    'food & beverage' =>
        '🍔',

    'beverage' =>
        '🥤',

    'home' =>
        '🏠',

    'home & living' =>
        '🏠',

    'living' =>
        '🛋️',

    'books' =>
        '📚',

    'education' =>
        '📚',

    'sports' =>
        '⚽',

    'sport' =>
        '⚽',

    'health' =>
        '💊',

    'health & wellness' =>
        '💚',

    'accessories' =>
        '👜',

    'shoes' =>
        '👟',

    'stationery' =>
        '✏️',

    'others' =>
        '✨',

    'other' =>
        '✨'

];


/*
|--------------------------------------------------------------------------
| CATEGORY COLORS
|--------------------------------------------------------------------------
|
| CSS classes are used instead of inline styling.
|
|--------------------------------------------------------------------------
*/

$categoryColorClasses = [

    'category-blue',

    'category-purple',

    'category-pink',

    'category-cyan',

    'category-indigo',

    'category-violet'

];


/*
|--------------------------------------------------------------------------
| HELPER - CATEGORY ICON
|--------------------------------------------------------------------------
*/

function getCategoryIcon(
    $categoryName,
    $categoryIcons
) {

    $key = strtolower(
        trim($categoryName)
    );

    return $categoryIcons[$key]
        ?? '🛍️';
}


/*
|--------------------------------------------------------------------------
| HELPER - CATEGORY COLOR
|--------------------------------------------------------------------------
*/

function getCategoryColor(
    $index,
    $categoryColorClasses
) {

    return $categoryColorClasses[
        $index %
        count($categoryColorClasses)
    ];

}


/*
|--------------------------------------------------------------------------
| SELECTED CATEGORY
|--------------------------------------------------------------------------
*/

$selectedCategory = null;

if ($selectedCategoryId > 0) {

    foreach ($categories as $category) {

        if (
            (int) $category['category_id']
            === $selectedCategoryId
        ) {

            $selectedCategory = $category;

            break;

        }

    }

}


/*
|--------------------------------------------------------------------------
| HEADER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';

require_once __DIR__ . '/includes/navbar.php';

?>


<style>

/*
|--------------------------------------------------------------------------
| CATEGORY PAGE
|--------------------------------------------------------------------------
*/

.category-page {

    min-height: 100vh;

    padding:
        55px
        25px
        80px;

    background:
        linear-gradient(
            180deg,
            #f4f8ff 0%,
            #ffffff 45%,
            #f7faff 100%
        );

}


.category-container {

    width: 100%;

    max-width: 1250px;

    margin: 0 auto;

}


/*
|--------------------------------------------------------------------------
| HERO
|--------------------------------------------------------------------------
*/

.category-hero {

    position: relative;

    overflow: hidden;

    border-radius: 30px;

    padding:
        55px
        55px
        50px;

    margin-bottom: 45px;

    background:
        linear-gradient(
            135deg,
            #071a52 0%,
            #123b9e 48%,
            #1877ff 100%
        );

    color: white;

    box-shadow:
        0 20px 55px
        rgba(
            13,
            71,
            161,
            0.20
        );

}


.category-hero::before {

    content: "";

    position: absolute;

    width: 260px;

    height: 260px;

    border-radius: 50%;

    right: -70px;

    top: -100px;

    background:
        rgba(
            255,
            255,
            255,
            0.10
        );

}


.category-hero::after {

    content: "";

    position: absolute;

    width: 180px;

    height: 180px;

    border-radius: 50%;

    right: 180px;

    bottom: -100px;

    background:
        rgba(
            0,
            213,
            255,
            0.15
        );

}


.category-hero-content {

    position: relative;

    z-index: 2;

    max-width: 720px;

}


.category-label {

    display: inline-flex;

    align-items: center;

    gap: 8px;

    margin-bottom: 14px;

    padding:
        7px
        13px;

    border-radius: 999px;

    background:
        rgba(
            255,
            255,
            255,
            0.12
        );

    border:
        1px solid
        rgba(
            255,
            255,
            255,
            0.18
        );

    font-size: 12px;

    font-weight: 800;

    letter-spacing: 1.5px;

}


.category-hero h1 {

    margin: 0 0 15px;

    font-size: clamp(
        36px,
        5vw,
        62px
    );

    line-height: 1.05;

    font-weight: 900;

    letter-spacing: -2px;

}


.category-hero h1 span {

    color: #62e8ff;

}


.category-hero p {

    max-width: 620px;

    margin: 0;

    color:
        rgba(
            255,
            255,
            255,
            0.78
        );

    font-size: 17px;

    line-height: 1.7;

}


/*
|--------------------------------------------------------------------------
| CATEGORY HEADER
|--------------------------------------------------------------------------
*/

.category-section-header {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    gap: 20px;

    margin-bottom: 25px;

}


.category-section-header h2 {

    margin: 0;

    color: #081b4b;

    font-size: 28px;

    font-weight: 900;

}


.category-section-header p {

    margin:
        7px
        0
        0;

    color: #71809d;

    font-size: 14px;

}


.category-count {

    padding:
        9px
        14px;

    border-radius: 999px;

    background: #e9f1ff;

    color: #1459d9;

    font-size: 13px;

    font-weight: 800;

}


/*
|--------------------------------------------------------------------------
| CATEGORY GRID
|--------------------------------------------------------------------------
*/

.category-grid {

    display: grid;

    grid-template-columns:
        repeat(
            3,
            minmax(
                0,
                1fr
            )
        );

    gap: 22px;

}


/*
|--------------------------------------------------------------------------
| CATEGORY CARD
|--------------------------------------------------------------------------
*/

.category-card {

    position: relative;

    display: flex;

    flex-direction: column;

    min-height: 230px;

    padding: 27px;

    overflow: hidden;

    text-decoration: none;

    border-radius: 24px;

    background: white;

    border:
        1px solid
        #e5ebf7;

    box-shadow:
        0 10px 30px
        rgba(
            18,
            56,
            120,
            0.07
        );

    transition:
        transform 0.25s ease,
        box-shadow 0.25s ease,
        border-color 0.25s ease;

}


.category-card:hover {

    transform:
        translateY(-7px);

    box-shadow:
        0 18px 45px
        rgba(
            18,
            56,
            120,
            0.14
        );

    border-color:
        #9fc2ff;

}


.category-card::after {

    content: "";

    position: absolute;

    width: 120px;

    height: 120px;

    border-radius: 50%;

    right: -45px;

    bottom: -45px;

    opacity: 0.10;

}


.category-blue::after {

    background: #1769ff;

}


.category-purple::after {

    background: #7b4dff;

}


.category-pink::after {

    background: #ff4d9d;

}


.category-cyan::after {

    background: #00bfe8;

}


.category-indigo::after {

    background: #465cff;

}


.category-violet::after {

    background: #a34dff;

}


/*
|--------------------------------------------------------------------------
| ICON
|--------------------------------------------------------------------------
*/

.category-icon {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 68px;

    height: 68px;

    margin-bottom: 22px;

    border-radius: 20px;

    background:
        linear-gradient(
            135deg,
            #e7f0ff,
            #f4f8ff
        );

    font-size: 32px;

}


.category-card h3 {

    margin:
        0
        0
        7px;

    color: #091b46;

    font-size: 20px;

    font-weight: 900;

}


.category-card p {

    margin: 0;

    color: #7786a3;

    font-size: 13px;

    line-height: 1.5;

}


.category-card-bottom {

    display: flex;

    justify-content: space-between;

    align-items: center;

    margin-top: auto;

    padding-top: 20px;

}


.category-products {

    color: #52709f;

    font-size: 12px;

    font-weight: 700;

}


.category-arrow {

    display: flex;

    align-items: center;

    justify-content: center;

    width: 35px;

    height: 35px;

    border-radius: 50%;

    background: #edf4ff;

    color: #1769ff;

    font-size: 17px;

    font-weight: 900;

    transition:
        transform 0.2s ease,
        background 0.2s ease;

}


.category-card:hover
.category-arrow {

    transform:
        translateX(4px);

    background: #1769ff;

    color: white;

}


/*
|--------------------------------------------------------------------------
| EMPTY STATE
|--------------------------------------------------------------------------
*/

.category-empty {

    padding: 70px 30px;

    text-align: center;

    background: white;

    border:
        1px solid
        #e4eaf5;

    border-radius: 24px;

}


.category-empty-icon {

    font-size: 55px;

    margin-bottom: 15px;

}


.category-empty h3 {

    margin: 0 0 8px;

    color: #10244e;

    font-size: 22px;

}


.category-empty p {

    margin: 0;

    color: #71809d;

}


/*
|--------------------------------------------------------------------------
| SELECTED CATEGORY
|--------------------------------------------------------------------------
*/

.selected-category {

    margin-top: 45px;

    padding: 25px;

    border-radius: 22px;

    background:
        #eef5ff;

    border:
        1px solid
        #cfe0ff;

}


.selected-category strong {

    color: #0d3f9c;

}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 950px) {

    .category-grid {

        grid-template-columns:
            repeat(
                2,
                minmax(
                    0,
                    1fr
                )
            );

    }

}


@media (max-width: 650px) {

    .category-page {

        padding:
            30px
            15px
            60px;

    }


    .category-hero {

        padding:
            35px
            25px;

        border-radius: 23px;

    }


    .category-hero h1 {

        font-size: 40px;

        letter-spacing: -1.5px;

    }


    .category-hero p {

        font-size: 14px;

    }


    .category-grid {

        grid-template-columns: 1fr;

        gap: 15px;

    }


    .category-card {

        min-height: 205px;

    }


    .category-section-header {

        align-items: flex-start;

        flex-direction: column;

    }

}

</style>


<main class="category-page">

    <div class="category-container">


        <!-- =====================================================
             HERO
        ====================================================== -->

        <section class="category-hero">

            <div class="category-hero-content">

                <div class="category-label">
                    ✦ HOCHIPOHUB CATEGORIES
                </div>

                <h1>
                    Shop by
                    <span>Category.</span>
                </h1>

                <p>
                    Explore products from different categories
                    and discover something that fits exactly
                    what you're looking for.
                </p>

            </div>

        </section>


        <!-- =====================================================
             CATEGORY HEADER
        ====================================================== -->

        <div class="category-section-header">

            <div>

                <h2>
                    Explore Categories
                </h2>

                <p>
                    Choose a category to start shopping.
                </p>

            </div>


            <div class="category-count">

                <?= count($categories) ?>

                <?= count($categories) === 1
                    ? 'Category'
                    : 'Categories' ?>

            </div>

        </div>


        <!-- =====================================================
             CATEGORY GRID
        ====================================================== -->

        <?php if (empty($categories)): ?>


            <div class="category-empty">

                <div class="category-empty-icon">
                    🛍️
                </div>

                <h3>
                    No categories available
                </h3>

                <p>
                    Categories will appear here once they
                    have been added to HochipoHub.
                </p>

            </div>


        <?php else: ?>


            <div class="category-grid">


                <?php foreach (
                    $categories
                    as $index => $category
                ): ?>


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

                    $productCount =
                        (int)
                        $category[
                            'product_count'
                        ];

                    $icon =
                        getCategoryIcon(
                            $categoryName,
                            $categoryIcons
                        );

                    $colorClass =
                        getCategoryColor(
                            $index,
                            $categoryColorClasses
                        );

                    ?>


                    <a
                        href="<?= $baseUrl ?>product.php?category_id=<?= $categoryId ?>"
                        class="category-card <?= e($colorClass) ?>"
                    >


                        <div class="category-icon">

                            <?= $icon ?>

                        </div>


                        <h3>

                            <?= e(
                                $categoryName
                            ) ?>

                        </h3>


                        <p>

                            Discover products
                            available in this category.

                        </p>


                        <div class="category-card-bottom">

                            <span class="category-products">

                                <?= $productCount ?>

                                <?= $productCount === 1
                                    ? 'product'
                                    : 'products' ?>

                            </span>


                            <span class="category-arrow">

                                →

                            </span>

                        </div>


                    </a>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


        <!-- =====================================================
             SELECTED CATEGORY INFO
        ====================================================== -->

        <?php if ($selectedCategory): ?>

            <div class="selected-category">

                You're viewing:

                <strong>

                    <?= e(
                        $selectedCategory[
                            'category_name'
                        ]
                    ) ?>

                </strong>

            </div>

        <?php endif; ?>


    </div>

</main>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/footer.php';

?>