<?php

function app_log($message, $echo_message = false)
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

    $logfile = ABS_PATH . '/logs/' . date('Ymd') . '.log';

    $remote_addr = $_SERVER['REMOTE_ADDR'] ?? "REMOTE_ADDR_UNKNOWN";
    $request_uri = $_SERVER['REQUEST_URI'] ?? "REQUEST_URI_UNKNOWN";

    $date = date("Y-m-d H:i:s");

    if ( $echo_message ) {
        echo $message . PHP_EOL;
    }

    if ( $fp = @fopen($logfile, "a") ) {
        $result = fputcsv($fp, [ $date, $remote_addr, $request_uri, $message ]);
        fclose($fp);
        return $result > 0;
    }

    return false;
}

function get_string_between($str, $start, $end, $first_only = true, $on_if_no_start = true)
{
    $btw = '';
    if ( !empty($str) && is_string($str) ) {
        $on = false;
        if ( $on_if_no_start && strpos($str, $start) === false ) {
            $on = true;
        }
        foreach ( str_split($str) as $char ) {
            if ( $char == $start ) {
                $on = true;
                continue;
            } elseif ( $char == $end ) {
                $on = false;
                if ( $first_only ) {
                    return $btw;
                }
            }
            if ( $on ) {
                $btw .= $char;
            }
        }
    }
    return $btw;
}
