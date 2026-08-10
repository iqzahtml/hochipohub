<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/database/db.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| PAGE DATA
|--------------------------------------------------------------------------
*/

$pageTitle = 'Contact Us';

/*
|--------------------------------------------------------------------------
| USER DATA
|--------------------------------------------------------------------------
*/

$currentUser = null;

if (isset($_SESSION['user_id'])) {

    $userId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT
            user_id,
            name,
            email,
            phone,
            role,
            status
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    $currentUser = $result->fetch_assoc();

    $stmt->close();
}

/*
|--------------------------------------------------------------------------
| FORM VARIABLES
|--------------------------------------------------------------------------
*/

$name = '';
$email = '';
$subject = '';
$message = '';

$formError = '';
$formSuccess = '';

/*
|--------------------------------------------------------------------------
| PREFILL LOGGED-IN USER
|--------------------------------------------------------------------------
*/

if ($currentUser) {

    $name = $currentUser['name'] ?? '';
    $email = $currentUser['email'] ?? '';

}

/*
|--------------------------------------------------------------------------
| CONTACT FORM
|--------------------------------------------------------------------------
|
| IMPORTANT:
| There is no contact_messages table in the provided database.
| Therefore this page validates the form and prepares the message,
| but does NOT pretend to insert data into a non-existing table.
|
| When mail/send_mail.php is connected later, this form can be
| changed to send the message through PHPMailer.
|
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */

    if ($name === '') {

        $formError = 'Please enter your name.';

    } elseif ($email === '') {

        $formError = 'Please enter your email address.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $formError = 'Please enter a valid email address.';

    } elseif ($subject === '') {

        $formError = 'Please enter a subject.';

    } elseif ($message === '') {

        $formError = 'Please enter your message.';

    } elseif (strlen($message) < 10) {

        $formError = 'Your message is too short. Please provide more details.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | FORM VALID
        |--------------------------------------------------------------------------
        |
        | Do not insert into DB because the supplied database has
        | no contact_messages table.
        |
        */

        $formSuccess =
            'Your message has been prepared successfully. ' .
            'Our support team can be contacted through the email below.';

    }

}

/*
|--------------------------------------------------------------------------
| SUPPORT INFORMATION
|--------------------------------------------------------------------------
*/

