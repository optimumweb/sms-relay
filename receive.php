<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {

    if ( isset($_POST['AccountSid']) ) {

        if ( defined('TWILIO_ACCOUNT_SID') && TWILIO_ACCOUNT_SID == $_POST['AccountSid'] ) {

            if ( isset($_POST['To'], $_POST['From'], $_POST['Body']) ) {

                $to   = $_POST['To'];
                $from = $_POST['From'];
                $body = $_POST['Body'];

                if ( $message = Message::where('to_tel', $from, [ 'order_by' => 'created_at DESC', 'first' => true ]) ) {
                    $email_to = $message->from_email;
                    $subject  = sprintf("SMS message from '%s' [%s]", $from, $message->reference);
                } else {
                    $email_to = defined('DEFAULT_EMAIL') ? DEFAULT_EMAIL : null;
                    $subject  = sprintf("SMS message from '%s'", $from);
                }

                if ( !empty($email_to) ) {

                    $email_from = $from . '@' . SERVICE_DOMAIN;
                    $headers    = sprintf("From: %s\r\nReply-To: %s\r\nX-Mailer: PHP/%s", $email_from, $email_from, phpversion());

                    if ( @mail($email_to, $subject, $body, $headers) ) {
                        app_log(sprintf("SMS message from '%s' relayed successfully to '%s'", $from, $email_to));
                    } else {
                        app_log(sprintf("SMS message from '%s' could not be relayed to '%s'", $from, $email_to));
                    }

                } else {
                    app_log(sprintf("No recipient for SMS message from '%s'", $from));
                }

            } else {
                app_log(sprintf("Invalid POST data '%s'", print_r($_POST, true)));
            }

        } else {
            app_log(sprintf("Invalid Twilio Account Sid '%s'", $_POST['AccountSid']));
        }

    } else {
        app_log(sprintf("No Twilio Account Sid provided"));
    }

}
