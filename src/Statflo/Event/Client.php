<?php

namespace Statflo\Event;

use PhpAmqpLib\Connection\AMQPStreamConnection;

class Client extends AMQPStreamConnection implements ClientInterface
{
    public function isTLS()
    {
        return false;
    }
}
