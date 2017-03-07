<?php

namespace Statflo\Event;

use Bunny\Async\Client as BaseClient;

class AsyncClient extends BaseClient
{
    public function getEventLoop()
    {
        return $this->eventLoop;
    }
}