$supportEmail = 'support@hochipohub.com';
$supportHours = 'Monday - Friday, 9:00 AM - 6:00 PM';

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
        <?php echo htmlspecialchars($pageTitle); ?>
        |
        <?php echo htmlspecialchars(SITE_NAME); ?>
    </title>


    <!-- MAIN CSS -->

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/style.css'); ?>"
    >

    <link
        rel="stylesheet"
        href="<?php echo site_url('css/responsive.css'); ?>"
    >


    <style>

        /* =====================================================
           CONTACT PAGE
        ===================================================== */

        .contact-page {

            min-height: 100vh;

            padding-bottom: 80px;

            background:

                radial-gradient(
                    circle at 10% 5%,
                    rgba(37, 99, 235, .20),
                    transparent 30%
                ),

                radial-gradient(
                    circle at 90% 15%,
                    rgba(14, 165, 233, .15),
                    transparent 28%
                ),

                linear-gradient(
                    145deg,
                    #020617,
                    #061a35 55%,
                    #020617
                );

            color: #f8fafc;

        }


        .contact-container {

            width: 90%;

            max-width: 1300px;

            margin: auto;

            padding-top: 45px;

        }


        /* =====================================================
           HERO
        ===================================================== */

        .contact-hero {

            position: relative;

            overflow: hidden;

            padding: 45px 40px;

            margin-bottom: 25px;

            border:

                1px solid
                rgba(56, 189, 248, .18);

            border-radius: 30px;

            background:

                linear-gradient(
                    135deg,
                    rgba(15, 23, 42, .96),
                    rgba(8, 47, 73, .78)
                );

            box-shadow:

                0 25px 80px
                rgba(0, 0, 0, .25);

        }


        .contact-hero::before {

            content: "";

            position: absolute;

            width: 330px;

            height: 330px;

            right: -120px;

            top: -170px;

            border-radius: 50%;

            background:

                rgba(14, 165, 233, .12);

            filter: blur(5px);

        }


        .contact-hero::after {

            content: "";

            position: absolute;

            width: 180px;

            height: 180px;

            left: -100px;

            bottom: -120px;

            border-radius: 50%;

            background:

                rgba(37, 99, 235, .12);

        }


        .contact-eyebrow {

            position: relative;

            z-index: 2;

            display: inline-flex;

            align-items: center;

            gap: 8px;

            margin-bottom: 12px;

            color: #38bdf8;

            font-size: 10px;

            font-weight: 950;

            letter-spacing: 2px;

        }


        .contact-eyebrow::before {

            content: "";

            width: 7px;

            height: 7px;

            border-radius: 50%;

            background: #22d3ee;

            box-shadow:

                0 0 15px
                rgba(34, 211, 238, .9);

        }


        .contact-hero h1 {

            position: relative;

            z-index: 2;

            margin: 0;

            font-size: clamp(
                32px,
                5vw,
                58px
            );

            line-height: .95;

            font-weight: 950;

            letter-spacing: -2px;

        }


        .contact-hero h1 span {

            color: #38bdf8;

        }


        .contact-hero p {

            position: relative;

            z-index: 2;

            max-width: 650px;

            margin: 18px 0 0;

            color: #64748b;

            font-size: 13px;

            line-height: 1.8;

        }


        /* =====================================================
           GRID
        ===================================================== */

        .contact-grid {

            display: grid;

            grid-template-columns:
                1fr 1.45fr;

            gap: 20px;

            align-items: start;

        }


        /* =====================================================
           CONTACT CARDS
        ===================================================== */

        .contact-info {

            display: grid;

            gap: 15px;

        }


        .contact-card {

            position: relative;

            overflow: hidden;

            padding: 23px;

            border:

                1px solid
                rgba(148, 163, 184, .10);

            border-radius: 20px;

            background:

                rgba(15, 23, 42, .78);

            transition: .25s ease;

        }


        .contact-card:hover {

            transform:
                translateY(-3px);

            border-color:

                rgba(56, 189, 248, .28);

            box-shadow:

                0 20px 45px
                rgba(0, 0, 0, .20);

        }


        .contact-card-icon {

            display: flex;

            align-items: center;

            justify-content: center;

            width: 43px;

            height: 43px;

            margin-bottom: 16px;

            border-radius: 14px;

            background:

                rgba(14, 165, 233, .10);

            color: #38bdf8;

            font-size: 17px;

            font-weight: 900;

        }


        .contact-card h3 {

            margin: 0 0 7px;

            color: #e2e8f0;

            font-size: 14px;

            font-weight: 900;

        }


        .contact-card p {

            margin: 0;

            color: #64748b;

            font-size: 11px;

            line-height: 1.7;

        }


        .contact-card a {

            display: inline-block;

            margin-top: 8px;

            color: #7dd3fc;

            font-size: 11px;

            font-weight: 800;

            text-decoration: none;

            word-break: break-word;

        }


        .contact-card a:hover {

            color: #38bdf8;

        }


        /* =====================================================
           FORM CARD
        ===================================================== */

        .contact-form-card {

            padding: 28px;

            border:

                1px solid
                rgba(148, 163, 184, .10);

            border-radius: 22px;

            background:

                rgba(15, 23, 42, .82);

            box-shadow:

                0 20px 60px
                rgba(0, 0, 0, .18);

        }


        .contact-form-heading {

            margin-bottom: 22px;

        }


        .contact-form-heading h2 {

            margin: 0;

            color: #f8fafc;

            font-size: 21px;

            font-weight: 950;

            letter-spacing: -.5px;

        }


        .contact-form-heading p {

            margin: 6px 0 0;

            color: #64748b;

            font-size: 10px;

            line-height: 1.6;

        }


        /* =====================================================
           ALERT
        ===================================================== */

        .contact-alert {

            margin-bottom: 18px;

            padding: 13px 15px;

            border-radius: 12px;

            font-size: 10px;

            font-weight: 700;

            line-height: 1.5;

        }


        .contact-alert.error {

            border:

                1px solid
                rgba(248, 113, 113, .18);

            background:

                rgba(239, 68, 68, .08);

            color: #fca5a5;

        }


        .contact-alert.success {

            border:

                1px solid
                rgba(34, 197, 94, .18);

            background:

                rgba(34, 197, 94, .08);

            color: #86efac;

        }


        /* =====================================================
           FORM
        ===================================================== */

        .contact-form {

            display: grid;

            gap: 16px;

        }


        .contact-row {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 13px;

        }


        .contact-field {

            display: grid;

            gap: 7px;

        }


        .contact-field label {

            color: #94a3b8;

            font-size: 9px;

            font-weight: 900;

            letter-spacing: 1px;

            text-transform: uppercase;

        }


        .contact-field input,
        .contact-field textarea,
        .contact-field select {

            width: 100%;

            box-sizing: border-box;

            padding: 13px 14px;

            border:

                1px solid
                rgba(148, 163, 184, .12);

            border-radius: 12px;

            outline: none;

            background:

                rgba(2, 6, 23, .62);

            color: #f8fafc;

            font-family: inherit;

            font-size: 11px;

            transition: .2s ease;

        }


        .contact-field input::placeholder,
        .contact-field textarea::placeholder {

            color: #334155;

        }


        .contact-field input:focus,
        .contact-field textarea:focus,
        .contact-field select:focus {

            border-color:

                rgba(56, 189, 248, .55);

            box-shadow:

                0 0 0 3px
                rgba(56, 189, 248, .06);

        }


        .contact-field textarea {

            min-height: 150px;

            resize: vertical;

            line-height: 1.6;

        }


        .contact-submit {

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;

            margin-top: 3px;

        }


        .contact-submit-note {

            color: #475569;

            font-size: 9px;

            line-height: 1.5;

        }


        .contact-submit-btn {

            border: 0;

            padding: 13px 21px;

            border-radius: 12px;

            background:

                linear-gradient(
                    135deg,
                    #0284c7,
                    #2563eb
                );

            color: white;

            font-size: 10px;

            font-weight: 950;

            letter-spacing: .5px;

            cursor: pointer;

            box-shadow:

                0 10px 25px
                rgba(37, 99, 235, .22);

            transition: .2s ease;

        }


        .contact-submit-btn:hover {

            transform:
                translateY(-2px);

            box-shadow:

                0 15px 35px
                rgba(37, 99, 235, .32);

        }


        /* =====================================================
           QUICK LINKS
        ===================================================== */

        .contact-quick {

            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 10px;

            margin-top: 15px;

        }


        .quick-item {

            padding: 14px;

            border:

                1px solid
                rgba(148, 163, 184, .08);

            border-radius: 14px;

            background:

                rgba(2, 6, 23, .35);

        }


        .quick-item strong {

            display: block;

            margin-bottom: 4px;

            color: #cbd5e1;

            font-size: 10px;

        }


        .quick-item span {

            color: #475569;

            font-size: 9px;

        }


        /* =====================================================
           RESPONSIVE
        ===================================================== */

        @media (max-width: 900px) {

            .contact-grid {

                grid-template-columns: 1fr;

            }

            .contact-info {

                grid-template-columns:
                    repeat(2, 1fr);

            }

        }


        @media (max-width: 650px) {

            .contact-container {

                width: 92%;

                padding-top: 25px;

            }

            .contact-hero {

                padding: 32px 25px;

            }

            .contact-form-card {

                padding: 22px;

            }

            .contact-info {

                grid-template-columns: 1fr;

            }

            .contact-row {

                grid-template-columns: 1fr;

            }

            .contact-submit {

                flex-direction: column;

                align-items: stretch;

            }

            .contact-submit-btn {

                width: 100%;

            }

        }


        @media (max-width: 450px) {

            .contact-hero h1 {

                font-size: 36px;

            }

            .contact-quick {

                grid-template-columns: 1fr;

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

require_once __DIR__ . '/includes/navbar.php';

?>


<main class="contact-page">


    <div class="contact-container">


        <!-- =================================================
             HERO
        ================================================== -->

        <section class="contact-hero">

            <div class="contact-eyebrow">
                HOCHIPOHUB SUPPORT
            </div>

            <h1>
                Let's talk.
                <span>We're listening.</span>
            </h1>

            <p>
                Need help with your account, products,
                orders or vendor activities? Reach out
                to the HochipoHub support team.
            </p>

        </section>


        <!-- =================================================
             CONTENT
        ================================================== -->

        <div class="contact-grid">


            <!-- =============================================
                 INFORMATION
            ============================================== -->

            <section class="contact-info">


                <!-- EMAIL -->

                <div class="contact-card">

                    <div class="contact-card-icon">
                        @
                    </div>

                    <h3>
                        Email Support
                    </h3>

                    <p>
                        Send us your questions or issues
                        and our support team can assist you.
                    </p>

                    <a
                        href="mailto:<?php
                        echo htmlspecialchars($supportEmail);
                        ?>"
                    >
                        <?php
                        echo htmlspecialchars($supportEmail);
                        ?>
                    </a>

                </div>


                <!-- HOURS -->

                <div class="contact-card">

                    <div class="contact-card-icon">
                        ◷
                    </div>

                    <h3>
                        Support Hours
                    </h3>

                    <p>
                        Our support team is available during
                        the following operating hours.
                    </p>

                    <a href="#">
                        <?php
                        echo htmlspecialchars($supportHours);
                        ?>
                    </a>

                </div>


                <!-- ACCOUNT -->

                <div class="contact-card">

                    <div class="contact-card-icon">
                        ◉
                    </div>

                    <h3>
                        Account Support
                    </h3>

                    <p>
                        For login, registration, MFA,
                        password reset or profile issues,
                        include your registered email.
                    </p>

                </div>


                <!-- VENDOR -->

                <div class="contact-card">

                    <div class="contact-card-icon">
                        ◆
                    </div>

                    <h3>
                        Vendor Support
                    </h3>

                    <p>
                        Vendors can contact support regarding
                        products, orders, inventory,
                        commissions and vendor applications.
                    </p>

                </div>


            </section>


            <!-- =============================================
                 FORM
            ============================================== -->

            <section class="contact-form-card">


                <div class="contact-form-heading">

                    <h2>
                        Send us a message
                    </h2>

                    <p>
                        Fill in the details below and explain
                        your issue clearly.
                    </p>

                </div>


                <?php if ($formError !== ''): ?>

                    <div class="contact-alert error">

                        <?php
                        echo htmlspecialchars(
                            $formError
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <?php if ($formSuccess !== ''): ?>

                    <div class="contact-alert success">

                        <?php
                        echo htmlspecialchars(
                            $formSuccess
                        );
                        ?>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    action=""
                    class="contact-form"
                >


                    <!-- NAME + EMAIL -->

                    <div class="contact-row">


                        <div class="contact-field">

                            <label for="name">
                                Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                placeholder="Your name"
                                maxlength="100"
                                value="<?php
                                echo htmlspecialchars(
                                    $name
                                );
                                ?>"
                                required
                            >

                        </div>


                        <div class="contact-field">

                            <label for="email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                placeholder="you@example.com"
                                maxlength="100"
                                value="<?php
                                echo htmlspecialchars(
                                    $email
                                );
                                ?>"
                                required
                            >

                        </div>


                    </div>


                    <!-- SUBJECT -->

                    <div class="contact-field">

                        <label for="subject">
                            Subject
                        </label>

                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            placeholder="What can we help you with?"
                            maxlength="150"
                            value="<?php
                            echo htmlspecialchars(
                                $subject
                            );
                            ?>"
                            required
                        >

                    </div>


                    <!-- MESSAGE -->

                    <div class="contact-field">

                        <label for="message">
                            Message
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            placeholder="Tell us what happened..."
                            maxlength="3000"
                            required
                        ><?php
                        echo htmlspecialchars(
                            $message
                        );
                        ?></textarea>

                    </div>


                    <!-- SUBMIT -->

                    <div class="contact-submit">

                        <span class="contact-submit-note">
                            Please avoid sharing your password
                            or MFA code.
                        </span>

                        <button
                            type="submit"
                            class="contact-submit-btn"
                        >
                            SEND MESSAGE →
                        </button>

                    </div>


                </form>


                <!-- QUICK INFORMATION -->

                <div class="contact-quick">

                    <div class="quick-item">

                        <strong>
                            Account
                        </strong>

                        <span>
                            Login & profile assistance
                        </span>

                    </div>


                    <div class="quick-item">

                        <strong>
                            Orders
                        </strong>

                        <span>
                            Payment & order assistance
                        </span>

                    </div>


                    <div class="quick-item">

                        <strong>
                            Vendor
                        </strong>

                        <span>
                            Store & commission assistance
                        </span>

                    </div>


                    <div class="quick-item">

                        <strong>
                            Security
                        </strong>

                        <span>
                            MFA & account security
                        </span>

                    </div>

                </div>


            </section>


        </div>


    </div>


</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>


</body>

</html>