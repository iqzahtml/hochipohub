<?php

/*
|--------------------------------------------------------------------------
| HOCHIPOHUB - EMAIL HELPER
|--------------------------------------------------------------------------
| File:
| includes/email.php
|--------------------------------------------------------------------------
|
| Uses SMTP configuration already defined inside config.php.
|
| Required constants:
|
| SMTP_HOST
| SMTP_PORT
| SMTP_USERNAME
| SMTP_PASSWORD
| SMTP_FROM_EMAIL
| SMTP_FROM_NAME
|
|--------------------------------------------------------------------------
*/


/*
|--------------------------------------------------------------------------
| EMAIL ESCAPE
|--------------------------------------------------------------------------
*/

if (!function_exists('hhEmailEscape')) {

    function hhEmailEscape($value): string
    {
        return htmlspecialchars(
            (string) $value,
            ENT_QUOTES,
            'UTF-8'
        );
    }
}


/*
|--------------------------------------------------------------------------
| SMTP READ RESPONSE
|--------------------------------------------------------------------------
*/

if (!function_exists('hhSmtpReadResponse')) {

    function hhSmtpReadResponse(
        $socket
    ): array {

        $response = '';

        $code = 0;

        while (
            !feof($socket)
        ) {

            $line =
                fgets(
                    $socket,
                    515
                );


            if ($line === false) {

                break;
            }


            $response .=
                $line;


            if (
                preg_match(
                    '/^(\d{3})([\s-])/',
                    $line,
                    $matches
                )
            ) {

                $code =
                    (int)
                    $matches[1];


                if (
                    $matches[2] === ' '
                ) {

                    break;
                }
            }
        }


        return [
            'code' => $code,
            'response' => trim(
                $response
            )
        ];
    }
}


/*
|--------------------------------------------------------------------------
| SMTP COMMAND
|--------------------------------------------------------------------------
*/

if (!function_exists('hhSmtpCommand')) {

    function hhSmtpCommand(
        $socket,
        string $command,
        array $allowedCodes
    ): array {

        fwrite(
            $socket,
            $command .
            "\r\n"
        );


        $response =
            hhSmtpReadResponse(
                $socket
            );


        if (
            !in_array(
                $response['code'],
                $allowedCodes,
                true
            )
        ) {

            throw new RuntimeException(
                'SMTP command failed. ' .
                $response['response']
            );
        }


        return $response;
    }
}


/*
|--------------------------------------------------------------------------
| CLEAN HEADER
|--------------------------------------------------------------------------
*/

if (!function_exists('hhEmailCleanHeader')) {

    function hhEmailCleanHeader(
        string $value
    ): string {

        return str_replace(
            [
                "\r",
                "\n"
            ],
            '',
            trim(
                $value
            )
        );
    }
}


/*
|--------------------------------------------------------------------------
| MIME SUBJECT
|--------------------------------------------------------------------------
*/

if (!function_exists('hhEmailSubject')) {

    function hhEmailSubject(
        string $subject
    ): string {

        return
            '=?UTF-8?B?' .
            base64_encode(
                $subject
            ) .
            '?=';
    }
}


/*
|--------------------------------------------------------------------------
| SEND SMTP EMAIL
|--------------------------------------------------------------------------
*/

