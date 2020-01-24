<?php

namespace SunValley\LoopUtil\Common\Server;

use React\Promise\PromiseInterface;
use React\Socket\ServerInterface;

interface GenericServerInterface extends ServerInterface
{

    public function listen(ServerInterface $server): void;

    public function close(): void;

    public function closeGracefully(): PromiseInterface;
    
    


}