<?php

namespace Statflo\Event\Service;

use Statflo\Event\ClientInterface;
use PhpAmqpLib\Message\AMQPMessage;

class EventDispatcher
{
    private $connection;
    private $exchange;
    private $channel;

    public function  __construct(
        ClientInterface $connection,
        $exchange = ''
    ) {
        $this->connection = $connection;
        $this->exchange   = $exchange ?: '';
        $this->channel    = $connection->channel();

        if (!is_null($exchange) && strlen(trim($exchange)) > 0) {
            $this
                ->channel
                ->exchange_declare($this->exchange, 'topic', false, false, false);
        }
    }

    /**
     * @param string $eventName
     * @param mixed  $data
     * @param array  $headers
     */
    public function dispatch($eventName, $data, $headers = [])
    {
        $contentType = 'shortstr';

        if (!is_string($data)) {
            $data        = json_encode($data);
            $contentType = 'application/json';
        }

        $msg = new AMQPMessage($data);

        $this
            ->channel
            ->basic_publish($msg, $this->exchange, $eventName)
        ;

        return $this;
    }
}
