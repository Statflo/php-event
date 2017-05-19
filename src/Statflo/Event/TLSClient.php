<?php

namespace Statflo\Event;

use PhpAmqpLib\Connection\AMQPSSLConnection;

class TLSClient extends AMQPSSLConnection implements ClientInterface
{
    public function isTLS()
    {
        return true;
    }
}
