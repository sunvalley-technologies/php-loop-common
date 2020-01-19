<?php

namespace SunValley\LoopUtil\Common\Tests\Server\Message;

use PHPUnit\Framework\TestCase;
use SunValley\LoopUtil\Common\Server\Message\Exception\MalformedMessageException;
use SunValley\LoopUtil\Common\Server\Message\Message;
use SunValley\LoopUtil\Common\Server\Message\MessageProcessor;

class MessageProcessorTest extends TestCase
{

    public function testSimpleMessagesAutoCRLF()
    {
        $messages = $this->makeTestSimpleMessagesFile(__DIR__ . '/fixtures/simple-msg-good-crlf.txt', null, 5);
        $this->makeTestSimpleMessages($messages);
    }

    public function testSimpleMessagesAutoLF()
    {
        $messages = $this->makeTestSimpleMessagesFile(__DIR__ . '/fixtures/simple-msg-good-lflf.txt', null, 5);
        $this->makeTestSimpleMessages($messages);
    }

    public function testSimpleMessagesCRLF()
    {
        $messages = $this->makeTestSimpleMessagesFile(__DIR__ . '/fixtures/simple-msg-good-crlf.txt', "\r\n", 5);
        $this->makeTestSimpleMessages($messages);
    }

    public function testSimpleMessagesLF()
    {
        $messages = $this->makeTestSimpleMessagesFile(__DIR__ . '/fixtures/simple-msg-good-lflf.txt', "\n", 5);
        $this->makeTestSimpleMessages($messages);
    }    
    
    public function testSimpleMessagesLFWellFormed()
    {
        $messages = $this->makeTestSimpleMessagesFile(__DIR__ . '/fixtures/simple-msg-good-lflf-well-formed.txt', "\n", 5);
        $this->makeTestSimpleMessages($messages);
    }

    /** @dataProvider badMessagesProvider */
    public function testBadMessages($file)
    {
        $this->expectException(MalformedMessageException::class);
        $parser = new MessageProcessor();
        /** @noinspection PhpUnhandledExceptionInspection */
        $parser->feed(file_get_contents(__DIR__ . '/fixtures/' . $file));
    }

    public function badMessagesProvider()
    {
        return [
          //  ['simple-bad-msg1.txt'],
            //['simple-bad-msg2.txt'],
            ['simple-bad-msg3.txt'],
        ];
    }

    protected function makeTestSimpleMessages(array $messages)
    {
        $this->assertCount(5, $messages);

        /** @var Message $message */
        // message 1
        $message = array_shift($messages);
        $this->assertTrue($message->hasHeader('Content-Length'));
        $this->assertCount(7, $message->getHeaders());
        $this->assertEquals('<1234@local.node.example>', $message->getHeader('Message-ID'));
        $this->assertEquals($message->getHeader('Content-Length'), strlen($message->getBody()));
        $this->assertStringStartsWith('This is a message just to say hello.', $message->getBody());
        $this->assertEmpty($message->getProtocol());

        // message 2
        $message = array_shift($messages);
        $this->assertTrue($message->hasHeader('Content-Length'));
        $this->assertCount(9, $message->getHeaders());
        $this->assertEquals('<3456@example.net>', $message->getHeader('Message-ID'));
        $this->assertEquals($message->getHeader('Content-Length'), strlen($message->getBody()));
        $this->assertEquals('This is a reply to your hello.', $message->getBody());
        $this->assertEmpty($message->getProtocol());

        // message 3
        $message = array_shift($messages);
        $this->assertTrue(!$message->hasHeader('Content-Length'));
        $this->assertCount(201, $message->getHeaders());
        $this->assertEquals('true', $message->getHeader('Channel-Screen-Bit'));
        $this->assertEquals(0, strlen($message->getBody()));
        $this->assertEmpty($message->getProtocol());

        // message 4
        $message = array_shift($messages);
        $this->assertTrue($message->hasHeader('Content-Type'));
        $this->assertTrue($message->hasHeader('Reply-Text'));
        $this->assertCount(2, $message->getHeaders());
        $this->assertEquals('command/reply', $message->getHeader('Content-Type'));
        $this->assertEquals('+OK Events Enabled\';', $message->getHeader('Reply-Text'));
        $this->assertEquals(0, strlen($message->getBody()));
        $this->assertEmpty($message->getProtocol());

        // message 5
        $message = array_shift($messages);
        $this->assertTrue($message->hasHeader('Content-Length'));
        $this->assertCount(5, $message->getHeaders());
        $this->assertEquals($message->getHeader('Content-Length'), strlen($message->getBody()));
        $this->assertEquals('HTTP/1.1 404 Not Found', $message->getProtocol());
    }

    protected function makeTestSimpleMessagesFile($sampleFile, $eol, $expectedCount): array
    {
        $sample = file_get_contents($sampleFile);
        $parser = new MessageProcessor($eol);
        try {
            $messageCount = $parser->feedAndCount($sample);
        } catch (MalformedMessageException $e) {
            $this->assertFalse(true, $e);

            return [];
        }
        $this->assertEquals($expectedCount, $messageCount);
        $this->assertCount($expectedCount, $parser->unshiftMessages());
        $this->assertCount(0, $parser->unshiftMessages());

        try {
            $messages = $parser->feed($sample);
            $this->assertCount($expectedCount, $messages);
            $this->assertCount(0, $parser->unshiftMessages());
        } catch (MalformedMessageException $e) {
            $this->assertFalse(true, $e);

            return [];
        }

        return $messages;
    }


    public function testHeaderDetection()
    {
        $this->assertFalse(
            $this->isHeaderLine('GET http://developer.mozilla.org:8090/en-US/docs/Web/HTTP/Messages HTTP/1.1')
        );

        $this->assertTrue(
            $this->isHeaderLine('Host: localhost:9000')
        );

        $this->assertTrue(
            $this->isHeaderLine('Host:  localhost:9000')
        );
    }

    protected function isHeaderLine($string): bool
    {
        return preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+:/', trim($string));
    }
}