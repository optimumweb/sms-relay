<?php

class Message extends OpenCrate\Model
{
    public static $db_table = 'messages';
    public static $primary_key = 'id';

    protected $id;
    protected $from_email;
    protected $to_tel;
    protected $reference;
    protected $body;
    protected $twilio_message_sid;
    protected $created_at;
    protected $twilio_status;

    public function __toString()
    {
        return sprintf(
            "[SMSRelay.Message id=%s from_email=%s to_tel=%s reference=%s body=%s twilio_message_id=%s created_at=%s]",
            $this->id, $this->from_email, $this->to_tel, $this->reference, $this->body, $this->twilio_message_sid, $this->created_at
        );
    }

    public function send()
    {
        if ( defined('TWILIO_ACCOUNT_SID') && defined('TWILIO_AUTH_TOKEN') && defined('TWILIO_SMS_FROM') ) {

            try {

                $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

                $options = [
                    'from'           => TWILIO_SMS_FROM,
                    'body'           => $this->body,
                    'statusCallback' => 'http://' . SERVICE_DOMAIN . '/update_status.php'
                ];

                $message = $twilio_client->messages->create($this->to_tel, $options);

                if ( $message->sid !== null ) {
                    $this->twilio_message_sid = $message->sid;
                    return true;
                }

            } catch ( Exception $e ) {
                app_log("Message::send - Exception thrown: " . $e);
            }

        }

        return false;
    }

    public function get_twilio_message()
    {
        if ( defined('TWILIO_ACCOUNT_SID') && defined('TWILIO_AUTH_TOKEN') ) {

            if ( $this->twilio_message_sid !== null ) {

                try {

                    $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

                    return $twilio_client->messages($this->twilio_message_sid)->fetch();

                } catch ( Exception $e ) {
                    app_log("Message::twilio_message - Exception thrown: " . $e);
                }

            }

        }

        return false;
    }

    public function default_properties()
    {
        return [
            'is_delivered'  => 0,
            'twilio_status' => ''
        ];
    }
}