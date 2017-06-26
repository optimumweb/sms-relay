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

            app_log('stdin: ' . $stdin);
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

    if ( !empty($email_from) && !empty($tel) && !empty($body) ) {

        if ( strpos($tel, '+1') === false ) {
            $tel = '+1' . $tel;
        }

        try {

            $message = new Message([
                'from_email' => $email_from,
                'to_tel'     => $tel,
                'body'       => $body
            ]);

            if ( $message->send() ) {

                app_log($message . " sent!");

                if ( $message->save() ) {
                    app_log($message . " saved!");
                } else {
                    app_log($message . " could not be saved: " . OpenCrate\Model::$pdo->errorInfo());
                }

            }

        } catch ( Exception $e ) {
            app_log($e);
        }

    } else {
        app_log("Missing 'tel' and/or 'body'!");
    }

} else {
    app_log("No authorization code supplied!");
}
