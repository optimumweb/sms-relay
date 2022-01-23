#!/usr/local/bin/php -q
<?php

use MimeDecoder\MimeDecoder;

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

$authorized = false;

$stdin = file_get_contents('php://stdin');

if ( !empty($stdin) ) {

    $message = MimeDecoder::decode($stdin);

    app_log(var_export($message, true));

    $message_from = $message->from['address'];

    if ( is_array($message->to) && count($message->to) > 0 ) {
        foreach ( $message->to as $to ) {
            if ( !empty($to['address']) ) {
                if ( strpos($to['address'], SERVICE_DOMAIN) !== false ) {
                    $message_to = $to['address'];
                    break;
                }
            }
        }
    }

    if ( !empty($message_to) ) {

        $reference = get_string_between($message->subject, '[', ']');

        if ( strpos($message_to, '@') !== false ) {
            $tel = @explode('@', $message_to, 2)[0];
            $tel = str_replace('-', '', $tel);
            app_log(sprintf("Tel: %s", $tel));
        }

        $body = $message->text;
        $body = str_replace("\r", "\n", $body);

        if ( strpos($body, '---') !== false ) {
            $body = @explode('---', $body, 2)[0];
        } elseif ( strpos($body, "\n\n\n") !== false ) {
            $body = @explode("\n\n\n", $body, 2)[0];
        }

        if ( !empty($tel) && !empty($body) ) {

            if ( !empty($message_from) ) {

                if ( defined('AUTHORIZED_EMAILS') ) {
                    $authorized_emails = array_map('trim', explode(',', AUTHORIZED_EMAILS));
                    if ( in_array($message_from, $authorized_emails) ) {
                        $authorized = true;
                    }
                }

                if ( !$authorized ) {
                    if ( defined('AUTHORIZED_DOMAINS') ) {
                        $authorized_domains = array_map('trim', explode(',', AUTHORIZED_DOMAINS));
                        $message_domain = @explode('@', $message_from, 2)[1];
                        if ( in_array($message_domain, $authorized_domains) ) {
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
                            'from_email' => $message_from,
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
                        $message_from,
                        sprintf("SMS message to '%s' [%s] not authorized!", $tel, $reference),
                        sprintf("Cannot send your message to '%s'. Your email address (%s) is unauthorized!", $tel, $message_from),
                        sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion())
                    );

                }

            }

        } else {
            app_log("Missing 'tel' and/or 'body'!");
        }

    }

} else {
    app_log("No data supplied!");
}
