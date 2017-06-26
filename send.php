#!/usr/local/bin/php -q
<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

$pdo = \OpenCrate\Model::get_PDO();

app_log(var_export($pdo, true));

if ( $sock = fopen('php://stdin', 'r') ) {

    try {

        $email_parser = new PhpMimeMailParser\Parser;

        $email_parser->setStream($sock);

        $email_from = parse_email_address($email_parser->getHeader('from'));
        $email_to   = parse_email_address($email_parser->getHeader('to'));

        $tel  = @explode('@', $email_to, 2)[0];
        $body = @explode('---', $email_parser->getMessageBody('text'))[0];

        if ( !empty($email_from) && $email_domain = @explode('@', $email_from, 2)[1] ) {
            if ( $email_domain == AUTHORIZED_DOMAIN ) {
                $authorization_code = $email_parser->getHeader('subject');
            }
        }

        app_log('email_from: ' . $email_from);
        app_log('email_to: ' . $email_to);
        app_log('tel: ' . $tel);
        app_log('body: ' . $body);
        app_log('authorization_code: ' . $authorization_code);

    } catch ( Exception $e ) {
        app_log($e);
    }

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

                app_log(var_export($message, true));

                app_log($message->save([ 'return_query_string' => true ]));

                if ( $message->save() ) {
                    app_log($message . " saved!");
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
