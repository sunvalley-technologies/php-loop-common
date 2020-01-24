<?php

namespace SunValley\LoopUtil\Common\Tests\Server;

use PHPUnit\Framework\TestCase;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;
use SunValley\LoopUtil\Common\Tests\Server\Util\StubGenericServer;
use SunValley\LoopUtil\Common\Tests\Server\Util\StubSocket;
use SunValley\LoopUtil\Common\Tests\Util\StubConnection;

class GenericServerTest extends TestCase
{

    public function testGenericServer()
    {
        // build connection handler
        $handler = $this->getMockBuilder(ConnectionHandlerInterface::class)->getMock();

        // create a stub socket
        /** @noinspection PhpUnhandledExceptionInspection */
        $socket = $this->getMockForAbstractClass(StubSocket::class);

        $server = new StubGenericServer($handler);
        $server->listen($socket);
        $exception = null;
        try {
            $server->listen($socket);
        } catch (\InvalidArgumentException $exception) {
        }
        $this->assertNotNull($exception);

        $this->assertCount(1, $socket->listeners('connection'));
        $this->assertCount(1, $socket->listeners('error'));

        // mock a connection to simulate connection handling
        /** @noinspection PhpUnhandledExceptionInspection */
        $connection = $this->getMockForAbstractClass(StubConnection::class);
        $handler->expects($this->once())->method('open');
        $socket->emit('connection', [$connection]);
        $this->assertCount(1, $server->getHandlers());
        $this->assertCount(1, $connection->listeners('close'));

        // pause server
        $socket->expects($this->once())->method('pause');
        $handler->expects($this->once())->method('pause');
        $server->pause();

        // resume server
        $socket->expects($this->once())->method('resume');
        $handler->expects($this->once())->method('resume');
        $server->resume();

        // close connection
        $connection->emit('close');
        $this->assertCount(0, $server->getHandlers());

        // check if socket errors are caught
        $expectedException = new \RuntimeException('error failing');
        $socket->emit('error', [$expectedException]);
        $this->assertEquals($expectedException, $server->getSocketError());

        // check if closed
        $socket->expects($this->once())->method('close');
        $server->close();
    }

    public function testFailedConnection()
    {
        // build connection handler
        $handler = $this->getMockBuilder(ConnectionHandlerInterface::class)->getMock();

        // create a stub socket
        /** @noinspection PhpUnhandledExceptionInspection */
        $socket = $this->getMockForAbstractClass(StubSocket::class);

        $server = new StubGenericServer($handler);
        $server->listen($socket);
        /** @noinspection PhpUnhandledExceptionInspection */
        $connection = $this->getMockForAbstractClass(StubConnection::class);
        $handler->expects($this->once())->method('open')->willThrowException(new \RuntimeException('Some exception'));
        $connection->expects($this->once())->method('close');
        $socket->emit('connection', [$connection]);
        $this->assertCount(0, $server->getHandlers());
    }

}