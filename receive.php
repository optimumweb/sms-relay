<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if ( !empty($_POST) ) {

    app_log('incoming message: ' . var_export($_POST, true));

    $to   = $_POST['To'];
    $from = $_POST['From'];
    $body = $_POST['Body'];

    if ( $message = Message::where('to_tel', $from, [ 'order_by' => 'created_at DESC', 'first' => true ]) ) {

        $email_to   = $message->from_email;
        $email_from = $from . '@' . SERVICE_DOMAIN;
        $subject    = sprintf("SMS message from %s", $from);
        $headers    = sprintf("From: %s\r\nReply-To: %s\r\nX-Mailer: PHP/%s", $email_from, $email_from, phpversion());

        if ( mail($email_to, $subject, $body, $headers) ) {
            app_log("SMS message from '%s' relayed successfully to '%s'", $from, $email_to);
        } else {
            app_log("SMS message from '%s' could not be relayed to '%s'", $from, $email_to);
        }

    } else {
        app_log("No previous SMS message to '%s'", $from);
    }

}
