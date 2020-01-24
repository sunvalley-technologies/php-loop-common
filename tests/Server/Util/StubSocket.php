<?php

namespace SunValley\LoopUtil\Common\Tests\Server\Util;

use Evenement\EventEmitterTrait;
use React\Socket\ServerInterface;

abstract class StubSocket implements ServerInterface
{

    use EventEmitterTrait;
}