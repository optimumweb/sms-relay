#!/usr/local/bin/php -q
<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if ( $sock = fopen("php://stdin", 'r') ) {
    $stdin = '';
    while ( !feof($sock) ) {
        $stdin .= fread($sock, 1024);
    }
    fclose($sock);
}

if ( !empty($stdin) ) {

    try {

        $email_parser = new Email_Parser;

        if ( $email = $email_parser->parse($stdin) ) {

            $email_from = parse_email_address($email->from);
            $email_to   = parse_email_address($email->to);

            $tel  = @explode('@', $email_to, 2)[0];
            $body = @explode('---', $email->plain())[0];

            if ( !empty($email_from) && $email_domain = @explode('@', $email_from, 2)[1] ) {
                if ( $email_domain == AUTHORIZED_DOMAIN ) {
                    $authorization_code = $email->subject;
                }
            }

            app_log('email_from: ' . $email_from);
            app_log('email_to: ' . $email_to);
            app_log('tel: ' . $tel);
            app_log('body: ' . $body);
            app_log('authorization_code: ' . $authorization_code);

        }

    } catch ( Exception $e ) {
        app_log("Email_Parser exception thrown: " . $e);
    }

} elseif ( !empty($_GET['tel']) && !empty($_GET['body']) && !empty($_GET['authorization_code']) ) {
    $tel = $_GET['tel'];
    $body = $_GET['body'];
    $authorization_code = $_GET['authorization_code'];
} else {
    app_log("No data supplied!");
}

if ( !empty($authorization_code) && $authorization_code == AUTHORIZATION_CODE ) {

    if ( !empty($tel) && !empty($body) ) {

        if ( strpos($tel, '+1') === false ) {
            $tel = '+1' . $tel;
        }

        try {

            $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

            $message = $twilio_client->messages->create($tel, [ 'from' => TWILIO_SMS_FROM, 'body' => $body ] );

            app_log($message);

        } catch ( Exception $e ) {
            app_log("Twilio Exception thrown: " . $e);
        }

    } else {
        app_log("Missing 'tel' and/or 'body'!");
    }

} else {
    app_log("No authorization code supplied!");
}
