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

    $logfile = ABS_PATH . '/logs/' . date('Ymd');

    if ( !$remote_addr = $_SERVER['REMOTE_ADDR'] ) {
        $remote_addr = "REMOTE_ADDR_UNKNOWN";
    }

    if ( !$request_uri = $_SERVER['REQUEST_URI'] ) {
        $request_uri = "REQUEST_URI_UNKNOWN";
    }

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

function mime_decode($raw)
{
    try {

        $guzzle_client = new GuzzleHttp\Client;

        $guzzle_response = $guzzle_client->post('https://mimedecoder.com', [
            'headers' => [ 'Content-Type' => 'multipart/form-data' ],
            'body'    => http_build_query([ 'raw' => $raw ])
        ]);

        if ( $guzzle_response_body = $guzzle_response->getBody() ) {
            if ( $guzzle_response_json = json_decode($guzzle_response_body) ) {
                return $guzzle_response_json;
            } else {
                app_log(sprintf("mime_decode - Could not decode response: %s", $guzzle_response_body));
            }
        } else {
            app_log(sprintf("mime_decode - Empty response"));
        }

    } catch ( Exception $e ) {
        app_log(sprintf("mime_decode - Exception thrown: %s", $e));
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

function parse_email_address($str)
{
    return get_string_between($str, '<', '>');
}
