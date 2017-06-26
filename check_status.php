<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if ( defined('ADMIN_TOKEN') && !empty($_GET['admin_token']) && $_GET['admin_token'] == ADMIN_TOKEN ) {

    $undelivered = Message::where('is_delivered', 0, [ 'limit' => 10 ]);

    if ( !empty($undelivered) ) {

        foreach ( $undelivered as $message ) {

            if ( $twilio_message = $message->twilio_message() ) {

                switch ( $twilio_message->Status ) {

                    case 'sent':

                        app_log($message . ' is sent');

                        @mail(
                            $message->from_email,
                            sprintf("SMS message to '%s' (%s) sent successfully!", $message->to_tel, $message->reference),
                            sprintf("Your message to '%s' (%s) has been delivered successfully!\r\nMessage: %s", $message->to_tel, $message->reference, $message->body),
                            sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion())
                        );

                        break;

                    case 'failed':

                        app_log($message . ' has failed');

                        @mail(
                            $message->from_email,
                            sprintf("SMS message to '%s' (%s) has failed!", $message->to_tel, $message->reference),
                            sprintf("Your message to '%s' (%s) has could not be delivered!\r\nMessage: %s", $message->to_tel, $message->reference, $message->body),
                            sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion())
                        );

                        break;

                }

            }

        }

    }

}
