<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - LOAD MESSAGES AJAX
|--------------------------------------------------------------------------
| File: ajax/load_messages.php
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
| RESPONSE
|--------------------------------------------------------------------------
*/

function loadMessagesJson(
    bool $success,
    array $data = [],
    string $message = '',
    int $statusCode = 200
): void {

    /*
    |--------------------------------------------------------------------------
    | REMOVE ANY WARNING / HTML / WHITESPACE PRODUCED BEFORE JSON
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
                'success' => $success,
                'message' => $message
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
| DATABASE
|--------------------------------------------------------------------------
*/

try {

    $db = getDB();


    if (!($db instanceof PDO)) {

        loadMessagesJson(
            false,
            [],
            'Database connection is not available.',
            500
        );
    }

} catch (Throwable $e) {

    error_log(
        'Load messages database error: ' .
        $e->getMessage()
    );


    loadMessagesJson(
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

    loadMessagesJson(
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

    loadMessagesJson(
        false,
        [],
        'Access denied.',
        403
    );
}


/*
|--------------------------------------------------------------------------
| CONVERSATION ID
|--------------------------------------------------------------------------
*/

$conversationId =
    isset($_GET['conversation_id'])
        ? (int) $_GET['conversation_id']
        : 0;


if ($conversationId <= 0) {

    loadMessagesJson(
        false,
        [],
        'Invalid conversation.',
        422
    );
}


/*
|--------------------------------------------------------------------------
| VERIFY CONVERSATION
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

                v.user_id AS vendor_user_id

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

        loadMessagesJson(
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

        loadMessagesJson(
            false,
            [],
            'You do not have access to this conversation.',
            403
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LOAD MESSAGES
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

                u.name AS sender_name,
                u.role AS sender_role

            FROM messages m

            LEFT JOIN users u
                ON m.sender_id =
                   u.user_id

            WHERE m.conversation_id = ?

            ORDER BY
                m.message_id ASC
        ");


    $stmt->execute([
        $conversationId
    ]);


    $messages =
        $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


    /*
    |--------------------------------------------------------------------------
    | MARK RECEIVED MESSAGES AS READ
    |--------------------------------------------------------------------------
    */

    $stmt =
        $db->prepare("
            UPDATE messages

            SET is_read = 1

            WHERE conversation_id = ?

            AND sender_id != ?

            AND is_read = 0
        ");


    $stmt->execute([
        $conversationId,
        $userId
    ]);


    /*
    |--------------------------------------------------------------------------
    | FORMAT MESSAGES
    |--------------------------------------------------------------------------
    */

    $formattedMessages = [];


    foreach (
        $messages
        as $messageRow
    ) {

        /*
        |--------------------------------------------------------------------------
        | DATE
        |--------------------------------------------------------------------------
        */

        $createdAt =
            (string) (
                $messageRow['created_at']
                ?? ''
            );


        $timestamp =
            $createdAt !== ''
                ? strtotime(
                    $createdAt
                )
                : false;


        $timeLabel = '';


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


        /*
        |--------------------------------------------------------------------------
        | MESSAGE
        |--------------------------------------------------------------------------
        */

        $formattedMessages[] = [

            'message_id' =>
                (int) (
                    $messageRow['message_id']
                    ?? 0
                ),

            'conversation_id' =>
                (int) (
                    $messageRow['conversation_id']
                    ?? 0
                ),

            'sender_id' =>
                (int) (
                    $messageRow['sender_id']
                    ?? 0
                ),

            'sender_name' =>
                (string) (
                    $messageRow['sender_name']
                    ?? ''
                ),

            'sender_role' =>
                (string) (
                    $messageRow['sender_role']
                    ?? ''
                ),

            'message' =>
                (string) (
                    $messageRow['message']
                    ?? ''
                ),

            'is_read' =>
                (int) (
                    $messageRow['is_read']
                    ?? 0
                ),

            'created_at' =>
                $createdAt,

            'time_label' =>
                $timeLabel
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    loadMessagesJson(
        true,
        [
            'conversation_id' =>
                $conversationId,

            'current_user_id' =>
                $userId,

            'messages' =>
                $formattedMessages,

            'message_count' =>
                count(
                    $formattedMessages
                )
        ],
        '',
        200
    );


} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | LOG REAL ERROR
    |--------------------------------------------------------------------------
    */

    error_log(
        'HOCHIPOHUB load_messages.php error: ' .
        $e->getMessage()
    );


    /*
    |--------------------------------------------------------------------------
    | RETURN JSON
    |--------------------------------------------------------------------------
    */

    loadMessagesJson(
        false,
        [],
        'Unable to load messages: ' .
        $e->getMessage(),
        500
    );
}