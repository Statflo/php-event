<?php

namespace Statflo\Event\DTO;

use PhpAmqpLib\Channel\AbstractChannel;

class Channel
{
    private $original;

    public function __construct(AbstractChannel $channel)
    {
        $this->original = $channel;
    }

    public function getOriginal()
    {
        return $this->original;
    }

    public function ack(Message $message)
    {
        $msg = $message->getOriginal();
        return $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    public function nack(Message $message)
    {
        $msg = $message->getOriginal();
        return $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
    }

    public function __get($variable)
    {
        return $this->original->{$variable};
    }

    public function __call($callable, $args)
    {
        return $this->original->{$callable}($args);
    }
}
