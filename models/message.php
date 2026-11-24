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
        // The message body is deliberately left out to keep message content out of the logs
        $body_length = mb_strlen((string) $this->body);

        return "[SMSRelay.Message id={$this->id} from_email={$this->from_email} to_tel={$this->to_tel} reference={$this->reference} body_length={$body_length} twilio_message_id={$this->twilio_message_sid} created_at={$this->created_at}]";
    }

    /**
     * Number of messages created since a Unix timestamp, optionally for a single sender.
     */
    public static function count_since($since, $from_email = null)
    {
        $query = sprintf("SELECT COUNT(*) FROM %s WHERE created_at >= :since", static::$db_table);
        $params = [ 'since' => (int) $since ];

        if ($from_email !== null) {
            $query .= " AND from_email = :from_email";
            $params['from_email'] = $from_email;
        }

        $stmt = static::get_PDO()->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    public function send()
    {
        if (
            defined('TWILIO_ACCOUNT_SID')
            && defined('TWILIO_AUTH_TOKEN')
            && defined('TWILIO_SMS_FROM')
        ) {
            try {
                $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);

                $options = [
                    'from' => TWILIO_SMS_FROM,
                    'body' => $this->body,
                    'statusCallback' => 'https://' . SERVICE_DOMAIN . '/update_status.php',
                ];

                $message = $twilio_client->messages->create($this->to_tel, $options);

                if ($message->sid !== null) {
                    $this->twilio_message_sid = $message->sid;
                    return true;
                }
            } catch ( Exception $e ) {
                app_log("Message::send - Exception thrown: {$e}");
            }
        }

        return false;
    }

    public function get_twilio_message()
    {
        if (
            defined('TWILIO_ACCOUNT_SID')
            && defined('TWILIO_AUTH_TOKEN')
        ) {
            if ($this->twilio_message_sid !== null) {
                try {
                    $twilio_client = new Twilio\Rest\Client(TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN);
                    return $twilio_client->messages($this->twilio_message_sid)->fetch();
                } catch (Exception $e) {
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
            'twilio_status' => '',
        ];
    }
}
