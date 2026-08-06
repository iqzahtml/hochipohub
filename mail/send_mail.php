<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


require_once "vendor/autoload.php";



function sendMail($to, $subject, $message)
{


    $mail = new PHPMailer(true);



    try {


        /*
        |--------------------------------------------------------------------------
        | SMTP CONFIGURATION
        |--------------------------------------------------------------------------
        */


        $mail->isSMTP();


        $mail->Host = "smtp.gmail.com";


        $mail->SMTPAuth = true;



        /*
        |--------------------------------------------------------------------------
        | CHANGE THIS
        |--------------------------------------------------------------------------
        */


        $mail->Username = "your_email@gmail.com";


        $mail->Password = "your_app_password";



        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;


        $mail->Port = 587;




        /*
        |--------------------------------------------------------------------------
        | EMAIL INFO
        |--------------------------------------------------------------------------
        */


        $mail->setFrom(

            "your_email@gmail.com",

            "HochipoHub"

        );



        $mail->addAddress($to);



        $mail->isHTML(true);



        $mail->Subject = $subject;



        $mail->Body = $message;



        $mail->send();



        return true;



    }


    catch(Exception $e){


        return false;


    }


}

?>