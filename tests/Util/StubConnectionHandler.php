<?php

namespace SunValley\LoopUtil\Common\Tests\Util;

use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerTrait;

abstract class StubConnectionHandler implements ConnectionHandlerInterface
{

    use ConnectionHandlerTrait;
}