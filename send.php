#!/usr/local/bin/php -q
<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

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

        app_log([
            'email_from'         => $email_from,
            'email_to'           => $email_to,
            'tel'                => $tel,
            'body'               => $body,
            'authorization_code' => $authorization_code
        ]);

    } catch ( Exception $e ) {
        app_log($e);
    }

} else {
    app_log("No data supplied!");
}

if ( !empty($email_from) && !empty($tel) && !empty($body) ) {

    if ( !empty($authorization_code) && $authorization_code == AUTHORIZATION_CODE ) {

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
                }

            }

        } catch ( Exception $e ) {
            app_log($e);
        }

    } else {

        app_log("No authorization code supplied!");

        @mail(
            $email_from,
            "Invalid authorization code!",
            sprintf("Cannot send your message to '%s'. The authorization code you supplied (%s) is invalid!", $tel, $authorization_code),
            sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion())
        );

    }

} else {
    app_log("Missing 'email_from', 'tel' and/or 'body'!");
}
