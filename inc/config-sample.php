<?php

define('SERVICE_DOMAIN', '');
define('DEFAULT_EMAIL',  '');

define('ADMIN_TOKEN', '');

define('AUTHORIZED_EMAILS',   ''); // separate values with commas
define('AUTHORIZED_DOMAINS',  ''); // separate values with commas

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
