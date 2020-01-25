<?php

namespace SunValley\LoopUtil\Common\Tests\Io;

use PHPUnit\Framework\TestCase;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;
use React\Promise\RejectedPromise;
use React\Socket\ConnectorInterface;
use SunValley\LoopUtil\Common\Io\Connector;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;
use SunValley\LoopUtil\Common\Tests\Io\Util\StubConnector;
use SunValley\LoopUtil\Common\Tests\Util\StubConnection;

use function React\Promise\reject;
use function React\Promise\resolve;

class ConnectorTest extends TestCase
{

    public function testConnector()
    {
        $handler = $this->getMockBuilder(ConnectionHandlerInterface::class)->getMock();
        $transport = $this->getMockBuilder(ConnectorInterface::class)->getMock();
        $connector = $this->getMockBuilder(StubConnector::class)->setConstructorArgs(
            [$handler, $transport]
        )->setMethods(null)->getMock();
        /** @noinspection PhpUnhandledExceptionInspection */
        $connection = $this->getMockForAbstractClass(StubConnection::class);

        $handler->expects($this->once())->method('open');
        $handler->expects($this->once())->method('close');
        $transport->expects($this->exactly(2))->method('connect')->willReturn(
            resolve($connection),
            reject(new \Exception('connection bla bla'))
        );

        $passedHandler = null;
        /** @var PromiseInterface $promise */
        $promise = $connector->connect('tcp://127.0.0.1:6565');
        $this->assertInstanceOf(FulfilledPromise::class, $promise);
        $promise->then(
            function ($handler) use (&$passedHandler) {
                $passedHandler = $handler;
            }
        );
        $this->assertEquals($handler, $passedHandler);
        $connection->emit('close');

        /** @var PromiseInterface $promise */
        $promise = $connector->connect('tls://127.0.0.1:6565');
        $this->assertInstanceOf(RejectedPromise::class, $promise);
    }

}