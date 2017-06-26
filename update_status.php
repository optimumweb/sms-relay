<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

app_log(var_export($_POST, true));

if ( !empty($_POST['SmsSid']) && $message = Message::where('twilio_message_sid', $_POST['SmsSid'], [ 'first' => true ]) ) {

    if ( !empty($_POST['SmsStatus']) ) {

        $message->twilio_status = $_POST['SmsStatus'];
        $message->save();

        switch ( $_POST['SmsStatus'] ) {

            case 'delivered':

                @mail(
                    $message->from_email,
                    sprintf("SMS message to '%s' (%s) sent successfully!", $message->to_tel, $message->reference),
                    sprintf("Your message to '%s' (%s) has been delivered successfully!\r\nMessage: %s", $message->to_tel, $message->reference, $message->body),
                    sprintf("From: %s\r\nX-Mailer: PHP/%s", $message->to_tel . '@' . SERVICE_DOMAIN, phpversion())
                );

                break;

            case 'undelivered':

                @mail(
                    $message->from_email,
                    sprintf("SMS message to '%s' (%s) has failed!", $message->to_tel, $message->reference),
                    sprintf("Your message to '%s' (%s) has could not be delivered!\r\nMessage: %s", $message->to_tel, $message->reference, $message->body),
                    sprintf("From: %s\r\nX-Mailer: PHP/%s", $message->to_tel . '@' . SERVICE_DOMAIN, phpversion())
                );

                break;

        }

    }

}
