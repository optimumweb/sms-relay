<?php

define('SERVICE_DOMAIN', '');
define('DEFAULT_EMAIL',  '');

// Senders allowed to send SMS. Separate values with commas.
// Avoid '*': it lets anyone with a working mail domain send SMS at your expense.
define('AUTHORIZED_EMAILS',   '');
define('AUTHORIZED_DOMAINS',  '');

// Only accept email whose From address passed DMARC (or aligned DKIM/SPF), as reported in the
// Authentication-Results header added by this server's MTA. AUTHSERV_ID is the first token of that
// header (usually the mail server's hostname): check a received message's raw headers to find it.
define('REQUIRE_SENDER_AUTH', true);
define('AUTHSERV_ID',         '');

// Maximum SMS sent per rolling hour (0 disables the limit)
define('RATE_LIMIT_PER_SENDER', 30);
define('RATE_LIMIT_GLOBAL',     300);

// Daily log files older than this are deleted (0 keeps them forever)
define('LOG_RETENTION_DAYS', 90);

define('DB_TYPE',    'mysql');
define('DB_HOST',    'localhost');
define('DB_CHARSET', 'utf8');
define('DB_NAME',    '');
define('DB_USER',    '');
define('DB_PASS',    '');

define('TWILIO_ACCOUNT_SID', '');
define('TWILIO_AUTH_TOKEN',  '');
define('TWILIO_SMS_FROM',    '');

define('MESSAGE_MAXLENGTH', 1600);

define('SENTRY_DSN', '');
define('SENTRY_ERROR_TYPES', E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
define('SENTRY_TRACES_SAMPLE_RATE', 1.0);
