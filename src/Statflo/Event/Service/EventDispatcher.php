<?php

namespace Statflo\Event\Service;

use Statflo\Event\Client;
use Bunny\Channel;
use Bunny\Message;

class EventDispatcher
{
    private $connection;
    private $exchange;
    private $queue;
    private $channel;

    public function  __construct(
        Client $connection,
        $exchange,
        $queue
    ) {
        $this->connection = $connection;
        $this->exchange   = $exchange;
        $this->queue      = $queue;

        $connection->connect();

        $this->channel = $connection->channel();

        $this
            ->channel
            ->exchangeDeclare($this->exchange, 'topic', false, false, false);

        $this
            ->channel
            ->queueDeclare($this->queue, false, false, false, false);
    }

    /**
     * @param string $eventName
     * @param mixed  $data
     * @param array  $headers
     */
    public function dispatch($eventName, $data, $headers = [])
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $this
            ->channel
            ->publish($data, $headers, $this->exchange, $eventName)
        ;

        return $this;
    }
}
