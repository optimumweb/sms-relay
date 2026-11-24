<?php

function app_log($message, $echo_message = false)
{
    if (is_array($message)) {
        $message = var_export($message, true);
    } elseif (is_object($message)) {
        if (method_exists($message, '__toString')) {
            $message = (string) $message;
        } else {
            $message = var_export($message, true);
        }
    } else {
        $message = (string) $message;
    }

    $logfile = ABS_PATH . '/logs/' . date('Ymd') . '.log';

    // First write of the day: drop log files past the retention period
    if (! file_exists($logfile)) {
        prune_logs();
    }

    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? "REMOTE_ADDR_UNKNOWN";
    $request_uri = $_SERVER['REQUEST_URI'] ?? "REQUEST_URI_UNKNOWN";

    $date = date("Y-m-d H:i:s");

    if ($echo_message) {
        echo $message . PHP_EOL;
    }

    if ($fp = @fopen($logfile, "a")) {
        $result = fputcsv($fp, [ $date, $remote_addr, $request_uri, $message ]);
        fclose($fp);
        return $result > 0;
    }

    return false;
}

function get_string_between($str, $start, $end, $first_only = true, $on_if_no_start = true)
{
    $btw = '';
    if (! empty($str) && is_string($str)) {
        $on = false;
        if ($on_if_no_start && ! str_contains($str, $start)) {
            $on = true;
        }
        foreach (str_split($str) as $char) {
            if ($char == $start) {
                $on = true;
                continue;
            } elseif ($char == $end) {
                $on = false;
                if ($first_only) {
                    return $btw;
                }
            }
            if ($on) {
                $btw .= $char;
            }
        }
    }
    return $btw;
}

function prune_logs()
{
    $days = defined('LOG_RETENTION_DAYS') ? (int) LOG_RETENTION_DAYS : 90;

    if ($days <= 0) {
        return;
    }

    $cutoff = date('Ymd', strtotime("-{$days} days"));

    foreach (glob(ABS_PATH . '/logs/*.log') ?: [] as $file) {
        $name = basename($file, '.log');

        if (preg_match('/^\d{8}$/', $name) && $name < $cutoff) {
            @unlink($file);
        }
    }
}

/**
 * Verifies the X-Twilio-Signature header of the current request.
 * Twilio signs the exact URL it calls, which must be https://SERVICE_DOMAIN/<script>.
 */
function is_valid_twilio_request()
{
    $signature = $_SERVER['HTTP_X_TWILIO_SIGNATURE'] ?? '';

    if ($signature === '' || ! defined('TWILIO_AUTH_TOKEN') || TWILIO_AUTH_TOKEN === '') {
        return false;
    }

    $url = 'https://' . SERVICE_DOMAIN . ($_SERVER['REQUEST_URI'] ?? '');

    $validator = new Twilio\Security\RequestValidator(TWILIO_AUTH_TOKEN);

    return $validator->validate($signature, $url, $_POST);
}

/**
 * All values of a header, top-most first.
 */
function header_values($headers, $name)
{
    $value = $headers[strtolower($name)] ?? null;

    if ($value === null) {
        return [];
    }

    return array_values(array_map('strval', (array) $value));
}

function header_first($headers, $name)
{
    return header_values($headers, $name)[0] ?? null;
}

/**
 * Auto-replies, bounces and list mail must never trigger an SMS (prevents mail loops).
 */
function is_auto_generated_email($headers)
{
    $auto_submitted = strtolower(trim(header_first($headers, 'auto-submitted') ?? ''));

    if ($auto_submitted !== '' && $auto_submitted !== 'no') {
        return true;
    }

    $suppress = strtolower(header_first($headers, 'x-auto-response-suppress') ?? '');

    if (str_contains($suppress, 'all') || str_contains($suppress, 'autoreply') || str_contains($suppress, 'oof')) {
        return true;
    }

    $precedence = strtolower(trim(header_first($headers, 'precedence') ?? ''));

    if (in_array($precedence, ['bulk', 'junk', 'list', 'auto_reply'], true)) {
        return true;
    }

    if (trim(header_first($headers, 'return-path') ?? '') === '<>') {
        return true;
    }

    foreach (['x-autoreply', 'x-autorespond', 'list-id'] as $name) {
        if (header_first($headers, $name) !== null) {
            return true;
        }
    }

    return false;
}

function sender_auth_required()
{
    return ! defined('REQUIRE_SENDER_AUTH') || REQUIRE_SENDER_AUTH !== false;
}

