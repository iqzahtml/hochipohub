<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Contact Us';

$success = '';
$error = '';

$name = '';
$email = '';
$subject = '';
$message = '';


/*
|--------------------------------------------------------------------------
| HANDLE CONTACT FORM
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {

        $error = 'Please enter your name.';

    } elseif (
        $email === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL)
    ) {

        $error = 'Please enter a valid email address.';

    } elseif ($subject === '') {

        $error = 'Please enter a subject.';

    } elseif ($message === '') {

        $error = 'Please enter your message.';

    } else {

        /*
        |--------------------------------------------------------------------------
        | CONTACT MESSAGE
        |--------------------------------------------------------------------------
        |
        | No contact_messages table exists in your locked database.
        | Therefore we do NOT INSERT into a fake table.
        |
        | For now the form confirms the message submission.
        | Later, if you explicitly add a table, we can connect it.
        |--------------------------------------------------------------------------
        */

        $success =
            'Thank you, ' .
            e($name) .
            '! Your message has been received.';

        $name = '';
        $email = '';
        $subject = '';
        $message = '';
    }
}


require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

?>

<main class="contact-page">

    <section class="contact-hero">

        <div class="contact-hero-content">

            <span class="contact-badge">
                HOCHIPOHUB SUPPORT
            </span>

            <h1>
                Let's Talk.
                <span>We're Here.</span>
            </h1>

            <p>
                Have a question about products, orders,
                vendors or your HochipoHub account?
                Send us a message and we'll help you out.
            </p>

        </div>

    </section>


    <section class="contact-container">

        <?php if ($success): ?>

            <div class="alert alert-success">
                <?= $success ?>
            </div>

        <?php endif; ?>


        <?php if ($error): ?>

            <div class="alert alert-error">
                <?= e($error) ?>
            </div>

        <?php endif; ?>


        <div class="contact-grid">


            <!-- CONTACT INFORMATION -->

            <div class="contact-info">

                <span class="section-label">
                    GET IN TOUCH
                </span>

                <h2>
                    Need Help?
                </h2>

                <p>
                    We're always ready to help customers
                    and vendors with their HochipoHub
                    experience.
                </p>


                <div class="contact-info-list">

                    <div class="contact-info-item">

                        <div class="contact-icon">
                            ✉
                        </div>

                        <div>

                            <h3>Email</h3>

                            <p>
                                support@hochipoHub.com
                            </p>

                        </div>

                    </div>


                    <div class="contact-info-item">

                        <div class="contact-icon">
                            ☎
                        </div>

                        <div>

                            <h3>Phone</h3>

                            <p>
                                +60 7-123 4567
                            </p>

                        </div>

                    </div>


                    <div class="contact-info-item">

                        <div class="contact-icon">
                            📍
                        </div>

                        <div>

                            <h3>Location</h3>

                            <p>
                                Johor, Malaysia
                            </p>

                        </div>

                    </div>

                </div>

            </div>


            <!-- CONTACT FORM -->

            <div class="contact-form-card">

                <div class="form-heading">

                    <h2>
                        Send Us A Message
                    </h2>

                    <p>
                        Fill in the form below.
                    </p>

                </div>


                <form
                    method="POST"
                    action=""
                    class="contact-form"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?= e(csrfToken()) ?>"
                    >


                    <div class="form-row">

                        <div class="form-group">

                            <label for="name">
                                Name
                            </label>

                            <input
                                type="text"
                                id="name"
                                name="name"
                                value="<?= e($name) ?>"
                                placeholder="Your name"
                                maxlength="100"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label for="email">
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= e($email) ?>"
                                placeholder="you@example.com"
                                maxlength="100"
                                required
                            >

                        </div>

                    </div>


                    <div class="form-group">

                        <label for="subject">
                            Subject
                        </label>

                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            value="<?= e($subject) ?>"
                            placeholder="What can we help you with?"
                            maxlength="150"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label for="message">
                            Message
                        </label>

                        <textarea
                            id="message"
                            name="message"
                            rows="7"
                            placeholder="Write your message here..."
                            maxlength="2000"
                            required
                        ><?= e($message) ?></textarea>

                    </div>


                    <button
                        type="submit"
                        class="btn-submit"
                    >
                        Send Message →
                    </button>

                </form>

            </div>

        </div>

    </section>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>