<?php

namespace SunValley\LoopUtil\Common\Message\Server;

use React\Promise\PromiseInterface;
use SunValley\LoopUtil\Common\Message\MessageInterface;

interface MessageHandlerInterface
{

    /**
     * @param MessageInterface $message
     * @return PromiseInterface<MessageInterface> Should handle the message and return a promise that resolves with the result message. 
     */
    public function handle(MessageInterface $message): PromiseInterface;
}