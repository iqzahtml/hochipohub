<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - CONTACT PAGE
|--------------------------------------------------------------------------
| File:
| contact.php
|
| Purpose:
| - Display HochipoHub contact/support page
| - Contact information
| - Customer enquiry form
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/includes/header.php';


/*
|--------------------------------------------------------------------------
| CONTACT FORM
|--------------------------------------------------------------------------
*/

$formError = '';
$formSuccess = '';

$name = '';
$email = '';
$subject = '';
$message = '';


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

    } else {

        /*
        |--------------------------------------------------------------------------
        | FORM ACCEPTED
        |--------------------------------------------------------------------------
        |
        | Backend email/database processing can be connected here later.
        |
        */

        $formSuccess =
            'Thank you for contacting HochipoHub. '
            . 'Our support team will get back to you soon.';

        /*
        |--------------------------------------------------------------------------
        | CLEAR FORM
        |--------------------------------------------------------------------------
        */

        $name = '';
        $email = '';
        $subject = '';
        $message = '';

    }

}


$pageTitle = 'Contact Us';

?>

<style>

/*
|--------------------------------------------------------------------------
| CONTACT PAGE
|--------------------------------------------------------------------------
*/

.contact-page {
    background:
        radial-gradient(
            circle at top left,
            rgba(37, 99, 235, 0.08),
            transparent 35%
        ),
        #f8fafc;

    min-height: calc(100vh - 80px);
    padding: 70px 20px 90px;
}


/*
|--------------------------------------------------------------------------
| CONTAINER
|--------------------------------------------------------------------------
*/

.contact-container {
    width: min(1180px, 100%);
    margin: 0 auto;
}


/*
|--------------------------------------------------------------------------
| HERO
|--------------------------------------------------------------------------
*/

.contact-hero {
    text-align: center;
    max-width: 760px;
    margin: 0 auto 55px;
}

.contact-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 8px;

    font-size: 13px;
    font-weight: 700;
    letter-spacing: 0.12em;

    color: #2563eb;

    background: rgba(37, 99, 235, 0.08);

    padding: 8px 14px;

    border-radius: 999px;

    margin-bottom: 18px;
}

.contact-eyebrow::before {
    content: "";
    width: 7px;
    height: 7px;

    background: #2563eb;

    border-radius: 50%;
}

.contact-hero h1 {
    margin: 0 0 16px;

    font-size: clamp(36px, 5vw, 56px);

    line-height: 1.05;

    color: #0f172a;

    font-weight: 800;

    letter-spacing: -0.04em;
}

.contact-hero h1 span {
    color: #2563eb;
}

.contact-hero p {
    margin: 0;

    color: #64748b;

    font-size: 17px;

    line-height: 1.8;
}


/*
|--------------------------------------------------------------------------
| MAIN GRID
|--------------------------------------------------------------------------
*/

.contact-grid {
    display: grid;

    grid-template-columns:
        minmax(280px, 0.85fr)
        minmax(400px, 1.15fr);

    gap: 30px;

    align-items: stretch;
}


/*
|--------------------------------------------------------------------------
| CONTACT INFO
|--------------------------------------------------------------------------
*/

.contact-info-card {
    position: relative;

    overflow: hidden;

    background: #0f1f3d;

    border-radius: 24px;

    padding: 38px;

    color: white;

    box-shadow:
        0 20px 50px rgba(15, 31, 61, 0.15);
}

.contact-info-card::before {
    content: "";

    position: absolute;

    width: 240px;
    height: 240px;

    right: -100px;
    top: -100px;

    background: rgba(37, 99, 235, 0.25);

    border-radius: 50%;
}

.contact-info-card::after {
    content: "";

    position: absolute;

    width: 180px;
    height: 180px;

    left: -90px;
    bottom: -90px;

    background: rgba(59, 130, 246, 0.12);

    border-radius: 50%;
}

.contact-info-content {
    position: relative;
    z-index: 2;
}

.contact-info-card h2 {
    margin: 0 0 14px;

    font-size: 28px;

    font-weight: 800;
}

.contact-info-description {
    margin: 0 0 35px;

    color: #cbd5e1;

    line-height: 1.7;

    font-size: 15px;
}


/*
|--------------------------------------------------------------------------
| INFO ITEMS
|--------------------------------------------------------------------------
*/

.contact-info-list {
    display: flex;

    flex-direction: column;

    gap: 22px;
}

.contact-info-item {
    display: flex;

    align-items: flex-start;

    gap: 15px;
}

.contact-info-icon {
    flex: 0 0 46px;

    width: 46px;
    height: 46px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background: rgba(255, 255, 255, 0.1);

    color: #60a5fa;

    font-size: 20px;
}

.contact-info-item strong {
    display: block;

    margin-bottom: 5px;

    font-size: 14px;

    color: white;
}

.contact-info-item span,
.contact-info-item a {
    color: #cbd5e1;

    font-size: 14px;

    line-height: 1.6;

    text-decoration: none;
}

.contact-info-item a:hover {
    color: white;
}


