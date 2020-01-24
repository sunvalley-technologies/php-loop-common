<?php

namespace SunValley\LoopUtil\Common\Tests\Server\Util;

use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;
use SunValley\LoopUtil\Common\Server\GenericServer;

class StubGenericServer extends GenericServer
{
    /** @var \Throwable|null */
    private $socketError;

    public function getHandlers()
    {
        return $this->handlers;
    }

    protected function createHandler(): ConnectionHandlerInterface
    {
        return $this->handlerTemplate;
    }

    protected function handleError(\Throwable $error): void
    {
        $this->socketError = $error;
    }
    
    public function getSocketError(): ?\Throwable
    {
        return $this->socketError;
    }
}