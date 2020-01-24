<?php

namespace SunValley\LoopUtil\Common\Tests\Util;

use Evenement\EventEmitterTrait;
use React\Socket\ConnectionInterface;

abstract class StubConnection implements ConnectionInterface
{
    use EventEmitterTrait;
}