function domains_align($a, $b)
{
    $a = strtolower(rtrim($a, '.'));
    $b = strtolower(rtrim($b, '.'));

    return $a !== '' && $b !== '' && ($a === $b || str_ends_with($a, ".{$b}") || str_ends_with($b, ".{$a}"));
}

/**
 * Checks that the From address was authenticated (DMARC, or DKIM/SPF aligned with the From domain)
 * according to the Authentication-Results header added by our own MTA (identified by AUTHSERV_ID).
 * Headers with another authserv-id, or lower down, may have been forged by the sender and are ignored.
 */
function sender_is_authenticated($headers, $from)
{
    if (! sender_auth_required()) {
        return true;
    }

    if (! defined('AUTHSERV_ID') || AUTHSERV_ID === '') {
        app_log("AUTHSERV_ID is not configured: cannot verify sender authentication");
        return false;
    }

    $from_domain = substr(strrchr($from, '@') ?: '', 1);

    foreach (header_values($headers, 'authentication-results') as $result) {
        $result = preg_replace('/\s+/', ' ', $result);
        $authserv_id = strtok(trim(explode(';', $result, 2)[0]), ' ');

        if (strcasecmp((string) $authserv_id, AUTHSERV_ID) !== 0) {
            continue;
        }

        // Only the top-most header from our own MTA is trusted
        if (preg_match('/\bdmarc=pass\b[^;]*\bheader\.from=([^\s;]+)/i', $result, $m) && domains_align($m[1], $from_domain)) {
            return true;
        }

        if (preg_match_all('/\bdkim=pass\b[^;]*\bheader\.d=([^\s;]+)/i', $result, $matches)) {
            foreach ($matches[1] as $domain) {
                if (domains_align($domain, $from_domain)) {
                    return true;
                }
            }
        }

        if (preg_match('/\bspf=pass\b[^;]*\bsmtp\.mailfrom=([^\s;]+)/i', $result, $m)) {
            $mailfrom_domain = str_contains($m[1], '@') ? substr(strrchr($m[1], '@'), 1) : $m[1];

            if (domains_align($mailfrom_domain, $from_domain)) {
                return true;
            }
        }

        return false;
    }

    return false;
}

function sender_is_authorized($from)
{
    $from = strtolower($from);
    $from_domain = substr(strrchr($from, '@') ?: '', 1);

    if (defined('AUTHORIZED_EMAILS') && AUTHORIZED_EMAILS !== '') {
        if (AUTHORIZED_EMAILS === '*') {
            return true;
        }

        $emails = array_map(fn($v) => strtolower(trim($v)), explode(',', AUTHORIZED_EMAILS));

        if (in_array($from, $emails, true)) {
            return true;
        }
    }

    if (defined('AUTHORIZED_DOMAINS') && AUTHORIZED_DOMAINS !== '') {
        if (AUTHORIZED_DOMAINS === '*') {
            return true;
        }

        $domains = array_map(fn($v) => strtolower(trim($v)), explode(',', AUTHORIZED_DOMAINS));

        if ($from_domain !== '' && in_array($from_domain, $domains, true)) {
            return true;
        }
    }

    return false;
}

/**
 * Normalizes a North American number to E.164 (+1NXXNXXXXXX), or returns null if invalid.
 */
function normalize_nanp_number($raw)
{
    $raw = preg_replace('/[\s\-.()]/', '', (string) $raw);

    if (preg_match('/^(?:\+1|1)?([2-9]\d{2}[2-9]\d{6})\z/', $raw, $m)) {
        return "+1{$m[1]}";
    }

    return null;
}

function is_e164_number($value)
{
    return is_string($value) && preg_match('/^\+[1-9]\d{6,14}\z/', $value) === 1;
}

/**
 * Returns a reason string if sending one more SMS would exceed a limit, null otherwise.
 * Limits are per rolling hour; set a limit to 0 to disable it.
 */
function rate_limit_exceeded($from_email)
{
    $per_sender = defined('RATE_LIMIT_PER_SENDER') ? (int) RATE_LIMIT_PER_SENDER : 30;
    $global = defined('RATE_LIMIT_GLOBAL') ? (int) RATE_LIMIT_GLOBAL : 300;
    $since = time() - 3600;

    if ($per_sender > 0 && Message::count_since($since, $from_email) >= $per_sender) {
        return "sender limit of {$per_sender}/hour reached";
    }

    if ($global > 0 && Message::count_since($since) >= $global) {
        return "global limit of {$global}/hour reached";
    }

    return null;
}
