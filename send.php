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

            if ( $email_domain = @explode('@', $email->from, 2)[1] ) {

                if ( defined('AUTHORIZED_DOMAIN') ) {

                    if ( $email_domain == AUTHORIZED_DOMAIN ) {

                        if ( $tel = @explode('@', $email->to, 2)[0] ) {

                            if ( strpos($tel, '+1') === false ) {
                                $tel = '+1' . $tel;
                            }

                            $body = $email->plain();

                            if ( !empty($body) ) {

                                if ( strpos($body, '---') !== false ) {
                                    $body = explode('---', $body)[0];
                                }

                                if ( !empty($body) ) {

                                    try {

                                        $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

                                        $message = $twilio_client->messages->create($tel, [ 'from' => TWILIO_SMS_FROM, 'body' => $body ] );

                                    } catch ( Exception $e ) {

                                    }

                                }

                            }

                        }

                    }

                }

            }

        }

    } catch ( Exception $e ) {

    }

}
