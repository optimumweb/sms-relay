<?php

ini_set('log_errors', 1);
ini_set('error_log', ABS_PATH . '/error.log');
error_reporting(E_ALL);

require_once ABS_PATH . '/vendor/autoload.php';

require_once ABS_PATH . '/inc/config.php';
require_once ABS_PATH . '/inc/helpers.php';
require_once ABS_PATH . '/inc/mime_decoder.php';

require_once ABS_PATH . '/models/message.php';

if (defined('SENTRY_DSN') && SENTRY_DSN !== '') {
    \Sentry\init([
        'dsn' => SENTRY_DSN,
        'error_types' => defined('SENTRY_ERROR_TYPES') && SENTRY_ERROR_TYPES !== '' ? SENTRY_ERROR_TYPES : null,
        'traces_sample_rate' => defined('SENTRY_TRACES_SAMPLE_RATE') && SENTRY_TRACES_SAMPLE_RATE !== '' ? SENTRY_TRACES_SAMPLE_RATE : null,
    ]);

    \Sentry\captureLastError();
}
