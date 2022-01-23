<?php

error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

require_once ABS_PATH . '/vendor/autoload.php';

require_once ABS_PATH . '/inc/config.php';
require_once ABS_PATH . '/inc/helpers.php';

require_once ABS_PATH . '/models/message.php';