if (!function_exists('sendHochipoEmail')) {

    function sendHochipoEmail(
        string $toEmail,
        string $toName,
        string $subject,
        string $htmlBody,
        string $plainBody = ''
    ): bool {

        /*
        |--------------------------------------------------------------------------
        | CONFIG CHECK
        |--------------------------------------------------------------------------
        */

        $requiredConstants = [

            'SMTP_HOST',

            'SMTP_PORT',

            'SMTP_USERNAME',

            'SMTP_PASSWORD',

            'SMTP_FROM_EMAIL',

            'SMTP_FROM_NAME'
        ];


        foreach (
            $requiredConstants as
            $constant
        ) {

            if (
                !defined(
                    $constant
                )
            ) {

                error_log(
                    'Email configuration missing: ' .
                    $constant
                );

                return false;
            }
        }


        /*
        |--------------------------------------------------------------------------
        | EMAIL VALIDATION
        |--------------------------------------------------------------------------
        */

        $toEmail =
            trim(
                $toEmail
            );


        if (
            !filter_var(
                $toEmail,
                FILTER_VALIDATE_EMAIL
            )
        ) {

            error_log(
                'Invalid recipient email: ' .
                $toEmail
            );

            return false;
        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK PLAIN BODY
        |--------------------------------------------------------------------------
        */

        if (
            trim(
                $plainBody
            ) === ''
        ) {

            $plainBody =
                html_entity_decode(
                    strip_tags(
                        str_replace(
                            [
                                '<br>',
                                '<br/>',
                                '<br />',
                                '</p>',
                                '</div>'
                            ],
                            [
                                "\n",
                                "\n",
                                "\n",
                                "\n\n",
                                "\n"
                            ],
                            $htmlBody
                        )
                    ),
                    ENT_QUOTES,
                    'UTF-8'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | SMTP VALUES
        |--------------------------------------------------------------------------
        */

        $smtpHost =
            (string)
            SMTP_HOST;


        $smtpPort =
            (int)
            SMTP_PORT;


        $smtpUsername =
            (string)
            SMTP_USERNAME;


        $smtpPassword =
            (string)
            SMTP_PASSWORD;


        $fromEmail =
            hhEmailCleanHeader(
                (string)
                SMTP_FROM_EMAIL
            );


        $fromName =
            hhEmailCleanHeader(
                (string)
                SMTP_FROM_NAME
            );


        $toName =
            hhEmailCleanHeader(
                $toName
            );


        $subject =
            hhEmailCleanHeader(
                $subject
            );


        /*
        |--------------------------------------------------------------------------
        | SOCKET
        |--------------------------------------------------------------------------
        */

        $errno = 0;

        $errstr = '';


        $socket =
            @stream_socket_client(
                'tcp://' .
                $smtpHost .
                ':' .
                $smtpPort,
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT
            );


        if (!$socket) {

            error_log(
                'SMTP connection failed: ' .
                $errstr .
                ' (' .
                $errno .
                ')'
            );

            return false;
        }


        stream_set_timeout(
            $socket,
            30
        );


        try {

            /*
            |--------------------------------------------------------------------------
            | SERVER READY
            |--------------------------------------------------------------------------
            */

            $response =
                hhSmtpReadResponse(
                    $socket
                );


            if (
                $response['code'] !== 220
            ) {

                throw new RuntimeException(
                    'SMTP server not ready. ' .
                    $response['response']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | EHLO
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'EHLO localhost',
                [
                    250
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | START TLS
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'STARTTLS',
                [
                    220
                ]
            );


            $cryptoEnabled =
                @stream_socket_enable_crypto(
                    $socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                );


            if (!$cryptoEnabled) {

                throw new RuntimeException(
                    'Unable to enable SMTP TLS encryption.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | EHLO AGAIN
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'EHLO localhost',
                [
                    250
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | LOGIN
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'AUTH LOGIN',
                [
                    334
                ]
            );


            hhSmtpCommand(
                $socket,
                base64_encode(
                    $smtpUsername
                ),
                [
                    334
                ]
            );


            hhSmtpCommand(
                $socket,
                base64_encode(
                    $smtpPassword
                ),
                [
                    235
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | MAIL FROM
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'MAIL FROM:<' .
                $fromEmail .
                '>',
                [
                    250
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | RECIPIENT
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'RCPT TO:<' .
                $toEmail .
                '>',
                [
                    250,
                    251
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | DATA
            |--------------------------------------------------------------------------
            */

            hhSmtpCommand(
                $socket,
                'DATA',
                [
                    354
                ]
            );


            /*
            |--------------------------------------------------------------------------
            | BUILD MIME EMAIL
            |--------------------------------------------------------------------------
            */

            $boundary =
                '=_HochipoHub_' .
                bin2hex(
                    random_bytes(12)
                );


            $encodedFromName =
                hhEmailSubject(
                    $fromName
                );


            $encodedToName =
                $toName !== ''
                    ? hhEmailSubject(
                        $toName
                    )
                    : '';


            $headers = [

                'Date: ' .
                date(
                    DATE_RFC2822
                ),

                'From: ' .
                $encodedFromName .
                ' <' .
                $fromEmail .
                '>',

                'To: ' .
                (
                    $encodedToName !== ''
                        ? $encodedToName .
                          ' <' .
                          $toEmail .
                          '>'
                        : '<' .
                          $toEmail .
                          '>'
                ),

                'Subject: ' .
                hhEmailSubject(
                    $subject
                ),

                'MIME-Version: 1.0',

                'Content-Type: multipart/alternative; boundary="' .
                $boundary .
                '"'
            ];


            $message =
                implode(
                    "\r\n",
                    $headers
                ) .
                "\r\n\r\n";


            /*
            |--------------------------------------------------------------------------
            | TEXT VERSION
            |--------------------------------------------------------------------------
            */

            $message .=
                '--' .
                $boundary .
                "\r\n";


            $message .=
                "Content-Type: text/plain; charset=UTF-8\r\n";


            $message .=
                "Content-Transfer-Encoding: base64\r\n\r\n";


            $message .=
                chunk_split(
                    base64_encode(
                        $plainBody
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | HTML VERSION
            |--------------------------------------------------------------------------
            */

            $message .=
                '--' .
                $boundary .
                "\r\n";


            $message .=
                "Content-Type: text/html; charset=UTF-8\r\n";


            $message .=
                "Content-Transfer-Encoding: base64\r\n\r\n";


            $message .=
                chunk_split(
                    base64_encode(
                        $htmlBody
                    )
                );


            $message .=
                '--' .
                $boundary .
                "--\r\n";


            /*
            |--------------------------------------------------------------------------
            | SMTP DOT STUFFING
            |--------------------------------------------------------------------------
            */

            $message =
                preg_replace(
                    '/(?m)^\./',
                    '..',
                    $message
                );


            fwrite(
                $socket,
                $message .
                "\r\n.\r\n"
            );


            $sendResponse =
                hhSmtpReadResponse(
                    $socket
                );


            if (
                $sendResponse['code'] !== 250
            ) {

                throw new RuntimeException(
                    'SMTP message rejected. ' .
                    $sendResponse['response']
                );
            }


            /*
            |--------------------------------------------------------------------------
            | QUIT
            |--------------------------------------------------------------------------
            */

            @hhSmtpCommand(
                $socket,
                'QUIT',
                [
                    221
                ]
            );


            fclose(
                $socket
            );


            return true;


        } catch (Throwable $e) {

            if (
                is_resource(
                    $socket
                )
            ) {

                @fwrite(
                    $socket,
                    "QUIT\r\n"
                );

                @fclose(
                    $socket
                );
            }


            error_log(
                'HochipoHub email error: ' .
                $e->getMessage()
            );


            return false;
        }
    }
}


/*
|--------------------------------------------------------------------------
| SELLER APPROVAL EMAIL
|--------------------------------------------------------------------------
*/

if (!function_exists('sendSellerApprovalEmail')) {

    function sendSellerApprovalEmail(
        string $email,
        string $sellerName,
        string $businessName
    ): bool {

        $sellerName =
            trim(
                $sellerName
            );


        if ($sellerName === '') {

            $sellerName =
                'Seller';
        }


        $businessName =
            trim(
                $businessName
            );


        if ($businessName === '') {

            $businessName =
                'Your Store';
        }


        $safeName =
            hhEmailEscape(
                $sellerName
            );


        $safeBusiness =
            hhEmailEscape(
                $businessName
            );


        $loginUrl =
            defined(
                'BASE_URL'
            )
                ? BASE_URL .
                  'index.php?login=1'
                : '#';


        $safeLoginUrl =
            hhEmailEscape(
                $loginUrl
            );


        $subject =
            'Your HochipoHub Seller Account Has Been Approved';


        $htmlBody = '
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
</head>

<body style="
    margin:0;
    padding:0;
    background:#f3f6fb;
    font-family:Arial,Helvetica,sans-serif;
    color:#172033;
">

<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    role="presentation"
    style="
        width:100%;
        background:#f3f6fb;
        padding:35px 15px;
    "
>

<tr>

<td align="center">

<table
    width="600"
    cellpadding="0"
    cellspacing="0"
    role="presentation"
    style="
        width:100%;
        max-width:600px;
        background:#ffffff;
        border-radius:18px;
        overflow:hidden;
        box-shadow:
            0 12px 35px
            rgba(31,65,120,.10);
    "
>


<tr>

<td
    style="
        padding:34px;
        text-align:center;
        background:
            linear-gradient(
                135deg,
                #123d89,
                #287de4
            );
        color:#ffffff;
    "
>

<div
    style="
        font-size:13px;
        font-weight:700;
        letter-spacing:1.5px;
        opacity:.80;
    "
>
    HOCHIPOHUB
</div>


<h1
    style="
        margin:12px 0 8px;
        font-size:27px;
        line-height:1.3;
    "
>
    Seller Account Approved 🎉
</h1>


<p
    style="
        margin:0;
        font-size:14px;
        line-height:1.7;
        color:#dbeafe;
    "
>
    Your seller application has been reviewed
    and approved.
</p>

</td>

</tr>


<tr>

<td style="padding:35px 35px 12px;">


<p
    style="
        margin:0 0 18px;
        font-size:15px;
        line-height:1.7;
    "
>

    Hi <strong>' .
    $safeName .
    '</strong>,

</p>


<p
    style="
        margin:0 0 22px;
        font-size:14px;
        line-height:1.8;
        color:#52627a;
    "
>

    Great news! Your seller application for
    <strong>' .
    $safeBusiness .
    '</strong>
    has been approved by the HochipoHub Admin.

</p>


<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    style="
        margin-bottom:25px;
        background:#ecfdf3;
        border:1px solid #bbf7d0;
        border-radius:12px;
    "
>

<tr>

<td
    style="
        padding:17px 20px;
        color:#087443;
        font-size:14px;
        font-weight:700;
    "
>

    ✓ STATUS: APPROVED

</td>

</tr>

</table>


<p
    style="
        margin:0 0 25px;
        font-size:14px;
        line-height:1.8;
        color:#52627a;
    "
>

    You can now log in to HochipoHub and access
    your Seller Center to manage your store,
    products, orders, inventory and sales.

</p>


<div style="text-align:center;margin:30px 0;">


<a
    href="' .
    $safeLoginUrl .
    '"
    style="
        display:inline-block;
        padding:14px 25px;
        color:#ffffff;
        background:#2563eb;
        border-radius:10px;
        font-size:14px;
        font-weight:700;
        text-decoration:none;
    "
>

    Login to HochipoHub

</a>


</div>


<p
    style="
        margin:28px 0 0;
        padding-top:22px;
        border-top:1px solid #edf1f6;
        color:#8492a8;
        font-size:12px;
        line-height:1.7;
    "
>

    If you did not apply for a HochipoHub seller
    account, please contact the HochipoHub
    administrator.

</p>


</td>

</tr>


<tr>

<td
    style="
        padding:20px 35px 30px;
        color:#94a3b8;
        font-size:11px;
        text-align:center;
    "
>

    Thank you,<br>

    <strong style="color:#3c5579;">
        HochipoHub Team
    </strong>

</td>

</tr>


</table>

</td>

</tr>

</table>

</body>
</html>
';


        $plainBody =
            "Hi " .
            $sellerName .
            ",\n\n" .

            "Great news!\n\n" .

            "Your seller application for \"" .
            $businessName .
            "\" has been approved by the HochipoHub Admin.\n\n" .

            "Status: APPROVED\n\n" .

            "You can now log in to HochipoHub and access your Seller Center.\n\n" .

            "Login: " .
            $loginUrl .
            "\n\n" .

            "Thank you,\n" .

            "HochipoHub Team";


        return sendHochipoEmail(
            $email,
            $sellerName,
            $subject,
            $htmlBody,
            $plainBody
        );
    }
}