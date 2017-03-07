<?php

namespace Statflo\Event\Service;

use Statflo\Event\AsyncClient as Client;
use Bunny\Channel;
use Bunny\Message;

class EventListener
{
    private $connection;
    private $exchange;
    private $queue;
    private $handlers = [];

    public function  __construct(
        Client $connection,
        $exchange,
        $queue
    ) {
        $this->connection = $connection;
        $this->exchange   = $exchange;
        $this->queue      = $queue;

    }

    /**
     * @param string   $eventName
     * @param callable $callable
     *
     * @return eventManager
     */
    public function on($eventName, $callable)
    {
        if (!isset($this->handlers[$eventName])) {
            $this->handlers[$eventName] = [];
        }

        $this->handlers[$eventName][] = $callable;

        return $this;
    }

    public function consume(Message $message, Channel $channel, Client $client)
    {
        $eventName = $message->routingKey;

        if (!isset($this->handlers[$eventName])) {
            return;
        }

        $handlers = $this
            ->handlers[$eventName]
        ;
        foreach ($handlers as $handler) {
            $handler($message, $channel);
        }
    }

    /**
     * Starts the consumer
     */
    public function listen()
    {
        $this
            ->connection
            ->connect()
            ->then(function (Client $client) {
                return $client->channel();
            })
            ->then(function (Channel $channel) {
                $channel->exchangeDeclare($this->exchange, 'topic', false, false, false);
                $channel->queueDeclare($this->queue, false, false, false, false);

                return $channel;
            })
            ->then(function (Channel $channel) {
                foreach ($this->handlers as $eventName => $none) {
                    $channel
                        ->queueBind($this->queue, $this->exchange, $eventName)
                    ;
                }

                return $channel;
            })
            ->then(function (Channel $channel) {
                echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";
                $channel->consume([$this, 'consume'],$this->queue);
            })
        ;
        $this->connection->getEventLoop()->run();
    }
}
