<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - SEND MESSAGE AJAX
|--------------------------------------------------------------------------
| File: ajax/send_message.php
|--------------------------------------------------------------------------
*/

ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


/*
|--------------------------------------------------------------------------
| CORE FILES
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/db.php';


/*
|--------------------------------------------------------------------------
| JSON HEADERS
|--------------------------------------------------------------------------
*/

header(
    'Content-Type: application/json; charset=UTF-8'
);

header(
    'Cache-Control: no-store, no-cache, must-revalidate, max-age=0'
);

header(
    'Pragma: no-cache'
);


/*
|--------------------------------------------------------------------------
| JSON RESPONSE
|--------------------------------------------------------------------------
*/

function sendMessageJson(
    bool $success,
    array $data = [],
    string $message = '',
    int $statusCode = 200
): void {

    /*
    |--------------------------------------------------------------------------
    | CLEAR ACCIDENTAL OUTPUT
    |--------------------------------------------------------------------------
    */

    while (ob_get_level() > 0) {
        ob_end_clean();
    }


    http_response_code(
        $statusCode
    );


    header(
        'Content-Type: application/json; charset=UTF-8'
    );


    echo json_encode(
        array_merge(
            [
                'success' =>
                    $success,

                'message' =>
                    $message
            ],
            $data
        ),
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


    exit;
}


/*
|--------------------------------------------------------------------------
| REQUEST METHOD
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] !==
    'POST'
) {

    sendMessageJson(
        false,
        [],
        'Invalid request method.',
        405
    );
}


/*
|--------------------------------------------------------------------------
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $db =
        getDB();


    if (!($db instanceof PDO)) {

        sendMessageJson(
            false,
            [],
            'Database connection is not available.',
            500
        );
    }

} catch (Throwable $e) {

    error_log(
        'Send message database error: ' .
        $e->getMessage()
    );


    sendMessageJson(
        false,
        [],
        'Database connection failed.',
        500
    );
}


/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

$userId =
    isset($_SESSION['user_id'])
        ? (int) $_SESSION['user_id']
        : 0;


if ($userId <= 0) {

    sendMessageJson(
        false,
        [],
        'Please login first.',
        401
    );
}


/*
|--------------------------------------------------------------------------
| ROLE
|--------------------------------------------------------------------------
*/

$role =
    strtolower(
        trim(
            (string) (
                $_SESSION['role']
                ?? ''
            )
        )
    );


if (
    $role !== 'customer'
    &&
    $role !== 'vendor'
) {

    sendMessageJson(
        false,
        [],
        'Access denied.',
        403
    );
}


/*
|--------------------------------------------------------------------------
| INPUT
|--------------------------------------------------------------------------
*/

$conversationId =
    isset($_POST['conversation_id'])
        ? (int)
          $_POST['conversation_id']
        : 0;


$message =
    trim(
        (string) (
            $_POST['message']
            ?? ''
        )
    );


$csrfToken =
    (string) (
        $_POST['csrf_token']
        ?? ''
    );


/*
|--------------------------------------------------------------------------
| VALIDATE CONVERSATION
|--------------------------------------------------------------------------
*/

if ($conversationId <= 0) {

    sendMessageJson(
        false,
        [],
        'Invalid conversation.',
        422
    );
}


/*
|--------------------------------------------------------------------------
| VALIDATE MESSAGE
|--------------------------------------------------------------------------
*/

if ($message === '') {

    sendMessageJson(
        false,
        [],
        'Message cannot be empty.',
        422
    );
}


/*
|--------------------------------------------------------------------------
| MESSAGE LENGTH
|--------------------------------------------------------------------------
*/

$messageLength =
    function_exists('mb_strlen')
        ? mb_strlen(
            $message
        )
        : strlen(
            $message
        );


if ($messageLength > 2000) {

    sendMessageJson(
        false,
        [],
        'Message cannot exceed 2000 characters.',
        422
    );
}


/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

$sessionCsrf =
    (string) (
        $_SESSION['csrf_token']
        ?? ''
    );


if (
    $sessionCsrf === ''
    ||
    $csrfToken === ''
    ||
    !hash_equals(
        $sessionCsrf,
        $csrfToken
    )
) {

    sendMessageJson(
        false,
        [],
        'Security token is invalid. Refresh the page and try again.',
        403
    );
}


/*
|--------------------------------------------------------------------------
| VERIFY CONVERSATION ACCESS
|--------------------------------------------------------------------------
*/

try {

    $stmt =
        $db->prepare("
            SELECT

                c.conversation_id,
                c.customer_id,
                c.vendor_id,
                c.order_id,

                v.user_id
                    AS vendor_user_id,

                v.business_name

            FROM conversations c

            INNER JOIN vendors v
                ON c.vendor_id =
                   v.vendor_id

            WHERE c.conversation_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $conversationId
    ]);


    $conversation =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    if (!$conversation) {

        sendMessageJson(
            false,
            [],
            'Conversation not found.',
            404
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PARTICIPANTS
    |--------------------------------------------------------------------------
    */

    $customerId =
        (int)
        $conversation['customer_id'];


    $vendorUserId =
        (int)
        $conversation['vendor_user_id'];


    /*
    |--------------------------------------------------------------------------
    | ACCESS CHECK
    |--------------------------------------------------------------------------
    */

    if (
        $userId !== $customerId
        &&
        $userId !== $vendorUserId
    ) {

        sendMessageJson(
            false,
            [],
            'You do not have access to this conversation.',
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ROLE CONSISTENCY
    |--------------------------------------------------------------------------
    */

    if (
        $role === 'customer'
        &&
        $userId !== $customerId
    ) {

        sendMessageJson(
            false,
            [],
            'Customer access denied.',
            403
        );
    }


    if (
        $role === 'vendor'
        &&
        $userId !== $vendorUserId
    ) {

        sendMessageJson(
            false,
            [],
            'Seller access denied.',
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TRANSACTION
    |--------------------------------------------------------------------------
    */

    $db->beginTransaction();


    /*
    |--------------------------------------------------------------------------
    | INSERT MESSAGE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            INSERT INTO messages
            (
                conversation_id,
                sender_id,
                message,
                is_read,
                created_at
            )
            VALUES
            (
                ?,
                ?,
                ?,
                0,
                NOW()
            )
        ");


    $stmt->execute([
        $conversationId,
        $userId,
        $message
    ]);


    $messageId =
        (int)
        $db->lastInsertId();


    /*
    |--------------------------------------------------------------------------
    | UPDATE CONVERSATION TIME
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            UPDATE conversations

            SET updated_at = NOW()

            WHERE conversation_id = ?
        ");


    $stmt->execute([
        $conversationId
    ]);


    /*
    |--------------------------------------------------------------------------
    | COMMIT
    |--------------------------------------------------------------------------
    */

    $db->commit();


    /*
    |--------------------------------------------------------------------------
    | GET INSERTED MESSAGE
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            SELECT

                m.message_id,
                m.conversation_id,
                m.sender_id,
                m.message,
                m.is_read,
                m.created_at,

                u.name
                    AS sender_name,

                u.role
                    AS sender_role

            FROM messages m

            LEFT JOIN users u
                ON m.sender_id =
                   u.user_id

            WHERE m.message_id = ?

            LIMIT 1
        ");


    $stmt->execute([
        $messageId
    ]);


    $savedMessage =
        $stmt->fetch(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | FORMAT TIME
    |--------------------------------------------------------------------------
    */

    $timeLabel = '';


    if (
        $savedMessage
        &&
        !empty(
            $savedMessage['created_at']
        )
    ) {

        $timestamp =
            strtotime(
                $savedMessage['created_at']
            );


        if ($timestamp !== false) {

            if (
                date(
                    'Y-m-d',
                    $timestamp
                )
                ===
                date('Y-m-d')
            ) {

                $timeLabel =
                    date(
                        'h:i A',
                        $timestamp
                    );

            } else {

                $timeLabel =
                    date(
                        'd M Y, h:i A',
                        $timestamp
                    );
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | RESPONSE MESSAGE DATA
    |--------------------------------------------------------------------------
    */

    $responseMessage = [

        'message_id' =>
            $messageId,

        'conversation_id' =>
            $conversationId,

        'sender_id' =>
            $userId,

        'sender_name' =>
            (string) (
                $savedMessage['sender_name']
                ?? ''
            ),

        'sender_role' =>
            (string) (
                $savedMessage['sender_role']
                ?? $role
            ),

        'message' =>
            $message,

        'is_read' =>
            0,

        'created_at' =>
            (string) (
                $savedMessage['created_at']
                ?? ''
            ),

        'time_label' =>
            $timeLabel
    ];


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    sendMessageJson(
        true,
        [
            'message_id' =>
                $messageId,

            'conversation_id' =>
                $conversationId,

            'data' =>
                $responseMessage
        ],
        'Message sent successfully.',
        200
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | ROLLBACK
    |--------------------------------------------------------------------------
    */

    if (
        isset($db)
        &&
        $db instanceof PDO
        &&
        $db->inTransaction()
    ) {

        $db->rollBack();
    }


    /*
    |--------------------------------------------------------------------------
    | LOG REAL ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'HOCHIPOHUB send_message.php error: ' .
        $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | JSON ERROR
    |--------------------------------------------------------------------------
    */

    sendMessageJson(
        false,
        [],
        'Unable to send message: ' .
        $e->getMessage(),
        500
    );
}