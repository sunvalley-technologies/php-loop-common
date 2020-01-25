<?php

namespace SunValley\LoopUtil\Common\Tests\Io\Util;

use SunValley\LoopUtil\Common\Io\Connector;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;

class StubConnector extends Connector
{

    protected function createHandler(): ConnectionHandlerInterface
    {
        return $this->handlerTemplate;
    }
}