<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - ADMIN VENDORS
|--------------------------------------------------------------------------
| File:
| admin/vendors.php
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/email.php';


/*
|--------------------------------------------------------------------------
| SESSION
|--------------------------------------------------------------------------
*/

if (
    session_status() ===
    PHP_SESSION_NONE
) {

    session_start();
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

$db =
    getDB();


if (
    !($db instanceof PDO)
) {

    die(
        'Database connection is not available.'
    );
}


/*
|--------------------------------------------------------------------------
| ADMIN ACCESS
|--------------------------------------------------------------------------
*/

if (
    !isset(
        $_SESSION['user_id']
    ) ||
    strtolower(
        (string) (
            $_SESSION['role']
            ?? ''
        )
    ) !== 'admin'
) {

    header(
        'Location: ../index.php'
    );

    exit;
}


$adminId =
    (int)
    $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

if (
    !function_exists(
        'vendorAdminEscape'
    )
) {

    function vendorAdminEscape(
        $value
    ): string {

        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


if (
    !function_exists(
        'vendorAdminStatusClass'
    )
) {

    function vendorAdminStatusClass(
        $status
    ): string {

        $status =
            strtolower(
                trim(
                    (string)
                    $status
                )
            );


        return match (
            $status
        ) {

            'approved' =>
                'approved',

            'rejected' =>
                'rejected',

            'suspended' =>
                'suspended',

            default =>
                'pending'
        };
    }
}


if (
    !function_exists(
        'vendorAdminInitial'
    )
) {

    function vendorAdminInitial(
        $name
    ): string {

        $name =
            trim(
                (string)
                $name
            );


        if ($name === '') {

            return 'V';
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


if (
    !function_exists(
        'vendorAdminMoney'
    )
) {

    function vendorAdminMoney(
        $value
    ): string {

        return number_format(
            (float) $value,
            2
        );
    }
}


if (
    !function_exists(
        'vendorAdminLog'
    )
) {

    function vendorAdminLog(
        PDO $db,
        int $adminId,
        string $action,
        string $targetType,
        int $targetId
    ): void {

        try {

            $stmt =
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


            $stmt->execute([

                $adminId,

                $action,

                $targetType,

                $targetId
            ]);


        } catch (
            Throwable $e
        ) {

            error_log(
                'Admin log error: ' .
                $e->getMessage()
            );
        }
    }
}


/*
|--------------------------------------------------------------------------
| GET USER FOR EMAIL
|--------------------------------------------------------------------------
*/

if (
    !function_exists(
        'vendorAdminGetUser'
    )
) {

    function vendorAdminGetUser(
        PDO $db,
        int $userId
    ): ?array {

        $stmt =
            $db->prepare("
                SELECT
                    user_id,
                    name,
                    email

                FROM users

                WHERE user_id = ?

                LIMIT 1
            ");


        $stmt->execute([
            $userId
        ]);


        $user =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return
            $user
                ? $user
                : null;
    }
}


/*
|--------------------------------------------------------------------------
| SEND APPROVAL NOTICE
|--------------------------------------------------------------------------
*/

if (
    !function_exists(
        'vendorAdminSendApprovalNotice'
    )
) {

    function vendorAdminSendApprovalNotice(
        PDO $db,
        int $userId,
        string $businessName
    ): bool {

        try {

            $user =
                vendorAdminGetUser(
                    $db,
                    $userId
                );


            if (!$user) {

                error_log(
                    'Seller approval email: user not found. User ID ' .
                    $userId
                );

                return false;
            }


            $email =
                trim(
                    (string) (
                        $user['email']
                        ?? ''
                    )
                );


            $name =
                trim(
                    (string) (
                        $user['name']
                        ?? 'Seller'
                    )
                );


            if (
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {

                error_log(
                    'Seller approval email invalid: ' .
                    $email
                );

                return false;
            }


            return sendSellerApprovalEmail(
                $email,
                $name,
                $businessName
            );


        } catch (
            Throwable $e
        ) {

            error_log(
                'Seller approval email error: ' .
                $e->getMessage()
            );


            return false;
        }
    }
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    empty(
        $_SESSION[
            'csrf_token'
        ]
    )
) {

    $_SESSION[
        'csrf_token'
    ] =
        bin2hex(
            random_bytes(
                32
            )
        );
}


$csrfToken =
    $_SESSION[
        'csrf_token'
    ];


/*
|--------------------------------------------------------------------------
| BACKFILL OLD PENDING VENDORS
|--------------------------------------------------------------------------
*/

try {

    $db->beginTransaction();


    $stmt =
        $db->query("
            SELECT

                v.vendor_id,

                v.user_id,

                v.business_name

            FROM vendors v

            LEFT JOIN vendor_applications va

                ON va.user_id =
                   v.user_id

                AND va.status =
                    'Pending'

            WHERE
                v.approval_status =
                'Pending'

            AND
                va.application_id
                IS NULL
        ");


    $missingApplications =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    if (
        !empty(
            $missingApplications
        )
    ) {

        $insertApplication =
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
                    ?,
                    ?,
                    ?,
                    'Pending'
                )
            ");


        foreach (
            $missingApplications as
            $missingApplication
        ) {

            $insertApplication->execute([

                (int)
                $missingApplication[
                    'user_id'
                ],

                $missingApplication[
                    'business_name'
                ],

                'Vendor application created from existing pending vendor profile.'
            ]);
        }
    }


    $db->commit();


} catch (
    Throwable $e
) {

    if (
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    error_log(
        'Vendor application backfill error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| FLASH MESSAGE
|--------------------------------------------------------------------------
*/

$message = '';

$messageType = '';


if (
    isset(
        $_GET['success']
    )
) {

    switch (
        $_GET['success']
    ) {

        case 'approved':

            $message =
                (
                    isset(
                        $_GET[
                            'email'
                        ]
                    ) &&
                    $_GET[
                        'email'
                    ] === 'sent'
                )
                    ? 'Vendor approved successfully. Approval email has been sent to the seller.'
                    : 'Vendor approved successfully. However, the approval email could not be sent.';

            $messageType =
                'success';

            break;


        case 'rejected':

            $message =
                'Vendor application rejected successfully.';

            $messageType =
                'success';

            break;


        case 'status':

            $message =
                'Vendor status updated successfully.';

            $messageType =
                'success';

            break;


        case 'status_approved':

            $message =
                (
                    isset(
                        $_GET[
                            'email'
                        ]
                    ) &&
                    $_GET[
                        'email'
                    ] === 'sent'
                )
                    ? 'Vendor status changed to Approved and an approval email was sent.'
                    : 'Vendor status changed to Approved, but the approval email could not be sent.';

            $messageType =
                'success';

            break;
    }
}


if (
    isset(
        $_GET['error']
    )
) {

    $messageType =
        'error';


    switch (
        $_GET['error']
    ) {

        case 'security':

            $message =
                'Invalid security token. Please refresh and try again.';

            break;


        case 'invalid':

            $message =
                'Invalid vendor information.';

            break;


        case 'notfound':

            $message =
                'Vendor or vendor application was not found.';

            break;


        case 'process':

            $message =
                'Unable to process vendor application.';

            break;


        case 'update':

            $message =
                'Unable to update vendor status.';

            break;


        default:

            $message =
                'Something went wrong. Please try again.';

            break;
    }
}


/*
|--------------------------------------------------------------------------
| POST REQUEST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER[
        'REQUEST_METHOD'
    ] === 'POST'
) {

    $submittedToken =
        $_POST[
            'csrf_token'
        ] ?? '';


    if (
        empty(
            $submittedToken
        ) ||
        !hash_equals(
            $csrfToken,
            $submittedToken
        )
    ) {

        header(
            'Location: vendors.php?error=security'
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | APPROVE / REJECT APPLICATION
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_POST[
                'application_action'
            ]
        )
    ) {

        $applicationId =
            (int) (
                $_POST[
                    'application_id'
                ]
                ?? 0
            );


        $action =
            trim(
                (string) (
                    $_POST[
                        'application_action'
                    ]
                    ?? ''
                )
            );


        if (
            $applicationId <= 0 ||
            !in_array(
                $action,
                [
                    'approve',
                    'reject'
                ],
                true
            )
        ) {

            header(
                'Location: vendors.php?error=invalid'
            );

            exit;
        }


        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | GET APPLICATION
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT

                        application_id,

                        user_id,

                        business_name,

                        reason,

                        status

                    FROM vendor_applications

                    WHERE
                        application_id = ?

                    LIMIT 1

                    FOR UPDATE
                ");


            $stmt->execute([
                $applicationId
            ]);


            $application =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (
                !$application
            ) {

                $db->rollBack();


                header(
                    'Location: vendors.php?error=notfound'
                );


                exit;
            }


            if (
                $application[
                    'status'
                ] !== 'Pending'
            ) {

                $db->rollBack();


                header(
                    'Location: vendors.php?error=invalid'
                );


                exit;
            }


            $vendorUserId =
                (int)
                $application[
                    'user_id'
                ];


            $businessName =
                trim(
                    (string)
                    $application[
                        'business_name'
                    ]
                );


            /*
            |--------------------------------------------------------------------------
            | APPROVE
            |--------------------------------------------------------------------------
            */

            if (
                $action ===
                'approve'
            ) {

                /*
                |--------------------------------------------------------------------------
                | APPLICATION
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE vendor_applications

                        SET

                            status =
                                'Approved',

                            reviewed_at =
                                NOW(),

                            reviewed_by =
                                ?

                        WHERE
                            application_id = ?
                    ");


                $stmt->execute([

                    $adminId,

                    $applicationId
                ]);


                /*
                |--------------------------------------------------------------------------
                | USER
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET

                            role =
                                'vendor',

                            status =
                                'active'

                        WHERE
                            user_id = ?

                        AND
                            role !=
                            'admin'
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);


                /*
                |--------------------------------------------------------------------------
                | EXISTING VENDOR
                |--------------------------------------------------------------------------
                */

                $stmt =
                    $db->prepare("
                        SELECT

                            vendor_id

                        FROM vendors

                        WHERE
                            user_id = ?

                        LIMIT 1
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);


                $existingVendorId =
                    $stmt->fetchColumn();


                if (
                    $existingVendorId
                ) {

                    $stmt =
                        $db->prepare("
                            UPDATE vendors

                            SET

                                business_name = ?,

                                approval_status =
                                    'Approved'

                            WHERE
                                user_id = ?
                        ");


                    $stmt->execute([

                        $businessName,

                        $vendorUserId
                    ]);


                    $vendorId =
                        (int)
                        $existingVendorId;


                } else {

                    /*
                    |--------------------------------------------------------------------------
                    | IMPORTANT:
                    | Current DB uses vendor_delivery_fee.
                    |--------------------------------------------------------------------------
                    */

                    $stmt =
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
                                ?,

                                ?,

                                'Both',

                                0.00,

                                0,

                                0,

                                0.00,

                                5.00,

                                'Approved'
                            )
                        ");


                    $stmt->execute([

                        $vendorUserId,

                        $businessName
                    ]);


                    $vendorId =
                        (int)
                        $db->lastInsertId();
                }


                /*
                |--------------------------------------------------------------------------
                | ADMIN LOG
                |--------------------------------------------------------------------------
                */

                vendorAdminLog(
                    $db,
                    $adminId,
                    'Approved vendor application',
                    'vendor',
                    $vendorId
                );


                /*
                |--------------------------------------------------------------------------
                | COMMIT APPROVAL FIRST
                |--------------------------------------------------------------------------
                |
                | Very important:
                | Approval remains successful even if email fails.
                |--------------------------------------------------------------------------
                */

                $db->commit();


                /*
                |--------------------------------------------------------------------------
                | SEND APPROVAL EMAIL
                |--------------------------------------------------------------------------
                */

                $emailSent =
                    vendorAdminSendApprovalNotice(
                        $db,
                        $vendorUserId,
                        $businessName
                    );


                header(
                    'Location: vendors.php?success=approved&email=' .
                    (
                        $emailSent
                            ? 'sent'
                            : 'failed'
                    )
                );


                exit;
            }


            /*
            |--------------------------------------------------------------------------
            | REJECT
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    UPDATE vendor_applications

                    SET

                        status =
                            'Rejected',

                        reviewed_at =
                            NOW(),

                        reviewed_by =
                            ?

                    WHERE
                        application_id = ?
                ");


            $stmt->execute([

                $adminId,

                $applicationId
            ]);


            /*
            |--------------------------------------------------------------------------
            | VENDOR
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    UPDATE vendors

                    SET

                        approval_status =
                            'Rejected'

                    WHERE
                        user_id = ?
                ");


            $stmt->execute([
                $vendorUserId
            ]);


            /*
            |--------------------------------------------------------------------------
            | USER
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    UPDATE users

                    SET

                        status =
                            'inactive'

                    WHERE
                        user_id = ?

                    AND
                        role !=
                        'admin'
                ");


            $stmt->execute([
                $vendorUserId
            ]);


            vendorAdminLog(
                $db,
                $adminId,
                'Rejected vendor application',
                'vendor_application',
                $applicationId
            );


            $db->commit();


            header(
                'Location: vendors.php?success=rejected'
            );


            exit;


        } catch (
            Throwable $e
        ) {

            if (
                $db->inTransaction()
            ) {

                $db->rollBack();
            }


            error_log(
                'Vendor application error: ' .
                $e->getMessage()
            );


            header(
                'Location: vendors.php?error=process'
            );


            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE VENDOR STATUS
    |--------------------------------------------------------------------------
    */

    if (
        isset(
            $_POST[
                'update_vendor_status'
            ]
        )
    ) {

        $vendorId =
            (int) (
                $_POST[
                    'vendor_id'
                ]
                ?? 0
            );


        $approvalStatus =
            trim(
                (string) (
                    $_POST[
                        'approval_status'
                    ]
                    ?? ''
                )
            );


        $allowedStatuses = [

            'Pending',

            'Approved',

            'Rejected',

            'Suspended'
        ];


        if (
            $vendorId <= 0 ||
            !in_array(
                $approvalStatus,
                $allowedStatuses,
                true
            )
        ) {

            header(
                'Location: vendors.php?error=invalid'
            );

            exit;
        }


        try {

            $db->beginTransaction();


            /*
            |--------------------------------------------------------------------------
            | GET VENDOR
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    SELECT

                        vendor_id,

                        user_id,

                        business_name,

                        approval_status

                    FROM vendors

                    WHERE
                        vendor_id = ?

                    LIMIT 1

                    FOR UPDATE
                ");


            $stmt->execute([
                $vendorId
            ]);


            $vendor =
                $stmt->fetch(
                    PDO::FETCH_ASSOC
                );


            if (!$vendor) {

                $db->rollBack();


                header(
                    'Location: vendors.php?error=notfound'
                );


                exit;
            }


            $vendorUserId =
                (int)
                $vendor[
                    'user_id'
                ];


            $oldApprovalStatus =
                (string)
                $vendor[
                    'approval_status'
                ];


            $businessName =
                (string)
                $vendor[
                    'business_name'
                ];


            /*
            |--------------------------------------------------------------------------
            | UPDATE VENDOR
            |--------------------------------------------------------------------------
            */

            $stmt =
                $db->prepare("
                    UPDATE vendors

                    SET

                        approval_status = ?

                    WHERE
                        vendor_id = ?
                ");


            $stmt->execute([

                $approvalStatus,

                $vendorId
            ]);


            /*
            |--------------------------------------------------------------------------
            | APPROVED
            |--------------------------------------------------------------------------
            */

            if (
                $approvalStatus ===
                'Approved'
            ) {

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET

                            role =
                                'vendor',

                            status =
                                'active'

                        WHERE
                            user_id = ?

                        AND
                            role !=
                                'admin'
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);


                $stmt =
                    $db->prepare("
                        UPDATE vendor_applications

                        SET

                            status =
                                'Approved',

                            reviewed_at =
                                NOW(),

                            reviewed_by =
                                ?

                        WHERE
                            user_id = ?

                        AND
                            status =
                                'Pending'
                    ");


                $stmt->execute([

                    $adminId,

                    $vendorUserId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | PENDING
            |--------------------------------------------------------------------------
            */

            elseif (
                $approvalStatus ===
                'Pending'
            ) {

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET

                            role =
                                'vendor',

                            status =
                                'pending'

                        WHERE
                            user_id = ?

                        AND
                            role !=
                                'admin'
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);


                $stmt =
                    $db->prepare("
                        SELECT

                            application_id

                        FROM vendor_applications

                        WHERE
                            user_id = ?

                        AND
                            status =
                                'Pending'

                        LIMIT 1
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);


                $pendingApplication =
                    $stmt->fetchColumn();


                if (
                    !$pendingApplication
                ) {

                    $stmt =
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
                                ?,
                                ?,
                                ?,
                                'Pending'
                            )
                        ");


                    $stmt->execute([

                        $vendorUserId,

                        $businessName,

                        'Vendor status changed to Pending by administrator.'
                    ]);
                }
            }


            /*
            |--------------------------------------------------------------------------
            | REJECTED
            |--------------------------------------------------------------------------
            */

            elseif (
                $approvalStatus ===
                'Rejected'
            ) {

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET

                            status =
                                'inactive'

                        WHERE
                            user_id = ?

                        AND
                            role !=
                                'admin'
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);


                $stmt =
                    $db->prepare("
                        UPDATE vendor_applications

                        SET

                            status =
                                'Rejected',

                            reviewed_at =
                                NOW(),

                            reviewed_by =
                                ?

                        WHERE
                            user_id = ?

                        AND
                            status =
                                'Pending'
                    ");


                $stmt->execute([

                    $adminId,

                    $vendorUserId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | SUSPENDED
            |--------------------------------------------------------------------------
            */

            elseif (
                $approvalStatus ===
                'Suspended'
            ) {

                $stmt =
                    $db->prepare("
                        UPDATE users

                        SET

                            status =
                                'suspended'

                        WHERE
                            user_id = ?

                        AND
                            role !=
                                'admin'
                    ");


                $stmt->execute([
                    $vendorUserId
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | ADMIN LOG
            |--------------------------------------------------------------------------
            */

            vendorAdminLog(
                $db,
                $adminId,
                'Updated vendor approval status to ' .
                $approvalStatus,
                'vendor',
                $vendorId
            );


            $db->commit();


            /*
            |--------------------------------------------------------------------------
            | SEND EMAIL ONLY WHEN STATUS BECOMES APPROVED
            |--------------------------------------------------------------------------
            |
            | Approved -> Approved
            | will NOT resend email.
            |
            | Pending / Rejected / Suspended -> Approved
            | WILL send email.
            |--------------------------------------------------------------------------
            */

            if (
                $approvalStatus ===
                    'Approved' &&
                $oldApprovalStatus !==
                    'Approved'
            ) {

                $emailSent =
                    vendorAdminSendApprovalNotice(
                        $db,
                        $vendorUserId,
                        $businessName
                    );


                header(
                    'Location: vendors.php?success=status_approved&email=' .
                    (
                        $emailSent
                            ? 'sent'
                            : 'failed'
                    )
                );


                exit;
            }


            header(
                'Location: vendors.php?success=status'
            );


            exit;


        } catch (
            Throwable $e
        ) {

            if (
                $db->inTransaction()
            ) {

                $db->rollBack();
            }


            error_log(
                'Vendor status update error: ' .
                $e->getMessage()
            );


            header(
                'Location: vendors.php?error=update'
            );


            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| SEARCH
|--------------------------------------------------------------------------
*/

$search =
    trim(
        (string) (
            $_GET[
                'search'
            ]
            ?? ''
        )
    );


$statusFilter =
    trim(
        (string) (
            $_GET[
                'status'
            ]
            ?? ''
        )
    );


$allowedFilters = [

    '',

    'Pending',

    'Approved',

    'Rejected',

    'Suspended'
];


if (
    !in_array(
        $statusFilter,
        $allowedFilters,
        true
    )
) {

    $statusFilter = '';
}


/*
|--------------------------------------------------------------------------
| LOAD VENDORS
|--------------------------------------------------------------------------
*/

$vendors = [];


try {

    $sql = "
        SELECT

            v.vendor_id,

            v.user_id,

            v.business_name,

            v.business_logo,

            v.business_description,

            v.business_address,

            v.category,

            v.delivery_method,

            v.postage_fee,

            v.allow_vendor_delivery,

            v.cod_enabled,

            v.vendor_delivery_fee,

            v.commission_rate,

            v.approval_status,

            v.created_at,

            u.name
                AS owner_name,

            u.email
                AS owner_email,

            u.phone
                AS owner_phone,

            u.status
                AS user_status

        FROM vendors v

        INNER JOIN users u

            ON v.user_id =
               u.user_id

        WHERE
            1 = 1
    ";


    $params = [];


    if (
        $search !== ''
    ) {

        $sql .= "
            AND
            (
                v.business_name
                    LIKE ?

                OR
                u.name
                    LIKE ?

                OR
                u.email
                    LIKE ?

                OR
                u.phone
                    LIKE ?
            )
        ";


        $searchValue =
            '%' .
            $search .
            '%';


        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;

        $params[] =
            $searchValue;
    }


    if (
        $statusFilter !== ''
    ) {

        $sql .= "
            AND
                v.approval_status = ?
        ";


        $params[] =
            $statusFilter;
    }


    $sql .= "
        ORDER BY

            v.created_at DESC,

            v.vendor_id DESC
    ";


    $stmt =
        $db->prepare(
            $sql
        );


    $stmt->execute(
        $params
    );


    $vendors =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (
    Throwable $e
) {

    $vendors = [];


    error_log(
        'Load vendors error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| LOAD PENDING APPLICATIONS
|--------------------------------------------------------------------------
*/

$applications = [];


try {

    $stmt =
        $db->query("
            SELECT

                va.application_id,

                va.user_id,

                va.business_name,

                va.reason,

                va.status,

                va.created_at,

                u.name
                    AS applicant_name,

                u.email
                    AS applicant_email,

                u.phone
                    AS applicant_phone,

                v.vendor_id,

                v.category,

                v.business_address,

                v.delivery_method,

                v.postage_fee,

                v.allow_vendor_delivery,

                v.cod_enabled,

                v.vendor_delivery_fee,

                v.commission_rate

            FROM vendor_applications va

            INNER JOIN users u

                ON va.user_id =
                   u.user_id

            LEFT JOIN vendors v

                ON va.user_id =
                   v.user_id

            WHERE
                va.status =
                    'Pending'

            ORDER BY

                va.created_at DESC,

                va.application_id DESC
        ");


    $applications =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


} catch (
    Throwable $e
) {

    $applications = [];


    error_log(
        'Load vendor applications error: ' .
        $e->getMessage()
    );
}


/*
|--------------------------------------------------------------------------
| STATISTICS
|--------------------------------------------------------------------------
*/

$totalVendors = 0;

$approvedVendors = 0;

$pendingVendors = 0;

$suspendedVendors = 0;

$rejectedVendors = 0;


try {

    $stmt =
        $db->query("
            SELECT

                COUNT(*)
                    AS total_vendors,

                SUM(
                    CASE
                        WHEN approval_status =
                            'Approved'
                        THEN 1
                        ELSE 0
                    END
                )
                    AS approved_vendors,

                SUM(
                    CASE
                        WHEN approval_status =
                            'Pending'
                        THEN 1
                        ELSE 0
                    END
                )
                    AS pending_vendors,

                SUM(
                    CASE
                        WHEN approval_status =
                            'Suspended'
                        THEN 1
                        ELSE 0
                    END
                )
                    AS suspended_vendors,

                SUM(
                    CASE
                        WHEN approval_status =
                            'Rejected'
                        THEN 1
                        ELSE 0
                    END
                )
                    AS rejected_vendors

            FROM vendors
        ");


    $statistics =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (
        $statistics
    ) {

        $totalVendors =
            (int)
            $statistics[
                'total_vendors'
            ];


        $approvedVendors =
            (int)
            $statistics[
                'approved_vendors'
            ];


        $pendingVendors =
            (int)
            $statistics[
                'pending_vendors'
            ];


        $suspendedVendors =
            (int)
            $statistics[
                'suspended_vendors'
            ];


        $rejectedVendors =
            (int)
            $statistics[
                'rejected_vendors'
            ];
    }


} catch (
    Throwable $e
) {

    error_log(
        'Vendor statistics error: ' .
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
    Vendors | HochipoHub Admin
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
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
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

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    min-height: 100%;
}

body {
    overflow-x: hidden;
    color: #14213d;

    background:
        radial-gradient(
            circle at 90% 2%,
            rgba(37,99,235,.10),
            transparent 24%
        ),
        linear-gradient(
            135deg,
            #f4f8fd,
            #eaf3ff
        );

    font-family:
        Poppins,
        Arial,
        sans-serif;
}

button,
input,
select {
    font-family: inherit;
}


/* =========================================================
   MAIN
========================================================= */

.vendors-main {
    min-height: 100vh;
    margin-left: 260px;
    width: calc(100% - 260px);
}

.vendors-content {
    width: 100%;
    max-width: 1500px;
    margin: 0 auto;
    padding: 38px 35px 70px;
}


/* =========================================================
   HERO
========================================================= */

.vendors-hero {
    position: relative;
    min-height: 165px;
    overflow: hidden;
    margin-bottom: 25px;
    padding: 34px 38px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 25px;

    color: #ffffff;

    background:
        linear-gradient(
            110deg,
            #08265a 0%,
            #123c8c 47%,
            #2480ed 100%
        );

    border-radius: 25px;

    box-shadow:
        0 20px 45px
        rgba(18,70,150,.15);
}

.vendors-hero::before {
    content: "";
    position: absolute;
    width: 270px;
    height: 270px;
    right: -70px;
    top: -145px;
    border-radius: 50%;
    background:
        rgba(255,255,255,.08);
}

.vendors-hero-copy {
    position: relative;
    z-index: 2;
}

.vendors-hero-label {
    display: block;
    margin-bottom: 7px;
    color: #b8dbff;
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 1.3px;
}

.vendors-hero h1 {
    margin: 0 0 7px;
    color: #ffffff;
    font-size: 37px;
    line-height: 1.1;
    font-weight: 800;
    letter-spacing: -1.4px;
}

.vendors-hero p {
    margin: 0;
    max-width: 720px;
    color: rgba(255,255,255,.79);
    font-size: 12px;
    line-height: 1.7;
}

.vendors-hero-icon {
    position: relative;
    z-index: 2;
    width: 83px;
    height: 83px;
    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        rgba(255,255,255,.14);

    border:
        1px solid
        rgba(255,255,255,.25);

    border-radius: 22px;

    font-size: 31px;
}


/* =========================================================
   MESSAGE
========================================================= */

.vendors-message {
    margin-bottom: 21px;
    padding: 14px 17px;

    display: flex;
    align-items: center;

    gap: 9px;

    border-radius: 12px;

    font-size: 10px;
    font-weight: 600;
}

.vendors-message.success {
    color: #166534;
    background: #ecfdf5;
    border: 1px solid #bbf7d0;
}

.vendors-message.error {
    color: #991b1b;
    background: #fff1f2;
    border: 1px solid #fecdd3;
}


/* =========================================================
   STATS
========================================================= */

.vendors-stats {
    margin-bottom: 25px;

    display: grid;

    grid-template-columns:
        repeat(
            4,
            minmax(
                0,
                1fr
            )
        );

    gap: 15px;
}

.vendor-stat-card {
    position: relative;
    min-height: 128px;
    overflow: hidden;

    padding: 22px;

    background: #ffffff;

    border:
        1px solid
        #dce7f3;

    border-top:
        4px solid
        #2563eb;

    border-radius: 18px;

    box-shadow:
        0 10px 26px
        rgba(20,60,120,.05);
}

.vendor-stat-card.approved {
    border-top-color: #16a34a;
}

.vendor-stat-card.pending {
    border-top-color: #f59e0b;
}

.vendor-stat-card.suspended {
    border-top-color: #ef4444;
}

.vendor-stat-label {
    display: block;
    margin-bottom: 13px;
    color: #61728e;
    font-size: 9px;
    font-weight: 800;
    letter-spacing: .7px;
}

.vendor-stat-value {
    display: block;
    color: #0b326d;
    font-size: 31px;
    font-weight: 800;
}


/* =========================================================
   PANEL
========================================================= */

.vendors-panel {
    overflow: hidden;
    margin-bottom: 27px;

    background: #ffffff;

    border:
        1px solid
        #dce7f3;

    border-radius: 22px;

    box-shadow:
        0 12px 32px
        rgba(24,64,120,.05);
}

.vendors-panel-header {
    min-height: 100px;
    padding: 24px 28px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 20px;

    border-bottom:
        1px solid
        #e7edf5;
}

.vendors-panel-title {
    display: flex;
    align-items: center;
    gap: 14px;
}

.vendors-panel-icon {
    width: 50px;
    height: 50px;
    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background:
        linear-gradient(
            135deg,
            #1476e8,
            #1d95f3
        );

    border-radius: 15px;

    font-size: 19px;
}

.vendors-panel-header h2 {
    margin: 0 0 4px;
    color: #092e65;
    font-size: 19px;
    font-weight: 800;
}

.vendors-panel-header p {
    margin: 0;
    color: #8999b4;
    font-size: 10px;
}

.vendors-count-badge {
    min-height: 34px;
    padding: 0 14px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    color: #2563eb;

    background: #eff6ff;

    border:
        1px solid
        #d6e7ff;

    border-radius: 999px;

    font-size: 9px;
    font-weight: 800;
}


/* =========================================================
   APPLICATIONS
========================================================= */

.applications-grid {
    padding: 20px;

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 14px;
}

.application-card {
    padding: 18px;

    background:
        linear-gradient(
            135deg,
            #fbfdff,
            #f6f9ff
        );

    border:
        1px solid
        #dde8f4;

    border-radius: 16px;
}

.application-head {
    margin-bottom: 13px;

    display: flex;
    align-items: center;
    justify-content: space-between;

    gap: 12px;
}

.application-person {
    display: flex;
    align-items: center;
    gap: 10px;
}

.application-avatar {
    width: 43px;
    height: 43px;
    flex-shrink: 0;

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

    border-radius: 11px;

    font-size: 13px;
    font-weight: 800;
}

.application-person strong {
    display: block;
    color: #17335c;
    font-size: 10px;
    font-weight: 800;
}

.application-person small {
    display: block;
    margin-top: 3px;
    color: #8b9ab0;
    font-size: 8px;
}

.pending-pill {
    min-height: 27px;
    padding: 0 9px;

    display: inline-flex;
    align-items: center;

    color: #a16207;
    background: #fffbea;

    border:
        1px solid
        #fde68a;

    border-radius: 999px;

    font-size: 7px;
    font-weight: 800;
}

.application-business {
    margin-bottom: 12px;
    padding: 12px;

    background: #ffffff;

    border:
        1px solid
        #e5ebf3;

    border-radius: 11px;
}

.application-business span,
.application-meta span {
    display: block;
    margin-bottom: 3px;
    color: #94a3b8;
    font-size: 7px;
    font-weight: 800;
}

.application-business strong {
    color: #263a55;
    font-size: 10px;
}

.application-meta {
    margin-bottom: 13px;

    display: grid;

    grid-template-columns:
        repeat(
            2,
            minmax(
                0,
                1fr
            )
        );

    gap: 8px;
}

.application-meta div {
    padding: 10px;

    background: #ffffff;

    border:
        1px solid
        #e6ebf2;

    border-radius: 9px;
}

.application-meta strong {
    color: #42536b;
    font-size: 8px;
}

.application-reason {
    margin-bottom: 13px;
    padding: 11px;

    color: #607089;

    background: #ffffff;

    border:
        1px solid
        #e6ebf2;

    border-radius: 10px;

    font-size: 8px;
    line-height: 1.6;
}

.application-actions {
    display: flex;
    gap: 8px;
}

.application-actions form {
    flex: 1;
    margin: 0;
}

.application-btn {
    width: 100%;
    min-height: 37px;

    border-radius: 9px;

    font-size: 8px;
    font-weight: 800;

    cursor: pointer;
}

.application-btn.approve {
    color: #ffffff;
    background: #16a34a;
    border: 1px solid #16a34a;
}

.application-btn.reject {
    color: #b91c1c;
    background: #fff1f2;
    border: 1px solid #fecdd3;
}


/* =========================================================
   FILTER
========================================================= */

.vendors-filter-wrapper {
    padding: 20px 25px;

    background: #fbfdff;

    border-bottom:
        1px solid
        #edf1f6;
}

.vendors-filter {
    display: grid;

    grid-template-columns:
        minmax(
            260px,
            1.7fr
        )
        minmax(
            160px,
            .55fr
        )
        auto
        auto;

    gap: 9px;
}

.vendors-filter input,
.vendors-filter select {
    width: 100%;
    height: 42px;
    padding: 0 12px;

    outline: none;

    color: #26354e;

    background: #ffffff;

    border:
        1px solid
        #d8e3ef;

    border-radius: 10px;

    font-size: 9px;
}

.vendor-btn {
    min-height: 42px;
    padding: 0 16px;

    display: inline-flex;
    align-items: center;
    justify-content: center;

    gap: 6px;

    border-radius: 10px;

    font-size: 9px;
    font-weight: 800;

    text-decoration: none;

    cursor: pointer;
}

.vendor-btn.primary {
    color: #ffffff;
    background: #2563eb;
    border: 0;
}

.vendor-btn.secondary {
    color: #66758b;
    background: #ffffff;
    border: 1px solid #d7e2ee;
}


/* =========================================================
   TABLE
========================================================= */

.vendors-table-wrapper {
    width: 100%;
    overflow-x: auto;
}

.vendors-table {
    width: 100%;
    min-width: 1250px;

    border-collapse: collapse;
}

.vendors-table thead {
    background: #f6f9fd;
}

.vendors-table th {
    height: 45px;
    padding: 0 16px;

    color: #65758f;

    border-bottom:
        1px solid
        #dfe7f0;

    font-size: 7px;
    font-weight: 800;

    letter-spacing: .5px;

    text-align: left;

    white-space: nowrap;
}

.vendors-table td {
    padding: 15px 16px;

    color: #435169;

    border-bottom:
        1px solid
        #edf1f6;

    font-size: 8px;

    vertical-align: middle;
}

.vendors-table tbody tr:hover {
    background: #f9fbff;
}

.vendor-person {
    min-width: 190px;

    display: flex;
    align-items: center;

    gap: 10px;
}

.vendor-avatar {
    width: 39px;
    height: 39px;
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

    border-radius: 10px;

    font-size: 11px;
    font-weight: 800;
}

.vendor-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.vendor-person strong,
.vendor-business strong {
    display: block;
    margin-bottom: 3px;
    color: #112b55;
    font-size: 9px;
    font-weight: 800;
}

.vendor-person small,
.vendor-business small {
    display: block;
    color: #8897ac;
    font-size: 7px;
}

.vendor-tag {
    min-height: 27px;
    padding: 0 9px;

    display: inline-flex;
    align-items: center;

    color: #52647f;
    background: #f1f5f9;

    border:
        1px solid
        #e2e8f0;

    border-radius: 999px;

    font-size: 7px;
    font-weight: 700;
}

.vendor-status-form {
    margin: 0;

    display: flex;
    align-items: center;

    gap: 6px;
}

.vendor-status-select {
    min-width: 110px;
    height: 34px;
    padding: 0 9px;

    outline: none;

    border-radius: 9px;

    font-size: 7px;
    font-weight: 800;

    cursor: pointer;
}

.vendor-status-select.approved {
    color: #15803d;
    background: #ecfdf3;
    border: 1px solid #bbf7d0;
}

.vendor-status-select.pending {
    color: #a16207;
    background: #fffbea;
    border: 1px solid #fde68a;
}

.vendor-status-select.rejected,
.vendor-status-select.suspended {
    color: #b91c1c;
    background: #fff1f2;
    border: 1px solid #fecdd3;
}

.status-save-btn {
    width: 34px;
    height: 34px;
    flex-shrink: 0;

    display: flex;
    align-items: center;
    justify-content: center;

    color: #ffffff;

    background: #2563eb;

    border: 0;

    border-radius: 9px;

    cursor: pointer;
}

.vendor-empty {
    padding: 55px 20px !important;
    color: #94a3b8 !important;
    text-align: center;
}

.vendor-empty strong {
    display: block;
    margin-bottom: 5px;
    color: #49617f;
    font-size: 10px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (
    max-width: 1200px
) {

    .vendors-stats {
        grid-template-columns:
            repeat(
                2,
                1fr
            );
    }

    .applications-grid {
        grid-template-columns:
            1fr;
    }

    .vendors-filter {
        grid-template-columns:
            1fr
            1fr;
    }

    .vendors-filter input {
        grid-column:
            1 / -1;
    }
}


@media (
    max-width: 900px
) {

    .vendors-main {
        margin-left: 0;
        width: 100%;
    }

    .vendors-content {
        padding:
            25px 20px
            50px;
    }
}


@media (
    max-width: 650px
) {

    .vendors-content {
        padding:
            18px 13px
            40px;
    }

    .vendors-hero {
        min-height: auto;
        padding: 25px 21px;
        border-radius: 20px;
    }

    .vendors-hero h1 {
        font-size: 27px;
    }

    .vendors-hero-icon {
        width: 55px;
        height: 55px;
        border-radius: 15px;
        font-size: 22px;
    }

    .vendors-stats,
    .vendors-filter {
        grid-template-columns:
            1fr;
    }

    .vendors-filter input {
        grid-column: auto;
    }

    .vendor-btn {
        width: 100%;
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


<main class="vendors-main">


<div class="vendors-content">


    <!-- =====================================================
         HERO
    ====================================================== -->

    <section class="vendors-hero">


        <div class="vendors-hero-copy">


            <span class="vendors-hero-label">

                MARKETPLACE MANAGEMENT

            </span>


            <h1>

                Vendors

            </h1>


            <p>

                Review seller applications,
                manage vendor approval,
                delivery settings,
                commission rates and
                seller account status.

            </p>


        </div>


        <div class="vendors-hero-icon">

            <i class="fa-solid fa-store"></i>

        </div>


    </section>



    <!-- =====================================================
         MESSAGE
    ====================================================== -->

    <?php if (
        $message !== ''
    ): ?>


        <div
            class="
                vendors-message
                <?= vendorAdminEscape(
                    $messageType
                ) ?>
            "
        >


            <?php if (
                $messageType ===
                    'success'
            ): ?>


                <i class="fa-solid fa-circle-check"></i>


            <?php else: ?>


                <i class="fa-solid fa-triangle-exclamation"></i>


            <?php endif; ?>


            <?= vendorAdminEscape(
                $message
            ) ?>


        </div>


    <?php endif; ?>



    <!-- =====================================================
         STATS
    ====================================================== -->

    <section class="vendors-stats">


        <article class="vendor-stat-card">

            <span class="vendor-stat-label">
                TOTAL VENDORS
            </span>

            <strong class="vendor-stat-value">

                <?= number_format(
                    $totalVendors
                ) ?>

            </strong>

        </article>


        <article class="
            vendor-stat-card
            approved
        ">

            <span class="vendor-stat-label">
                APPROVED
            </span>

            <strong class="vendor-stat-value">

                <?= number_format(
                    $approvedVendors
                ) ?>

            </strong>

        </article>


        <article class="
            vendor-stat-card
            pending
        ">

            <span class="vendor-stat-label">
                PENDING
            </span>

            <strong class="vendor-stat-value">

                <?= number_format(
                    $pendingVendors
                ) ?>

            </strong>

        </article>


        <article class="
            vendor-stat-card
            suspended
        ">

            <span class="vendor-stat-label">
                SUSPENDED
            </span>

            <strong class="vendor-stat-value">

                <?= number_format(
                    $suspendedVendors
                ) ?>

            </strong>

        </article>


    </section>



    <!-- =====================================================
         APPLICATIONS
    ====================================================== -->

    <section class="vendors-panel">


        <header class="vendors-panel-header">


            <div class="vendors-panel-title">


                <div class="vendors-panel-icon">

                    <i class="fa-solid fa-user-check"></i>

                </div>


                <div>

                    <h2>
                        Vendor Applications
                    </h2>

                    <p>

                        Approving a seller will
                        automatically send an
                        approval email.

                    </p>

                </div>


            </div>


            <span class="vendors-count-badge">

                <?= number_format(
                    count(
                        $applications
                    )
                ) ?>

                pending

            </span>


        </header>


        <?php if (
            empty(
                $applications
            )
        ): ?>


            <div class="vendor-empty">

                <strong>
                    No pending applications
                </strong>

                All vendor applications
                have been reviewed.

            </div>


        <?php else: ?>


            <div class="applications-grid">


                <?php foreach (
                    $applications as
                    $application
                ): ?>


                    <?php

                    $applicantInitial =
                        vendorAdminInitial(
                            $application[
                                'applicant_name'
                            ]
                            ?? 'A'
                        );

                    ?>


                    <article class="application-card">


                        <div class="application-head">


                            <div class="application-person">


                                <div class="application-avatar">

                                    <?= vendorAdminEscape(
                                        $applicantInitial
                                    ) ?>

                                </div>


                                <div>


                                    <strong>

                                        <?= vendorAdminEscape(
                                            $application[
                                                'applicant_name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= vendorAdminEscape(
                                            $application[
                                                'applicant_email'
                                            ]
                                        ) ?>

                                    </small>


                                    <?php if (
                                        !empty(
                                            $application[
                                                'applicant_phone'
                                            ]
                                        )
                                    ): ?>


                                        <small>

                                            <?= vendorAdminEscape(
                                                $application[
                                                    'applicant_phone'
                                                ]
                                            ) ?>

                                        </small>


                                    <?php endif; ?>


                                </div>


                            </div>


                            <span class="pending-pill">

                                Pending

                            </span>


                        </div>



                        <div class="application-business">


                            <span>
                                BUSINESS NAME
                            </span>


                            <strong>

                                <?= vendorAdminEscape(
                                    $application[
                                        'business_name'
                                    ]
                                ) ?>

                            </strong>


                        </div>



                        <div class="application-meta">


                            <div>

                                <span>
                                    CATEGORY
                                </span>

                                <strong>

                                    <?= vendorAdminEscape(
                                        $application[
                                            'category'
                                        ]
                                        ?? 'Not set'
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    DELIVERY
                                </span>

                                <strong>

                                    <?= vendorAdminEscape(
                                        $application[
                                            'delivery_method'
                                        ]
                                        ?? 'Not set'
                                    ) ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    COMMISSION
                                </span>

                                <strong>

                                    <?= vendorAdminEscape(
                                        number_format(
                                            (float) (
                                                $application[
                                                    'commission_rate'
                                                ]
                                                ?? 5
                                            ),
                                            2
                                        )
                                    ) ?>%

                                </strong>

                            </div>


                            <div>

                                <span>
                                    VENDOR DELIVERY
                                </span>

                                <strong>

                                    <?= !empty(
                                        $application[
                                            'allow_vendor_delivery'
                                        ]
                                    )
                                        ? 'Enabled'
                                        : 'Disabled' ?>

                                </strong>

                            </div>


                        </div>



                        <?php if (
                            !empty(
                                $application[
                                    'reason'
                                ]
                            )
                        ): ?>


                            <div class="application-reason">

                                <?= nl2br(
                                    vendorAdminEscape(
                                        $application[
                                            'reason'
                                        ]
                                    )
                                ) ?>

                            </div>


                        <?php endif; ?>



                        <div class="application-actions">


                            <form
                                method="POST"
                                action="vendors.php"
                            >


                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= vendorAdminEscape(
                                        $csrfToken
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int)
                                        $application[
                                            'application_id'
                                        ] ?>"
                                >


                                <input
                                    type="hidden"
                                    name="application_action"
                                    value="approve"
                                >


                                <button
                                    type="submit"
                                    class="
                                        application-btn
                                        approve
                                    "
                                    onclick="
                                        return confirm(
                                            'Approve this seller application? An approval email will be sent to the seller.'
                                        );
                                    "
                                >

                                    <i class="fa-solid fa-check"></i>

                                    Approve & Email Seller

                                </button>


                            </form>



                            <form
                                method="POST"
                                action="vendors.php"
                            >


                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= vendorAdminEscape(
                                        $csrfToken
                                    ) ?>"
                                >


                                <input
                                    type="hidden"
                                    name="application_id"
                                    value="<?= (int)
                                        $application[
                                            'application_id'
                                        ] ?>"
                                >


                                <input
                                    type="hidden"
                                    name="application_action"
                                    value="reject"
                                >


                                <button
                                    type="submit"
                                    class="
                                        application-btn
                                        reject
                                    "
                                    onclick="
                                        return confirm(
                                            'Reject this seller application?'
                                        );
                                    "
                                >

                                    <i class="fa-solid fa-xmark"></i>

                                    Reject

                                </button>


                            </form>


                        </div>


                    </article>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>



    <!-- =====================================================
         ALL VENDORS
    ====================================================== -->

    <section class="vendors-panel">


        <header class="vendors-panel-header">


            <div class="vendors-panel-title">


                <div class="vendors-panel-icon">

                    <i class="fa-solid fa-shop"></i>

                </div>


                <div>

                    <h2>
                        All Vendors
                    </h2>

                    <p>

                        Search sellers and
                        manage account status.

                    </p>

                </div>


            </div>


            <span class="vendors-count-badge">

                <?= number_format(
                    count(
                        $vendors
                    )
                ) ?>

                results

            </span>


        </header>



        <div class="vendors-filter-wrapper">


            <form
                method="GET"
                action="vendors.php"
                class="vendors-filter"
            >


                <input
                    type="text"
                    name="search"
                    value="<?= vendorAdminEscape(
                        $search
                    ) ?>"
                    placeholder="Search business, seller, email or phone..."
                >


                <select name="status">


                    <option value="">
                        All Statuses
                    </option>


                    <?php foreach (
                        [
                            'Pending',
                            'Approved',
                            'Rejected',
                            'Suspended'
                        ] as $option
                    ): ?>


                        <option
                            value="<?= vendorAdminEscape(
                                $option
                            ) ?>"
                            <?= $statusFilter ===
                                $option
                                    ? 'selected'
                                    : '' ?>
                        >

                            <?= vendorAdminEscape(
                                $option
                            ) ?>

                        </option>


                    <?php endforeach; ?>


                </select>


                <button
                    type="submit"
                    class="
                        vendor-btn
                        primary
                    "
                >

                    <i class="fa-solid fa-magnifying-glass"></i>

                    Search

                </button>


                <a
                    href="vendors.php"
                    class="
                        vendor-btn
                        secondary
                    "
                >

                    Reset

                </a>


            </form>


        </div>



        <div class="vendors-table-wrapper">


            <table class="vendors-table">


                <thead>

                <tr>

                    <th>
                        ID
                    </th>

                    <th>
                        SELLER
                    </th>

                    <th>
                        STORE
                    </th>

                    <th>
                        DELIVERY
                    </th>

                    <th>
                        POSTAGE
                    </th>

                    <th>
                        VENDOR DELIVERY
                    </th>

                    <th>
                        COMMISSION
                    </th>

                    <th>
                        STATUS
                    </th>

                </tr>

                </thead>


                <tbody>


                <?php if (
                    empty(
                        $vendors
                    )
                ): ?>


                    <tr>

                        <td
                            colspan="8"
                            class="vendor-empty"
                        >

                            <strong>
                                No vendors found
                            </strong>

                            Try another search
                            or filter.

                        </td>

                    </tr>


                <?php else: ?>


                    <?php foreach (
                        $vendors as
                        $vendor
                    ): ?>


                        <?php

                        $statusClass =
                            vendorAdminStatusClass(
                                $vendor[
                                    'approval_status'
                                ]
                            );


                        $initial =
                            vendorAdminInitial(
                                $vendor[
                                    'owner_name'
                                ]
                                ?? 'V'
                            );


                        $logoUrl = '';


                        if (
                            !empty(
                                $vendor[
                                    'business_logo'
                                ]
                            )
                        ) {

                            $logoUrl =
                                BASE_URL .
                                'uploads/vendors/' .
                                rawurlencode(
                                    basename(
                                        $vendor[
                                            'business_logo'
                                        ]
                                    )
                                );
                        }

                        ?>


                        <tr>


                            <td>

                                #<?= (int)
                                    $vendor[
                                        'vendor_id'
                                    ] ?>

                            </td>



                            <td>


                                <div class="vendor-person">


                                    <div class="vendor-avatar">


                                        <?php if (
                                            $logoUrl !== ''
                                        ): ?>


                                            <img
                                                src="<?= vendorAdminEscape(
                                                    $logoUrl
                                                ) ?>"
                                                alt="Store"
                                            >


                                        <?php else: ?>


                                            <?= vendorAdminEscape(
                                                $initial
                                            ) ?>


                                        <?php endif; ?>


                                    </div>


                                    <div>


                                        <strong>

                                            <?= vendorAdminEscape(
                                                $vendor[
                                                    'owner_name'
                                                ]
                                            ) ?>

                                        </strong>


                                        <small>

                                            <?= vendorAdminEscape(
                                                $vendor[
                                                    'owner_email'
                                                ]
                                            ) ?>

                                        </small>


                                        <?php if (
                                            !empty(
                                                $vendor[
                                                    'owner_phone'
                                                ]
                                            )
                                        ): ?>


                                            <small>

                                                <?= vendorAdminEscape(
                                                    $vendor[
                                                        'owner_phone'
                                                    ]
                                                ) ?>

                                            </small>


                                        <?php endif; ?>


                                    </div>


                                </div>


                            </td>



                            <td>


                                <div class="vendor-business">


                                    <strong>

                                        <?= vendorAdminEscape(
                                            $vendor[
                                                'business_name'
                                            ]
                                        ) ?>

                                    </strong>


                                    <small>

                                        <?= vendorAdminEscape(
                                            $vendor[
                                                'category'
                                            ]
                                            ?? 'No category'
                                        ) ?>

                                    </small>


                                </div>


                            </td>



                            <td>

                                <span class="vendor-tag">

                                    <?= vendorAdminEscape(
                                        $vendor[
                                            'delivery_method'
                                        ]
                                        ?? 'Not set'
                                    ) ?>

                                </span>

                            </td>



                            <td>

                                RM
                                <?= vendorAdminEscape(
                                    vendorAdminMoney(
                                        $vendor[
                                            'postage_fee'
                                        ]
                                        ?? 0
                                    )
                                ) ?>

                            </td>



                            <td>


                                <?php if (
                                    !empty(
                                        $vendor[
                                            'allow_vendor_delivery'
                                        ]
                                    )
                                ): ?>


                                    <span class="vendor-tag">

                                        RM
                                        <?= vendorAdminEscape(
                                            vendorAdminMoney(
                                                $vendor[
                                                    'vendor_delivery_fee'
                                                ]
                                                ?? 0
                                            )
                                        ) ?>

                                    </span>


                                    <?php if (
                                        !empty(
                                            $vendor[
                                                'cod_enabled'
                                            ]
                                        )
                                    ): ?>


                                        <span class="vendor-tag">

                                            COD

                                        </span>


                                    <?php endif; ?>


                                <?php else: ?>


                                    <span class="vendor-tag">

                                        Disabled

                                    </span>


                                <?php endif; ?>


                            </td>



                            <td>

                                <strong>

                                    <?= vendorAdminEscape(
                                        number_format(
                                            (float) (
                                                $vendor[
                                                    'commission_rate'
                                                ]
                                                ?? 5
                                            ),
                                            2
                                        )
                                    ) ?>%

                                </strong>

                            </td>



                            <td>


                                <form
                                    method="POST"
                                    action="vendors.php"
                                    class="vendor-status-form"
                                >


                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?= vendorAdminEscape(
                                            $csrfToken
                                        ) ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="vendor_id"
                                        value="<?= (int)
                                            $vendor[
                                                'vendor_id'
                                            ] ?>"
                                    >


                                    <input
                                        type="hidden"
                                        name="update_vendor_status"
                                        value="1"
                                    >


                                    <select
                                        name="approval_status"
                                        class="
                                            vendor-status-select
                                            <?= vendorAdminEscape(
                                                $statusClass
                                            ) ?>
                                        "
                                    >


                                        <?php foreach (
                                            [
                                                'Pending',
                                                'Approved',
                                                'Rejected',
                                                'Suspended'
                                            ] as $status
                                        ): ?>


                                            <option
                                                value="<?= vendorAdminEscape(
                                                    $status
                                                ) ?>"
                                                <?= $vendor[
                                                    'approval_status'
                                                ] ===
                                                $status
                                                    ? 'selected'
                                                    : '' ?>
                                            >

                                                <?= vendorAdminEscape(
                                                    $status
                                                ) ?>

                                            </option>


                                        <?php endforeach; ?>


                                    </select>


                                    <button
                                        type="submit"
                                        class="status-save-btn"
                                        title="Save status"
                                        onclick="
                                            return confirm(
                                                'Update this vendor status?'
                                            );
                                        "
                                    >

                                        <i class="fa-solid fa-floppy-disk"></i>

                                    </button>


                                </form>


                            </td>


                        </tr>


                    <?php endforeach; ?>


                <?php endif; ?>


                </tbody>


            </table>


        </div>


    </section>


</div>


</main>


</div>


<script>

/*
|--------------------------------------------------------------------------
| STATUS SELECT COLOR
|--------------------------------------------------------------------------
*/

document
.querySelectorAll(
    '.vendor-status-select'
)
.forEach(
    function (
        select
    ) {

        function updateClass()
        {
            select.classList.remove(
                'pending',
                'approved',
                'rejected',
                'suspended'
            );


            select.classList.add(
                select
                    .value
                    .toLowerCase()
            );
        }


        select.addEventListener(
            'change',
            updateClass
        );


        updateClass();
    }
);

</script>


</body>

</html>