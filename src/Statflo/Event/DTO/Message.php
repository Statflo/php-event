<?php

namespace Statflo\Event\DTO;

use PhpAmqpLib\Message\AMQPMessage;

class Message
{
    private $original;

    public function __construct(AMQPMessage $message)
    {
        $this->original = $message;
        $this->content;
    }

    public function getOriginal()
    {
        return $this->original;
    }

    public function ack(Message $message)
    {
        $msg = $message->getOriginal();
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    public function nack(Message $message)
    {
        $msg = $message->getOriginal();
        $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    public function __get($variable)
    {
        if ($variable === 'content') {
            if(!$message = @json_decode($this->original->body)) {
                $message = $this->original->body;
            }

            return $message;
        }

        return null;
    }
}
