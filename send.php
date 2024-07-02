#!/usr/local/bin/php -q
<?php

use MimeDecoder\MimeDecoder;

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

$stdin = file_get_contents('php://stdin');

if (! empty($stdin)) {
    $message = MimeDecoder::decode($stdin);

    $auto_submitted = $message->headers['auto-submitted'] ?? null;
    $auto_response_suppress = $message->headers['x-auto-response-suppress'] ?? null;

    if (in_array($auto_submitted, ['auto-generated', 'auto-replied', 'auto-notified']) || $auto_response_suppress === 'All') {
        // Ignore
        app_log("Ignore auto-reply: {$message->subject}");
    } else {
        $message_from = $message->from['address'];

        if (is_array($message->to) && count($message->to) > 0) {
            foreach ($message->to as $to) {
                if (! empty($to['address'])) {
                    if (str_contains($to['address'], SERVICE_DOMAIN)) {
                        $message_to = $to['address'];
                        break;
                    }
                }
            }
        }

        if (! empty($message_to)) {
            $reference = get_string_between($message->subject, '[', ']');

            if (str_contains($message_to, '@')) {
                $tel = @explode('@', $message_to, 2)[0];
                $tel = str_replace('-', '', $tel);
            }

            $body = $message->text;
            $body = str_replace("\r", "\n", $body);

            if (str_contains($body, '---')) {
                $body = explode('---', $body, 2)[0];
            } elseif (str_contains($body, "\n\n\n")) {
                $body = explode("\n\n\n", $body, 2)[0];
            }

            if (defined('MESSAGE_MAXLENGTH') && is_int(MESSAGE_MAXLENGTH)) {
                if (strlen($body) > MESSAGE_MAXLENGTH) {
                    $body = substr($body, 0, MESSAGE_MAXLENGTH - 3) . '...';
                }
            }

            if (! empty($tel) && ! empty($body)) {
                if (! empty($message_from)) {
                    $authorized = false;

                    if (defined('AUTHORIZED_EMAILS')) {
                        if (AUTHORIZED_EMAILS === '*') {
                            $authorized = true;
                        } else {
                            $authorized_emails = array_map('trim', explode(',', AUTHORIZED_EMAILS));

                            if (in_array($message_from, $authorized_emails)) {
                                $authorized = true;
                            }
                        }
                    }

                    if (! $authorized) {
                        if (defined('AUTHORIZED_DOMAINS')) {
                            if (AUTHORIZED_DOMAINS === '*') {
                                $authorized = true;
                            } else {
                                $authorized_domains = array_map('trim', explode(',', AUTHORIZED_DOMAINS));
                                $message_from_domain = explode('@', $message_from, 2)[1];

                                if (in_array($message_from_domain, $authorized_domains)) {
                                    $authorized = true;
                                }
                            }
                        }
                    }

                    if ($authorized) {
                        if (! str_starts_with($tel, '+1')) {
                            $tel = "+1{$tel}";
                        }

                        try {
                            $message = new Message([
                                'from_email' => $message_from,
                                'to_tel' => $tel,
                                'reference' => $reference,
                                'body' => $body,
                            ]);

                            if ($message->send()) {
                                app_log("{$message} sent!");

                                if ($message->save()) {
                                    app_log("{$message} saved!");
                                }
                            }
                        } catch ( Exception $e ) {
                            app_log($e);
                        }
                    } else {
                        app_log("Sender is not authorized!");

                        mail(
                            $message_from,
                            "SMS message to '{$tel}' [{$reference}] not authorized!",
                            "Cannot send your message to '{$tel}'. Your email address ({$message_from}) is unauthorized!",
                            sprintf("From: %s\r\nX-Mailer: PHP/%s", 'no-reply@' . SERVICE_DOMAIN, phpversion()),
                        );
                    }
                }
            } else {
                app_log("Missing 'tel' and/or 'body'!");
            }
        }
    }
} else {
    app_log("No data supplied!");
}
