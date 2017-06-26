#!/usr/local/bin/php -q
<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

$authorized = false;

if ( $sock = fopen('php://stdin', 'r') ) {

    try {

        $email_parser = new PhpMimeMailParser\Parser;

        $email_parser->setStream($sock);

        $email_from = parse_email_address($email_parser->getHeader('from'));
        $email_to   = parse_email_address($email_parser->getHeader('to'));

        $reference = $email_parser->getHeader('subject');

        $tel  = @explode('@', $email_to, 2)[0];
        $body = @explode("\n\n", str_replace("\r", "\n", $email_parser->getMessageBody('text')))[0];

        if ( !empty($tel) && !empty($body) ) {

            if ( !empty($email_from) ) {

                if ( defined('AUTHORIZED_EMAILS') ) {
                    $authorized_emails = array_map('trim', explode(',', AUTHORIZED_EMAILS));
                    if ( in_array($email_from, $authorized_emails) ) {
                        $authorized = true;
                    }
                }

                if ( !$authorized ) {
                    if ( defined('AUTHORIZED_DOMAINS') ) {
                        $authorized_domains = array_map('trim', explode(',', AUTHORIZED_DOMAINS));
                        $email_domain = @explode('@', $email_from, 2)[1];
                        if ( in_array($email_domain, $authorized_domains) ) {
                            $authorized = true;
                        }
                    }
                }

                if ( $authorized ) {

                    if ( strpos($tel, '+1') === false ) {
                        $tel = '+1' . $tel;
                    }

                    try {

                        $message = new Message([
                            'from_email' => $email_from,
                            'to_tel'     => $tel,
                            'reference'  => $reference,
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
                        sprintf("SMS message to '%s' (%s) not authorized!", $tel, $reference),
                        sprintf("Cannot send your message to '%s'. Your email address (%s) is unauthorized!", $tel, $email_from),
                        sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion())
                    );

                }

            }

        } else {
            app_log("Missing 'tel' and/or 'body'!");
        }

    } catch ( Exception $e ) {
        app_log($e);
    }

} else {
    app_log("No data supplied!");
}

