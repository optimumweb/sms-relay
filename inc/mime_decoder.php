<?php

namespace MimeDecoder;

use PhpMimeMailParser\Parser;

class MimeDecoder
{
    /**
     * Decode
     * Parses an Internet Message Format (RFC 822, 2822, 5322) into a Message object
     *
     * @param  $raw
     * @return Message
     */
    public static function decode($raw)
    {
        return new Message($raw);
    }
}

class Message
{
    var $from;
    var $to;
    var $cc;
    var $subject;
    var $date;
    var $text;
    var $html;
    var $htmlEmbedded;
    var $headersRaw;
    var $headers;
    var $attachments = [];

    public function __construct($raw, $options = [])
    {
        $options = array_merge([
            'save_dir' => sys_get_temp_dir(),
            'include_inline_attachments' => true,
        ], (array) $options);

        if ( is_string($raw) && strlen($raw) > 0 ) {
            $Parser = new Parser;

            $Parser->setText($raw);

            $this->from = $Parser->getAddresses('from')[0] ?? null;
            $this->to = $Parser->getAddresses('to');
            $this->cc = $Parser->getHeader('cc');
            $this->subject = $Parser->getHeader('subject');
            $this->date = $Parser->getHeader('date');
            $this->text = $Parser->getMessageBody('text');
            $this->html = $Parser->getMessageBody('html');
            $this->htmlEmbedded = $Parser->getMessageBody('htmlEmbedded');
            $this->headersRaw = $Parser->getHeadersRaw();
            $this->headers = $Parser->getHeaders();

            $attachments = $Parser->saveAttachments($options['save_dir'], $options['include_inline_attachments']);

            if ( is_array($attachments) && count($attachments) > 0 ) {
                foreach ( $attachments as $filepath ) {
                    $this->attachments[] = new Attachment($filepath);
                }
            }
        }
    }

    /**
     * json
     * Converts instance object to JSON
     *
     * @param bool $utf8ize
     * @return false|string
     */
    public function json($utf8ize = true)
    {
        $instance = $this;
        if ($utf8ize) $instance = static::utf8ize($instance);
        return json_encode($instance);
    }

    /**
     * utf8ize
     * Encode variable string into utf8
     *
     * @param $mixed
     * @return mixed|string
     */
    public static function utf8ize($mixed)
    {
        if ( is_array($mixed) ) {
            foreach ( $mixed as $key => $value ) {
                $mixed[$key] = static::utf8ize($value);
            }
        } elseif ( is_object($mixed) ) {
            foreach ( $mixed as $property => $value ) {
                $mixed->$property = static::utf8ize($value);
            }
        } elseif ( is_string($mixed) ) {
            if ( !static::is_utf8($mixed) ) {
                return utf8_encode($mixed);
            }
        }
        return $mixed;
    }

    /**
     * is_utf8
     * Checks if a string is encoded with utf8
     *
     * @param $string
     * @return false|int
     */
    public static function is_utf8($string)
    {
        return preg_match('!!u', $string);
    }
}

class Attachment
{
    var $filepath;
    var $filename;
    var $filesize;
    var $filetype;

    public function __construct($filepath)
    {
        $this->filepath = $filepath;

        $file_exists = $this->filepath !== null && file_exists($this->filepath);

        $this->filename = basename($filepath);

        if ( $this->filesize === null ) {
            if ( $file_exists && $filesize = filesize($this->filepath) ) {
                $this->filesize = $filesize;
            }
        }

        if ( $this->filetype === null ) {
            if ( function_exists('mime_content_type') ) {
                if ( $file_exists && $filetype = mime_content_type($this->filepath) ) {
                    $this->filetype = $filetype;
                }
            }
        }
    }

    /**
     * json
     * Encodes instance into JSON
     *
     * @return false|string
     */
    public function json()
    {
        return json_encode($this);
    }
}
