<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if ( defined('ADMIN_TOKEN') && !empty($_GET['admin_token']) && $_GET['admin_token'] == ADMIN_TOKEN ) {

    $runtime    = 60;
    $sleep      = 15;
    $start_time = time();

    set_time_limit($runtime);

    while ( time() - $start_time < $runtime ) {

        $undelivered = Message::where('is_delivered', 0, [ 'limit' => 10 ]);

        if ( !empty($undelivered) ) {

            foreach ( $undelivered as $message ) {

                app_log($message . ": checking Twilio status");

                if ( $twilio_message = $message->get_twilio_message() ) {

                    if ( property_exists($twilio_message, 'status') ) {

                        switch ( $twilio_message->status ) {

                            case 'sent':

                                app_log($message . ' is sent');

                                @mail(
                                    $message->from_email,
                                    sprintf("SMS message to '%s' (%s) sent successfully!", $message->to_tel, $message->reference),
                                    sprintf("Your message to '%s' (%s) has been delivered successfully!\r\nMessage: %s", $message->to_tel, $message->reference, $message->body),
                                    sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion())
                                );

                                $message->is_delivered = 1;

                                $message->save();

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

        } else {
            app_log('check_status: no messages to check');
        }

        sleep($sleep);

    }

}
