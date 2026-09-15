<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CUSTOMER MESSAGES
|--------------------------------------------------------------------------
| File: messages.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';


requireLogin();


$db = getDB();


if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| CUSTOMER ACCESS
|--------------------------------------------------------------------------
*/

$customerId =
    (int) (
        $_SESSION['user_id']
        ?? 0
    );


$currentRole =
    strtolower(
        trim(
            (string) (
                $_SESSION['role']
                ?? ''
            )
        )
    );


if (
    $customerId <= 0 ||
    $currentRole !== 'customer'
) {

    redirect(
        BASE_URL .
        'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('messageEscape')) {

    function messageEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('messageInitial')) {

    function messageInitial($name): string
    {
        $name =
            trim(
                (string) $name
            );

        if ($name === '') {
            return 'S';
        }

        if (
            function_exists(
                'mb_substr'
            )
        ) {

            return strtoupper(
                mb_substr(
                    $name,
                    0,
                    1
                )
            );
        }

        return strtoupper(
            substr(
                $name,
                0,
                1
            )
        );
    }
}


if (!function_exists('messageDate')) {

    function messageDate($date): string
    {
        if (empty($date)) {
            return '';
        }

        $timestamp =
            strtotime(
                (string) $date
            );

        if (!$timestamp) {
            return '';
        }

        if (
            date(
                'Y-m-d',
                $timestamp
            )
            ===
            date('Y-m-d')
        ) {

            return date(
                'h:i A',
                $timestamp
            );
        }

        return date(
            'd M Y',
            $timestamp
        );
    }
}


if (!function_exists('messageLogoUrl')) {

    function messageLogoUrl(
        $logo,
        $baseUrl
    ): string {

        $logo =
            trim(
                (string) $logo
            );

        if ($logo === '') {
            return '';
        }

        if (
            strpos(
                $logo,
                'http://'
            ) === 0 ||
            strpos(
                $logo,
                'https://'
            ) === 0
        ) {
            return $logo;
        }

        if (
            strpos(
                $logo,
                'uploads/'
            ) === 0
        ) {

            return
                $baseUrl .
                ltrim(
                    $logo,
                    '/'
                );
        }

        return
            $baseUrl .
            'uploads/vendors/' .
            rawurlencode(
                basename($logo)
            );
    }
}


/*
|--------------------------------------------------------------------------
| BASE URL
|--------------------------------------------------------------------------
*/

$baseUrl =
    defined('BASE_URL')
        ? rtrim(
            BASE_URL,
            '/'
        ) . '/'
        : '/hochipohub/';


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION['csrf_token']
    )
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
| NAV COUNTS
|--------------------------------------------------------------------------
*/

$cartCount = 0;
$wishlistCount = 0;


