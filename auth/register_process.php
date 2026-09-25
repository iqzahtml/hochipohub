<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - REGISTER PROCESS
|--------------------------------------------------------------------------
| File:
| auth/register_process.php
|
| Handles:
| - Customer registration
| - Vendor registration
| - Vendor pending approval
| - Vendor application creation
| - Duplicate email
| - Duplicate phone number
| - Malaysian phone validation
| - Secure register password validation
| - Registration validation
|--------------------------------------------------------------------------
*/

require_once dirname(__DIR__) . '/config.php';


/*
|--------------------------------------------------------------------------
| START SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| ONLY ALLOW POST REQUEST
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header(
        'Location: ' .
        BASE_URL .
        'index.php'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| GET FORM DATA
|--------------------------------------------------------------------------
*/

$name = trim(
    $_POST['name'] ?? ''
);


/*
|--------------------------------------------------------------------------
| NORMALIZE EMAIL
|--------------------------------------------------------------------------
*/

$email = strtolower(
    trim(
        $_POST['email'] ?? ''
    )
);


$phone = trim(
    $_POST['phone'] ?? ''
);


$role = strtolower(
    trim(
        $_POST['role'] ?? 'customer'
    )
);


$password =
    $_POST['password'] ?? '';


$confirmPassword =
    $_POST['confirm_password'] ?? '';


$terms =
    isset($_POST['terms']);


/*
|--------------------------------------------------------------------------
| CLEAN / NORMALIZE PHONE NUMBER
|--------------------------------------------------------------------------
|
| Examples:
|
| 012-3456789
| 012 3456789
| 0123456789
|
| All become:
|
| 0123456789
|
|--------------------------------------------------------------------------
*/

$cleanPhone = preg_replace(
    '/[^0-9]/',
    '',
    $phone
);


/*
|--------------------------------------------------------------------------
| OLD FORM DATA
|--------------------------------------------------------------------------
|
| Password is intentionally NOT stored in session.
|--------------------------------------------------------------------------
*/

$_SESSION['register_old'] = [

    'name' =>
        $name,

    'email' =>
        $email,

    'phone' =>
        $phone,

    'role' =>
        $role

];


/*
|--------------------------------------------------------------------------
| REGISTER ERROR FUNCTION
|--------------------------------------------------------------------------
*/

