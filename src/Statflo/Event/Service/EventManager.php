<?php

namespace Statflo\Event\Service;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class EventManager
{
    private $connection;
    private $exchange;
    private $queue;
    private $channel;
    private $handlers = [];

    public function  __construct(
        AMQPStreamConnection $connection,
        $exchange,
        $queue
    ) {
        $this->connection = $connection;
        $this->exchange   = $exchange;
        $this->queue      = $queue;
        $this->channel    = $connection->channel();

        $this
            ->channel
            ->exchange_declare($this->exchange, 'topic', false, false, false);

        $this
            ->channel
            ->queue_declare($this->queue, false, false, false, false);
    }

    /**
     * @param string $eventName
     * @param mixed  $data
     */
    public function dispatch($eventName, $data)
    {
        if (!is_string($data)) {
            $data = json_encode($data);
        }

        $message = new AMQPMessage($data);

        $this
            ->channel
            ->basic_publish($message, $this->exchange, $eventName)
        ;
    }

    /**
     * @param string   $eventName
     * @param callable $callable
     *
     * @return eventManager
     */
    public function on($eventName, $callable)
    {
        $this
            ->channel
            ->queue_bind($this->queue, $this->exchange, $eventName)
        ;

        if (!isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = [];
        }

        $this->handlers[$eventName][] = $callable;

        $this
            ->channel
            ->basic_consume($this->queue, '', false, true, false, false, [$this, 'consume'])
        ;

        return $this;
    }

    public function consume($message)
    {
        $eventName = $message->delivery_info['routing_key'];

        if (!isset($this->handlers[$eventName])) {
            return;
        }

        $handlers = $this
            ->handlers[$eventName]
        ;
        foreach ($handlers as $handler) {
            $handler($message);
        }
    }

    /**
     * Starts the consumer
     */
    public function listen()
    {
        while(count($this->channel->callbacks)) {
            $this->channel->wait();
        }
    }
}