/*
|--------------------------------------------------------------------------
| FORM CARD
|--------------------------------------------------------------------------
*/

.contact-form-card {
    background: white;

    border: 1px solid #e2e8f0;

    border-radius: 24px;

    padding: 38px;

    box-shadow:
        0 15px 45px rgba(15, 23, 42, 0.07);
}

.contact-form-header {
    margin-bottom: 28px;
}

.contact-form-header h2 {
    margin: 0 0 8px;

    color: #0f172a;

    font-size: 28px;

    font-weight: 800;
}

.contact-form-header p {
    margin: 0;

    color: #64748b;

    font-size: 14px;
}


/*
|--------------------------------------------------------------------------
| ALERT
|--------------------------------------------------------------------------
*/

.contact-alert {
    padding: 14px 16px;

    border-radius: 12px;

    margin-bottom: 22px;

    font-size: 14px;

    line-height: 1.5;
}

.contact-alert-error {
    color: #991b1b;

    background: #fef2f2;

    border: 1px solid #fecaca;
}

.contact-alert-success {
    color: #166534;

    background: #f0fdf4;

    border: 1px solid #bbf7d0;
}


/*
|--------------------------------------------------------------------------
| FORM GRID
|--------------------------------------------------------------------------
*/

.contact-form-grid {
    display: grid;

    grid-template-columns: 1fr 1fr;

    gap: 20px;
}

.contact-field {
    display: flex;

    flex-direction: column;

    gap: 8px;
}

.contact-field-full {
    grid-column: 1 / -1;
}

.contact-field label {
    color: #334155;

    font-size: 13px;

    font-weight: 700;
}

.contact-field input,
.contact-field textarea {
    width: 100%;

    box-sizing: border-box;

    border: 1px solid #dbe3ef;

    background: #f8fafc;

    border-radius: 12px;

    padding: 13px 14px;

    font-family: inherit;

    font-size: 14px;

    color: #0f172a;

    outline: none;

    transition:
        border-color 0.2s ease,
        box-shadow 0.2s ease,
        background 0.2s ease;
}

.contact-field input {
    height: 48px;
}

.contact-field textarea {
    min-height: 150px;

    resize: vertical;

    line-height: 1.6;
}

.contact-field input:focus,
.contact-field textarea:focus {
    background: white;

    border-color: #2563eb;

    box-shadow:
        0 0 0 4px rgba(37, 99, 235, 0.1);
}

.contact-field input::placeholder,
.contact-field textarea::placeholder {
    color: #94a3b8;
}


/*
|--------------------------------------------------------------------------
| SUBMIT BUTTON
|--------------------------------------------------------------------------
*/

.contact-submit {
    display: inline-flex;

    align-items: center;
    justify-content: center;

    gap: 9px;

    margin-top: 5px;

    min-height: 48px;

    padding: 0 24px;

    border: none;

    border-radius: 12px;

    background: #2563eb;

    color: white;

    font-family: inherit;

    font-size: 14px;

    font-weight: 700;

    cursor: pointer;

    transition:
        transform 0.2s ease,
        background 0.2s ease,
        box-shadow 0.2s ease;
}

.contact-submit:hover {
    background: #1d4ed8;

    transform: translateY(-1px);

    box-shadow:
        0 10px 25px rgba(37, 99, 235, 0.25);
}

.contact-submit:active {
    transform: translateY(0);
}


/*
|--------------------------------------------------------------------------
| BOTTOM NOTE
|--------------------------------------------------------------------------
*/

.contact-note {
    margin-top: 18px;

    color: #94a3b8;

    font-size: 12px;

    line-height: 1.6;
}


/*
|--------------------------------------------------------------------------
| RESPONSIVE
|--------------------------------------------------------------------------
*/

@media (max-width: 900px) {

    .contact-grid {
        grid-template-columns: 1fr;
    }

    .contact-info-card {
        min-height: auto;
    }

}


@media (max-width: 600px) {

    .contact-page {
        padding: 45px 15px 60px;
    }

    .contact-hero {
        margin-bottom: 35px;
    }

    .contact-hero h1 {
        font-size: 38px;
    }

    .contact-hero p {
        font-size: 15px;
    }

    .contact-info-card,
    .contact-form-card {
        padding: 25px;
        border-radius: 18px;
    }

    .contact-form-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .contact-field-full {
        grid-column: auto;
    }

    .contact-submit {
        width: 100%;
    }

}

</style>


<!-- =========================================================
     CONTACT PAGE
     ========================================================= -->