try {

    $stmt = $db->prepare("
        SELECT
            COALESCE(
                SUM(quantity),
                0
            )

        FROM cart

        WHERE customer_id = ?
    ");

    $stmt->execute([
        $customerId
    ]);

    $cartCount =
        (int)
        $stmt->fetchColumn();

} catch (Throwable $e) {

    $cartCount = 0;
}


try {

    $stmt = $db->prepare("
        SELECT
            COUNT(*)

        FROM wishlist

        WHERE user_id = ?
    ");

    $stmt->execute([
        $customerId
    ]);

    $wishlistCount =
        (int)
        $stmt->fetchColumn();

} catch (Throwable $e) {

    $wishlistCount = 0;
}


/*
|--------------------------------------------------------------------------
| URL PARAMETERS
|--------------------------------------------------------------------------
*/

$requestedVendorId =
    isset($_GET['vendor_id'])
        ? (int) $_GET['vendor_id']
        : 0;


$requestedOrderId =
    isset($_GET['order_id'])
        ? (int) $_GET['order_id']
        : 0;


$selectedConversationId =
    isset($_GET['conversation'])
        ? (int) $_GET['conversation']
        : 0;


/*
|--------------------------------------------------------------------------
| CREATE / FIND CONVERSATION
|--------------------------------------------------------------------------
*/

if ($requestedVendorId > 0) {

    try {

        /*
        |--------------------------------------------------------------------------
        | VERIFY VENDOR
        |--------------------------------------------------------------------------
        */

        $stmt = $db->prepare("
            SELECT
                vendor_id,
                business_name,
                approval_status

            FROM vendors

            WHERE vendor_id = ?

            AND approval_status = 'Approved'

            LIMIT 1
        ");

        $stmt->execute([
            $requestedVendorId
        ]);


        $requestedVendor =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if ($requestedVendor) {

            /*
            |--------------------------------------------------------------------------
            | VERIFY ORDER IF PROVIDED
            |--------------------------------------------------------------------------
            */

            if ($requestedOrderId > 0) {

                $stmt = $db->prepare("
                    SELECT
                        vo.vendor_order_id

                    FROM vendor_orders vo

                    INNER JOIN orders o
                        ON vo.order_id =
                           o.order_id

                    WHERE vo.order_id = ?

                    AND vo.vendor_id = ?

                    AND o.customer_id = ?

                    LIMIT 1
                ");

                $stmt->execute([
                    $requestedOrderId,
                    $requestedVendorId,
                    $customerId
                ]);


                if (
                    !$stmt->fetchColumn()
                ) {

                    $requestedOrderId = 0;
                }
            }


            /*
            |--------------------------------------------------------------------------
            | FIND EXISTING CONVERSATION
            |--------------------------------------------------------------------------
            */

            if ($requestedOrderId > 0) {

                $stmt = $db->prepare("
                    SELECT
                        conversation_id

                    FROM conversations

                    WHERE customer_id = ?

                    AND vendor_id = ?

                    AND order_id = ?

                    ORDER BY
                        conversation_id DESC

                    LIMIT 1
                ");

                $stmt->execute([
                    $customerId,
                    $requestedVendorId,
                    $requestedOrderId
                ]);

            } else {

                $stmt = $db->prepare("
                    SELECT
                        conversation_id

                    FROM conversations

                    WHERE customer_id = ?

                    AND vendor_id = ?

                    AND order_id IS NULL

                    ORDER BY
                        conversation_id DESC

                    LIMIT 1
                ");

                $stmt->execute([
                    $customerId,
                    $requestedVendorId
                ]);
            }


            $conversationId =
                (int)
                $stmt->fetchColumn();


            /*
            |--------------------------------------------------------------------------
            | CREATE CONVERSATION
            |--------------------------------------------------------------------------
            */

            if ($conversationId <= 0) {

                $stmt = $db->prepare("
                    INSERT INTO conversations
                    (
                        customer_id,
                        vendor_id,
                        order_id
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?
                    )
                ");

                $stmt->execute([
                    $customerId,
                    $requestedVendorId,
                    $requestedOrderId > 0
                        ? $requestedOrderId
                        : null
                ]);


                $conversationId =
                    (int)
                    $db->lastInsertId();
            }


            redirect(
                $baseUrl .
                'messages.php?conversation=' .
                $conversationId
            );
        }

    } catch (Throwable $e) {

        error_log(
            'Create conversation error: ' .
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| LOAD CONVERSATIONS
|--------------------------------------------------------------------------
*/

$conversations = [];


try {

    $stmt = $db->prepare("
        SELECT

            c.conversation_id,
            c.vendor_id,
            c.order_id,
            c.created_at,
            c.updated_at,

            v.business_name,
            v.business_logo,

            (
                SELECT
                    m.message

                FROM messages m

                WHERE m.conversation_id =
                      c.conversation_id

                ORDER BY
                    m.message_id DESC

                LIMIT 1
            ) AS last_message,

            (
                SELECT
                    m.created_at

                FROM messages m

                WHERE m.conversation_id =
                      c.conversation_id

                ORDER BY
                    m.message_id DESC

                LIMIT 1
            ) AS last_message_at,

            (
                SELECT
                    COUNT(*)

                FROM messages m

                WHERE m.conversation_id =
                      c.conversation_id

                AND m.sender_id != ?

                AND m.is_read = 0
            ) AS unread_count

        FROM conversations c

        INNER JOIN vendors v
            ON c.vendor_id =
               v.vendor_id

        WHERE c.customer_id = ?

        ORDER BY

            COALESCE(
                (
                    SELECT
                        MAX(
                            m2.created_at
                        )

                    FROM messages m2

                    WHERE m2.conversation_id =
                          c.conversation_id
                ),
                c.created_at
            ) DESC
    ");

    $stmt->execute([
        $customerId,
        $customerId
    ]);


    $conversations =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

} catch (Throwable $e) {

    error_log(
        'Load conversations error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| AUTO SELECT FIRST
|--------------------------------------------------------------------------
*/

if (
    $selectedConversationId <= 0 &&
    !empty($conversations)
) {

    $selectedConversationId =
        (int)
        $conversations[0][
            'conversation_id'
        ];
}


/*
|--------------------------------------------------------------------------
| LOAD SELECTED CONVERSATION
|--------------------------------------------------------------------------
*/

$selectedConversation = null;


if ($selectedConversationId > 0) {

    try {

        $stmt = $db->prepare("
            SELECT

                c.conversation_id,
                c.customer_id,
                c.vendor_id,
                c.order_id,

                v.business_name,
                v.business_logo,
                v.business_address,

                u.name
                    AS vendor_owner_name

            FROM conversations c

            INNER JOIN vendors v
                ON c.vendor_id =
                   v.vendor_id

            INNER JOIN users u
                ON v.user_id =
                   u.user_id

            WHERE c.conversation_id = ?

            AND c.customer_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $selectedConversationId,
            $customerId
        ]);


        $selectedConversation =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$selectedConversation) {

            $selectedConversationId = 0;
        }

    } catch (Throwable $e) {

        error_log(
            'Load selected conversation error: ' .
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| MARK MESSAGES READ
|--------------------------------------------------------------------------
*/

if ($selectedConversationId > 0) {

    try {

        $stmt = $db->prepare("
            UPDATE messages

            SET is_read = 1

            WHERE conversation_id = ?

            AND sender_id != ?
        ");

        $stmt->execute([
            $selectedConversationId,
            $customerId
        ]);

    } catch (Throwable $e) {

        error_log(
            'Mark message read error: ' .
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| SELECTED SELLER LOGO
|--------------------------------------------------------------------------
*/

$selectedSellerLogo = '';


if ($selectedConversation) {

    $selectedSellerLogo =
        messageLogoUrl(
            $selectedConversation[
                'business_logo'
            ]
            ?? '',
            $baseUrl
        );
}


/*
|--------------------------------------------------------------------------
| PAGE SETUP
|--------------------------------------------------------------------------
*/

$pageTitle =
    'Messages - HochipoHub';


$hideSiteMainWrapper =
    true;


$extraCSS = [
    'dashboard.css'
];


require_once __DIR__ .
    '/includes/header.php';


require_once __DIR__ .
    '/includes/customer_sidebar.php';

?>


<style>

* {
    box-sizing: border-box;
}


.hh-message-page {
    width: 100%;
    min-height: calc(100vh - 100px);

    padding:
        34px
        24px
        70px;

    overflow-x: hidden;

    color: #17233c;

    background:
        radial-gradient(
            circle at 5% 5%,
            rgba(37,99,235,.07),
            transparent 26%
        ),
        radial-gradient(
            circle at 95% 10%,
            rgba(59,130,246,.06),
            transparent 25%
        ),
        linear-gradient(
            180deg,
            #f3f7ff 0%,
            #f7f9fd 60%,
            #ffffff 100%
        );

    font-family:
        Inter,
        Poppins,
        Arial,
        sans-serif;
}


.hh-message-container {
    width: 100%;
    max-width: 1350px;
    margin: 0 auto;
}


/* HERO */

.hh-message-hero {
    position: relative;

    min-height: 220px;

    margin-bottom: 22px;

    padding:
        38px
        44px;

    overflow: hidden;

    display: grid;

    grid-template-columns:
        minmax(0,1fr)
        280px;

    align-items: center;

    gap: 30px;

    color: #fff;

    background:
        linear-gradient(
            115deg,
            #0b2c6b 0%,
            #174d9d 48%,
            #2c89ee 100%
        );

    border-radius: 27px;

    box-shadow:
        0 18px 45px
        rgba(23,79,165,.15);
}


.hh-message-hero-copy {
    position: relative;
    z-index: 2;
}


.hh-message-pill {
    min-height: 32px;

    padding: 0 12px;
    margin-bottom: 14px;

    display: inline-flex;
    align-items: center;
    gap: 7px;

    color: #fff;

    background:
        rgba(255,255,255,.11);

    border:
        1px solid
        rgba(255,255,255,.2);

    border-radius: 999px;

    font-size: 8px;
    font-weight: 900;
}


.hh-message-hero h1 {
    margin: 0 0 10px;

    font-size:
        clamp(
            34px,
            4.2vw,
            48px
        );

    font-weight: 800;

    color: #fff;
}


.hh-message-hero h1 span {
    color: #72e6f5;
}


.hh-message-hero p {
    max-width: 670px;

    margin: 0;

    color:
        rgba(255,255,255,.78);

    font-size: 11px;
    line-height: 1.75;
}


.hh-message-hero-art {
    position: relative;

    height: 150px;
}


.hh-message-main-icon {
    position: absolute;

    width: 118px;
    height: 118px;

    top: 16px;
    right: 60px;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #fff;

    background:
        rgba(255,255,255,.13);

    border:
        1px solid
        rgba(255,255,255,.2);

    border-radius: 30px;

    font-size: 45px;
}


/* CHAT APP */

.hh-chat-app {
    width: 100%;
    height: 650px;

    display: grid;

    grid-template-columns:
        330px
        minmax(0,1fr);

    overflow: hidden;

    background: #fff;

    border:
        1px solid #e1e8f1;

    border-radius: 22px;

    box-shadow:
        0 13px 35px
        rgba(33,64,112,.07);
}


/* LEFT */

.hh-chat-sidebar {
    overflow-y: auto;

    background: #fbfdff;

    border-right:
        1px solid #e7edf4;
}


.hh-chat-sidebar-head {
    min-height: 82px;

    padding:
        17px
        18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    background: #fff;

    border-bottom:
        1px solid #e8edf4;
}


.hh-chat-sidebar-title strong {
    display: block;

    margin-bottom: 3px;

    color: #17345d;

    font-size: 13px;
    font-weight: 900;
}


.hh-chat-sidebar-title span {
    color: #94a3b8;

    font-size: 8px;
}


.hh-conversation-count {
    min-width: 31px;
    height: 31px;

    padding: 0 8px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius: 999px;

    font-size: 8px;
    font-weight: 900;
}


.hh-conversation-link {
    position: relative;

    min-height: 80px;

    padding:
        14px
        16px;

    display: flex;

    align-items: center;

    gap: 11px;

    color: inherit;

    text-decoration: none;

    border-bottom:
        1px solid #edf1f6;
}


.hh-conversation-link:hover {
    background: #f3f8ff;
}


.hh-conversation-link.active {
    background:
        linear-gradient(
            90deg,
            #edf6ff,
            #f8fbff
        );
}


.hh-conversation-link.active::before {
    content: "";

    position: absolute;

    width: 3px;

    top: 12px;
    bottom: 12px;
    left: 0;

    background: #2563eb;

    border-radius:
        0
        999px
        999px
        0;
}


.hh-conversation-avatar {
    width: 45px;
    height: 45px;

    flex-shrink: 0;

    overflow: hidden;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #fff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #60a5fa
        );

    border-radius: 13px;

    font-size: 14px;
    font-weight: 900;
}


.hh-conversation-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.hh-conversation-copy {
    min-width: 0;
    flex: 1;
}


.hh-conversation-top {
    display: flex;

    justify-content: space-between;

    gap: 7px;
}


.hh-conversation-top strong {
    overflow: hidden;

    color: #243a58;

    font-size: 9px;
    font-weight: 900;

    text-overflow: ellipsis;
    white-space: nowrap;
}


.hh-conversation-time {
    color: #a0aec0;

    font-size: 7px;

    white-space: nowrap;
}


.hh-conversation-order {
    margin-top: 3px;

    color: #4d78b3;

    font-size: 7px;
}


.hh-conversation-bottom {
    margin-top: 5px;

    display: flex;

    gap: 6px;
}


.hh-conversation-preview {
    min-width: 0;
    flex: 1;

    overflow: hidden;

    color: #8896aa;

    font-size: 8px;

    text-overflow: ellipsis;

    white-space: nowrap;
}


.hh-unread-badge {
    min-width: 18px;
    height: 18px;

    padding: 0 5px;

    display: inline-flex;

    align-items: center;
    justify-content: center;

    color: #fff;

    background: #2563eb;

    border-radius: 999px;

    font-size: 7px;
}


/* RIGHT */

.hh-chat-main {
    min-width: 0;
    min-height: 0;

    display: flex;

    flex-direction: column;
}


.hh-chat-header {
    min-height: 82px;

    padding:
        14px
        19px;

    display: flex;

    align-items: center;

    gap: 12px;

    background: #fff;

    border-bottom:
        1px solid #e8edf4;
}


.hh-chat-header-copy strong {
    display: block;

    margin-bottom: 3px;

    color: #1c3557;

    font-size: 11px;
    font-weight: 900;
}


.hh-chat-header-copy span {
    display: flex;

    align-items: center;

    gap: 5px;

    color: #8998ad;

    font-size: 8px;
}


.hh-online-dot {
    width: 6px;
    height: 6px;

    display: inline-block;

    background: #22c55e;

    border-radius: 50%;
}


.hh-chat-header-actions {
    margin-left: auto;

    display: flex;

    gap: 7px;
}


.hh-order-link,
.hh-store-link {
    min-height: 34px;

    padding:
        0
        11px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    border-radius: 999px;

    font-size: 8px;
    font-weight: 800;

    text-decoration: none;
}


.hh-order-link {
    color: #7c3aed;

    background: #f5f3ff;

    border:
        1px solid #e9ddff;
}


.hh-store-link {
    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid #dbeafe;
}


/* MESSAGES */

.hh-chat-messages {
    min-height: 0;

    flex: 1;

    overflow-y: auto;

    padding:
        24px
        25px;

    background:
        #f8fbff;
}


.hh-message-row {
    width: 100%;

    margin-bottom: 12px;

    display: flex;

    justify-content: flex-start;
}


.hh-message-row.mine {
    justify-content: flex-end;
}


.hh-message-bubble {
    max-width: 68%;

    padding:
        10px
        13px;

    color: #35455e;

    background: #fff;

    border:
        1px solid #e2e8f0;

    border-radius:
        15px
        15px
        15px
        4px;
}


.hh-message-row.mine
.hh-message-bubble {
    color: #fff;

    background:
        linear-gradient(
            135deg,
            #2d6ae8,
            #1f5bc9
        );

    border-color:
        transparent;

    border-radius:
        15px
        15px
        4px
        15px;
}


.hh-message-text {
    font-size: 9px;

    line-height: 1.65;

    white-space: pre-wrap;

    overflow-wrap: anywhere;
}


.hh-message-meta {
    margin-top: 5px;

    opacity: .67;

    font-size: 7px;

    text-align: right;
}


/* EMPTY / LOADING */

.hh-chat-empty,
.hh-chat-loading {
    height: 100%;

    min-height: 340px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #93a2b7;

    text-align: center;
}


.hh-chat-empty-inner {
    max-width: 260px;
}


.hh-chat-empty-icon {
    width: 62px;
    height: 62px;

    margin:
        0 auto
        14px;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #3977dc;

    background: #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius: 18px;

    font-size: 25px;
}


.hh-chat-empty strong {
    display: block;

    margin-bottom: 5px;

    color: #354760;

    font-size: 11px;
    font-weight: 900;
}


.hh-chat-empty p {
    margin: 0;

    color: #94a3b8;

    font-size: 8px;

    line-height: 1.7;
}


/* FORM */

.hh-chat-form {
    min-height: 76px;

    padding:
        13px
        16px;

    display: flex;

    align-items: flex-end;

    gap: 9px;

    background: #fff;

    border-top:
        1px solid #e7edf5;
}


.hh-message-input-wrap {
    min-width: 0;
    flex: 1;
}


.hh-chat-form textarea {
    width: 100%;

    height: 48px;

    max-height: 120px;

    padding:
        13px
        15px;

    resize: none;

    outline: none;

    color: #334155;

    background: #f8fafc;

    border:
        1px solid #dce4ef;

    border-radius: 13px;

    font-family: inherit;

    font-size: 9px;
}


.hh-send-btn {
    width: 48px;
    height: 48px;

    display: flex;

    align-items: center;
    justify-content: center;

    color: #fff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #3b82f6
        );

    border: 0;

    border-radius: 13px;

    cursor: pointer;
}


.hh-send-btn:disabled {
    opacity: .55;

    cursor: not-allowed;
}


/* RESPONSIVE */

@media (max-width: 850px) {

    .hh-chat-app {
        grid-template-columns:
            270px
            minmax(0,1fr);
    }
}


@media (max-width: 680px) {

    .hh-message-page {
        padding:
            20px
            12px
            45px;
    }


    .hh-message-hero {
        grid-template-columns: 1fr;

        padding:
            25px
            22px;
    }


    .hh-message-hero-art {
        display: none;
    }


    .hh-chat-app {
        height: auto;

        display: block;
    }


    .hh-chat-sidebar {
        max-height: 270px;

        border-right: 0;

        border-bottom:
            1px solid #e7edf4;
    }


    .hh-chat-main {
        height: 510px;
    }
}

</style>


<main class="hh-message-page">

<div class="hh-message-container">


    <section class="hh-message-hero">


        <div class="hh-message-hero-copy">


            <span class="hh-message-pill">

                <i class="bi bi-chat-square-dots"></i>

                CUSTOMER MESSAGE CENTER

            </span>


            <h1>

                Chat with

                <span>
                    Sellers.
                </span>

            </h1>


            <p>

                Ask sellers about products,
                order status, pickup information,
                postage and vendor delivery
                directly from your HochipoHub account.

            </p>


        </div>


        <div class="hh-message-hero-art">


            <div class="hh-message-main-icon">

                <i class="bi bi-chat-dots"></i>

            </div>


        </div>


    </section>



    <section class="hh-chat-app">


        <aside class="hh-chat-sidebar">


            <div class="hh-chat-sidebar-head">


                <div class="hh-chat-sidebar-title">

                    <strong>
                        Conversations
                    </strong>

                    <span>
                        Your seller chats
                    </span>

                </div>


                <span class="hh-conversation-count">

                    <?= count(
                        $conversations
                    ) ?>

                </span>


            </div>



            <?php if (empty($conversations)): ?>


                <div class="hh-chat-empty">

                    <div class="hh-chat-empty-inner">

                        <div class="hh-chat-empty-icon">

                            <i class="bi bi-chat-square-text"></i>

                        </div>

                        <strong>
                            No conversations yet
                        </strong>

                        <p>
                            Open a product and
                            choose Chat with Seller.
                        </p>

                    </div>

                </div>


            <?php else: ?>


                <?php foreach (
                    $conversations
                    as $conversation
                ): ?>


                    <?php

                    $conversationId =
                        (int)
                        $conversation[
                            'conversation_id'
                        ];


                    $logoUrl =
                        messageLogoUrl(
                            $conversation[
                                'business_logo'
                            ]
                            ?? '',
                            $baseUrl
                        );

                    ?>


                    <a
                        href="<?= messageEscape(
                            $baseUrl .
                            'messages.php?conversation=' .
                            $conversationId
                        ) ?>"
                        class="
                            hh-conversation-link
                            <?= $conversationId ===
                                $selectedConversationId
                                ? 'active'
                                : '' ?>
                        "
                    >


                        <div class="hh-conversation-avatar">


                            <?php if ($logoUrl !== ''): ?>


                                <img
                                    src="<?= messageEscape(
                                        $logoUrl
                                    ) ?>"
                                    alt="<?= messageEscape(
                                        $conversation[
                                            'business_name'
                                        ]
                                    ) ?>"
                                >


                            <?php else: ?>


                                <?= messageEscape(
                                    messageInitial(
                                        $conversation[
                                            'business_name'
                                        ]
                                    )
                                ) ?>


                            <?php endif; ?>


                        </div>


                        <div class="hh-conversation-copy">


                            <div class="hh-conversation-top">

                                <strong>

                                    <?= messageEscape(
                                        $conversation[
                                            'business_name'
                                        ]
                                    ) ?>

                                </strong>


                                <span class="hh-conversation-time">

                                    <?= messageEscape(
                                        messageDate(
                                            $conversation[
                                                'last_message_at'
                                            ]
                                            ??
                                            $conversation[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>

                                </span>

                            </div>


                            <?php if (
                                !empty(
                                    $conversation[
                                        'order_id'
                                    ]
                                )
                            ): ?>


                                <div class="hh-conversation-order">

                                    <i class="bi bi-receipt"></i>

                                    Order
                                    #<?= (int)
                                        $conversation[
                                            'order_id'
                                        ] ?>

                                </div>


                            <?php endif; ?>


                            <div class="hh-conversation-bottom">


                                <span class="hh-conversation-preview">

                                    <?= messageEscape(
                                        $conversation[
                                            'last_message'
                                        ]
                                        ??
                                        'Start conversation'
                                    ) ?>

                                </span>


                                <?php if (
                                    (int)
                                    $conversation[
                                        'unread_count'
                                    ] > 0
                                ): ?>


                                    <span class="hh-unread-badge">

                                        <?= min(
                                            99,
                                            (int)
                                            $conversation[
                                                'unread_count'
                                            ]
                                        ) ?>

                                    </span>


                                <?php endif; ?>


                            </div>


                        </div>


                    </a>


                <?php endforeach; ?>


            <?php endif; ?>


        </aside>



        <div class="hh-chat-main">


            <?php if ($selectedConversation): ?>


                <header class="hh-chat-header">


                    <div class="hh-conversation-avatar">


                        <?php if (
                            $selectedSellerLogo !== ''
                        ): ?>


                            <img
                                src="<?= messageEscape(
                                    $selectedSellerLogo
                                ) ?>"
                                alt="<?= messageEscape(
                                    $selectedConversation[
                                        'business_name'
                                    ]
                                ) ?>"
                            >


                        <?php else: ?>


                            <?= messageEscape(
                                messageInitial(
                                    $selectedConversation[
                                        'business_name'
                                    ]
                                )
                            ) ?>


                        <?php endif; ?>


                    </div>


                    <div class="hh-chat-header-copy">


                        <strong>

                            <?= messageEscape(
                                $selectedConversation[
                                    'business_name'
                                ]
                            ) ?>

                        </strong>


                        <span>

                            <i class="hh-online-dot"></i>

                            Seller

                        </span>


                    </div>


                    <div class="hh-chat-header-actions">


                        <?php if (
                            !empty(
                                $selectedConversation[
                                    'order_id'
                                ]
                            )
                        ): ?>


                            <a
                                href="<?= messageEscape(
                                    $baseUrl .
                                    'order_details.php?id=' .
                                    (int)
                                    $selectedConversation[
                                        'order_id'
                                    ]
                                ) ?>"
                                class="hh-order-link"
                            >

                                <i class="bi bi-receipt"></i>

                                Order
                                #<?= (int)
                                    $selectedConversation[
                                        'order_id'
                                    ] ?>

                            </a>


                        <?php endif; ?>


                        <a
                            href="<?= messageEscape(
                                $baseUrl .
                                'vendor.php?id=' .
                                (int)
                                $selectedConversation[
                                    'vendor_id'
                                ]
                            ) ?>"
                            class="hh-store-link"
                        >

                            <i class="bi bi-shop"></i>

                            View Store

                        </a>


                    </div>


                </header>



                <div
                    class="hh-chat-messages"
                    id="chatMessages"
                >


                    <div class="hh-chat-loading">


                        <div class="hh-chat-empty-inner">


                            <div class="hh-chat-empty-icon">

                                <i class="bi bi-chat-dots"></i>

                            </div>


                            Loading messages...


                        </div>


                    </div>


                </div>



                <form
                    class="hh-chat-form"
                    id="messageForm"
                >


                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= messageEscape(
                            $csrfToken
                        ) ?>"
                    >


                    <input
                        type="hidden"
                        name="conversation_id"
                        value="<?= (int)
                            $selectedConversationId ?>"
                    >


                    <div class="hh-message-input-wrap">


                        <textarea
                            name="message"
                            id="messageInput"
                            maxlength="2000"
                            placeholder="Type your message to seller..."
                            required
                        ></textarea>


                    </div>


                    <button
                        type="submit"
                        class="hh-send-btn"
                        id="sendMessageButton"
                        aria-label="Send message"
                    >

                        <i class="bi bi-send-fill"></i>

                    </button>


                </form>


            <?php else: ?>


                <div class="hh-chat-empty">


                    <div class="hh-chat-empty-inner">


                        <div class="hh-chat-empty-icon">

                            <i class="bi bi-chat-dots"></i>

                        </div>


                        <strong>
                            Select a conversation
                        </strong>


                        <p>

                            Choose a seller conversation
                            from the left.

                        </p>


                    </div>


                </div>


            <?php endif; ?>


        </div>


    </section>


</div>

</main>


<?php if ($selectedConversation): ?>


<script>

const conversationId =
    <?= (int)
        $selectedConversationId ?>;


const currentUserId =
    <?= (int)
        $customerId ?>;


const baseUrl =
    <?= json_encode(
        $baseUrl
    ) ?>;


const chatMessages =
    document.getElementById(
        'chatMessages'
    );


const messageForm =
    document.getElementById(
        'messageForm'
    );


const messageInput =
    document.getElementById(
        'messageInput'
    );


const sendMessageButton =
    document.getElementById(
        'sendMessageButton'
    );


let previousMessageCount = -1;


/*
|--------------------------------------------------------------------------
| ESCAPE
|--------------------------------------------------------------------------
*/

function escapeHtml(value) {

    const div =
        document.createElement(
            'div'
        );

    div.textContent =
        value ?? '';

    return div.innerHTML;
}


/*
|--------------------------------------------------------------------------
| EMPTY
|--------------------------------------------------------------------------
*/

function emptyMessageState() {

    return `
        <div class="hh-chat-empty">

            <div class="hh-chat-empty-inner">

                <div class="hh-chat-empty-icon">

                    <i class="bi bi-chat-square-text"></i>

                </div>

                <strong>
                    No messages yet
                </strong>

                <p>
                    Start the conversation by
                    sending the seller a message.
                </p>

            </div>

        </div>
    `;
}


/*
|--------------------------------------------------------------------------
| ERROR
|--------------------------------------------------------------------------
*/

function messageLoadError(
    message
) {

    chatMessages.innerHTML = `

        <div class="hh-chat-empty">

            <div class="hh-chat-empty-inner">

                <div
                    class="hh-chat-empty-icon"
                    style="
                        color:#dc2626;
                        background:#fef2f2;
                        border-color:#fecaca;
                    "
                >

                    <i class="bi bi-exclamation-triangle"></i>

                </div>

                <strong>
                    Unable to load messages
                </strong>

                <p>
                    ${escapeHtml(message)}
                </p>

                <button
                    type="button"
                    onclick="loadMessages(true)"
                    style="
                        margin-top:14px;
                        min-height:36px;
                        padding:0 14px;
                        border:0;
                        border-radius:9px;
                        background:#2563eb;
                        color:#fff;
                        cursor:pointer;
                    "
                >
                    Try Again
                </button>

            </div>

        </div>
    `;
}


/*
|--------------------------------------------------------------------------
| LOAD MESSAGES
|--------------------------------------------------------------------------
*/

async function loadMessages(
    forceScroll = false
) {

    const url =
        baseUrl +
        'ajax/load_messages.php?conversation_id=' +
        encodeURIComponent(
            conversationId
        );


    try {

        const response =
            await fetch(
                url,
                {
                    credentials:
                        'same-origin',

                    cache:
                        'no-store',

                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest'
                    }
                }
            );


        const responseText =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    responseText
                );

        } catch (error) {

            console.error(
                'INVALID LOAD RESPONSE:',
                responseText
            );


            throw new Error(
                'Server returned invalid JSON.'
            );
        }


        if (!response.ok) {

            throw new Error(
                data.message ||
                'HTTP error ' +
                response.status
            );
        }


        if (
            data.success !== true
        ) {

            throw new Error(
                data.message ||
                'Unable to load messages.'
            );
        }


        if (
            !Array.isArray(
                data.messages
            )
        ) {

            throw new Error(
                'Invalid messages response.'
            );
        }


        const shouldScroll =

            forceScroll ||

            previousMessageCount === -1 ||

            (
                chatMessages.scrollHeight -
                chatMessages.scrollTop -
                chatMessages.clientHeight
            ) < 120;


        previousMessageCount =
            data.messages.length;


        if (
            data.messages.length === 0
        ) {

            chatMessages.innerHTML =
                emptyMessageState();

            return;
        }


        chatMessages.innerHTML =

            data.messages

                .map(

                    function (item) {


                        const mine =

                            Number(
                                item.sender_id
                            )

                            ===

                            Number(
                                currentUserId
                            );


                        return `

                            <div
                                class="
                                    hh-message-row
                                    ${
                                        mine
                                            ? 'mine'
                                            : ''
                                    }
                                "
                            >

                                <div class="hh-message-bubble">

                                    <div class="hh-message-text">${
                                        escapeHtml(
                                            item.message
                                        )
                                    }</div>

                                    <div class="hh-message-meta">${
                                        escapeHtml(
                                            item.time_label
                                            ?? ''
                                        )
                                    }</div>

                                </div>

                            </div>

                        `;
                    }
                )

                .join('');


        if (shouldScroll) {

            chatMessages.scrollTop =
                chatMessages.scrollHeight;
        }


    } catch (error) {

        console.error(
            'LOAD MESSAGE ERROR:',
            error
        );


        messageLoadError(
            error.message ||
            'Unknown error.'
        );
    }
}


/*
|--------------------------------------------------------------------------
| SEND MESSAGE
|--------------------------------------------------------------------------
*/

messageForm.addEventListener(
    'submit',
    async function (event) {

        event.preventDefault();


        const message =
            messageInput.value.trim();


        if (!message) {
            return;
        }


        sendMessageButton.disabled =
            true;


        try {

            const formData =
                new FormData(
                    messageForm
                );


            const response =
                await fetch(
                    baseUrl +
                    'ajax/send_message.php',

                    {
                        method:
                            'POST',

                        body:
                            formData,

                        credentials:
                            'same-origin',

                        headers: {
                            'X-Requested-With':
                                'XMLHttpRequest'
                        }
                    }
                );


            const responseText =
                await response.text();


            let data;


            try {

                data =
                    JSON.parse(
                        responseText
                    );

            } catch (error) {

                console.error(
                    'INVALID SEND RESPONSE:',
                    responseText
                );


                throw new Error(
                    'Server returned invalid JSON.'
                );
            }


            if (!response.ok) {

                throw new Error(
                    data.message ||
                    'Unable to send message.'
                );
            }


            if (
                data.success !== true
            ) {

                throw new Error(
                    data.message ||
                    'Unable to send message.'
                );
            }


            messageInput.value =
                '';


            messageInput.style.height =
                '48px';


            await loadMessages(
                true
            );


        } catch (error) {

            console.error(
                'SEND MESSAGE ERROR:',
                error
            );


            alert(
                error.message ||
                'Unable to send message.'
            );


        } finally {

            sendMessageButton.disabled =
                false;


            messageInput.focus();
        }
    }
);


/*
|--------------------------------------------------------------------------
| ENTER SEND
|--------------------------------------------------------------------------
*/

messageInput.addEventListener(
    'keydown',
    function (event) {

        if (
            event.key === 'Enter'
            &&
            !event.shiftKey
        ) {

            event.preventDefault();

            messageForm.requestSubmit();
        }
    }
);


/*
|--------------------------------------------------------------------------
| AUTO HEIGHT
|--------------------------------------------------------------------------
*/

messageInput.addEventListener(
    'input',
    function () {

        this.style.height =
            '48px';


        this.style.height =
            Math.min(
                this.scrollHeight,
                120
            ) + 'px';
    }
);


/*
|--------------------------------------------------------------------------
| FIRST LOAD
|--------------------------------------------------------------------------
*/

loadMessages(
    true
);


/*
|--------------------------------------------------------------------------
| POLLING
|--------------------------------------------------------------------------
*/

setInterval(
    function () {

        loadMessages(
            false
        );

    },
    3000
);

</script>


<?php endif; ?>


<?php

require_once __DIR__ .
    '/includes/footer.php';

?>