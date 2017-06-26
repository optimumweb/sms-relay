<?php

function app_log($message)
{
    if ( is_array($message) ) {
        $message = var_export($message, true);
    } elseif ( is_object($message) ) {
        if ( method_exists($message, '__toString') ) {
            $message = (string) $message;
        } else {
            $message = var_export($message, true);
        }
    } else {
        $message = (string) $message;
    }

    $logfile = ABS_PATH . '/logs/' . date('Ymd');

    if ( !$remote_addr = $_SERVER['REMOTE_ADDR'] ) {
        $remote_addr = "REMOTE_ADDR_UNKNOWN";
    }

    if ( !$request_uri = $_SERVER['REQUEST_URI'] ) {
        $request_uri = "REQUEST_URI_UNKNOWN";
    }

    $date = date("Y-m-d H:i:s");

    if ( $fp = @fopen($logfile, "a") ) {
        $result = fputcsv($fp, [ $date, $remote_addr, $request_uri, $message ]);
        fclose($fp);
        return $result > 0;
    }

    return false;
}
