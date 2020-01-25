<?php

namespace SunValley\LoopUtil\Common\Tests\Message;

use PHPUnit\Framework\TestCase;
use React\Promise\PromiseInterface;
use SunValley\LoopUtil\Common\Message\Message;
use SunValley\LoopUtil\Common\Message\MessageInterface;
use SunValley\LoopUtil\Common\Message\Server\MessageConnectionHandler;
use SunValley\LoopUtil\Common\Message\Server\MessageHandlerInterface;

use SunValley\LoopUtil\Common\Tests\Util\StubConnection;

use function React\Promise\resolve;

class MessageConnectionHandlerTest extends TestCase
{

    public function testMessageHandlerClient()
    {
        $messageHandler = new class() implements MessageHandlerInterface {

            private $called = false;

            public function handle(MessageInterface $message): PromiseInterface
            {
                $this->called = true;

                return resolve();
            }

            public function isCalled(): bool
            {
                return $this->called;
            }
        };

        /** @noinspection PhpUnhandledExceptionInspection */
        $connection = $this->getMockForAbstractClass(StubConnection::class);

        $message1 = new Message(['value' => 1], 'PostData', 'someData1');
        $message2 = new Message(['value' => 2], 'PostData', 'someData2');
        $message3 = new Message(['value' => 3], 'PostData', 'someData3');
        $message4 = new Message(['value' => 4], 'PostData', 'someData4');
        $connHandler = new MessageConnectionHandler($messageHandler);
        $connHandler->open($connection);
        $msgCall1 = false;
        $connHandler->write($message1)->then(
            function (MessageInterface $message) use (&$msgCall1) {
                $msgCall1 = $message->getHeader('value') == 1 && $message->getBody() === 'responseData1';
            }
        );
        $msgCall2 = false;
        $connHandler->write($message2)->then(
            function (MessageInterface $message) use (&$msgCall2) {
                $msgCall2 = $message->getHeader('value') == 2 && $message->getBody() === 'responseData2';
            }
        );
        $msgCall3 = false;
        $connHandler->write($message3)->then(
            function (MessageInterface $message) use (&$msgCall3) {
                $msgCall3 = $message->getHeader('value') == 3 && $message->getBody() === 'responseData3';
            }
        );
        $msgCall4 = false;
        $connHandler->write($message4)->then(
            function (MessageInterface $message) use (&$msgCall4) {
                $msgCall4 = $message->getHeader('value') == 4 && $message->getBody() === 'responseData4';
            }
        );

        $connection->emit(
            'data',
            [
                $message3->setBody('responseData3')->setHeader(
                    'X-Sequence-Id',
                    $connHandler->getPrefix() . '-' . 3
                )
            ]
        );
        $connection->emit(
            'data',
            [
                $message2->setBody('responseData2')->setHeader(
                    'X-Sequence-Id',
                    $connHandler->getPrefix() . '-' . 2
                )
            ]
        );
        $connection->emit(
            'data',
            [
                $message1->setBody('responseData1')->setHeader(
                    'X-Sequence-Id',
                    $connHandler->getPrefix() . '-' . 1
                )
            ]
        );
        $connection->emit(
            'data',
            [
                $message4->setBody('responseData4')->setHeader(
                    'X-Sequence-Id',
                    $connHandler->getPrefix() . '-' . 4
                )
            ]
        );

        $this->assertFalse($messageHandler->isCalled());
        $this->assertTrue($msgCall1);
        $this->assertTrue($msgCall2);
        $this->assertTrue($msgCall3);
        $this->assertTrue($msgCall4);
    }

    public function testMessageHandlerServer()
    {
        $messageHandler = new class() implements MessageHandlerInterface {

            private $called = false;

            private $calledIds = [];

            public function handle(MessageInterface $message): PromiseInterface
            {
                $this->called = true;
                $this->calledIds[] = $message->getHeader('x-sequence-id');

                return resolve(
                    $message->setHeader('x-sequence-id', null)->setBody(
                        str_replace('someData', 'responseData', $message->getBody())
                    )
                );
            }

            public function isCalled(): bool
            {
                return $this->called;
            }

            public function getCalledIds(): array
            {
                return $this->calledIds;
            }
        };

        /** @noinspection PhpUnhandledExceptionInspection */
        $connection = $this->getMockForAbstractClass(StubConnection::class);
        $connection->expects($this->exactly(4))->method('write');
        $connHandler = new MessageConnectionHandler($messageHandler);
        $connHandler->open($connection);

        $message1 = new Message(['value' => 1], 'PostData', 'someData1');
        $message2 = new Message(['value' => 2], 'PostData', 'someData2');
        $message3 = new Message(['value' => 3], 'PostData', 'someData3');
        $message4 = new Message(['value' => 4], 'PostData', 'someData4');

        $connection->emit('data', [$message3->setBody('someData3')->setHeader('X-Sequence-Id', 4)]);
        $connection->emit('data', [$message2->setBody('someData2')->setHeader('X-Sequence-Id', 6)]);
        $connection->emit('data', [$message1->setBody('someData1')->setHeader('X-Sequence-Id', 2)]);
        $connection->emit('data', [$message4->setBody('someData4')->setHeader('X-Sequence-Id', 8)]);


        $this->assertTrue($messageHandler->isCalled());
        $this->assertContains(4, $messageHandler->getCalledIds());
        $this->assertContains(6, $messageHandler->getCalledIds());
        $this->assertContains(2, $messageHandler->getCalledIds());
        $this->assertContains(8, $messageHandler->getCalledIds());
        
    }
}