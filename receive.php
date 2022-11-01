<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['AccountSid'])) {
        if (defined('TWILIO_ACCOUNT_SID') && TWILIO_ACCOUNT_SID === $_POST['AccountSid']) {
            if (isset($_POST['To'], $_POST['From'], $_POST['Body'])) {
                $to = $_POST['To'];
                $from = $_POST['From'];
                $body = $_POST['Body'];

                if ($message = Message::where('to_tel', $from, [ 'order_by' => 'created_at DESC', 'first' => true ])) {
                    $email_to = $message->from_email;
                    $subject = "SMS message from '{$from}' [{$message->reference}]";
                } else {
                    $email_to = defined('DEFAULT_EMAIL') ? DEFAULT_EMAIL : null;
                    $subject = "SMS message from '{$from}'";
                }

                if (! empty($email_to)) {
                    $email_from = $from . '@' . SERVICE_DOMAIN;

                    $headers = implode("\r\n", [
                        "From: {$email_from}",
                        "Reply-To: {$email_from}",
                        "X-Mailer: PHP/" . phpversion(),
                    ]);

                    if (mail($email_to, $subject, $body, $headers)) {
                        app_log("SMS message from '{$from}' relayed successfully to '{$email_to}'");
                    } else {
                        app_log("SMS message from '{$from}' could not be relayed to '{$email_to}'");
                    }
                } else {
                    app_log("No recipient for SMS message from '{$from}'");
                }
            } else {
                app_log(sprintf("Invalid POST data: %s", print_r($_POST, true)));
            }
        } else {
            app_log(sprintf("Invalid Twilio Account Sid: %s", $_POST['AccountSid']));
        }
    } else {
        app_log("No Twilio Account Sid provided");
    }
}
