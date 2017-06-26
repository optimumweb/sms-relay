<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

app_log('GET: ' . var_export($_GET, true));
app_log('POST: ' . var_export($_POST, true));
