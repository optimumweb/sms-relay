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

            if ( !empty($email->from) && $email_domain = @explode('@', $email->from, 2)[1] ) {

                if ( defined('AUTHORIZED_DOMAIN') && $email_domain == AUTHORIZED_DOMAIN ) {

                    $authorization_code = $email->subject;
                    $tel = @explode('@', $email->to, 2)[0];

                    $body = $email->plain();

                    if ( !empty($body) && strpos($body, '---') !== false ) {
                        $body = explode('---', $body)[0];
                    }

                }

            }

        }

    } catch ( Exception $e ) {
        echo "Email_Parser exception thrown: " . $e . PHP_EOL;
    }

} elseif ( !empty($_GET['tel']) && !empty($_GET['body']) && !empty($_GET['authorization_code']) ) {
    $tel = $_GET['tel'];
    $body = $_GET['body'];
    $authorization_code = $_GET['authorization_code'];
} else {
    echo "No data supplied!" . PHP_EOL;
}

if ( !empty($authorization_code) && $authorization_code == AUTHORIZATION_CODE ) {

    if ( !empty($tel) && !empty($body) ) {

        if ( strpos($tel, '+1') === false ) {
            $tel = '+1' . $tel;
        }

        try {

            $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

            $message = $twilio_client->messages->create($tel, [ 'from' => TWILIO_SMS_FROM, 'body' => $body ] );

            echo $message . PHP_EOL;

        } catch ( Exception $e ) {
            echo "Twilio Exception thrown: " . $e . PHP_EOL;
        }

    } else {
        echo "Missing 'tel' and/or 'body'!" . PHP_EOL;
    }

} else {
    echo "No authorization code supplied!" . PHP_EOL;
}
