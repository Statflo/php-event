<?php

namespace Statflo\Event\Service;

use Statflo\Event\ClientInterface;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Message\AMQPMessage as Message;
use Statflo\Event\DTO\Message as MessageDTO;
use Statflo\Event\DTO\Channel as ChannelDTO;

class EventListener
{
    private $connection;
    private $exchange;
    private $queue;
    private $handlers = [];

    public function  __construct(
        ClientInterface $connection,
        $exchange = '',
        $queue
    ) {
        $this->connection = $connection;
        $this->exchange   = $exchange ?: '';
        $this->queue      = $queue;
        $this->channel    = $connection->channel();

        if (!is_null($exchange) && strlen(trim($exchange)) > 0) {
            $this
                ->channel
                ->exchange_declare($this->exchange, 'topic', false, false, false);
        }

        if (!is_null($queue) && strlen(trim($queue)) > 0) {
            $this
                ->channel
                ->queue_declare($this->queue, false, true, false, false);
        }
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

    public function consume(Message $message, AbstractChannel $channel, ClientInterface $client)
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
        $channel = $this->channel;
        foreach ($this->handlers as $eventName => $none) {
            $channel
                ->queue_bind($this->queue, $this->exchange, $eventName)
            ;
        }

        $channel->basic_consume($this->queue, '', false, false, false, false, [$this, 'processMessage']);
        while(count($channel->callbacks)) {
            $channel->wait();
        }

        $channel->close();
        $this->connection->close();
    }

    /**
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function processMessage($message)
    {
        $routingKey = $message->delivery_info['routing_key'];
        $handlers   = $this->handlers[$routingKey] ?? [];

        foreach ($handlers as $handler) {
            $handler(new MessageDTO($message), new ChannelDTO($message->delivery_info['channel']));
        }
    }
}
