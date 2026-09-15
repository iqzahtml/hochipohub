<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SELLER MESSAGES
|--------------------------------------------------------------------------
| File: seller/messages.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = getDB();

if (!($db instanceof PDO)) {
    die('Database connection is not available.');
}


/*
|--------------------------------------------------------------------------
| ACCESS
|--------------------------------------------------------------------------
*/

$userId =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


$currentRole =
    strtolower(
        trim(
            (string) (
                $_SESSION['role']
                ?? $_SESSION['user_role']
                ?? ''
            )
        )
    );


if ($userId <= 0) {

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;
}


if ($currentRole !== 'vendor') {

    header(
        'Location: ' .
        BASE_URL .
        'dashboard.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('sellerMessageEscape')) {

    function sellerMessageEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('sellerMessageInitial')) {

    function sellerMessageInitial($name): string
    {
        $name =
            trim(
                (string) $name
            );

        if ($name === '') {
            return 'C';
        }

        if (function_exists('mb_substr')) {

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


if (!function_exists('sellerMessageDate')) {

    function sellerMessageDate($date): string
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
            date('Y-m-d', $timestamp) ===
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


if (!function_exists('sellerMessageImage')) {

    function sellerMessageImage($image): string
    {
        $image =
            trim(
                (string) $image
            );

        if ($image === '') {
            return '';
        }

        if (
            strpos($image, 'http://') === 0 ||
            strpos($image, 'https://') === 0
        ) {
            return $image;
        }

        if (
            strpos($image, 'uploads/') === 0
        ) {

            return
                BASE_URL .
                ltrim(
                    $image,
                    '/'
                );
        }

        return
            BASE_URL .
            'uploads/' .
            rawurlencode(
                basename($image)
            );
    }
}


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
    (string)
    $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| VENDOR INFORMATION
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                v.vendor_id,
                v.user_id,
                v.business_name,
                v.business_logo,
                v.business_description,
                v.business_address,
                v.category,
                v.delivery_method,
                v.approval_status,

                u.name,
                u.email,
                u.phone,
                u.status

            FROM vendors v

            INNER JOIN users u
                ON v.user_id =
                   u.user_id

            WHERE v.user_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $userId
    ]);


    $vendor =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    die(
        'Vendor profile error: ' .
        sellerMessageEscape(
            $e->getMessage()
        )
    );
}


if (!$vendor) {

    header(
        'Location: ' .
        BASE_URL .
        'seller/setup_profile.php'
    );

    exit;
}


$vendorId =
    (int)
    $vendor['vendor_id'];


/*
|--------------------------------------------------------------------------
| SIDEBAR SESSION DATA
|--------------------------------------------------------------------------
*/

$_SESSION['business_name'] =
    (string) (
        $vendor['business_name']
        ?? 'Seller'
    );


$_SESSION['business_logo'] =
    (string) (
        $vendor['business_logo']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| SELECTED CONVERSATION
|--------------------------------------------------------------------------
*/

$selectedConversationId =
    isset($_GET['conversation'])
        ? (int) $_GET['conversation']
        : 0;


/*
|--------------------------------------------------------------------------
| CONVERSATIONS
|--------------------------------------------------------------------------
*/

$conversations = [];


try {

    $stmt =
        $db->prepare("
            SELECT

                c.conversation_id,
                c.customer_id,
                c.vendor_id,
                c.order_id,
                c.created_at,
                c.updated_at,

                u.name
                    AS customer_name,

                u.email
                    AS customer_email,

                u.phone
                    AS customer_phone,

                u.profile_image,

                (
                    SELECT
                        m.message

                    FROM messages m

                    WHERE m.conversation_id =
                          c.conversation_id

                    ORDER BY
                        m.message_id DESC

                    LIMIT 1
                )
                    AS last_message,

                (
                    SELECT
                        m.created_at

                    FROM messages m

                    WHERE m.conversation_id =
                          c.conversation_id

                    ORDER BY
                        m.message_id DESC

                    LIMIT 1
                )
                    AS last_message_at,

                (
                    SELECT
                        COUNT(*)

                    FROM messages m

                    WHERE m.conversation_id =
                          c.conversation_id

                    AND m.sender_id != ?

                    AND m.is_read = 0
                )
                    AS unread_count

            FROM conversations c

            INNER JOIN users u
                ON c.customer_id =
                   u.user_id

            WHERE c.vendor_id = ?

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
        $userId,
        $vendorId
    ]);


    $conversations =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (Throwable $e) {

    die(
        'Conversation list error: ' .
        sellerMessageEscape(
            $e->getMessage()
        )
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
| SELECTED CONVERSATION
|--------------------------------------------------------------------------
*/

$selectedConversation = null;


if ($selectedConversationId > 0) {

    try {

        $stmt =
            $db->prepare("
                SELECT

                    c.conversation_id,
                    c.customer_id,
                    c.vendor_id,
                    c.order_id,

                    u.name
                        AS customer_name,

                    u.email
                        AS customer_email,

                    u.phone
                        AS customer_phone,

                    u.profile_image,

                    o.order_status,
                    o.delivery_method

                FROM conversations c

                INNER JOIN users u
                    ON c.customer_id =
                       u.user_id

                LEFT JOIN orders o
                    ON c.order_id =
                       o.order_id

                WHERE c.conversation_id = ?

                AND c.vendor_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $selectedConversationId,
            $vendorId
        ]);


        $selectedConversation =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$selectedConversation) {
            $selectedConversationId = 0;
        }


    } catch (Throwable $e) {

        die(
            'Selected conversation error: ' .
            sellerMessageEscape(
                $e->getMessage()
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| MARK READ
|--------------------------------------------------------------------------
*/

if ($selectedConversationId > 0) {

    try {

        $stmt =
            $db->prepare("
                UPDATE messages

                SET is_read = 1

                WHERE conversation_id = ?

                AND sender_id != ?

                AND is_read = 0
            ");


        $stmt->execute([
            $selectedConversationId,
            $userId
        ]);


    } catch (Throwable $e) {

        error_log(
            $e->getMessage()
        );
    }
}


/*
|--------------------------------------------------------------------------
| UNREAD COUNT
|--------------------------------------------------------------------------
*/

$totalUnread = 0;


foreach ($conversations as $conversation) {

    $totalUnread +=
        (int) (
            $conversation[
                'unread_count'
            ]
            ?? 0
        );
}


/*
|--------------------------------------------------------------------------
| CUSTOMER IMAGE
|--------------------------------------------------------------------------
*/

$selectedCustomerImage = '';


if ($selectedConversation) {

    $selectedCustomerImage =
        sellerMessageImage(
            $selectedConversation[
                'profile_image'
            ]
            ?? ''
        );
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
        Messages | Seller | HochipoHub
    </title>


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
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
    >

    <link
        rel="stylesheet"
        href="../css/vendor.css"
    >


<style>

:root {
    --seller-sidebar: 280px;
}


* {
    box-sizing: border-box;
}


html,
body {
    margin: 0;
    padding: 0;
}


body {
    min-height: 100vh;

    font-family:
        Inter,
        Poppins,
        Arial,
        sans-serif;

    color: #14213d;

    background: #f6f8fc;
}


/* ================================================================
   MAIN - SAME STRUCTURE AS SALES
================================================================ */

.seller-message-main {
    width:
        calc(
            100% -
            var(--seller-sidebar)
        );

    min-height: 100vh;

    margin-left:
        var(--seller-sidebar);

    background:

        radial-gradient(
            circle at 95% 5%,
            rgba(37,99,235,.065),
            transparent 24%
        ),

        #f6f8fc;
}


/* ================================================================
   TOPBAR - SAME AS SALES
================================================================ */

.seller-message-topbar {
    height: 72px;

    padding:
        0
        32px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    background:
        rgba(255,255,255,.96);

    border-bottom:
        1px solid
        #e8edf5;
}


.seller-message-topbar-label {
    color: #94a3b8;

    font-size: 11px;

    font-weight: 700;
}


.seller-message-user {
    display: flex;

    align-items: center;

    gap: 9px;
}


.seller-message-avatar {
    width: 38px;
    height: 38px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #4f67f6,
            #4965ef
        );

    border-radius: 50%;

    font-size: 12px;

    font-weight: 900;
}


.seller-message-user strong {
    display: block;

    color: #17233c;

    font-size: 10px;

    font-weight: 800;
}


.seller-message-user small {
    display: block;

    margin-top: 2px;

    color: #94a3b8;

    font-size: 8px;
}


/* ================================================================
   CONTENT
================================================================ */

.seller-message-content {
    width: 100%;

    max-width: 1450px;

    margin: 0 auto;

    padding:
        28px
        32px
        60px;
}


/* ================================================================
   PAGE HEADER - SAME PATTERN AS SALES
================================================================ */

.seller-message-header {
    margin-bottom: 22px;
}


.seller-message-eyebrow {
    display: block;

    margin-bottom: 5px;

    color: #2563eb;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.5px;
}


.seller-message-header h1 {
    margin: 0;

    color: #14213d;

    font-size:
        clamp(
            25px,
            3vw,
            33px
        );

    font-weight: 900;

    letter-spacing: -.8px;
}


.seller-message-header p {
    margin:
        7px
        0
        0;

    color: #7b879c;

    font-size: 11px;
}


/* ================================================================
   HERO - SAME VISUAL FAMILY AS SALES
================================================================ */

.seller-message-hero {
    position: relative;

    min-height: 160px;

    margin-bottom: 22px;

    padding:
        34px
        32px;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 30px;

    color: #ffffff;

    background:
        linear-gradient(
            110deg,
            #09285e,
            #17458d,
            #2d82eb
        );

    border-radius: 24px;

    box-shadow:
        0 16px 40px
        rgba(20,65,145,.12);
}


.seller-message-hero::after {
    content: "";

    position: absolute;

    width: 210px;
    height: 210px;

    right: -45px;
    top: -100px;

    border-radius: 50%;

    background:
        rgba(255,255,255,.07);
}


.seller-message-hero-copy {
    position: relative;
    z-index: 2;
}


.seller-message-hero-label {
    display: block;

    margin-bottom: 13px;

    color: #dbeafe;

    font-size: 8px;

    font-weight: 900;

    letter-spacing: 1.3px;
}


.seller-message-hero h2 {
    margin:
        0
        0
        10px;

    color: #ffffff;

    font-size:
        clamp(
            21px,
            2.5vw,
            28px
        );

    font-weight: 900;
}


.seller-message-hero p {
    max-width: 760px;

    margin: 0;

    color:
        rgba(255,255,255,.78);

    font-size: 10px;

    line-height: 1.7;
}


.seller-message-hero-icon {
    position: relative;
    z-index: 2;

    width: 82px;
    height: 82px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.12);

    border:
        1px solid
        rgba(255,255,255,.20);

    border-radius: 20px;

    font-size: 29px;
}


/* ================================================================
   STATS
================================================================ */

.seller-message-stats {
    margin-bottom: 22px;

    display: grid;

    grid-template-columns:
        repeat(3,1fr);

    gap: 15px;
}


.seller-message-stat {
    position: relative;

    min-height: 112px;

    padding:
        20px;

    overflow: hidden;

    background: #ffffff;

    border:
        1px solid
        #e2e8f0;

    border-radius: 18px;
}


.seller-message-stat::after {
    content: "";

    position: absolute;

    width: 82px;
    height: 82px;

    right: -34px;
    bottom: -34px;

    background: #edf4ff;

    border-radius: 50%;
}


.seller-message-stat.purple::after {
    background: #f3edff;
}


.seller-message-stat.green::after {
    background: #e9fbf0;
}


.seller-message-stat-icon {
    position: relative;
    z-index: 2;

    width: 42px;
    height: 42px;

    margin-bottom: 13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border-radius: 12px;
}


.seller-message-stat.purple
.seller-message-stat-icon {
    color: #7c3aed;

    background: #f5f3ff;
}


.seller-message-stat.green
.seller-message-stat-icon {
    color: #16a34a;

    background: #ecfdf3;
}


.seller-message-stat-label {
    position: relative;
    z-index: 2;

    display: block;

    margin-bottom: 5px;

    color: #7c8a9f;

    font-size: 7px;

    font-weight: 900;

    letter-spacing: .7px;
}


.seller-message-stat-value {
    position: relative;
    z-index: 2;

    color: #12213e;

    font-size: 21px;

    font-weight: 900;
}


/* ================================================================
   CHAT APP
================================================================ */

.seller-message-app {
    width: 100%;

    height: 640px;

    overflow: hidden;

    display: grid;

    grid-template-columns:
        340px
        minmax(0,1fr);

    background: #ffffff;

    border:
        1px solid #e2e8f0;

    border-radius: 20px;

    box-shadow:
        0 10px 30px
        rgba(40,65,120,.045);
}


/* ================================================================
   CONVERSATION LIST
================================================================ */

.seller-conversation-list {
    min-width: 0;

    overflow-y: auto;

    background: #fbfdff;

    border-right:
        1px solid #e8edf5;
}


.seller-conversation-head {
    position: sticky;

    top: 0;

    z-index: 5;

    min-height: 78px;

    padding:
        17px
        18px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    background:
        rgba(255,255,255,.98);

    border-bottom:
        1px solid #e8edf5;
}


.seller-conversation-head strong {
    display: block;

    color: #17233c;

    font-size: 12px;

    font-weight: 900;
}


.seller-conversation-head span {
    display: block;

    margin-top: 3px;

    color: #94a3b8;

    font-size: 7px;
}


.seller-conversation-count {
    min-width: 31px;
    height: 31px;

    padding:
        0
        8px;

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


.seller-conversation {
    position: relative;

    min-height: 82px;

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


.seller-conversation:hover {
    background: #f5f9ff;
}


.seller-conversation.active {
    background:
        linear-gradient(
            90deg,
            #edf5ff,
            #f8fbff
        );
}


.seller-conversation.active::before {
    content: "";

    position: absolute;

    width: 3px;

    top: 11px;
    bottom: 11px;
    left: 0;

    background: #2563eb;

    border-radius:
        0
        999px
        999px
        0;
}


.customer-avatar {
    width: 45px;
    height: 45px;

    flex-shrink: 0;

    overflow: hidden;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

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


.customer-avatar img {
    width: 100%;
    height: 100%;

    object-fit: cover;
}


.conversation-info {
    min-width: 0;

    flex: 1;
}


.conversation-info-top {
    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 8px;
}


.conversation-info-top strong {
    min-width: 0;

    overflow: hidden;

    color: #243a58;

    font-size: 9px;

    font-weight: 900;

    white-space: nowrap;

    text-overflow: ellipsis;
}


.conversation-info-top small {
    color: #a0aec0;

    font-size: 6px;

    white-space: nowrap;
}


.conversation-order {
    margin-top: 3px;

    color: #5277a8;

    font-size: 7px;
}


.conversation-preview-row {
    margin-top: 5px;

    display: flex;

    align-items: center;

    gap: 6px;
}


.conversation-preview-row > span:first-child {
    min-width: 0;

    flex: 1;

    overflow: hidden;

    color: #8998ad;

    font-size: 7px;

    white-space: nowrap;

    text-overflow: ellipsis;
}


.seller-unread {
    min-width: 18px;
    height: 18px;

    padding:
        0
        5px;

    display: inline-flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background: #2563eb;

    border-radius: 999px;

    font-size: 7px;

    font-weight: 900;
}


/* ================================================================
   CHAT RIGHT
================================================================ */

.seller-chat {
    min-width: 0;
    min-height: 0;

    display: flex;

    flex-direction: column;
}


.seller-chat-header {
    min-height: 78px;

    padding:
        14px
        18px;

    display: flex;

    align-items: center;

    gap: 11px;

    background: #ffffff;

    border-bottom:
        1px solid #e8edf5;
}


.seller-chat-user {
    min-width: 0;
}


.seller-chat-user strong {
    display: block;

    color: #18365c;

    font-size: 10px;

    font-weight: 900;
}


.seller-chat-user span {
    display: block;

    margin-top: 3px;

    color: #8998ad;

    font-size: 7px;
}


.seller-online-dot {
    width: 6px;
    height: 6px;

    margin-right: 4px;

    display: inline-block;

    background: #22c55e;

    border-radius: 50%;
}


.seller-chat-actions {
    margin-left: auto;
}


.seller-order-link {
    min-height: 34px;

    padding:
        0
        11px;

    display: inline-flex;

    align-items: center;

    gap: 5px;

    color: #7c3aed;

    background: #f5f3ff;

    border:
        1px solid #e9ddff;

    border-radius: 999px;

    font-size: 7px;

    font-weight: 850;

    text-decoration: none;
}


.seller-chat-messages {
    min-height: 0;

    flex: 1;

    overflow-y: auto;

    padding:
        24px;

    background:

        radial-gradient(
            circle at 100% 0,
            rgba(37,99,235,.04),
            transparent 25%
        ),

        #f8fbff;
}


.message-row {
    width: 100%;

    margin-bottom: 12px;

    display: flex;
}


.message-row.mine {
    justify-content: flex-end;
}


.message-bubble {
    max-width: 68%;

    padding:
        10px
        13px;

    color: #35455e;

    background: #ffffff;

    border:
        1px solid #e2e8f0;

    border-radius:
        14px
        14px
        14px
        4px;
}


.message-row.mine
.message-bubble {
    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #1d5fd0
        );

    border-color: transparent;

    border-radius:
        14px
        14px
        4px
        14px;
}


.message-text {
    font-size: 9px;

    line-height: 1.6;

    white-space: pre-wrap;

    overflow-wrap: anywhere;
}


.message-meta {
    margin-top: 5px;

    opacity: .68;

    font-size: 6px;

    text-align: right;
}


/* ================================================================
   EMPTY
================================================================ */

.message-empty,
.message-loading {
    height: 100%;

    min-height: 300px;

    display: flex;

    align-items: center;

    justify-content: center;

    text-align: center;

    color: #94a3b8;
}


.message-empty-inner {
    max-width: 260px;
}


.message-empty-icon {
    width: 58px;
    height: 58px;

    margin:
        0 auto
        13px;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid #dbeafe;

    border-radius: 16px;

    font-size: 22px;
}


.message-empty strong {
    display: block;

    margin-bottom: 5px;

    color: #354760;

    font-size: 10px;

    font-weight: 900;
}


.message-empty p {
    margin: 0;

    color: #94a3b8;

    font-size: 8px;

    line-height: 1.6;
}


/* ================================================================
   SEND FORM
================================================================ */

.seller-message-form {
    min-height: 76px;

    padding:
        13px
        15px;

    display: flex;

    align-items: flex-end;

    gap: 9px;

    background: #ffffff;

    border-top:
        1px solid #e8edf5;
}


.seller-message-input-wrap {
    min-width: 0;

    flex: 1;
}


.seller-message-form textarea {
    width: 100%;
    height: 48px;

    max-height: 120px;

    padding:
        13px
        14px;

    resize: none;

    outline: none;

    color: #334155;

    background: #f8fafc;

    border:
        1px solid #dce4ef;

    border-radius: 12px;

    font-family: inherit;

    font-size: 9px;
}


.seller-message-form textarea:focus {
    background: #ffffff;

    border-color: #3b82f6;

    box-shadow:
        0
        0
        0
        3px
        rgba(59,130,246,.07);
}


.seller-send-button {
    width: 48px;
    height: 48px;

    flex-shrink: 0;

    display: flex;

    align-items: center;

    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #2563eb,
            #3b82f6
        );

    border: 0;

    border-radius: 12px;

    font-size: 14px;

    cursor: pointer;
}


.seller-send-button:disabled {
    opacity: .5;

    cursor: not-allowed;
}


/* ================================================================
   RESPONSIVE
================================================================ */

@media (max-width: 1100px) {

    .seller-message-app {
        grid-template-columns:
            290px
            minmax(0,1fr);
    }
}


@media (max-width: 768px) {

    .seller-message-main {
        width: 100%;
        margin-left: 0;
    }


    .seller-message-topbar {
        padding:
            0
            20px;
    }


    .seller-message-content {
        padding:
            24px
            20px
            50px;
    }


    .seller-message-stats {
        grid-template-columns: 1fr;
    }


    .seller-message-app {
        height: auto;

        display: block;
    }


    .seller-conversation-list {
        max-height: 270px;

        border-right: 0;

        border-bottom:
            1px solid #e7edf4;
    }


    .seller-chat {
        height: 520px;
    }
}


@media (max-width: 600px) {

    .seller-message-user > div:last-child {
        display: none;
    }


    .seller-message-content {
        padding:
            20px
            14px
            45px;
    }


    .seller-message-hero {
        min-height: auto;

        padding: 23px;

        align-items: flex-start;
    }


    .seller-message-hero-icon {
        display: none;
    }
}

</style>

</head>


<body class="seller-dashboard-page seller-message-page">


<?php

/*
|--------------------------------------------------------------------------
| SHARED SELLER SIDEBAR
|--------------------------------------------------------------------------
*/

require_once __DIR__ .
    '/../includes/vendor_sidebar.php';

?>


<main class="seller-message-main">


    <!-- ===========================================================
         TOPBAR
    ============================================================ -->

    <header class="seller-message-topbar">


        <span class="seller-message-topbar-label">

            Seller Center

        </span>


        <div class="seller-message-user">


            <div class="seller-message-avatar">

                <?= sellerMessageEscape(
                    strtoupper(
                        substr(
                            $vendor['name']
                            ?? 'V',
                            0,
                            1
                        )
                    )
                ) ?>

            </div>


            <div>

                <strong>

                    <?= sellerMessageEscape(
                        $vendor['name']
                        ?? 'Vendor'
                    ) ?>

                </strong>

                <small>

                    <?= sellerMessageEscape(
                        $vendor['business_name']
                        ?? 'Vendor'
                    ) ?>

                </small>

            </div>


        </div>


    </header>



    <!-- ===========================================================
         CONTENT
    ============================================================ -->

    <div class="seller-message-content">


        <!-- =======================================================
             PAGE HEADER
        ======================================================== -->

        <section class="seller-message-header">


            <span class="seller-message-eyebrow">

                CUSTOMER COMMUNICATION

            </span>


            <h1>

                Messages

            </h1>


            <p>

                Manage customer conversations and
                reply to questions for

                <?= sellerMessageEscape(
                    $vendor['business_name']
                ) ?>.

            </p>


        </section>



        <!-- =======================================================
             HERO
        ======================================================== -->

        <section class="seller-message-hero">


            <div class="seller-message-hero-copy">


                <span class="seller-message-hero-label">

                    MESSAGE CENTER

                </span>


                <h2>

                    Keep every customer conversation
                    in one place.

                </h2>


                <p>

                    Reply to product questions, order enquiries,
                    pickup arrangements, postage and vendor delivery
                    without leaving your Seller Center.

                </p>


            </div>


            <div class="seller-message-hero-icon">

                <i class="fa-solid fa-comments"></i>

            </div>


        </section>



        <!-- =======================================================
             STATS
        ======================================================== -->

        <section class="seller-message-stats">


            <article class="seller-message-stat">


                <div class="seller-message-stat-icon">

                    <i class="fa-regular fa-comments"></i>

                </div>


                <span class="seller-message-stat-label">

                    CONVERSATIONS

                </span>


                <strong class="seller-message-stat-value">

                    <?= number_format(
                        count($conversations)
                    ) ?>

                </strong>


            </article>



            <article class="seller-message-stat purple">


                <div class="seller-message-stat-icon">

                    <i class="fa-regular fa-envelope"></i>

                </div>


                <span class="seller-message-stat-label">

                    UNREAD

                </span>


                <strong class="seller-message-stat-value">

                    <?= number_format(
                        $totalUnread
                    ) ?>

                </strong>


            </article>



            <article class="seller-message-stat green">


                <div class="seller-message-stat-icon">

                    <i class="fa-solid fa-store"></i>

                </div>


                <span class="seller-message-stat-label">

                    STORE

                </span>


                <strong
                    class="seller-message-stat-value"
                    style="font-size:14px;"
                >

                    <?= sellerMessageEscape(
                        $vendor['business_name']
                        ?? 'My Store'
                    ) ?>

                </strong>


            </article>


        </section>



        <!-- =======================================================
             CHAT APP
        ======================================================== -->

        <section class="seller-message-app">


            <!-- ===================================================
                 LEFT
            ==================================================== -->

            <aside class="seller-conversation-list">


                <div class="seller-conversation-head">


                    <div>

                        <strong>

                            Customer Chats

                        </strong>

                        <span>

                            Your customer conversations

                        </span>

                    </div>


                    <div class="seller-conversation-count">

                        <?= number_format(
                            count($conversations)
                        ) ?>

                    </div>


                </div>



                <?php if (
                    empty($conversations)
                ): ?>


                    <div class="message-empty">


                        <div class="message-empty-inner">


                            <div class="message-empty-icon">

                                <i class="fa-regular fa-comments"></i>

                            </div>


                            <strong>

                                No customer messages yet

                            </strong>


                            <p>

                                Customer conversations will appear
                                here after they contact your store.

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


                        $customerImage =
                            sellerMessageImage(
                                $conversation[
                                    'profile_image'
                                ]
                                ?? ''
                            );

                        ?>


                        <a
                            href="messages.php?conversation=<?= $conversationId ?>"
                            class="
                                seller-conversation
                                <?= $conversationId ===
                                    $selectedConversationId
                                    ? 'active'
                                    : '' ?>
                            "
                        >


                            <div class="customer-avatar">


                                <?php if (
                                    $customerImage !== ''
                                ): ?>


                                    <img
                                        src="<?= sellerMessageEscape(
                                            $customerImage
                                        ) ?>"
                                        alt="<?= sellerMessageEscape(
                                            $conversation[
                                                'customer_name'
                                            ]
                                            ?? 'Customer'
                                        ) ?>"
                                    >


                                <?php else: ?>


                                    <?= sellerMessageEscape(
                                        sellerMessageInitial(
                                            $conversation[
                                                'customer_name'
                                            ]
                                            ?? 'Customer'
                                        )
                                    ) ?>


                                <?php endif; ?>


                            </div>



                            <div class="conversation-info">


                                <div class="conversation-info-top">


                                    <strong>

                                        <?= sellerMessageEscape(
                                            $conversation[
                                                'customer_name'
                                            ]
                                            ?? 'Customer'
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= sellerMessageEscape(
                                            sellerMessageDate(
                                                $conversation[
                                                    'last_message_at'
                                                ]
                                                ??
                                                $conversation[
                                                    'created_at'
                                                ]
                                            )
                                        ) ?>

                                    </small>


                                </div>



                                <?php if (
                                    !empty(
                                        $conversation[
                                            'order_id'
                                        ]
                                    )
                                ): ?>


                                    <div class="conversation-order">

                                        <i class="fa-solid fa-receipt"></i>

                                        Order
                                        #<?= (int)
                                            $conversation[
                                                'order_id'
                                            ] ?>

                                    </div>


                                <?php endif; ?>



                                <div class="conversation-preview-row">


                                    <span>

                                        <?= sellerMessageEscape(
                                            $conversation[
                                                'last_message'
                                            ]
                                            ??
                                            'New conversation'
                                        ) ?>

                                    </span>


                                    <?php if (
                                        (int)
                                        (
                                            $conversation[
                                                'unread_count'
                                            ]
                                            ?? 0
                                        ) > 0
                                    ): ?>


                                        <span class="seller-unread">

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



            <!-- ===================================================
                 RIGHT
            ==================================================== -->

            <div class="seller-chat">


                <?php if (
                    $selectedConversation
                ): ?>


                    <header class="seller-chat-header">


                        <div class="customer-avatar">


                            <?php if (
                                $selectedCustomerImage !== ''
                            ): ?>


                                <img
                                    src="<?= sellerMessageEscape(
                                        $selectedCustomerImage
                                    ) ?>"
                                    alt="<?= sellerMessageEscape(
                                        $selectedConversation[
                                            'customer_name'
                                        ]
                                        ?? 'Customer'
                                    ) ?>"
                                >


                            <?php else: ?>


                                <?= sellerMessageEscape(
                                    sellerMessageInitial(
                                        $selectedConversation[
                                            'customer_name'
                                        ]
                                        ?? 'Customer'
                                    )
                                ) ?>


                            <?php endif; ?>


                        </div>



                        <div class="seller-chat-user">


                            <strong>

                                <?= sellerMessageEscape(
                                    $selectedConversation[
                                        'customer_name'
                                    ]
                                    ?? 'Customer'
                                ) ?>

                            </strong>


                            <span>

                                <i class="seller-online-dot"></i>

                                Customer

                            </span>


                        </div>



                        <div class="seller-chat-actions">


                            <?php if (
                                !empty(
                                    $selectedConversation[
                                        'order_id'
                                    ]
                                )
                            ): ?>


                                <a
                                    href="orders.php?order_id=<?= (int)
                                        $selectedConversation[
                                            'order_id'
                                        ] ?>"
                                    class="seller-order-link"
                                >

                                    <i class="fa-solid fa-receipt"></i>

                                    Order
                                    #<?= (int)
                                        $selectedConversation[
                                            'order_id'
                                        ] ?>

                                </a>


                            <?php endif; ?>


                        </div>


                    </header>



                    <div
                        class="seller-chat-messages"
                        id="chatMessages"
                    >


                        <div class="message-loading">


                            <div class="message-empty-inner">


                                <div class="message-empty-icon">

                                    <i class="fa-regular fa-comments"></i>

                                </div>


                                Loading messages...


                            </div>


                        </div>


                    </div>



                    <form
                        class="seller-message-form"
                        id="messageForm"
                    >


                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= sellerMessageEscape(
                                $csrfToken
                            ) ?>"
                        >


                        <input
                            type="hidden"
                            name="conversation_id"
                            value="<?= (int)
                                $selectedConversationId ?>"
                        >


                        <div class="seller-message-input-wrap">


                            <textarea
                                name="message"
                                id="messageInput"
                                maxlength="2000"
                                placeholder="Type your reply to customer..."
                                required
                            ></textarea>


                        </div>


                        <button
                            type="submit"
                            class="seller-send-button"
                            id="sendMessageButton"
                            aria-label="Send message"
                        >

                            <i class="fa-solid fa-paper-plane"></i>

                        </button>


                    </form>


                <?php else: ?>


                    <div class="message-empty">


                        <div class="message-empty-inner">


                            <div class="message-empty-icon">

                                <i class="fa-regular fa-comments"></i>

                            </div>


                            <strong>

                                Select a conversation

                            </strong>


                            <p>

                                Choose a customer conversation
                                from the left to start chatting.

                            </p>


                        </div>


                    </div>


                <?php endif; ?>


            </div>


        </section>


    </div>


</main>



<?php if (
    $selectedConversation
): ?>


<script>

(function () {

    'use strict';


    const conversationId =
        <?= (int)
            $selectedConversationId ?>;


    const currentUserId =
        <?= (int)
            $userId ?>;


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


    let previousMessageCount =
        -1;


    function escapeHtml(value) {

        const div =
            document.createElement(
                'div'
            );

        div.textContent =
            value ?? '';

        return div.innerHTML;
    }


    function emptyMessageState() {

        return `
            <div class="message-empty">

                <div class="message-empty-inner">

                    <div class="message-empty-icon">

                        <i class="fa-regular fa-comments"></i>

                    </div>

                    <strong>
                        No messages yet
                    </strong>

                    <p>
                        Send a reply to start
                        the conversation.
                    </p>

                </div>

            </div>
        `;
    }


    function errorMessageState(message) {

        chatMessages.innerHTML = `
            <div class="message-empty">

                <div class="message-empty-inner">

                    <div
                        class="message-empty-icon"
                        style="
                            color:#dc2626;
                            background:#fef2f2;
                            border-color:#fecaca;
                        "
                    >

                        <i class="fa-solid fa-triangle-exclamation"></i>

                    </div>

                    <strong>
                        Unable to load messages
                    </strong>

                    <p>
                        ${escapeHtml(message)}
                    </p>

                </div>

            </div>
        `;
    }


    async function loadMessages(
        forceScroll = false
    ) {

        try {

            const response =
                await fetch(
                    baseUrl +
                    'ajax/load_messages.php?conversation_id=' +
                    encodeURIComponent(
                        conversationId
                    ),
                    {
                        credentials:
                            'same-origin',

                        cache:
                            'no-store'
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
                    responseText
                );


                throw new Error(
                    'Server returned invalid JSON.'
                );
            }


            if (!response.ok) {

                throw new Error(
                    data.message
                    ||
                    'HTTP error ' +
                    response.status
                );
            }


            if (
                data.success !== true
            ) {

                throw new Error(
                    data.message
                    ||
                    'Unable to load messages.'
                );
            }


            if (
                !Array.isArray(
                    data.messages
                )
            ) {

                throw new Error(
                    'Invalid message response.'
                );
            }


            const nearBottom =
                (
                    chatMessages.scrollHeight -
                    chatMessages.scrollTop -
                    chatMessages.clientHeight
                ) < 120;


            const shouldScroll =
                forceScroll
                ||
                previousMessageCount === -1
                ||
                nearBottom;


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
                                        message-row
                                        ${mine ? 'mine' : ''}
                                    "
                                >

                                    <div class="message-bubble">

                                        <div class="message-text">${
                                            escapeHtml(
                                                item.message
                                            )
                                        }</div>

                                        <div class="message-meta">${
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
                error
            );


            errorMessageState(
                error.message
                ||
                'Unable to load messages.'
            );
        }
    }


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
                                'same-origin'
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
                        responseText
                    );


                    throw new Error(
                        'Server returned invalid JSON.'
                    );
                }


                if (!response.ok) {

                    throw new Error(
                        data.message
                        ||
                        'Unable to send message.'
                    );
                }


                if (
                    data.success !== true
                ) {

                    throw new Error(
                        data.message
                        ||
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
                    error
                );


                alert(
                    error.message
                    ||
                    'Unable to send message.'
                );


            } finally {

                sendMessageButton.disabled =
                    false;


                messageInput.focus();
            }
        }
    );


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


    loadMessages(
        true
    );


    setInterval(
        function () {

            loadMessages(
                false
            );

        },
        3000
    );

})();

</script>


<?php endif; ?>


</body>

</html>