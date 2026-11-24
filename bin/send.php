#!/usr/local/bin/php -q
<?php

use MimeDecoder\MimeDecoder;

define('ABS_PATH', dirname(__DIR__));

require_once ABS_PATH . '/inc/init.php';

// Every path exits with 0: a non-zero exit makes the MTA bounce the email to its (possibly forged) sender.

$stdin = file_get_contents('php://stdin');

if (empty($stdin)) {
    app_log("No data supplied!");
    exit(0);
}

$email = MimeDecoder::decode($stdin);
$headers = is_array($email->headers) ? $email->headers : [];
$service_domain = strtolower(SERVICE_DOMAIN);

if (is_auto_generated_email($headers)) {
    app_log("Ignore auto-generated email: {$email->subject}");
    exit(0);
}

$message_from = strtolower(trim($email->from['address'] ?? ''));

if (! filter_var($message_from, FILTER_VALIDATE_EMAIL)) {
    app_log("Missing or invalid sender address");
    exit(0);
}

// Never relay mail sent from our own domain (e.g. relayed SMS replies bouncing back)
if (str_ends_with($message_from, "@{$service_domain}")) {
    app_log("Ignore email from own domain: {$message_from}");
    exit(0);
}

$message_to = null;

foreach ((array) $email->to as $to) {
    $address = strtolower(trim($to['address'] ?? ''));

    if (str_ends_with($address, "@{$service_domain}")) {
        $message_to = $address;
        break;
    }
}

if ($message_to === null) {
    app_log("No recipient at {$service_domain}");
    exit(0);
}

$tel = normalize_nanp_number(explode('@', $message_to, 2)[0]);

if ($tel === null) {
    app_log("Invalid phone number in recipient '{$message_to}'");
    exit(0);
}

$reference = get_string_between($email->subject, '[', ']');

$body = str_replace("\r", "\n", (string) $email->text);

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

if (trim($body) === '') {
    app_log("Missing 'body'!");
    exit(0);
}

if (! sender_is_authenticated($headers, $message_from)) {
    // No reply: the From address is unverified, replying would send mail to a forged address
    app_log("Sender authentication failed for '{$message_from}'");
    exit(0);
}

if (! sender_is_authorized($message_from)) {
    app_log("Sender '{$message_from}' is not authorized!");

    // Only reply when the From address has been verified, to avoid backscatter
    if (sender_auth_required()) {
        mail(
            $message_from,
            "SMS message to '{$tel}' [{$reference}] not authorized!",
            "Cannot send your message to '{$tel}'. Your email address ({$message_from}) is unauthorized!",
            sprintf("From: %s\r\nAuto-Submitted: auto-replied\r\nX-Mailer: PHP/%s", "no-reply@{$service_domain}", phpversion()),
        );
    }

    exit(0);
}

try {
    if ($reason = rate_limit_exceeded($message_from)) {
        app_log("Rate limit: not sending SMS from '{$message_from}' to '{$tel}' ({$reason})");
        exit(0);
    }

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
} catch (Throwable $e) {
    app_log($e);
}

exit(0);
