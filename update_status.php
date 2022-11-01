<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (! empty($_POST['SmsSid']) && $message = Message::where('twilio_message_sid', $_POST['SmsSid'], [ 'first' => true ])) {
        if (! empty($_POST['SmsStatus'])) {
            app_log("{$message} status updated to " . $_POST['SmsStatus']);

            $message->twilio_status = $_POST['SmsStatus'];
            $message->save();

            switch ($_POST['SmsStatus']) {
                case 'delivered':
                    mail(
                        $message->from_email,
                        "SMS message to '{$message->to_tel}' [{$message->reference}] sent successfully!",
                        "Your message to '{$message->to_tel}' [{$message->reference}] has been delivered successfully!\r\nMessage: {$message->body}",
                        sprintf("From: %s\r\nX-Mailer: PHP/%s", $message->to_tel . '@' . SERVICE_DOMAIN, phpversion())
                    );
                    break;

                case 'undelivered':
                    mail(
                        $message->from_email,
                        "SMS message to '{$message->to_tel}' [{$message->reference}] has failed!",
                        "Your message to '{$message->to_tel}' [{$message->reference}] has could not be delivered!\r\nMessage: {$message->body}",
                        sprintf("From: %s\r\nX-Mailer: PHP/%s", $message->to_tel . '@' . SERVICE_DOMAIN, phpversion())
                    );
                    break;
            }
        }
    }
}
