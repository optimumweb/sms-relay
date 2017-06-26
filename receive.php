<?php

define('ABS_PATH', dirname(__FILE__));

require_once ABS_PATH . '/inc/init.php';

app_log('GET: ' . $_GET);
app_log('POST: ' . $_POST);
