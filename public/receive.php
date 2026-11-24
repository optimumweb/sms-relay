<?php

define('ABS_PATH', dirname(__DIR__));

require_once ABS_PATH . '/inc/init.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    exit;
}

if (! is_valid_twilio_request()) {
    app_log("Invalid or missing Twilio signature");
    http_response_code(403);
    exit;
}

$from = $_POST['From'] ?? null;
$body = $_POST['Body'] ?? null;

// From ends up in email headers: only accept a strict phone number to rule out header injection
if (! is_e164_number($from) || ! is_string($body)) {
    app_log(sprintf("Invalid POST data (fields: %s)", implode(', ', array_keys($_POST))));
    http_response_code(400);
    exit;
}

if ($message = Message::where('to_tel', $from, [ 'order_by' => 'created_at DESC', 'first' => true ])) {
    $email_to = $message->from_email;
    $subject = "SMS message from '{$from}' [{$message->reference}]";
} else {
    $email_to = defined('DEFAULT_EMAIL') ? DEFAULT_EMAIL : null;
    $subject = "SMS message from '{$from}'";
}

if (empty($email_to)) {
    app_log("No recipient for SMS message from '{$from}'");
    exit;
}

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
