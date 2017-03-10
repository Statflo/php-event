<?php

namespace Statflo\Event\Service;

use Statflo\Event\Client;
use Bunny\Channel;
use Bunny\Message;

class EventDispatcher
{
    private $connection;
    private $exchange;
    private $channel;

    public function  __construct(
        Client $connection,
        $exchange = ''
    ) {
        $this->connection = $connection;
        $this->exchange   = $exchange ?: '';

        try {
            $connection->connect();
        } catch (\Bunny\Exception\ClientException $e) {
            if (strpos($e->getMessage(), "already connected") === false) {
                throw $e;
            }
        }

        $this->channel = $connection->channel();

        if (!is_null($exchange) && strlen(trim($exchange)) > 0) {
            $this
                ->channel
                ->exchangeDeclare($this->exchange, 'topic', false, false, false);
        }
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

