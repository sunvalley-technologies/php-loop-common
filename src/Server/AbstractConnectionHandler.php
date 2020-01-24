<?php

namespace SunValley\LoopUtil\Common\Server;

/**
 * Class AbstractConnectionHandler provides an asynchronous connection handler abstract.
 * @package SunValley\LoopUtil\Common\Server
 */
abstract class AbstractConnectionHandler implements ConnectionHandlerInterface
{

    use ConnectionHandlerTrait;
}