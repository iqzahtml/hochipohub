<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN CONTACT MESSAGES
|--------------------------------------------------------------------------
| File: admin/contact_messages.php
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE & REQUIRED FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/email.php';

$db = getDB();


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset($_SESSION['user_id']) ||
    strtolower(
        trim(
            $_SESSION['role']
            ?? ''
        )
    ) !== 'admin'
) {

    header('Location: ../index.php');
    exit;
}

$adminId = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (!function_exists('contactAdminEscape')) {

    function contactAdminEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (!function_exists('contactAdminDate')) {

    function contactAdminDate($date): string
    {
        if (!$date) {
            return '-';
        }

        $timestamp = strtotime($date);

        if (!$timestamp) {
            return '-';
        }

        return date(
            'd M Y, h:i A',
            $timestamp
        );
    }
}


if (!function_exists('contactAdminStatusClass')) {

    function contactAdminStatusClass($status): string
    {
        switch ($status) {

            case 'Read':
                return 'read';

            case 'Replied':
                return 'replied';

            case 'New':
            default:
                return 'new';
        }
    }
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

$csrfToken = $_SESSION['csrf_token'];


/*
|--------------------------------------------------------------------------
| ALERT MESSAGES
|--------------------------------------------------------------------------
*/

$message = '';
$error = '';


if (
    isset($_GET['success']) &&
    $_GET['success'] === 'reply'
) {

    $message =
        'Reply sent successfully and the contact message has been marked as replied.';
}


if (isset($_GET['error'])) {

    switch ($_GET['error']) {

        case 'security':

            $error =
                'Invalid security token. Please refresh the page and try again.';

            break;


        case 'invalid':

            $error =
                'Invalid contact message information.';

            break;


        case 'notfound':

            $error =
                'Contact message could not be found.';

            break;


        case 'email':

            $error =
                'The reply could not be sent by email. The message has not been marked as replied.';

            break;


        case 'reply':

            $error =
                'Unable to process the reply. Please try again.';

            break;


        default:

            $error =
                'Something went wrong while processing the request.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| SEND ADMIN REPLY
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['send_reply'])
) {

    /*
    |--------------------------------------------------------------------------
    | CSRF CHECK
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

        header(
            'Location: contact_messages.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | INPUT
    |--------------------------------------------------------------------------
    */

    $contactMessageId =
        isset($_POST['contact_message_id'])
            ? (int) $_POST['contact_message_id']
            : 0;

    $adminReply =
        trim(
            $_POST['admin_reply']
            ?? ''
        );


    if (
        $contactMessageId <= 0 ||
        $adminReply === ''
    ) {

        header(
            'Location: contact_messages.php?error=invalid'
        );

        exit;
    }


    try {

        /*
        |--------------------------------------------------------------------------
        | GET ORIGINAL MESSAGE
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    contact_message_id,
                    user_id,
                    name,
                    email,
                    subject,
                    message,
                    status,
                    admin_reply,
                    created_at

                FROM contact_messages

                WHERE contact_message_id = ?

                LIMIT 1
            ");

        $stmt->execute([
            $contactMessageId
        ]);

        $contact =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$contact) {

            header(
                'Location: contact_messages.php?error=notfound'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | EMAIL SUBJECT
        |--------------------------------------------------------------------------
        */

        $replySubject =
            'Re: ' .
            (
                trim(
                    (string) $contact['subject']
                ) !== ''
                    ? $contact['subject']
                    : 'Your HochipoHub Enquiry'
            );


        /*
        |--------------------------------------------------------------------------
        | EMAIL CONTENT
        |--------------------------------------------------------------------------
        */

        $customerName =
            trim(
                (string) $contact['name']
            );

        if ($customerName === '') {
            $customerName = 'Customer';
        }


        $safeCustomerName =
            htmlspecialchars(
                $customerName,
                ENT_QUOTES,
                'UTF-8'
            );

        $safeSubject =
            htmlspecialchars(
                (string) $contact['subject'],
                ENT_QUOTES,
                'UTF-8'
            );

        $safeOriginalMessage =
            nl2br(
                htmlspecialchars(
                    (string) $contact['message'],
                    ENT_QUOTES,
                    'UTF-8'
                )
            );

        $safeAdminReply =
            nl2br(
                htmlspecialchars(
                    $adminReply,
                    ENT_QUOTES,
                    'UTF-8'
                )
            );


        $emailBody = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        </head>

        <body style="
            margin:0;
            padding:0;
            background:#f4f8fd;
            font-family:Arial,Helvetica,sans-serif;
            color:#1e293b;
        ">

            <table
                width="100%"
                cellpadding="0"
                cellspacing="0"
                border="0"
                style="padding:30px 15px;"
            >
                <tr>
                    <td align="center">

                        <table
                            width="100%"
                            cellpadding="0"
                            cellspacing="0"
                            border="0"
                            style="
                                max-width:650px;
                                background:#ffffff;
                                border-radius:18px;
                                overflow:hidden;
                                box-shadow:0 12px 35px rgba(15,50,100,.10);
                            "
                        >

                            <tr>
                                <td style="
                                    padding:30px;
                                    background:linear-gradient(
                                        135deg,
                                        #08265a,
                                        #176fd1
                                    );
                                    color:#ffffff;
                                ">

                                    <div style="
                                        font-size:24px;
                                        font-weight:700;
                                        margin-bottom:5px;
                                    ">
                                        HochipoHub
                                    </div>

                                    <div style="
                                        font-size:13px;
                                        opacity:.85;
                                    ">
                                        Customer Support
                                    </div>

                                </td>
                            </tr>


                            <tr>
                                <td style="padding:32px;">

                                    <p style="
                                        margin:0 0 18px;
                                        font-size:15px;
                                    ">
                                        Hi ' . $safeCustomerName . ',
                                    </p>

                                    <p style="
                                        margin:0 0 22px;
                                        font-size:14px;
                                        line-height:1.7;
                                        color:#475569;
                                    ">
                                        Thank you for contacting HochipoHub.
                                        Our administrator has replied to your enquiry.
                                    </p>


                                    <div style="
                                        margin-bottom:22px;
                                        padding:20px;
                                        background:#eff6ff;
                                        border-left:4px solid #2563eb;
                                        border-radius:10px;
                                    ">

                                        <div style="
                                            margin-bottom:8px;
                                            font-size:11px;
                                            font-weight:700;
                                            color:#2563eb;
                                            text-transform:uppercase;
                                        ">
                                            Our Reply
                                        </div>

                                        <div style="
                                            font-size:14px;
                                            line-height:1.7;
                                            color:#1e3a5f;
                                        ">
                                            ' . $safeAdminReply . '
                                        </div>

                                    </div>


                                    <div style="
                                        padding:18px;
                                        background:#f8fafc;
                                        border:1px solid #e2e8f0;
                                        border-radius:10px;
                                    ">

                                        <div style="
                                            margin-bottom:10px;
                                            font-size:11px;
                                            font-weight:700;
                                            color:#64748b;
                                            text-transform:uppercase;
                                        ">
                                            Your Original Enquiry
                                        </div>

                                        <div style="
                                            margin-bottom:8px;
                                            font-size:13px;
                                            font-weight:700;
                                            color:#334155;
                                        ">
                                            Subject:
                                            ' . $safeSubject . '
                                        </div>

                                        <div style="
                                            font-size:12px;
                                            line-height:1.6;
                                            color:#64748b;
                                        ">
                                            ' . $safeOriginalMessage . '
                                        </div>

                                    </div>


                                    <p style="
                                        margin:24px 0 0;
                                        font-size:12px;
                                        line-height:1.6;
                                        color:#94a3b8;
                                    ">
                                        This email was sent by HochipoHub Customer Support.
                                    </p>

                                </td>
                            </tr>

                        </table>

                    </td>
                </tr>
            </table>

        </body>
        </html>
        ';


        /*
        |--------------------------------------------------------------------------
        | SEND EMAIL
        |--------------------------------------------------------------------------
        */

        $plainBody =
            "Hi " . $customerName . ",\n\n" .
            "Thank you for contacting HochipoHub.\n\n" .
            "Our administrator has replied to your enquiry.\n\n" .
            "OUR REPLY:\n" .
            $adminReply . "\n\n" .
            "YOUR ORIGINAL ENQUIRY\n" .
            "Subject: " .
            (
                trim((string) $contact['subject']) !== ''
                    ? $contact['subject']
                    : 'Your HochipoHub Enquiry'
            ) .
            "\n\n" .
            $contact['message'] .
            "\n\n" .
            "Thank you,\n" .
            "HochipoHub Customer Support";

        $emailSent = false;

        try {

            $emailSent =
                sendHochipoEmail(
                    (string) $contact['email'],
                    $customerName,
                    $replySubject,
                    $emailBody,
                    $plainBody
                );

        }
        catch (Throwable $mailException) {

            error_log(
                'HOCHIPOHUB CONTACT REPLY EMAIL ERROR: ' .
                $mailException->getMessage()
            );

            $emailSent = false;
        }


        /*
        |--------------------------------------------------------------------------
        | EMAIL FAILED
        |--------------------------------------------------------------------------
        */

        if (!$emailSent) {

            header(
                'Location: contact_messages.php?view=' .
                $contactMessageId .
                '&error=email'
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE DATABASE AFTER EMAIL SUCCESS
        |--------------------------------------------------------------------------
        */

        $db->beginTransaction();


        $update =
            $db->prepare("
                UPDATE contact_messages

                SET
                    status = 'Replied',
                    admin_reply = ?,
                    replied_by = ?,
                    replied_at = NOW(),
                    read_at = COALESCE(
                        read_at,
                        NOW()
                    )

                WHERE contact_message_id = ?
            ");

        $update->execute([
            $adminReply,
            $adminId,
            $contactMessageId
        ]);


        /*
        |--------------------------------------------------------------------------
        | ADMIN LOG
        |--------------------------------------------------------------------------
        */

        $log =
            $db->prepare("
                INSERT INTO admin_logs
                (
                    admin_id,
                    action,
                    target_type,
                    target_id
                )

                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?
                )
            ");

        $log->execute([
            $adminId,
            'Replied to contact message #' .
                $contactMessageId,
            'contact_message',
            $contactMessageId
        ]);


        $db->commit();


        header(
            'Location: contact_messages.php?view=' .
            $contactMessageId .
            '&success=reply'
        );

        exit;

    }
    catch (Throwable $e) {

        if ($db->inTransaction()) {
            $db->rollBack();
        }

        error_log(
            'HOCHIPOHUB ADMIN CONTACT REPLY ERROR: ' .
            $e->getMessage()
        );


        header(
            'Location: contact_messages.php?error=reply'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH & FILTER
|--------------------------------------------------------------------------
*/

$search =
    trim(
        $_GET['search']
        ?? ''
    );

$statusFilter =
    trim(
        $_GET['status']
        ?? ''
    );


$allowedStatusFilters = [
    'New',
    'Read',
    'Replied'
];

if (
    $statusFilter !== '' &&
    !in_array(
        $statusFilter,
        $allowedStatusFilters,
        true
    )
) {

    $statusFilter = '';
}


/*
|--------------------------------------------------------------------------
| OPEN / VIEW MESSAGE
|--------------------------------------------------------------------------
*/

$viewId =
    isset($_GET['view'])
        ? (int) $_GET['view']
        : 0;

$selectedMessage = null;


if ($viewId > 0) {

    try {

        /*
        |--------------------------------------------------------------------------
        | MARK NEW MESSAGE AS READ
        |--------------------------------------------------------------------------
        */

        $markRead =
            $db->prepare("
                UPDATE contact_messages

                SET
                    status = CASE
                        WHEN status = 'New'
                            THEN 'Read'
                        ELSE status
                    END,

                    read_at = CASE
                        WHEN read_at IS NULL
                            THEN NOW()
                        ELSE read_at
                    END

                WHERE contact_message_id = ?
            ");

        $markRead->execute([
            $viewId
        ]);


        /*
        |--------------------------------------------------------------------------
        | FETCH SELECTED MESSAGE
        |--------------------------------------------------------------------------
        */

        $stmt =
            $db->prepare("
                SELECT
                    cm.contact_message_id,
                    cm.user_id,
                    cm.name,
                    cm.email,
                    cm.subject,
                    cm.message,
                    cm.status,
                    cm.admin_reply,
                    cm.replied_by,
                    cm.created_at,
                    cm.read_at,
                    cm.replied_at,

                    u.name AS account_name,
                    u.role AS account_role,

                    admin_user.name AS replied_by_name

                FROM contact_messages cm

                LEFT JOIN users u
                    ON cm.user_id = u.user_id

                LEFT JOIN users admin_user
                    ON cm.replied_by =
                       admin_user.user_id

                WHERE cm.contact_message_id = ?

                LIMIT 1
            ");

        $stmt->execute([
            $viewId
        ]);

        $selectedMessage =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        if (!$selectedMessage) {

            $error =
                'Contact message could not be found.';
        }

    }
    catch (Throwable $e) {

        error_log(
            'HOCHIPOHUB ADMIN CONTACT VIEW ERROR: ' .
            $e->getMessage()
        );

        $error =
            'Unable to open the contact message.';
    }
}


/*
|--------------------------------------------------------------------------
| FETCH CONTACT MESSAGES
|--------------------------------------------------------------------------
*/

$contactMessages = [];


try {

    $sql = "
        SELECT
            contact_message_id,
            user_id,
            name,
            email,
            subject,
            message,
            status,
            created_at,
            read_at,
            replied_at

        FROM contact_messages

        WHERE 1 = 1
    ";

    $params = [];


    /*
    |--------------------------------------------------------------------------
    | SEARCH
    |--------------------------------------------------------------------------
    */

    if ($search !== '') {

        $sql .= "
            AND
            (
                CAST(
                    contact_message_id AS CHAR
                ) LIKE ?

                OR name LIKE ?

                OR email LIKE ?

                OR subject LIKE ?

                OR message LIKE ?
            )
        ";

        $searchValue =
            '%' .
            $search .
            '%';

        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
        $params[] = $searchValue;
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS FILTER
    |--------------------------------------------------------------------------
    */

    if ($statusFilter !== '') {

        $sql .= "
            AND status = ?
        ";

        $params[] =
            $statusFilter;
    }


    /*
    |--------------------------------------------------------------------------
    | ORDER
    |--------------------------------------------------------------------------
    */

    $sql .= "
        ORDER BY
            CASE status
                WHEN 'New' THEN 1
                WHEN 'Read' THEN 2
                WHEN 'Replied' THEN 3
                ELSE 4
            END,

            created_at DESC,
            contact_message_id DESC
    ";


    $stmt =
        $db->prepare(
            $sql
        );

    $stmt->execute(
        $params
    );

    $contactMessages =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );

}
catch (Throwable $e) {

    $contactMessages = [];

    error_log(
        'HOCHIPOHUB ADMIN CONTACT FETCH ERROR: ' .
        $e->getMessage()
    );

    $error =
        'Unable to load contact messages.';
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalMessages = 0;
$newMessages = 0;
$readMessages = 0;
$repliedMessages = 0;


try {

    $totalMessages =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM contact_messages
            ")
            ->fetchColumn();


    $newMessages =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM contact_messages
                WHERE status = 'New'
            ")
            ->fetchColumn();


    $readMessages =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM contact_messages
                WHERE status = 'Read'
            ")
            ->fetchColumn();


    $repliedMessages =
        (int)
        $db
            ->query("
                SELECT COUNT(*)
                FROM contact_messages
                WHERE status = 'Replied'
            ")
            ->fetchColumn();

}
catch (Throwable $e) {

    error_log(
        'HOCHIPOHUB CONTACT STATISTICS ERROR: ' .
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
        Contact Messages | HochipoHub Admin
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
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
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

        :root {
            --contact-sidebar-width: 260px;
            --contact-border: #dce7f3;
            --contact-text: #0b2d63;
            --contact-muted: #8294b3;
        }


        * {
            box-sizing: border-box;
        }


        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: 'Poppins', sans-serif;
            background: #eef5fd;
        }


        body {
            overflow-x: hidden;
        }


        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }


        .admin-wrapper,
        .admin-wrapper *,
        .admin-sidebar,
        .admin-sidebar *,
        .sidebar,
        .sidebar * {
            font-family: 'Poppins', sans-serif !important;
        }


        /*
        |--------------------------------------------------------------------------
        | MAIN
        |--------------------------------------------------------------------------
        */

        .contact-main {

            min-height: 100vh;

            margin-left:
                var(
                    --contact-sidebar-width
                );

            width:
                calc(
                    100% -
                    var(
                        --contact-sidebar-width
                    )
                );

            background:
                radial-gradient(
                    circle at 90% 2%,
                    rgba(37, 99, 235, .12),
                    transparent 24%
                ),
                linear-gradient(
                    135deg,
                    #f4f8fd,
                    #eaf3ff
                );
        }


        .contact-content {
            width: 100%;
            max-width: 1450px;
            margin: 0 auto;
            padding: 38px 35px 70px;
        }


        /*
        |--------------------------------------------------------------------------
        | HERO
        |--------------------------------------------------------------------------
        */

        .contact-hero {

            position: relative;
            min-height: 155px;
            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: space-between;

            padding: 34px 38px;
            margin-bottom: 26px;

            color: #ffffff;

            background:
                linear-gradient(
                    110deg,
                    #08265a 0%,
                    #123c8c 47%,
                    #2480ed 100%
                );

            border-radius: 26px;

            box-shadow:
                0 20px 45px
                rgba(18, 70, 150, .15);
        }


        .contact-hero::before {

            content: "";

            position: absolute;

            width: 260px;
            height: 260px;

            right: -70px;
            top: -140px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, .07);
        }


        .contact-hero::after {

            content: "";

            position: absolute;

            width: 170px;
            height: 170px;

            right: 155px;
            bottom: -110px;

            border-radius: 50%;

            background:
                rgba(255, 255, 255, .045);
        }


        .contact-hero-text {
            position: relative;
            z-index: 2;
        }


        .contact-hero h1 {

            margin: 0 0 8px;

            color: #ffffff;

            font-size: 38px;
            line-height: 1.05;

            font-weight: 800;

            letter-spacing: -1.5px;
        }


        .contact-hero p {

            margin: 0;

            color:
                rgba(
                    255,
                    255,
                    255,
                    .82
                );

            font-size: 14px;
            font-weight: 500;
        }


        .contact-hero-icon {

            position: relative;
            z-index: 2;

            width: 82px;
            height: 82px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border:
                1px solid
                rgba(255, 255, 255, .26);

            border-radius: 22px;

            background:
                linear-gradient(
                    145deg,
                    rgba(255, 255, 255, .20),
                    rgba(255, 255, 255, .10)
                );

            box-shadow:
                inset 0 1px 0
                rgba(255, 255, 255, .25),

                0 12px 30px
                rgba(0, 35, 100, .18);

            font-size: 34px;
        }


        /*
        |--------------------------------------------------------------------------
        | ALERT
        |--------------------------------------------------------------------------
        */

        .contact-alert {

            margin-bottom: 22px;

            padding: 14px 17px;

            border-radius: 12px;

            font-size: 11px;
            font-weight: 600;
        }


        .contact-alert.success {

            color: #166534;

            background: #ecfdf5;

            border: 1px solid #bbf7d0;
        }


        .contact-alert.error {

            color: #991b1b;

            background: #fff1f2;

            border: 1px solid #fecdd3;
        }


        /*
        |--------------------------------------------------------------------------
        | STATS
        |--------------------------------------------------------------------------
        */

        .contact-stats {

            display: grid;

            grid-template-columns:
                repeat(
                    4,
                    minmax(0, 1fr)
                );

            gap: 18px;

            margin-bottom: 30px;
        }


        .contact-stat {

            position: relative;

            min-height: 135px;

            overflow: hidden;

            padding: 24px 22px;

            background: #ffffff;

            border:
                1px solid
                var(--contact-border);

            border-top:
                4px solid #2563eb;

            border-radius: 20px;

            box-shadow:
                0 12px 28px
                rgba(20, 60, 120, .055);
        }


        .contact-stat::after {

            content: "";

            position: absolute;

            right: -29px;
            bottom: -45px;

            width: 110px;
            height: 110px;

            border-radius: 50%;

            background: #edf4ff;
        }


        .contact-stat.new {
            border-top-color: #f59e0b;
        }


        .contact-stat.new::after {
            background: #fff7df;
        }


        .contact-stat.read {
            border-top-color: #3b82f6;
        }


        .contact-stat.replied {
            border-top-color: #16a34a;
        }


        .contact-stat.replied::after {
            background: #eaf9ef;
        }


        .contact-stat-label {

            position: relative;
            z-index: 2;

            display: block;

            margin-bottom: 14px;

            color: #61728e;

            font-size: 10px;
            font-weight: 800;

            letter-spacing: .75px;

            text-transform: uppercase;
        }


        .contact-stat-value {

            position: relative;
            z-index: 2;

            display: block;

            color: #0b326d;

            font-size: 32px;
            line-height: 1;

            font-weight: 800;
        }


        /*
        |--------------------------------------------------------------------------
        | LAYOUT
        |--------------------------------------------------------------------------
        */

        .contact-layout {

            display: grid;

            grid-template-columns:
                minmax(0, 1.45fr)
                minmax(330px, .75fr);

            gap: 22px;

            align-items: start;
        }


        /*
        |--------------------------------------------------------------------------
        | PANEL
        |--------------------------------------------------------------------------
        */

        .contact-panel {

            overflow: hidden;

            background: #ffffff;

            border:
                1px solid
                var(--contact-border);

            border-radius: 24px;

            box-shadow:
                0 14px 35px
                rgba(24, 64, 120, .055);
        }


        .contact-panel-header {

            min-height: 100px;

            padding: 24px 27px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;

            border-bottom:
                1px solid #e7edf5;
        }


        .contact-panel-title {

            display: flex;
            align-items: center;

            gap: 15px;
        }


        .contact-panel-icon {

            width: 51px;
            height: 51px;

            flex-shrink: 0;

            display: flex;
            align-items: center;
            justify-content: center;

            border-radius: 15px;

            background:
                linear-gradient(
                    135deg,
                    #1476e8,
                    #1d95f3
                );

            font-size: 21px;

            box-shadow:
                0 9px 20px
                rgba(37, 99, 235, .22);
        }


        .contact-panel-header h2 {

            margin: 0 0 5px;

            color: #092e65;

            font-size: 19px;
            font-weight: 800;
        }


        .contact-panel-header p {

            margin: 0;

            color: #8999b4;

            font-size: 10px;
        }


        .contact-count {

            min-height: 36px;

            padding: 0 15px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            color: #2563eb;

            background: #eff6ff;

            border: 1px solid #d6e7ff;

            border-radius: 999px;

            font-size: 10px;
            font-weight: 800;

            white-space: nowrap;
        }


        /*
        |--------------------------------------------------------------------------
        | FILTER
        |--------------------------------------------------------------------------
        */

        .contact-filter-wrapper {

            padding: 20px 24px;

            background: #fbfdff;

            border-bottom:
                1px solid #edf1f6;
        }


        .contact-filter {

            display: grid;

            grid-template-columns:
                minmax(200px, 1fr)
                140px
                auto
                auto;

            gap: 9px;
        }


        .contact-filter input,
        .contact-filter select {

            width: 100%;
            height: 42px;

            padding: 0 12px;

            outline: none;

            color: #26354e;

            background: #ffffff;

            border: 1px solid #d8e3ef;

            border-radius: 10px;

            font-size: 10px;
        }


        .contact-filter input:focus,
        .contact-filter select:focus {

            border-color: #3b82f6;

            box-shadow:
                0 0 0 3px
                rgba(59, 130, 246, .08);
        }


        /*
        |--------------------------------------------------------------------------
        | BUTTONS
        |--------------------------------------------------------------------------
        */

        .contact-btn {

            min-height: 42px;

            padding: 0 16px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border-radius: 10px;

            font-size: 10px;
            font-weight: 800;

            text-decoration: none;

            cursor: pointer;

            white-space: nowrap;
        }


        .contact-btn-primary {

            color: #ffffff;

            border: 0;

            background:
                linear-gradient(
                    135deg,
                    #2563eb,
                    #1d65d8
                );
        }


        .contact-btn-secondary {

            color: #66758b;

            background: #ffffff;

            border: 1px solid #d7e2ee;
        }


        /*
        |--------------------------------------------------------------------------
        | MESSAGE LIST
        |--------------------------------------------------------------------------
        */

        .contact-message-list {
            padding: 8px;
        }


        .contact-message-item {

            display: block;

            margin-bottom: 7px;

            padding: 17px;

            color: inherit;

            background: #ffffff;

            border:
                1px solid #e8eef6;

            border-radius: 14px;

            text-decoration: none;

            transition:
                transform .15s ease,
                border-color .15s ease,
                background .15s ease;
        }


        .contact-message-item:hover {

            transform:
                translateY(-1px);

            border-color:
                #bdd6f5;

            background:
                #fbfdff;
        }


        .contact-message-item.active {

            border-color:
                #75aaf1;

            background:
                #f2f7ff;

            box-shadow:
                0 0 0 2px
                rgba(37, 99, 235, .05);
        }


        .contact-message-top {

            display: flex;

            justify-content: space-between;

            gap: 12px;

            margin-bottom: 8px;
        }


        .contact-message-person {

            min-width: 0;
        }


        .contact-message-person strong {

            display: block;

            margin-bottom: 3px;

            overflow: hidden;

            color: #112b55;

            font-size: 11px;
            font-weight: 800;

            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .contact-message-person small {

            display: block;

            overflow: hidden;

            color: #8797ae;

            font-size: 8px;

            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .contact-message-subject {

            display: block;

            margin-bottom: 6px;

            overflow: hidden;

            color: #334155;

            font-size: 10px;
            font-weight: 700;

            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .contact-message-preview {

            display: -webkit-box;

            overflow: hidden;

            color: #8290a5;

            font-size: 9px;
            line-height: 1.55;

            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
        }


        .contact-message-footer {

            margin-top: 10px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 10px;
        }


        .contact-message-date {

            color: #9aa8bb;

            font-size: 8px;
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        .contact-status {

            min-height: 25px;

            padding: 0 8px;

            display: inline-flex;
            align-items: center;

            gap: 5px;

            border-radius: 999px;

            font-size: 7px;
            font-weight: 800;
        }


        .contact-status::before {

            content: "";

            width: 5px;
            height: 5px;

            border-radius: 50%;

            background: currentColor;
        }


        .contact-status.new {

            color: #a16207;

            background: #fffbea;
        }


        .contact-status.read {

            color: #1d4ed8;

            background: #eff6ff;
        }


        .contact-status.replied {

            color: #15803d;

            background: #ecfdf3;
        }


        /*
        |--------------------------------------------------------------------------
        | DETAIL
        |--------------------------------------------------------------------------
        */

        .contact-detail-body {
            padding: 27px;
        }


        .contact-detail-empty {

            min-height: 420px;

            padding: 40px 25px;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;

            text-align: center;
        }


        .contact-detail-empty-icon {

            width: 70px;
            height: 70px;

            margin-bottom: 16px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: #eff6ff;

            border: 1px solid #dbeafe;

            border-radius: 20px;

            font-size: 30px;
        }


        .contact-detail-empty h3 {

            margin: 0 0 7px;

            color: #49617f;

            font-size: 15px;
            font-weight: 800;
        }


        .contact-detail-empty p {

            max-width: 330px;

            margin: 0;

            color: #94a3b8;

            font-size: 10px;
            line-height: 1.7;
        }


        .contact-detail-heading {

            margin-bottom: 21px;
        }


        .contact-detail-heading h3 {

            margin: 0 0 8px;

            color: #0b326d;

            font-size: 19px;
            font-weight: 800;

            line-height: 1.35;
        }


        .contact-detail-heading p {

            margin: 0;

            color: #8291a8;

            font-size: 9px;
        }


        .contact-info-grid {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;

            margin-bottom: 20px;
        }


        .contact-info-card {

            padding: 13px;

            background: #f8fbff;

            border:
                1px solid #e4edf7;

            border-radius: 11px;
        }


        .contact-info-card span {

            display: block;

            margin-bottom: 4px;

            color: #8a9ab1;

            font-size: 7px;
            font-weight: 800;

            letter-spacing: .5px;

            text-transform: uppercase;
        }


        .contact-info-card strong {

            display: block;

            overflow: hidden;

            color: #334155;

            font-size: 9px;
            font-weight: 700;

            text-overflow: ellipsis;
            white-space: nowrap;
        }


        .contact-original {

            margin-bottom: 21px;

            padding: 18px;

            color: #475569;

            background: #f8fafc;

            border:
                1px solid #e2e8f0;

            border-radius: 12px;

            font-size: 10px;
            line-height: 1.8;

            white-space: pre-wrap;

            word-break: break-word;
        }


        .contact-section-label {

            display: block;

            margin-bottom: 8px;

            color: #64748b;

            font-size: 8px;
            font-weight: 800;

            letter-spacing: .5px;

            text-transform: uppercase;
        }


        /*
        |--------------------------------------------------------------------------
        | REPLY
        |--------------------------------------------------------------------------
        */

        .contact-reply-box {

            margin-top: 20px;

            padding-top: 20px;

            border-top:
                1px solid #e7edf5;
        }


        .contact-reply-box textarea {

            width: 100%;
            min-height: 150px;

            resize: vertical;

            padding: 14px;

            outline: none;

            color: #334155;

            background: #ffffff;

            border:
                1px solid #d7e2ef;

            border-radius: 11px;

            font-size: 10px;
            line-height: 1.7;
        }


        .contact-reply-box textarea:focus {

            border-color: #3b82f6;

            box-shadow:
                0 0 0 3px
                rgba(59, 130, 246, .08);
        }


        .contact-reply-actions {

            margin-top: 11px;

            display: flex;
            justify-content: flex-end;
        }


        .contact-reply-actions .contact-btn {

            min-width: 135px;
        }


        .contact-previous-reply {

            margin-top: 20px;

            padding: 17px;

            color: #166534;

            background: #f0fdf4;

            border:
                1px solid #bbf7d0;

            border-radius: 12px;

            font-size: 10px;
            line-height: 1.75;

            white-space: pre-wrap;

            word-break: break-word;
        }


        /*
        |--------------------------------------------------------------------------
        | EMPTY LIST
        |--------------------------------------------------------------------------
        */

        .contact-list-empty {

            padding: 65px 20px;

            text-align: center;
        }


        .contact-list-empty div {

            margin-bottom: 10px;

            font-size: 28px;
        }


        .contact-list-empty strong {

            display: block;

            margin-bottom: 5px;

            color: #49617f;

            font-size: 13px;
        }


        .contact-list-empty p {

            margin: 0;

            color: #94a3b8;

            font-size: 9px;
        }


        /*
        |--------------------------------------------------------------------------
        | RESPONSIVE
        |--------------------------------------------------------------------------
        */

        @media (max-width: 1150px) {

            .contact-layout {
                grid-template-columns: 1fr;
            }

            .contact-stats {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }


        @media (max-width: 900px) {

            :root {
                --contact-sidebar-width: 0px;
            }


            .contact-main {
                margin-left: 0;
                width: 100%;
            }


            .contact-content {
                padding: 25px 20px 50px;
            }


            .contact-hero {
                min-height: 140px;
                padding: 28px;
            }


            .contact-hero h1 {
                font-size: 31px;
            }


            .contact-hero-icon {
                width: 67px;
                height: 67px;
                font-size: 28px;
            }
        }


        @media (max-width: 650px) {

            .contact-content {
                padding: 18px 13px 40px;
            }


            .contact-hero {

                min-height: auto;

                padding: 25px 21px;

                border-radius: 20px;
            }


            .contact-hero h1 {
                font-size: 27px;
            }


            .contact-hero p {

                max-width: 230px;

                font-size: 11px;
            }


            .contact-hero-icon {

                width: 55px;
                height: 55px;

                border-radius: 15px;

                font-size: 24px;
            }


            .contact-stats {
                grid-template-columns: 1fr;
            }


            .contact-panel-header {

                flex-direction: column;

                align-items: flex-start;

                padding: 20px 17px;
            }


            .contact-filter {
                grid-template-columns: 1fr;
            }


            .contact-btn {
                width: 100%;
            }


            .contact-info-grid {
                grid-template-columns: 1fr;
            }


            .contact-detail-body {
                padding: 20px 16px;
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


    <main class="contact-main">


        <div class="contact-content">


            <!-- HERO -->

            <section class="contact-hero">


                <div class="contact-hero-text">

                    <h1>
                        Contact Messages
                    </h1>

                    <p>
                        View and reply to customer enquiries sent through HochipoHub.
                    </p>

                </div>


                <div class="contact-hero-icon">
                    ✉️
                </div>


            </section>


            <!-- ALERT -->

            <?php if ($message !== ''): ?>

                <div class="contact-alert success">

                    <?= contactAdminEscape(
                        $message
                    ) ?>

                </div>

            <?php endif; ?>


            <?php if ($error !== ''): ?>

                <div class="contact-alert error">

                    <?= contactAdminEscape(
                        $error
                    ) ?>

                </div>

            <?php endif; ?>


            <!-- STATISTICS -->

            <section class="contact-stats">


                <div class="contact-stat">

                    <span class="contact-stat-label">
                        Total Messages
                    </span>

                    <strong class="contact-stat-value">

                        <?= number_format(
                            $totalMessages
                        ) ?>

                    </strong>

                </div>


                <div class="contact-stat new">

                    <span class="contact-stat-label">
                        New
                    </span>

                    <strong class="contact-stat-value">

                        <?= number_format(
                            $newMessages
                        ) ?>

                    </strong>

                </div>


                <div class="contact-stat read">

                    <span class="contact-stat-label">
                        Read
                    </span>

                    <strong class="contact-stat-value">

                        <?= number_format(
                            $readMessages
                        ) ?>

                    </strong>

                </div>


                <div class="contact-stat replied">

                    <span class="contact-stat-label">
                        Replied
                    </span>

                    <strong class="contact-stat-value">

                        <?= number_format(
                            $repliedMessages
                        ) ?>

                    </strong>

                </div>


            </section>


            <!-- MAIN LAYOUT -->

            <section class="contact-layout">


                <!-- MESSAGE LIST -->

                <div class="contact-panel">


                    <div class="contact-panel-header">


                        <div class="contact-panel-title">


                            <div class="contact-panel-icon">
                                📥
                            </div>


                            <div>

                                <h2>
                                    Customer Enquiries
                                </h2>

                                <p>
                                    Search, filter and open customer messages.
                                </p>

                            </div>


                        </div>


                        <span class="contact-count">

                            <?= number_format(
                                count(
                                    $contactMessages
                                )
                            ) ?>

                            messages

                        </span>


                    </div>


                    <!-- FILTER -->

                    <div class="contact-filter-wrapper">


                        <form
                            method="GET"
                            action="contact_messages.php"
                            class="contact-filter"
                        >


                            <input
                                type="search"
                                name="search"
                                value="<?= contactAdminEscape(
                                    $search
                                ) ?>"
                                placeholder="Search name, email, subject or message..."
                                autocomplete="off"
                            >


                            <select
                                name="status"
                                aria-label="Filter message status"
                            >

                                <option value="">
                                    All Status
                                </option>


                                <?php foreach (
                                    [
                                        'New',
                                        'Read',
                                        'Replied'
                                    ]
                                    as $filterStatus
                                ): ?>

                                    <option
                                        value="<?= contactAdminEscape(
                                            $filterStatus
                                        ) ?>"
                                        <?= $statusFilter ===
                                            $filterStatus
                                                ? 'selected'
                                                : '' ?>
                                    >

                                        <?= contactAdminEscape(
                                            $filterStatus
                                        ) ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>


                            <button
                                type="submit"
                                class="
                                    contact-btn
                                    contact-btn-primary
                                "
                            >
                                Search
                            </button>


                            <a
                                href="contact_messages.php"
                                class="
                                    contact-btn
                                    contact-btn-secondary
                                "
                            >
                                Reset
                            </a>


                        </form>


                    </div>


                    <!-- LIST -->

                    <?php if (
                        empty(
                            $contactMessages
                        )
                    ): ?>


                        <div class="contact-list-empty">

                            <div>
                                📭
                            </div>

                            <strong>
                                No contact messages found
                            </strong>

                            <p>
                                Customer enquiries will appear here after they submit the contact form.
                            </p>

                        </div>


                    <?php else: ?>


                        <div class="contact-message-list">


                            <?php foreach (
                                $contactMessages
                                as $contactItem
                            ): ?>


                                <?php

                                $itemId =
                                    (int)
                                    $contactItem[
                                        'contact_message_id'
                                    ];

                                $itemStatus =
                                    $contactItem[
                                        'status'
                                    ]
                                    ?? 'New';

                                $itemStatusClass =
                                    contactAdminStatusClass(
                                        $itemStatus
                                    );

                                ?>


                                <a
                                    href="contact_messages.php?view=<?= $itemId ?>"
                                    class="
                                        contact-message-item
                                        <?= $viewId === $itemId
                                            ? 'active'
                                            : '' ?>
                                    "
                                >


                                    <div class="contact-message-top">


                                        <div class="contact-message-person">

                                            <strong>

                                                <?= contactAdminEscape(
                                                    $contactItem[
                                                        'name'
                                                    ]
                                                ) ?>

                                            </strong>


                                            <small>

                                                <?= contactAdminEscape(
                                                    $contactItem[
                                                        'email'
                                                    ]
                                                ) ?>

                                            </small>

                                        </div>


                                        <span
                                            class="
                                                contact-status
                                                <?= contactAdminEscape(
                                                    $itemStatusClass
                                                ) ?>
                                            "
                                        >

                                            <?= contactAdminEscape(
                                                $itemStatus
                                            ) ?>

                                        </span>


                                    </div>


                                    <span class="contact-message-subject">

                                        <?= contactAdminEscape(
                                            $contactItem[
                                                'subject'
                                            ]
                                        ) ?>

                                    </span>


                                    <span class="contact-message-preview">

                                        <?= contactAdminEscape(
                                            $contactItem[
                                                'message'
                                            ]
                                        ) ?>

                                    </span>


                                    <div class="contact-message-footer">

                                        <span class="contact-message-date">

                                            <?= contactAdminEscape(
                                                contactAdminDate(
                                                    $contactItem[
                                                        'created_at'
                                                    ]
                                                )
                                            ) ?>

                                        </span>

                                    </div>


                                </a>


                            <?php endforeach; ?>


                        </div>


                    <?php endif; ?>


                </div>


                <!-- MESSAGE DETAIL -->

                <div class="contact-panel">


                    <div class="contact-panel-header">


                        <div class="contact-panel-title">


                            <div class="contact-panel-icon">
                                💬
                            </div>


                            <div>

                                <h2>
                                    Message Details
                                </h2>

                                <p>
                                    Read the enquiry and send your reply.
                                </p>

                            </div>


                        </div>


                    </div>


                    <?php if (!$selectedMessage): ?>


                        <div class="contact-detail-empty">


                            <div class="contact-detail-empty-icon">
                                ✉️
                            </div>


                            <h3>
                                Select a message
                            </h3>


                            <p>
                                Choose a customer enquiry from the list to view the full message and reply.
                            </p>


                        </div>


                    <?php else: ?>


                        <div class="contact-detail-body">


                            <?php

                            $selectedStatus =
                                $selectedMessage[
                                    'status'
                                ]
                                ?? 'Read';

                            $selectedStatusClass =
                                contactAdminStatusClass(
                                    $selectedStatus
                                );

                            ?>


                            <div class="contact-detail-heading">


                                <span
                                    class="
                                        contact-status
                                        <?= contactAdminEscape(
                                            $selectedStatusClass
                                        ) ?>
                                    "
                                >

                                    <?= contactAdminEscape(
                                        $selectedStatus
                                    ) ?>

                                </span>


                                <h3>

                                    <?= contactAdminEscape(
                                        $selectedMessage[
                                            'subject'
                                        ]
                                    ) ?>

                                </h3>


                                <p>

                                    Message #<?= (int)
                                        $selectedMessage[
                                            'contact_message_id'
                                        ] ?>

                                    ·

                                    <?= contactAdminEscape(
                                        contactAdminDate(
                                            $selectedMessage[
                                                'created_at'
                                            ]
                                        )
                                    ) ?>

                                </p>


                            </div>


                            <div class="contact-info-grid">


                                <div class="contact-info-card">

                                    <span>
                                        Customer Name
                                    </span>

                                    <strong>

                                        <?= contactAdminEscape(
                                            $selectedMessage[
                                                'name'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div class="contact-info-card">

                                    <span>
                                        Email
                                    </span>

                                    <strong>

                                        <?= contactAdminEscape(
                                            $selectedMessage[
                                                'email'
                                            ]
                                        ) ?>

                                    </strong>

                                </div>


                                <div class="contact-info-card">

                                    <span>
                                        Account
                                    </span>

                                    <strong>

                                        <?php if (
                                            !empty(
                                                $selectedMessage[
                                                    'user_id'
                                                ]
                                            )
                                        ): ?>

                                            Registered User
                                            #<?= (int)
                                                $selectedMessage[
                                                    'user_id'
                                                ] ?>

                                        <?php else: ?>

                                            Guest / Visitor

                                        <?php endif; ?>

                                    </strong>

                                </div>


                                <div class="contact-info-card">

                                    <span>
                                        Status
                                    </span>

                                    <strong>

                                        <?= contactAdminEscape(
                                            $selectedStatus
                                        ) ?>

                                    </strong>

                                </div>


                            </div>


                            <span class="contact-section-label">
                                Customer Message
                            </span>


                            <div class="contact-original"><?= contactAdminEscape(
                                $selectedMessage[
                                    'message'
                                ]
                            ) ?></div>


                            <?php if (
                                $selectedStatus === 'Replied' &&
                                !empty(
                                    $selectedMessage[
                                        'admin_reply'
                                    ]
                                )
                            ): ?>


                                <span class="contact-section-label">
                                    Admin Reply
                                </span>


                                <div class="contact-previous-reply"><?= contactAdminEscape(
                                    $selectedMessage[
                                        'admin_reply'
                                    ]
                                ) ?></div>


                                <?php if (
                                    !empty(
                                        $selectedMessage[
                                            'replied_at'
                                        ]
                                    )
                                ): ?>

                                    <p
                                        style="
                                            margin:8px 0 0;
                                            color:#94a3b8;
                                            font-size:8px;
                                        "
                                    >

                                        Replied on

                                        <?= contactAdminEscape(
                                            contactAdminDate(
                                                $selectedMessage[
                                                    'replied_at'
                                                ]
                                            )
                                        ) ?>

                                        <?php if (
                                            !empty(
                                                $selectedMessage[
                                                    'replied_by_name'
                                                ]
                                            )
                                        ): ?>

                                            by

                                            <?= contactAdminEscape(
                                                $selectedMessage[
                                                    'replied_by_name'
                                                ]
                                            ) ?>

                                        <?php endif; ?>

                                    </p>

                                <?php endif; ?>


                            <?php else: ?>


                                <div class="contact-reply-box">


                                    <span class="contact-section-label">
                                        Reply to Customer
                                    </span>


                                    <form
                                        method="POST"
                                        action="contact_messages.php"
                                    >


                                        <input
                                            type="hidden"
                                            name="csrf_token"
                                            value="<?= contactAdminEscape(
                                                $csrfToken
                                            ) ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="contact_message_id"
                                            value="<?= (int)
                                                $selectedMessage[
                                                    'contact_message_id'
                                                ] ?>"
                                        >


                                        <input
                                            type="hidden"
                                            name="send_reply"
                                            value="1"
                                        >


                                        <textarea
                                            name="admin_reply"
                                            required
                                            maxlength="5000"
                                            placeholder="Write your reply to the customer here..."
                                        ></textarea>


                                        <div class="contact-reply-actions">


                                            <button
                                                type="submit"
                                                class="
                                                    contact-btn
                                                    contact-btn-primary
                                                "
                                                onclick="
                                                    return confirm(
                                                        'Send this reply to the customer by email?'
                                                    );
                                                "
                                            >
                                                Send Reply
                                            </button>


                                        </div>


                                    </form>


                                </div>


                            <?php endif; ?>


                        </div>


                    <?php endif; ?>


                </div>


            </section>


        </div>


    </main>


</div>


</body>

</html>