<?php

namespace SunValley\LoopUtil\Common\Tests\Server;

use PHPUnit\Framework\TestCase;
use React\Promise\FulfilledPromise;
use React\Promise\RejectedPromise;
use SunValley\LoopUtil\Common\Tests\Util\StubConnection;
use SunValley\LoopUtil\Common\Tests\Util\StubConnectionHandler;

class ConnectionHandlerTest extends TestCase
{

    public function testConnectionHandlerTrait()
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        $handler = $this->getMockForAbstractClass(StubConnectionHandler::class);

        // try error cases
        $exception = null;
        try {
            $handler->write('something');
        } catch (\BadMethodCallException $exception) {
        }
        $this->assertNotNull($exception);

        /** @noinspection PhpUnhandledExceptionInspection */
        $connection = $this->getMockForAbstractClass(StubConnection::class);
        $handler->expects($this->once())->method('handle');
        $handler->open($connection);

        $connection->expects($this->exactly(2))->method('write')->willReturn(true, false);
        $this->assertInstanceOf(FulfilledPromise::class, $handler->write('test'));
        $this->assertInstanceOf(RejectedPromise::class, $handler->write('test2'));
    }
}