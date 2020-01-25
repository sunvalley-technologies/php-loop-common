<?php

namespace SunValley\LoopUtil\Common\Message\Server;

use Psr\Log\LoggerInterface;
use React\Promise\CancellablePromiseInterface;
use React\Promise\Deferred;
use SunValley\LoopUtil\Common\Message\Exception\MessageSequenceException;
use SunValley\LoopUtil\Common\Message\Io\ReadableMessageStream;
use SunValley\LoopUtil\Common\Message\MessageInterface;
use SunValley\LoopUtil\Common\Message\MessageProcessor;
use SunValley\LoopUtil\Common\Message\MessageProcessorInterface;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerInterface;
use SunValley\LoopUtil\Common\Server\ConnectionHandlerTrait;

use function React\Promise\resolve;

/**
 * Class MessageConnectionHandler, defines an async simple message protocol to handle messages. Server and client can send and receive requests and responses in an unordered fashion.
 * This is handled with the additional header `X-Sequence-Id`. This header is changeable.
 * @package SunValley\LoopUtil\Common\Message\Server
 */
class MessageConnectionHandler implements ConnectionHandlerInterface
{

    use ConnectionHandlerTrait {
        write as traitWrite;
    }

    /** @var MessageHandlerInterface */
    protected $handler;

    /** @var string */
    protected $trackingHeader = 'X-Sequence-Id';

    /** @var int */
    protected $sequence = 0;

    /** @var Deferred[] */
    protected $defers = [];

    /** @var string */
    protected $prefix;

    public function __construct(MessageHandlerInterface $handler, ?LoggerInterface $logger = null)
    {
        $this->handler = $handler;
        $this->logger = $logger;
        $this->prefix = uniqid('', true);
    }

    /**
     * Set the tracking header that will be used to track messages.
     *
     * @param string $trackingHeader
     *
     * @return $this
     */
    public function setTrackingHeader(string $trackingHeader): self
    {
        $this->trackingHeader = $trackingHeader;

        return $this;
    }

    protected function handle(): void
    {
        $stream = new ReadableMessageStream($this->connection, $this->createProcessor());
        $stream->on('data', \Closure::fromCallable([$this, 'read']));
    }

    /**
     * Create processor
     *
     * @return MessageProcessorInterface
     */
    protected function createProcessor(): MessageProcessorInterface
    {
        return new MessageProcessor();
    }

    /**
     * @inheritDoc
     */
    protected function read(MessageInterface $data): void
    {
        $sequence = $data->getHeader($this->trackingHeader);
        if ($sequence === null) {
            $this->error(new MessageSequenceException($data, 'Tracking header is not found'));

            return;
        }

        // handles the response
        if (isset($this->defers[$sequence])) {
            $defer = $this->defers[$sequence];
            unset($this->defers[$sequence]);
            $defer->resolve($data);

            return;
        } elseif (array_key_exists($sequence, $this->defers)) { // drop a canceled response
            unset($this->defers[$sequence]);

            return;
        }

        // handles request and if there is response, handle with same sequence
        $this->handler->handle($data)->then(
            function (MessageInterface $return = null) use ($sequence) {
                if ($return !== null) {
                    $return->setHeader($this->trackingHeader, $sequence);
                    return $this->write($return);
                }

                return resolve();
            }
        );
    }

    /**
     * Write message
     *
     * @param MessageInterface $data
     * @return CancellablePromiseInterface<MessageInterface,\Throwable>
     */
    public function write(MessageInterface $data): CancellablePromiseInterface
    {
        $sequence = $data->getHeader($this->trackingHeader);
        if ($sequence === null) {
            $headers = $data->getHeaders();
            $sequence = ++$this->sequence;
            $sequence = $this->prefix . '-' . $sequence;
            $headers[$this->trackingHeader] = $sequence;
        }
        $defer = new Deferred(
            function () use ($sequence) {
                $this->defers[$sequence] = null;
            }
        );
        $this->defers[$sequence] = $defer;
        $this->traitWrite($data)->then(null, [$defer, 'reject']);

        return $defer->promise();
    }

    /**
     * Returns current unique id prefix
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

}