function registerError($message)
{
    $_SESSION['register_error'] =
        $message;

    $_SESSION['open_register_modal'] =
        true;

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| NAME VALIDATION
|--------------------------------------------------------------------------
*/

if ($name === '') {

    registerError(
        'Name is required.'
    );
}


/*
|--------------------------------------------------------------------------
| EMAIL VALIDATION
|--------------------------------------------------------------------------
*/

if (
    $email === '' ||
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
) {

    registerError(
        'Please enter a valid email address.'
    );
}


/*
|--------------------------------------------------------------------------
| PHONE REQUIRED
|--------------------------------------------------------------------------
*/

if ($cleanPhone === '') {

    registerError(
        'Phone number is required.'
    );
}


/*
|--------------------------------------------------------------------------
| MALAYSIAN MOBILE PHONE VALIDATION
|--------------------------------------------------------------------------
|
| Accepted:
|
| 0123456789
| 012-3456789
| 01112345678
| 011-12345678
|
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/^01[0-9]{8,9}$/',
        $cleanPhone
    )
) {

    registerError(
        'Please enter a valid Malaysian phone number.'
    );
}


/*
|--------------------------------------------------------------------------
| ROLE VALIDATION
|--------------------------------------------------------------------------
*/

$allowedRoles = [
    'customer',
    'vendor'
];


if (
    !in_array(
        $role,
        $allowedRoles,
        true
    )
) {

    registerError(
        'Invalid account type.'
    );
}


/*
|--------------------------------------------------------------------------
| PASSWORD REQUIRED
|--------------------------------------------------------------------------
*/

if ($password === '') {

    registerError(
        'Password is required.'
    );
}


/*
|--------------------------------------------------------------------------
| PASSWORD MINIMUM LENGTH
|--------------------------------------------------------------------------
|
| REGISTER ONLY:
| Minimum 8 characters.
|--------------------------------------------------------------------------
*/

if (
    strlen($password) < 8
) {

    registerError(
        'Password must contain at least 8 characters.'
    );
}


/*
|--------------------------------------------------------------------------
| PASSWORD SPECIAL CHARACTER
|--------------------------------------------------------------------------
|
| REGISTER ONLY:
|
| Password must contain at least one character that is NOT:
| A-Z
| a-z
| 0-9
|
| Examples:
| @
| #
| $
| %
| !
| &
| *
| _
| -
|
|--------------------------------------------------------------------------
*/

if (
    !preg_match(
        '/[^A-Za-z0-9]/',
        $password
    )
) {

    registerError(
        'Password must contain at least 1 special character.'
    );
}


/*
|--------------------------------------------------------------------------
| CONFIRM PASSWORD
|--------------------------------------------------------------------------
*/

if (
    $password !==
    $confirmPassword
) {

    registerError(
        'Passwords do not match.'
    );
}


/*
|--------------------------------------------------------------------------
| TERMS & CONDITIONS
|--------------------------------------------------------------------------
*/

if (!$terms) {

    registerError(
        'You must agree to the Terms & Conditions.'
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db = null;


try {

    /*
    |--------------------------------------------------------------------------
    | DATABASE CONNECTION
    |--------------------------------------------------------------------------
    */

    $db = getDB();


    if (!($db instanceof PDO)) {

        throw new Exception(
            'Database connection failed.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE EMAIL
    |--------------------------------------------------------------------------
    |
    | One email address = one account.
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT
            user_id

        FROM users

        WHERE LOWER(email) = LOWER(:email)

        LIMIT 1
    ");


    $stmt->execute([

        ':email' =>
            $email

    ]);


    if (
        $stmt->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        registerError(
            'This email is already registered. Please use another email.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK DUPLICATE PHONE NUMBER
    |--------------------------------------------------------------------------
    |
    | One phone number = one account.
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        SELECT
            user_id

        FROM users

        WHERE phone = :phone

        LIMIT 1
    ");


    $stmt->execute([

        ':phone' =>
            $cleanPhone

    ]);


    if (
        $stmt->fetch(
            PDO::FETCH_ASSOC
        )
    ) {

        registerError(
            'This phone number is already registered. Please use another phone number.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HASH PASSWORD
    |--------------------------------------------------------------------------
    */

    $passwordHash =
        password_hash(
            $password,
            PASSWORD_DEFAULT
        );


    if ($passwordHash === false) {

        throw new Exception(
            'Failed to secure password.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | START TRANSACTION
    |--------------------------------------------------------------------------
    */

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | USER STATUS
    |--------------------------------------------------------------------------
    |
    | Customer = active
    | Vendor   = pending until admin approval
    |
    |--------------------------------------------------------------------------
    */

    $userStatus =
        ($role === 'vendor')
            ? 'pending'
            : 'active';


    /*
    |--------------------------------------------------------------------------
    | INSERT USER
    |--------------------------------------------------------------------------
    */

    $stmt = $db->prepare("
        INSERT INTO users
        (
            name,
            email,
            phone,
            password,
            role,
            status
        )

        VALUES
        (
            :name,
            :email,
            :phone,
            :password,
            :role,
            :status
        )
    ");


    $stmt->execute([

        ':name' =>
            $name,

        ':email' =>
            $email,

        ':phone' =>
            $cleanPhone,

        ':password' =>
            $passwordHash,

        ':role' =>
            $role,

        ':status' =>
            $userStatus

    ]);


    /*
    |--------------------------------------------------------------------------
    | GET USER ID
    |--------------------------------------------------------------------------
    */

    $userId =
        (int) $db->lastInsertId();


    if ($userId <= 0) {

        throw new Exception(
            'Failed to create user.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | VENDOR REGISTRATION
    |--------------------------------------------------------------------------
    */

    if ($role === 'vendor') {


        /*
        |--------------------------------------------------------------------------
        | CREATE VENDOR PROFILE
        |--------------------------------------------------------------------------
        */

        $vendorStmt =
            $db->prepare("
                INSERT INTO vendors
                (
                    user_id,
                    business_name,
                    delivery_method,
                    postage_fee,
                    allow_vendor_delivery,
                    cod_enabled,
                    vendor_delivery_fee,
                    commission_rate,
                    approval_status
                )

                VALUES
                (
                    :user_id,
                    :business_name,
                    'Both',
                    0.00,
                    0,
                    0,
                    0.00,
                    5.00,
                    'Pending'
                )
            ");


        $vendorStmt->execute([

            ':user_id' =>
                $userId,

            ':business_name' =>
                $name

        ]);


        /*
        |--------------------------------------------------------------------------
        | CHECK VENDOR ID
        |--------------------------------------------------------------------------
        */

        $vendorId =
            (int) $db->lastInsertId();


        if ($vendorId <= 0) {

            throw new Exception(
                'Failed to create vendor profile.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | CREATE VENDOR APPLICATION
        |--------------------------------------------------------------------------
        */

        $applicationStmt =
            $db->prepare("
                INSERT INTO vendor_applications
                (
                    user_id,
                    business_name,
                    reason,
                    status
                )

                VALUES
                (
                    :user_id,
                    :business_name,
                    :reason,
                    'Pending'
                )
            ");


        $applicationStmt->execute([

            ':user_id' =>
                $userId,

            ':business_name' =>
                $name,

            ':reason' =>
                'New vendor registration application.'

        ]);


        /*
        |--------------------------------------------------------------------------
        | CHECK APPLICATION ID
        |--------------------------------------------------------------------------
        */

        $applicationId =
            (int) $db->lastInsertId();


        if ($applicationId <= 0) {

            throw new Exception(
                'Failed to create vendor application.'
            );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | COMMIT TRANSACTION
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | CLEAR REGISTER SESSION
    |--------------------------------------------------------------------------
    */

    unset(
        $_SESSION['register_old'],
        $_SESSION['register_error'],
        $_SESSION['open_register_modal']
    );


    /*
    |--------------------------------------------------------------------------
    | SUCCESS MESSAGE
    |--------------------------------------------------------------------------
    */

    if ($role === 'vendor') {

        $_SESSION['login_success'] =
            'Vendor account created successfully. Your application is waiting for admin approval.';

    } else {

        $_SESSION['login_success'] =
            'Account created successfully. Please login.';
    }


    /*
    |--------------------------------------------------------------------------
    | REMEMBER EMAIL FOR LOGIN MODAL
    |--------------------------------------------------------------------------
    */

    $_SESSION['login_email'] =
        $email;


    /*
    |--------------------------------------------------------------------------
    | OPEN LOGIN MODAL
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        'index.php?login=1'
    );

    exit;

}


/*
|--------------------------------------------------------------------------
| REGISTRATION ERROR
|--------------------------------------------------------------------------
*/

catch (Throwable $e) {


    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        isset($db) &&
        $db instanceof PDO &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | RESTORE FORM
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_old'] = [

        'name' =>
            $name,

        'email' =>
            $email,

        'phone' =>
            $phone,

        'role' =>
            $role

    ];


    /*
    |--------------------------------------------------------------------------
    | ERROR MESSAGE
    |--------------------------------------------------------------------------
    */

    $_SESSION['register_error'] =
        'Registration error: ' .
        $e->getMessage();


    /*
    |--------------------------------------------------------------------------
    | OPEN REGISTER MODAL
    |--------------------------------------------------------------------------
    */

    $_SESSION['open_register_modal'] =
        true;


    /*
    |--------------------------------------------------------------------------
    | RETURN TO REGISTER
    |--------------------------------------------------------------------------
    */

    header(
        'Location: ' .
        BASE_URL .
        'index.php?register=1'
    );

    exit;
}

?>