<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOGIN PROCESS
|--------------------------------------------------------------------------
| File:
| auth/login_process.php
|
| Purpose:
| - Process user login
| - Verify email and password
| - Check account status
| - Create secure login session
| - Redirect user according to role
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| LOAD CONFIGURATION
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/session.php';


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    redirect(
        BASE_URL . 'index.php'
    );
}


/*
|--------------------------------------------------------------------------
| GET LOGIN FORM DATA
|--------------------------------------------------------------------------
*/

$email = strtolower(
    trim(
        (string) (
            $_POST['email']
            ?? ''
        )
    )
);

$password =
    (string) (
        $_POST['password']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL AND PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $email === ''
    ||
    $password === ''
) {

    $_SESSION['login_error'] =
        'Email and password are required.';

    $_SESSION['login_email'] =
        $email;

    redirect(
        BASE_URL
        . 'index.php?login=1'
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE EMAIL FORMAT
|--------------------------------------------------------------------------
*/

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    $_SESSION['login_error'] =
        'Please enter a valid email address.';

    $_SESSION['login_email'] =
        $email;

    redirect(
        BASE_URL
        . 'index.php?login=1'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE LOGIN PROCESS
|--------------------------------------------------------------------------
*/

try {

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    $pdo = getDB();


    /*
    |--------------------------------------------------------------------------
    | FIND USER BY EMAIL
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare("
        SELECT
            user_id,
            name,
            email,
            password,
            role,
            status
        FROM users
        WHERE LOWER(email) = LOWER(?)
        LIMIT 1
    ");


    $stmt->execute([
        $email
    ]);


    $user = $stmt->fetch(
        PDO::FETCH_ASSOC
    );


    /*
    |--------------------------------------------------------------------------
    | INVALID EMAIL
    |--------------------------------------------------------------------------
    |
    | Keep the message generic so the website does not reveal
    | whether a particular email exists.
    |
    |--------------------------------------------------------------------------
    */

    if (!$user) {

        $_SESSION['login_error'] =
            'Invalid email or password.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL
            . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFY PASSWORD
    |--------------------------------------------------------------------------
    */

    if (
        !password_verify(
            $password,
            (string) $user['password']
        )
    ) {

        $_SESSION['login_error'] =
            'Invalid email or password.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL
            . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    $status = strtolower(
        trim(
            (string) (
                $user['status']
                ?? ''
            )
        )
    );


    /*
    |--------------------------------------------------------------------------
    | CHECK ACCOUNT STATUS
    |--------------------------------------------------------------------------
    */

    if ($status !== 'active') {

        /*
        |--------------------------------------------------------------------------
        | PENDING ACCOUNT
        |--------------------------------------------------------------------------
        */

        if ($status === 'pending') {

            $_SESSION['login_error'] =
                'Your account is still pending approval.';

        /*
        |--------------------------------------------------------------------------
        | SUSPENDED ACCOUNT
        |--------------------------------------------------------------------------
        */

        } elseif ($status === 'suspended') {

            $_SESSION['login_error'] =
                'Your account has been suspended. Please contact HochipoHub.';

        /*
        |--------------------------------------------------------------------------
        | INACTIVE ACCOUNT
        |--------------------------------------------------------------------------
        */

        } elseif ($status === 'inactive') {

            $_SESSION['login_error'] =
                'Your account is currently inactive.';

        /*
        |--------------------------------------------------------------------------
        | UNKNOWN STATUS
        |--------------------------------------------------------------------------
        */

        } else {

            $_SESSION['login_error'] =
                'Your account is not active.';
        }


        $_SESSION['login_email'] =
            $email;


        redirect(
            BASE_URL
            . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | NORMALIZE ROLE
    |--------------------------------------------------------------------------
    */

    $role = strtolower(
        trim(
            (string) (
                $user['role']
                ?? ''
            )
        )
    );


    /*
    |--------------------------------------------------------------------------
    | VALIDATE ROLE
    |--------------------------------------------------------------------------
    */

    $allowedRoles = [
        'admin',
        'vendor',
        'customer'
    ];


    if (
        !in_array(
            $role,
            $allowedRoles,
            true
        )
    ) {

        $_SESSION['login_error'] =
            'Unable to determine your account role.';

        $_SESSION['login_email'] =
            $email;

        redirect(
            BASE_URL
            . 'index.php?login=1'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR APPROVAL CHECK
    |--------------------------------------------------------------------------
    |
    | Vendor must exist in vendors table and must be approved.
    |
    | Admin and customer are not affected by this check.
    |
    |--------------------------------------------------------------------------
    */

    if ($role === 'vendor') {

        $vendorStmt = $pdo->prepare("
            SELECT
                vendor_id,
                approval_status
            FROM vendors
            WHERE user_id = ?
            LIMIT 1
        ");


        $vendorStmt->execute([
            (int) $user['user_id']
        ]);


        $vendor = $vendorStmt->fetch(
            PDO::FETCH_ASSOC
        );


        /*
        |--------------------------------------------------------------------------
        | VENDOR PROFILE NOT FOUND
        |--------------------------------------------------------------------------
        */

        if (!$vendor) {

            $_SESSION['login_error'] =
                'Vendor profile was not found. Please contact HochipoHub.';

            $_SESSION['login_email'] =
                $email;

            redirect(
                BASE_URL
                . 'index.php?login=1'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | NORMALIZE APPROVAL STATUS
        |--------------------------------------------------------------------------
        */

        $approvalStatus = strtolower(
            trim(
                (string) (
                    $vendor['approval_status']
                    ?? 'pending'
                )
            )
        );


        /*
        |--------------------------------------------------------------------------
        | CHECK VENDOR APPROVAL
        |--------------------------------------------------------------------------
        */

        if ($approvalStatus !== 'approved') {

            if ($approvalStatus === 'pending') {

                $_SESSION['login_error'] =
                    'Your seller account is still waiting for admin approval.';

            } elseif ($approvalStatus === 'rejected') {

                $_SESSION['login_error'] =
                    'Your seller application was rejected. Please contact HochipoHub.';

            } elseif ($approvalStatus === 'suspended') {

                $_SESSION['login_error'] =
                    'Your seller account has been suspended. Please contact HochipoHub.';

            } else {

                $_SESSION['login_error'] =
                    'Your seller account is not approved.';
            }


            $_SESSION['login_email'] =
                $email;


            redirect(
                BASE_URL
                . 'index.php?login=1'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | REGENERATE SESSION ID
    |--------------------------------------------------------------------------
    |
    | Helps prevent session fixation.
    |
    |--------------------------------------------------------------------------
    */

    session_regenerate_id(true);


    /*
    |--------------------------------------------------------------------------
    | CLEAR OLD LOGIN SESSION VALUES
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_email'],
        $_SESSION['name'],
        $_SESSION['email'],
        $_SESSION['role'],
        $_SESSION['user_role'],
        $_SESSION['status'],
        $_SESSION['user_status'],
        $_SESSION['logged_in'],
        $_SESSION['login_time'],
        $_SESSION['last_activity'],
        $_SESSION['vendor_id']
    );


    /*
    |--------------------------------------------------------------------------
    | CREATE USER SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['user_id'] =
        (int) $user['user_id'];


    $_SESSION['user_name'] =
        (string) $user['name'];


    $_SESSION['user_email'] =
        (string) $user['email'];


    /*
    |--------------------------------------------------------------------------
    | COMPATIBILITY SESSION VALUES
    |--------------------------------------------------------------------------
    |
    | Some existing HochipoHub pages may still use:
    |
    | $_SESSION['name']
    | $_SESSION['email']
    |
    |--------------------------------------------------------------------------
    */

    $_SESSION['name'] =
        (string) $user['name'];


    $_SESSION['email'] =
        (string) $user['email'];


    /*
    |--------------------------------------------------------------------------
    | ROLE SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['role'] =
        $role;


    $_SESSION['user_role'] =
        $role;


    /*
    |--------------------------------------------------------------------------
    | STATUS SESSION
    |--------------------------------------------------------------------------
    */

    $_SESSION['status'] =
        $status;


    $_SESSION['user_status'] =
        $status;


    /*
    |--------------------------------------------------------------------------
    | LOGIN INFORMATION
    |--------------------------------------------------------------------------
    */

    $_SESSION['logged_in'] =
        true;


    $_SESSION['login_time'] =
        time();


    $_SESSION['last_activity'] =
        time();


    /*
    |--------------------------------------------------------------------------
    | SAVE VENDOR ID
    |--------------------------------------------------------------------------
    */

    if (
        $role === 'vendor'
        &&
        isset($vendor['vendor_id'])
    ) {

        $_SESSION['vendor_id'] =
            (int) $vendor['vendor_id'];
    }


    /*
    |--------------------------------------------------------------------------
    | CLEAR LOGIN ERROR MESSAGES
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['login_error'],
        $_SESSION['login_email'],
        $_SESSION['error']
    );


    /*
    |--------------------------------------------------------------------------
    | REDIRECT ADMIN
    |--------------------------------------------------------------------------
    */

    if ($role === 'admin') {

        redirect(
            BASE_URL
            . 'admin/dashboard.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REDIRECT VENDOR
    |--------------------------------------------------------------------------
    */

    if ($role === 'vendor') {

        redirect(
            BASE_URL
            . 'seller/dashboard.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REDIRECT CUSTOMER
    |--------------------------------------------------------------------------
    */

    if ($role === 'customer') {

        redirect(
            BASE_URL
            . 'dashboard.php'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FALLBACK
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Unable to complete login. Please try again.';


    redirect(
        BASE_URL
        . 'index.php?login=1'
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | LOG REAL ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'HochipoHub Login Error: '
        . $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | USER-FRIENDLY ERROR
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_error'] =
        'Unable to login right now. Please try again.';


    $_SESSION['login_email'] =
        $email;


    redirect(
        BASE_URL
        . 'index.php?login=1'
    );
}