<section class="contact-page">

    <div class="contact-container">


        <!-- =====================================================
             HERO
        ====================================================== -->

        <div class="contact-hero">

            <span class="contact-eyebrow">
                HOCHIPOHUB SUPPORT
            </span>

            <h1>
                Let's Talk.
                <span>We're Here.</span>
            </h1>

            <p>
                Have a question about products, orders, vendors,
                or your HochipoHub account? Send us a message
                and our team will be happy to help.
            </p>

        </div>


        <!-- =====================================================
             CONTENT GRID
        ====================================================== -->

        <div class="contact-grid">


            <!-- =================================================
                 CONTACT INFORMATION
            ================================================== -->

            <div class="contact-info-card">

                <div class="contact-info-content">

                    <h2>
                        Need Help?
                    </h2>

                    <p class="contact-info-description">
                        We're always ready to help customers
                        and vendors with their HochipoHub
                        experience.
                    </p>


                    <div class="contact-info-list">


                        <!-- EMAIL -->

                        <div class="contact-info-item">

                            <div class="contact-info-icon">
                                <i class="bi bi-envelope"></i>
                            </div>

                            <div>

                                <strong>
                                    Email
                                </strong>

                                <a href="mailto:support@hochipohub.com">
                                    support@hochipohub.com
                                </a>

                            </div>

                        </div>


                        <!-- PHONE -->

                        <div class="contact-info-item">

                            <div class="contact-info-icon">
                                <i class="bi bi-telephone"></i>
                            </div>

                            <div>

                                <strong>
                                    Phone
                                </strong>

                                <a href="tel:+6071234567">
                                    +60 7-123 4567
                                </a>

                            </div>

                        </div>


                        <!-- LOCATION -->

                        <div class="contact-info-item">

                            <div class="contact-info-icon">
                                <i class="bi bi-geo-alt"></i>
                            </div>

                            <div>

                                <strong>
                                    Location
                                </strong>

                                <span>
                                    Johor, Malaysia
                                </span>

                            </div>

                        </div>


                        <!-- SUPPORT HOURS -->

                        <div class="contact-info-item">

                            <div class="contact-info-icon">
                                <i class="bi bi-clock"></i>
                            </div>

                            <div>

                                <strong>
                                    Support Hours
                                </strong>

                                <span>
                                    Monday – Friday<br>
                                    9:00 AM – 6:00 PM
                                </span>

                            </div>

                        </div>


                    </div>

                </div>

            </div>


            <!-- =================================================
                 CONTACT FORM
            ================================================== -->

            <div class="contact-form-card">

                <div class="contact-form-header">

                    <h2>
                        Send Us a Message
                    </h2>

                    <p>
                        Fill in the form below and tell us
                        how we can help.
                    </p>

                </div>


                <!-- ERROR -->

                <?php if ($formError !== ''): ?>

                    <div class="contact-alert contact-alert-error">

                        <i class="bi bi-exclamation-circle"></i>

                        <?= htmlspecialchars(
                            $formError,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <!-- SUCCESS -->

                <?php if ($formSuccess !== ''): ?>

                    <div class="contact-alert contact-alert-success">

                        <i class="bi bi-check-circle"></i>

                        <?= htmlspecialchars(
                            $formSuccess,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>

                    </div>

                <?php endif; ?>


                <form
                    action="<?= htmlspecialchars(
                        BASE_URL . 'contact.php',
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    method="POST"
                >

                    <div class="contact-form-grid">


                        <!-- NAME -->

                        <div class="contact-field">

                            <label for="contact-name">
                                Name
                            </label>

                            <input
                                type="text"
                                id="contact-name"
                                name="name"
                                placeholder="Your name"
                                value="<?= htmlspecialchars(
                                    $name,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="100"
                                required
                            >

                        </div>


                        <!-- EMAIL -->

                        <div class="contact-field">

                            <label for="contact-email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="contact-email"
                                name="email"
                                placeholder="you@example.com"
                                value="<?= htmlspecialchars(
                                    $email,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="150"
                                required
                            >

                        </div>


                        <!-- SUBJECT -->

                        <div class="contact-field contact-field-full">

                            <label for="contact-subject">
                                Subject
                            </label>

                            <input
                                type="text"
                                id="contact-subject"
                                name="subject"
                                placeholder="What can we help you with?"
                                value="<?= htmlspecialchars(
                                    $subject,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                maxlength="200"
                                required
                            >

                        </div>


                        <!-- MESSAGE -->

                        <div class="contact-field contact-field-full">

                            <label for="contact-message">
                                Message
                            </label>

                            <textarea
                                id="contact-message"
                                name="message"
                                placeholder="Write your message here..."
                                maxlength="2000"
                                required
                            ><?= htmlspecialchars(
                                $message,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?></textarea>

                        </div>


                        <!-- SUBMIT -->

                        <div class="contact-field contact-field-full">

                            <button
                                type="submit"
                                class="contact-submit"
                            >

                                <i class="bi bi-send"></i>

                                Send Message

                            </button>

                        </div>

                    </div>


                    <p class="contact-note">

                        <i class="bi bi-shield-check"></i>

                        Your information will only be used
                        to respond to your enquiry.

                    </p>

                </form>

            </div>


        </div>

    </div>

</section>


<?php

/*
|--------------------------------------------------------------------------
| FOOTER
|--------------------------------------------------------------------------
*/

$footerPath = __DIR__ . '/includes/footer.php';

if (file_exists($footerPath)) {
    require_once $footerPath;
}

?>